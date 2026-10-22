<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Mail\ReservationInvoiceMail;
use App\Models\CurrencyType;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationDiscount;
use App\Models\ReservationRoom;
use App\Models\ReservationStatusHistory;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\RoomType;
use App\Models\User;
use App\Support\BusinessDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Creating reservations and moving them through their statuses.
 * Every change runs in a transaction together with the inventory update
 * and a status-history (audit) row.
 */
class Reservations
{
    const PENDING_MINUTES = 30; // unpaid online bookings hold rooms this long

    public function __construct(
        private BookingQuotes $quotes,
        private Inventory $inventory,
        private GuestMatcher $guests,
        private ExchangeRates $exchangeRates,
    ) {
    }

    /**
     * @param array<int, array{roomType: RoomType, occupancy_id: int, mealType_id: int, rooms: int}> $lines
     * @param array{name: string, email: string, phone: string, address?: ?string, country?: ?string, special_requests?: ?string} $guest
     */
    public function create(Property $property, array $lines, string $checkIn, string $checkOut, CurrencyType $currency,
                           array $guest, PaymentMethod $paymentMethod, ?User $account = null, ?User $createdBy = null,
                           string $source = 'web'): Reservation
    {
        if ($error = $this->quotes->validateStay($checkIn, $checkOut)) {
            throw ValidationException::withMessages(['check_in' => $error]);
        }
        if (!$lines) {
            throw ValidationException::withMessages(['rooms' => 'Please choose at least one room.']);
        }

        $reservation = DB::transaction(function () use ($property, $lines, $checkIn, $checkOut, $currency, $guest, $paymentMethod, $account, $createdBy, $source) {
            // Price every line and take the rooms (inventory rows are locked until commit)
            $priced = [];
            foreach ($lines as $line) {
                $quote = $this->quotes->quote($property, $line['roomType'], $line['occupancy_id'], $line['mealType_id'],
                    $checkIn, $checkOut, $line['rooms'], $currency);
                if (!$quote) {
                    throw ValidationException::withMessages(['rooms' => "{$line['roomType']->roomType} is no longer available for these dates."]);
                }
                try {
                    $this->inventory->book($property, $line['roomType'], $checkIn, $checkOut, $line['rooms']);
                } catch (RuntimeException $e) {
                    throw ValidationException::withMessages(['rooms' => "{$line['roomType']->roomType}: ".$e->getMessage()]);
                }
                $priced[] = $line + ['quote' => $quote];
            }

            $subtotal = round(array_sum(array_map(fn ($l) => $l['quote']['subtotal'], $priced)), 2);
            $discountTotal = round(array_sum(array_map(fn ($l) => $l['quote']['discount_total'], $priced)), 2);
            $total = round($subtotal - $discountTotal, 2);
            $snapshot = $this->exchangeRates->snapshot((int) $property->property_owner_id, $currency, $total);

            $status = $paymentMethod === PaymentMethod::PayAtHotel ? ReservationStatus::Confirmed : ReservationStatus::Pending;

            $reservation = new Reservation();
            $reservation->reference = $this->newReference();
            $reservation->property_id = $property->id;
            $reservation->guest_profile_id = $this->guests->match($guest, $account)->id;
            $reservation->user_id = $account?->id;
            $reservation->status = $status;
            $reservation->payment_method = $paymentMethod;
            $reservation->source = $source;
            $reservation->check_in = $checkIn;
            $reservation->check_out = $checkOut;
            $reservation->nights = $priced[0]['quote']['nights'];
            $reservation->guest_name = $guest['name'];
            $reservation->guest_email = $guest['email'];
            $reservation->guest_phone = $guest['phone'];
            $reservation->guest_address = $guest['address'] ?? null;
            $reservation->guest_country = $guest['country'] ?? null;
            $reservation->special_requests = $guest['special_requests'] ?? null;
            $reservation->currency_type_id = $currency->id;
            $reservation->subtotal = $subtotal;
            $reservation->discount_total = $discountTotal;
            $reservation->total = $total;
            $reservation->exchange_rate = $snapshot['exchange_rate'];
            $reservation->total_lkr = $snapshot['amount_lkr'];
            $reservation->expires_at = $status === ReservationStatus::Pending ? now()->addMinutes(self::PENDING_MINUTES) : null;
            $reservation->created_by_user_id = $createdBy?->id;
            $reservation->save();

            foreach ($priced as $line) {
                $room = new ReservationRoom();
                $room->reservation_id = $reservation->id;
                $room->roomType_id = $line['roomType']->id;
                $room->occupancy_id = $line['occupancy_id'];
                $room->mealType_id = $line['mealType_id'];
                $room->rooms = $line['rooms'];
                $room->nightly_rates = $line['quote']['nightly'];
                $room->subtotal = $line['quote']['subtotal'];
                $room->discount = $line['quote']['discount_total'];
                $room->total = $line['quote']['total'];
                $room->save();

                foreach ($line['quote']['discounts'] as $discount) {
                    $row = new ReservationDiscount();
                    $row->reservation_room_id = $room->id;
                    $row->promotion_id = $discount['promotion']->id;
                    $row->description = $discount['description'];
                    $row->amount = $discount['amount'];
                    $row->save();
                }
            }

            $this->recordStatus($reservation, null, $status, $createdBy ?? $account, 'Booking created ('.$paymentMethod->label().')');

            return $reservation;
        });

        $this->sendInvoice($reservation);

        return $reservation;
    }

