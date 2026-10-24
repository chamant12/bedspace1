<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Models\RoomInventory;
use App\Models\RoomType;
use App\Support\Audit;
use App\Support\BusinessDate;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Room availability per property x room type x date:
 *
 *   available = total rooms - closed rooms - booked rooms
 *
 * and 0 when the date is closed in the rates calendar (no complete
 * USD + LKR combination), or the property / room type is inactive.
 *
 * Owners change total rooms and closed rooms (today and future dates only);
 * booked rooms only change through book() / release(), which lock the
 * affected rows so two bookings can never take the same last room.
 */
class Inventory
{
    const MAX_VIEW_DAYS = 31;
    const MAX_RANGE_DAYS = 366;

    public function __construct(private RatesCalendar $rates)
    {
    }

    public function totalRooms(Property $property, RoomType $roomType): int
    {
        return (int) PropertyRoomType::where(['property_id' => $property->id, 'roomType_id' => $roomType->id])->value('total_rooms');
    }

    /**
     * ['totalRooms' => int, 'sellable' => bool, 'days' => [date => [closed, booked, open, available, past]]]
     */
    public function load(Property $property, RoomType $roomType, string $from, string $to, int $maxDays = self::MAX_VIEW_DAYS): array
    {
        $dates = $this->dateRange($from, $to, $maxDays);
        $total = $this->totalRooms($property, $roomType);
        $sellable = $this->isSellable($property, $roomType);
        $open = $this->rates->load($property, $roomType, $from, $to, $maxDays)['open'];
        $rows = $this->rows($property, $roomType, $dates)->keyBy('inventory_date');

        $days = [];
        foreach ($dates as $date) {
            $closed = (int) ($rows[$date]->closed_rooms ?? 0);
            $booked = (int) ($rows[$date]->booked_rooms ?? 0);
            $days[$date] = [
                'closed' => $closed,
                'booked' => $booked,
                'open' => $open[$date],
                'available' => ($sellable && $open[$date]) ? max(0, $total - $closed - $booked) : 0,
                'past' => BusinessDate::isPast($date),
            ];
        }

        return ['totalRooms' => $total, 'sellable' => $sellable, 'days' => $days];
    }

    // Rooms bookable for every night of a stay (the lowest nightly availability)
    public function availableForStay(Property $property, RoomType $roomType, string $checkIn, string $checkOut): int
    {
        $nights = $this->nights($checkIn, $checkOut);
        if (!$nights || BusinessDate::isPast($nights[0])) {
            return 0;
        }

        $days = $this->load($property, $roomType, $nights[0], end($nights), self::MAX_RANGE_DAYS)['days'];

        return min(array_column($days, 'available'));
    }

    public function setTotalRooms(Property $property, RoomType $roomType, int $total): void
    {
        if ($total < 0 || $total > 10000) {
            throw ValidationException::withMessages(['total_rooms' => 'Please enter a valid number of rooms.']);
        }

        // Cannot drop below what is already closed + booked on any future date
        $inUse = (int) RoomInventory::where(['property_id' => $property->id, 'roomType_id' => $roomType->id])
            ->where('inventory_date', '>=', BusinessDate::today())
            ->max(DB::raw('closed_rooms + booked_rooms'));

        if ($total < $inUse) {
            throw ValidationException::withMessages(['total_rooms' => "Some future dates already have $inUse rooms closed or booked; the total cannot be lower than that."]);
        }

        PropertyRoomType::updateOrCreate(
            ['property_id' => $property->id, 'roomType_id' => $roomType->id],
            ['total_rooms' => $total]
        );
    }

    /**
     * Sets closed rooms for individual dates: [date => closed count].
     * Today and future dates only; closed + booked may not exceed the total.
     */
    public function setClosed(Property $property, RoomType $roomType, array $closedByDate): void
    {
        $total = $this->totalRooms($property, $roomType);
        $dates = array_keys($closedByDate);
        $booked = $this->rows($property, $roomType, $dates)->pluck('booked_rooms', 'inventory_date');

        $errors = [];
        $values = [];
        foreach ($closedByDate as $date => $closed) {
            if (!$this->isValidDate($date)) {
                $errors[] = 'Invalid date.';
                continue;
            }
            if (BusinessDate::isPast($date)) {
                $errors[] = "$date is in the past; past inventory cannot be changed.";
                continue;
            }
            if (!is_numeric($closed) || (int) $closed != $closed || $closed < 0) {
                $errors[] = "$date: closed rooms must be a whole number of 0 or more.";
                continue;
            }
            $max = $total - (int) ($booked[$date] ?? 0);
            if ($closed > $max) {
                $errors[] = "$date: at most $max room(s) can be closed (total $total, booked ".(int) ($booked[$date] ?? 0).').';
                continue;
            }
            $values[$date] = (int) $closed;
        }

        if ($errors) {
            throw ValidationException::withMessages(['closed' => array_values(array_unique($errors))]);
        }

        $this->writeClosed($property, $roomType, $values);

        $summary = collect($values)->map(fn ($closed, $date) => "$date: $closed")->take(31)->join(', ');
        Audit::log('inventory.closed', $roomType, "Closed rooms of {$roomType->roomType} set ($summary".(count($values) > 31 ? ', ...' : '').')', null, $property->id);
    }

