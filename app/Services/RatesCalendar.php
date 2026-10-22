<?php

namespace App\Services;

use App\Models\BaseRate;
use App\Models\CurrencyType;
use App\Models\MealType;
use App\Models\Occupancy;
use App\Models\Property;
use App\Models\RateType;
use App\Models\RoomType;
use App\Support\BusinessDate;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rules of the rates calendar (one room type at one property):
 *
 * - A "combination" is occupancy x meal plan. Each combination on a date has
 *   both a USD and an LKR price, or neither (partial = rejected).
 * - A date is OPEN for the room type when it has at least one complete
 *   combination; otherwise it is CLOSED and behaves as fully booked.
 * - Past dates (business timezone) are read-only: never saved, reset or
 *   copied onto.
 * - Nothing is deleted: clearing / resetting sets the rate to 0.
 *
 * Rates are stored in base_rates, one row per currency, keyed by the
 * property's "base_rate" rate type, so the same owner-level room type can
 * have different rates (or none) at each of the owner's properties.
 */
class RatesCalendar
{
    const CURRENCIES = [CurrencyType::USD, CurrencyType::LKR];
    const MAX_VIEW_DAYS = 31;
    const MAX_COPY_DAYS = 366;

    public function rateTypeId(Property $property): int
    {
        return RateType::where(['property_id' => $property->id, 'rateType' => 'base_rate'])->firstOrFail()->id;
    }

    // Combinations offered for a room type: occupancies up to its maximum x all meal plans
    public function occupancies(RoomType $roomType): Collection
    {
        return Occupancy::where('occupancy', '<=', $roomType->max_occupancy)->orderBy('occupancy')->get();
    }

    public function mealTypes(): Collection
    {
        return MealType::orderBy('id')->get();
    }

    /**
     * $grid[date][occupancy_id][mealType_id][USD|LKR] = price (only prices > 0)
     * $open[date] = bool
     */
    public function load(Property $property, RoomType $roomType, string $from, string $to, int $maxDays = self::MAX_VIEW_DAYS): array
    {
        $dates = $this->dateRange($from, $to, $maxDays);
        $currencyCodes = $this->currencyIds()->flip(); // id => code

        $grid = [];
        BaseRate::where(['roomType_id' => $roomType->id, 'rateType_id' => $this->rateTypeId($property)])
            ->whereIn('currency_type_id', $currencyCodes->keys())
            ->whereBetween('rate_for', [$from, $to])
            ->where('rate', '>', 0)
            ->get()
            ->each(function ($rate) use (&$grid, $currencyCodes) {
                $grid[substr($rate->rate_for, 0, 10)][$rate->occupancy_id][$rate->mealType_id][$currencyCodes[$rate->currency_type_id]] = (float) $rate->rate;
            });

        $open = [];
        foreach ($dates as $date) {
            $open[$date] = $this->hasCompleteCombination($grid[$date] ?? []);
        }

        return ['dates' => $dates, 'grid' => $grid, 'open' => $open];
    }