    /**
     * Moves a reservation to a new status if allowed. Statuses that no longer
     * hold rooms return them to inventory; check-out releases room numbers.
     */
    public function transition(Reservation $reservation, ReservationStatus $to, ?User $by, ?string $note = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $to, $by, $note) {
            $reservation = Reservation::with('rooms.roomType', 'property')->lockForUpdate()->findOrFail($reservation->id);
            $from = $reservation->status;

            if (!$from->canTransitionTo($to)) {
                throw ValidationException::withMessages(['status' => "A {$from->label()} booking cannot be changed to {$to->label()}."]);
            }
            if ($to === ReservationStatus::NoShow && $reservation->check_in->toDateString() > BusinessDate::today()) {
                throw ValidationException::withMessages(['status' => 'A booking can only be marked as no-show from its check-in date.']);
            }
            if ($to === ReservationStatus::CheckedOut && $reservation->check_in->toDateString() > BusinessDate::today()) {
                throw ValidationException::withMessages(['status' => 'Cannot check out before the check-in date.']);
            }

            if ($from->holdsInventory() && !$to->holdsInventory()) {
                foreach ($reservation->rooms as $line) {
                    $this->inventory->release($reservation->property, $line->roomType,
                        $reservation->check_in->toDateString(), $reservation->check_out->toDateString(), $line->rooms);
                }
            }

            if ($to === ReservationStatus::CheckedOut) {
                RoomAssignment::whereIn('reservation_room_id', $reservation->rooms->pluck('id'))
                    ->whereNull('released_at')
                    ->update(['released_at' => now(), 'updated_at' => now()]);
            }

            if ($to === ReservationStatus::Cancelled) {
                $reservation->cancelled_at = now();
                $reservation->cancellation_reason = $note;
            }

            $reservation->status = $to;
            $reservation->save();
            $this->recordStatus($reservation, $from, $to, $by, $note);

            return $reservation;
        });
    }

    /**
     * Checks a confirmed booking in and assigns room numbers.
     * $roomIds: [reservation_room_id => [room_id, ...]] — one room number per booked room.
     */
    public function checkIn(Reservation $reservation, array $roomIds, ?User $by): Reservation
    {
        return DB::transaction(function () use ($reservation, $roomIds, $by) {
            $reservation = Reservation::with('rooms.roomType')->lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->check_in->toDateString() > BusinessDate::today()) {
                throw ValidationException::withMessages(['rooms' => 'Guests can be checked in from the check-in date.']);
            }
            if ($reservation->check_out->toDateString() <= BusinessDate::today()) {
                throw ValidationException::withMessages(['rooms' => 'The stay has already ended.']);
            }

            $errors = [];
            $chosen = [];
            foreach ($reservation->rooms as $line) {
                $ids = array_values(array_unique(array_map('intval', (array) ($roomIds[$line->id] ?? []))));
                if (count($ids) !== $line->rooms) {
                    $errors[] = "Choose {$line->rooms} room number(s) for {$line->roomType->roomType}.";
                    continue;
                }
                $valid = Room::where(['property_id' => $reservation->property_id, 'roomType_id' => $line->roomType_id, 'is_active' => true])
                    ->whereIn('id', $ids)->lockForUpdate()->count();
                if ($valid !== count($ids)) {
                    $errors[] = "Some room numbers chosen for {$line->roomType->roomType} are not valid.";
                    continue;
                }
                $chosen[$line->id] = $ids;
            }

            $allIds = array_merge(...array_values($chosen ?: [[]]));
            if (count($allIds) !== count(array_unique($allIds))) {
                $errors[] = 'The same room number was chosen twice.';
            }
            $occupied = RoomAssignment::with('room')->whereIn('room_id', $allIds)->whereNull('released_at')->get();
            foreach ($occupied as $assignment) {
                $errors[] = "Room {$assignment->room->room_number} is occupied.";
            }

            if ($errors) {
                throw ValidationException::withMessages(['rooms' => $errors]);
            }

            foreach ($chosen as $lineId => $ids) {
                foreach ($ids as $roomId) {
                    $assignment = new RoomAssignment();
                    $assignment->reservation_room_id = $lineId;
                    $assignment->room_id = $roomId;
                    $assignment->assigned_at = now();
                    $assignment->assigned_by_user_id = $by?->id;
                    $assignment->save();
                }
            }

            return $this->transition($reservation, ReservationStatus::CheckedIn, $by, 'Rooms assigned');
        });
    }

    // Free rooms held by unpaid online bookings past their deadline
    public function expirePending(): int
    {
        $expired = 0;
        Reservation::where('status', ReservationStatus::Pending)
            ->where('expires_at', '<', now())
            ->each(function (Reservation $reservation) use (&$expired) {
                $this->transition($reservation, ReservationStatus::Expired, null, 'Payment not received in time');
                $expired++;
            });

        return $expired;
    }

    /**
     * Invoice to the guest, plus copies to the owner's login email and the
     * property's contact person; an address never receives two copies.
     */
    public function sendInvoice(Reservation $reservation): void
    {
        $reservation->loadMissing('property.propertyOwner', 'property.primaryContact', 'rooms.roomType', 'rooms.occupancy', 'rooms.mealType', 'rooms.discounts', 'currency');

        $recipients = collect([
            ['email' => $reservation->guest_email, 'copy' => false],
            ['email' => $reservation->property->propertyOwner?->email, 'copy' => true],
            ['email' => $reservation->property->primaryContact?->email, 'copy' => true],
        ])
            ->filter(fn ($r) => filled($r['email']))
            ->unique(fn ($r) => strtolower(trim($r['email'])));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient['email'])->send(new ReservationInvoiceMail($reservation, $recipient['copy']));
            } catch (\Throwable $e) {
                report($e); // the booking stands even if an email fails
            }
        }
    }

    private function recordStatus(Reservation $reservation, ?ReservationStatus $from, ReservationStatus $to, ?User $by, ?string $note): void
    {
        $history = new ReservationStatusHistory();
        $history->reservation_id = $reservation->id;
        $history->from_status = $from;
        $history->to_status = $to;
        $history->user_id = $by?->id;
        $history->note = $note;
        $history->created_at = now();
        $history->save();
    }

    // e.g. BS2611A7KQ: date part + 4 unambiguous characters
    private function newReference(): string
    {
        do {
            $reference = 'BS'.now(config('bedspace.timezone'))->format('ymd')
                .collect(range(1, 4))->map(fn () => Str::of('ABCDEFGHJKLMNPQRSTUVWXYZ23456789')->substr(random_int(0, 31), 1))->join('');
        } while (Reservation::where('reference', $reference)->exists());

        return $reference;
    }
}