    /**
     * Sets the same closed count on a range of dates (optionally only some
     * weekdays, 0 = Sunday). Past dates in the range are skipped.
     * Returns the number of dates updated.
     */
    public function setClosedForRange(Property $property, RoomType $roomType, string $from, string $to, array $weekdays, int $closed): int
    {
        if (!$this->isValidDate($from) || !$this->isValidDate($to) || $to < $from) {
            throw ValidationException::withMessages(['range' => 'Please choose a valid date range.']);
        }

        $from = max($from, BusinessDate::today());
        if ($from > $to) {
            throw ValidationException::withMessages(['range' => 'The range must include today or a future date.']);
        }

        $dates = array_values(array_filter(
            $this->dateRange($from, $to, self::MAX_RANGE_DAYS),
            fn ($date) => !$weekdays || in_array((int) date('w', strtotime($date)), $weekdays)
        ));

        if (!$dates) {
            throw ValidationException::withMessages(['range' => 'No dates match the selected range and weekdays.']);
        }

        $this->setClosed($property, $roomType, array_fill_keys($dates, $closed));

        return count($dates);
    }

    /**
     * Books $rooms rooms for every night of the stay. Must run inside the
     * booking's transaction. Throws if any night does not have enough rooms.
     */
    public function book(Property $property, RoomType $roomType, string $checkIn, string $checkOut, int $rooms): void
    {
        $nights = $this->nights($checkIn, $checkOut);
        if (!$nights || $rooms < 1) {
            throw new RuntimeException('Invalid stay or room count.');
        }

        $available = $this->availableForStay($property, $roomType, $checkIn, $checkOut);
        $locked = $this->lockRows($property, $roomType, $nights);

        // Re-check against the locked rows: another booking may have committed meanwhile
        $total = $this->totalRooms($property, $roomType);
        foreach ($nights as $date) {
            $row = $locked[$date];
            if ($available < $rooms || $total - $row->closed_rooms - $row->booked_rooms < $rooms) {
                throw new RuntimeException("Not enough rooms available on $date.");
            }
        }

        foreach ($nights as $date) {
            $locked[$date]->increment('booked_rooms', $rooms);
        }
    }

    // Returns booked rooms to inventory (cancellation, no-show, expiry). Must run inside a transaction.
    public function release(Property $property, RoomType $roomType, string $checkIn, string $checkOut, int $rooms): void
    {
        $nights = $this->nights($checkIn, $checkOut);
        foreach ($this->lockRows($property, $roomType, $nights) as $row) {
            $row->booked_rooms = max(0, $row->booked_rooms - $rooms);
            $row->save();
        }
    }

    public function isSellable(Property $property, RoomType $roomType): bool
    {
        return $property->is_active
            && $roomType->is_active
            && PropertyRoomType::where(['property_id' => $property->id, 'roomType_id' => $roomType->id, 'is_active' => true])->exists();
    }

    private function rows(Property $property, RoomType $roomType, array $dates)
    {
        return RoomInventory::where(['property_id' => $property->id, 'roomType_id' => $roomType->id])
            ->whereIn('inventory_date', $dates)
            ->get()
            ->each(fn ($row) => $row->inventory_date = substr($row->inventory_date, 0, 10));
    }

    // Creates missing rows, then locks every night's row for update; [date => RoomInventory]
    private function lockRows(Property $property, RoomType $roomType, array $nights): array
    {
        RoomInventory::insertOrIgnore(array_map(fn ($date) => [
            'property_id' => $property->id,
            'roomType_id' => $roomType->id,
            'inventory_date' => $date,
            'closed_rooms' => 0,
            'booked_rooms' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $nights));

        return RoomInventory::where(['property_id' => $property->id, 'roomType_id' => $roomType->id])
            ->whereIn('inventory_date', $nights)
            ->lockForUpdate()
            ->get()
            ->keyBy(fn ($row) => substr($row->inventory_date, 0, 10))
            ->all();
    }

    private function writeClosed(Property $property, RoomType $roomType, array $values): void
    {
        DB::transaction(function () use ($property, $roomType, $values) {
            foreach ($values as $date => $closed) {
                RoomInventory::updateOrCreate(
                    ['property_id' => $property->id, 'roomType_id' => $roomType->id, 'inventory_date' => $date],
                    ['closed_rooms' => $closed]
                );
            }
        });
    }

    // Nights of a stay: check-in up to (not including) check-out
    private function nights(string $checkIn, string $checkOut): array
    {
        if (!$this->isValidDate($checkIn) || !$this->isValidDate($checkOut) || $checkOut <= $checkIn) {
            return [];
        }

        return $this->dateRange($checkIn, date('Y-m-d', strtotime($checkOut.' -1 day')), self::MAX_RANGE_DAYS);
    }

    private function dateRange(string $from, string $to, int $maxDays): array
    {
        $dates = collect(CarbonPeriod::create($from, $to))->map(fn ($date) => $date->toDateString())->all();

        if (count($dates) > $maxDays) {
            throw ValidationException::withMessages(['dates' => "Please select a range of up to $maxDays days."]);
        }

        return $dates;
    }

    private function isValidDate($date): bool
    {
        return is_string($date)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));
    }
}