    /**
     * Saves prices for future dates.
     * $rates[date][occupancy_id][mealType_id][USD|LKR] = string|number|null
     */
    public function save(Property $property, RoomType $roomType, array $rates): void
    {
        $occupancyNames = $this->occupancies($roomType)->pluck('occupancy_name', 'id');
        $mealTypeNames = $this->mealTypes()->pluck('mealType', 'id');

        $errors = [];
        $values = [];
        foreach ($rates as $date => $occupancies) {
            if (!$this->isValidDate($date) || !is_array($occupancies)) {
                $errors[] = 'Invalid date in rates.';
                continue;
            }
            if (BusinessDate::isPast($date)) {
                $errors[] = "$date is in the past; past rates cannot be changed.";
                continue;
            }
            foreach ($occupancies as $occupancyId => $meals) {
                foreach ((array) $meals as $mealTypeId => $prices) {
                    if (!isset($occupancyNames[$occupancyId], $mealTypeNames[$mealTypeId])) {
                        $errors[] = 'Invalid occupancy or meal plan in rates.';
                        continue;
                    }
                    $label = "$date, {$occupancyNames[$occupancyId]} / {$mealTypeNames[$mealTypeId]}";
                    $cell = [];
                    foreach (self::CURRENCIES as $code) {
                        $raw = trim((string) ($prices[$code] ?? ''));
                        if ($raw === '' || (is_numeric($raw) && (float) $raw == 0)) {
                            $cell[$code] = null;
                        } elseif (!is_numeric($raw) || (float) $raw < 0 || (float) $raw > 99999999) {
                            $errors[] = "$label: $code price must be a positive amount.";
                            $cell[$code] = null;
                        } else {
                            $cell[$code] = round((float) $raw, 2);
                        }
                    }
                    if (($cell['USD'] === null) !== ($cell['LKR'] === null)) {
                        $errors[] = "$label: enter both the USD and the LKR price, or leave both empty.";
                    }
                    $values[$date][(int) $occupancyId][(int) $mealTypeId] = $cell;
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['rates' => array_values(array_unique($errors))]);
        }

        $this->write($property, $roomType, $values);
    }

    /**
     * Resets (sets to 0) the room type's prices at this property for one date,
     * or for every date from today on when $date is null. Returns rows changed.
     */
    public function reset(Property $property, RoomType $roomType, ?string $date): int
    {
        if ($date !== null) {
            if (!$this->isValidDate($date) || BusinessDate::isPast($date)) {
                throw ValidationException::withMessages(['date' => 'Only today and future dates can be reset.']);
            }
        }

        $query = BaseRate::where(['roomType_id' => $roomType->id, 'rateType_id' => $this->rateTypeId($property)])
            ->whereIn('currency_type_id', $this->currencyIds()->values())
            ->where('rate', '>', 0);

        $date !== null
            ? $query->where('rate_for', $date)
            : $query->where('rate_for', '>=', BusinessDate::today());

        return $query->update(['rate' => 0, 'updated_at' => now()]);
    }

    /**
     * Copies one date's prices onto future dates between $from and $to,
     * optionally only on some weekdays (0 = Sunday ... 6 = Saturday).
     * Target dates get exactly the source's prices. Returns dates updated.
     */
    public function copy(Property $property, RoomType $roomType, string $sourceDate, string $from, string $to, array $weekdays): int
    {
        if (!$this->isValidDate($sourceDate) || !$this->isValidDate($from) || !$this->isValidDate($to) || $to < $from) {
            throw ValidationException::withMessages(['copy' => 'Please choose a valid source date and target range.']);
        }

        $source = $this->load($property, $roomType, $sourceDate, $sourceDate)['grid'][$sourceDate] ?? [];
        if (!$this->hasCompleteCombination($source)) {
            throw ValidationException::withMessages(['copy' => "$sourceDate has no complete rates to copy."]);
        }

        $from = max($from, BusinessDate::today()); // never onto past dates
        if ($from > $to) {
            throw ValidationException::withMessages(['copy' => 'The target range must include today or a future date.']);
        }

        $targets = array_values(array_filter(
            $this->dateRange($from, $to, self::MAX_COPY_DAYS),
            fn ($date) => $date !== $sourceDate && (!$weekdays || in_array((int) date('w', strtotime($date)), $weekdays))
        ));

        if (!$targets) {
            throw ValidationException::withMessages(['copy' => 'No target dates match the selected range and weekdays.']);
        }

        // Every combination of the room type, so combinations absent on the source are cleared on targets
        $cells = [];
        foreach ($this->occupancies($roomType) as $occupancy) {
            foreach ($this->mealTypes() as $mealType) {
                $sourceCell = $source[$occupancy->id][$mealType->id] ?? [];
                $complete = isset($sourceCell['USD'], $sourceCell['LKR']);
                foreach (self::CURRENCIES as $code) {
                    $cells[$occupancy->id][$mealType->id][$code] = $complete ? $sourceCell[$code] : null;
                }
            }
        }

        $this->write($property, $roomType, array_fill_keys($targets, $cells));

        return count($targets);
    }

    /**
     * Open dates of a room type at a property within [from, to]:
     * dates that have at least one complete (USD + LKR) combination.
     * Used by availability: a closed date has 0 rooms available.
     */
    public function openDates(Property $property, RoomType $roomType, string $from, string $to): array
    {
        return array_keys(array_filter($this->load($property, $roomType, $from, $to)['open']));
    }

    // $values[date][occupancy][meal][code] = float|null; upserts, null = 0
    private function write(Property $property, RoomType $roomType, array $values): void
    {
        $rateTypeId = $this->rateTypeId($property);
        $currencyIds = $this->currencyIds();

        DB::transaction(function () use ($values, $roomType, $rateTypeId, $currencyIds) {
            $existing = BaseRate::where(['roomType_id' => $roomType->id, 'rateType_id' => $rateTypeId])
                ->whereIn('rate_for', array_keys($values))
                ->whereIn('currency_type_id', $currencyIds->values())
                ->get()
                ->keyBy(fn ($rate) => substr($rate->rate_for, 0, 10).'|'.$rate->occupancy_id.'|'.$rate->mealType_id.'|'.$rate->currency_type_id);

            foreach ($values as $date => $occupancies) {
                foreach ($occupancies as $occupancyId => $meals) {
                    foreach ($meals as $mealTypeId => $cell) {
                        foreach ($cell as $code => $amount) {
                            $baseRate = $existing["$date|$occupancyId|$mealTypeId|{$currencyIds[$code]}"] ?? null;

                            if (!$baseRate) {
                                if ($amount === null) {
                                    continue; // nothing to store
                                }
                                $baseRate = new BaseRate();
                                $baseRate->roomType_id = $roomType->id;
                                $baseRate->rateType_id = $rateTypeId;
                                $baseRate->mealType_id = $mealTypeId;
                                $baseRate->occupancy_id = $occupancyId;
                                $baseRate->currency_type_id = $currencyIds[$code];
                                $baseRate->rate_for = $date;
                            } elseif ((float) $baseRate->rate === (float) ($amount ?? 0)) {
                                continue; // unchanged
                            }

                            $baseRate->rate = $amount ?? 0;
                            $baseRate->save();
                        }
                    }
                }
            }
        });
    }

    // [occupancy][meal][code] => price; true if any combination has both prices
    private function hasCompleteCombination(array $dateGrid): bool
    {
        foreach ($dateGrid as $meals) {
            foreach ($meals as $prices) {
                if (isset($prices['USD'], $prices['LKR'])) {
                    return true;
                }
            }
        }
        return false;
    }

    // code => id
    private function currencyIds(): Collection
    {
        return CurrencyType::whereIn('code', self::CURRENCIES)->pluck('id', 'code');
    }

    private function dateRange(string $from, string $to, int $maxDays = self::MAX_VIEW_DAYS): array
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
