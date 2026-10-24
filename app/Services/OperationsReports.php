<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Models\Reservation;
use App\Models\RoomInventory;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Occupancy and booking reports (FR-12), per property, for one month
 * (Sri Lanka dates). Money is in LKR at each booking's exchange rate.
 */
class OperationsReports
{
    // Statuses whose nights count as sold
    const STAYING = [ReservationStatus::Confirmed, ReservationStatus::CheckedIn, ReservationStatus::CheckedOut];

    public function __construct(private RatesCalendar $rates, private IncomeReports $income)
    {
    }

    /**
     * Per property and room type:
     * - available room-nights: total - closed rooms on dates open in the rates calendar
     * - sold room-nights: rooms booked (from inventory)
     * - occupancy % = sold / available
     * - room revenue (LKR) of staying bookings for nights in the month, ADR, RevPAR
     * Totals use the current room count (changes to totals are not back-dated).
     */
    public function occupancy(string $month, array $propertyIds): Collection
    {
        [$from, $to] = $this->monthDates($month);
        $dates = collect(CarbonPeriod::create($from, $to))->map(fn ($d) => $d->toDateString())->all();

        $rows = collect();
        $properties = Property::whereIn('id', $propertyIds)->orderBy('property_name')->get();

        foreach ($properties as $property) {
            $totals = PropertyRoomType::where('property_id', $property->id)->pluck('total_rooms', 'roomType_id');
            $roomTypes = RoomType::whereIn('id', $totals->keys())->orderBy('roomType')->get();
            $inventory = RoomInventory::where('property_id', $property->id)
                ->whereBetween('inventory_date', [$from, $to])->get()
                ->groupBy('roomType_id');
            $revenue = $this->roomRevenue($property, $from, $to);

            foreach ($roomTypes as $roomType) {
                $open = $this->rates->load($property, $roomType, $from, $to)['open'];
                $byDate = ($inventory[$roomType->id] ?? collect())->keyBy(fn ($row) => substr($row->inventory_date, 0, 10));
                $total = (int) ($totals[$roomType->id] ?? 0);

                $available = 0;
                $sold = 0;
                foreach ($dates as $date) {
                    $row = $byDate[$date] ?? null;
                    $closed = (int) ($row->closed_rooms ?? 0);
                    $booked = (int) ($row->booked_rooms ?? 0);
                    $available += $open[$date] ? max(0, $total - $closed) : 0;
                    $sold += $booked;
                }

                $roomRevenue = round($revenue[$roomType->id]['lkr'] ?? 0, 2);
                $revenueNights = $revenue[$roomType->id]['nights'] ?? 0;

                if ($total === 0 && $sold === 0) {
                    continue;
                }

                $rows->push((object) [
                    'property_name' => $property->property_name,
                    'room_type' => $roomType->roomType,
                    'rooms' => $total,
                    'available_nights' => $available,
                    'sold_nights' => $sold,
                    'occupancy' => $available > 0 ? round(min(100, $sold / $available * 100), 1) : 0,
                    'revenue_lkr' => $roomRevenue,
                    'adr_lkr' => $revenueNights > 0 ? round($roomRevenue / $revenueNights, 2) : 0, // average daily rate
                    'revpar_lkr' => $available > 0 ? round($roomRevenue / $available, 2) : 0,    // revenue per available room-night
                ]);
            }
        }

        return $rows;
    }

    /**
     * Bookings made in the month, per property: counts by status, source and
     * payment method, value, cancellations, lead time, length of stay and
     * new vs returning guests (guest profile matching).
     */
    public function bookings(string $month, array $propertyIds): Collection
    {
        [$fromUtc, $toUtc] = $this->income->monthRange($month);

        $reservations = Reservation::with('property')
            ->whereIn('property_id', $propertyIds)
            ->where('created_at', '>=', $fromUtc)
            ->where('created_at', '<', $toUtc)
            ->get();

        // Guests who had booked before (any property) are "returning"
        $firstBooking = Reservation::whereIn('guest_profile_id', $reservations->pluck('guest_profile_id')->unique())
            ->selectRaw('guest_profile_id, MIN(id) as first_id')
            ->groupBy('guest_profile_id')
            ->pluck('first_id', 'guest_profile_id');

        return $reservations->groupBy('property_id')->map(function ($group) use ($firstBooking) {
            $cancelled = $group->filter(fn ($r) => in_array($r->status, [ReservationStatus::Cancelled, ReservationStatus::Expired], true));
            $kept = $group->reject(fn ($r) => in_array($r->status, [ReservationStatus::Cancelled, ReservationStatus::Expired], true));
            $tz = config('bedspace.timezone');

            return (object) [
                'property_name' => $group->first()->property->property_name,
                'bookings' => $group->count(),
                'confirmed' => $group->filter(fn ($r) => in_array($r->status, self::STAYING, true))->count(),
                'cancelled' => $cancelled->count(),
                'no_show' => $group->where('status', ReservationStatus::NoShow)->count(),
                'pending' => $group->where('status', ReservationStatus::Pending)->count(),
                'cancellation_rate' => round($cancelled->count() / max(1, $group->count()) * 100, 1),
                'web' => $group->where('source', 'web')->count(),
                'walk_in' => $group->where('source', 'owner')->count(),
                'online_payment' => $group->filter(fn ($r) => $r->payment_method->value === 'online')->count(),
                'pay_at_hotel' => $group->filter(fn ($r) => $r->payment_method->value === 'pay_at_hotel')->count(),
                'value_lkr' => round((float) $kept->sum('total_lkr'), 2),
                'room_nights' => (int) $kept->sum(fn ($r) => $r->nights * $r->rooms()->sum('rooms')),
                'avg_nights' => round((float) $group->avg('nights'), 1),
                'avg_lead_days' => round((float) $group->avg(fn ($r) => CarbonImmutable::parse($r->created_at->timezone($tz)->toDateString())
                    ->diffInDays(CarbonImmutable::parse($r->check_in->toDateString()))), 1),
                'new_guests' => $group->filter(fn ($r) => (int) ($firstBooking[$r->guest_profile_id] ?? 0) === (int) $r->id)->count(),
                'returning_guests' => $group->filter(fn ($r) => (int) ($firstBooking[$r->guest_profile_id] ?? 0) !== (int) $r->id)->count(),
            ];
        })->sortBy('property_name')->values();
    }

    // [roomType_id => ['lkr' => revenue, 'nights' => room-nights]] for staying bookings, nights inside [from, to]
    private function roomRevenue(Property $property, string $from, string $to): array
    {
        $result = [];
        Reservation::with('rooms')
            ->where('property_id', $property->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, self::STAYING))
            ->where('check_in', '<=', $to)
            ->where('check_out', '>', $from)
            ->get()
            ->each(function (Reservation $reservation) use (&$result, $from, $to) {
                $rate = (float) $reservation->exchange_rate;
                foreach ($reservation->rooms as $line) {
                    foreach ((array) $line->nightly_rates as $date => $price) {
                        if ($date < $from || $date > $to) {
                            continue;
                        }
                        $result[$line->roomType_id]['lkr'] = ($result[$line->roomType_id]['lkr'] ?? 0) + (float) $price * $line->rooms * $rate;
                        $result[$line->roomType_id]['nights'] = ($result[$line->roomType_id]['nights'] ?? 0) + $line->rooms;
                    }
                }
            });

        return $result;
    }

    // First and last date of a "YYYY-MM" month
    private function monthDates(string $month): array
    {
        $start = CarbonImmutable::parse($month.'-01');
        return [$start->toDateString(), $start->endOfMonth()->toDateString()];
    }
}
