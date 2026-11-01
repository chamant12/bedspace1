<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Monthly income from completed payments, per property and currency.
 * LKR payments are totalled in LKR and USD payments in USD; amounts in
 * different currencies are never converted or added together. Months
 * follow the business timezone; a payment counts in the month it was received.
 *
 * Money flows:
 * - online payments are collected by the platform, which owes the hotel
 *   the amount minus commission;
 * - payments at the hotel are collected by the hotel, which owes the
 *   platform the commission.
 * settlement > 0: platform pays the hotel; < 0: the hotel pays the platform.
 *
 * Refunds are negative payment rows: they reduce the collected amounts and
 * the commission in the month they are made.
 */
class IncomeReports
{
    // [start, end) of a "YYYY-MM" month in UTC
    public function monthRange(string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $month.'-01 00:00:00', config('bedspace.timezone'));
        return [$start->utc(), $start->addMonth()->utc()];
    }

    public function isValidMonth(?string $month): bool
    {
        return (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $month);
    }

    /**
     * One row per property and currency with payments in the month.
     * @param int|null $ownerId limit to one owner's properties
     */
    public function byProperty(string $month, ?int $ownerId = null): Collection
    {
        [$from, $to] = $this->monthRange($month);

        $rows = Payment::query()
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->join('properties', 'properties.id', '=', 'reservations.property_id')
            ->join('currency_types', 'currency_types.id', '=', 'payments.currency_type_id')
            ->leftJoin('users', 'users.id', '=', 'properties.property_owner_id')
            ->where('payments.status', Payment::COMPLETED)
            ->where('payments.paid_at', '>=', $from)
            ->where('payments.paid_at', '<', $to)
            ->when($ownerId, fn ($q) => $q->where('properties.property_owner_id', $ownerId))
            ->groupBy('properties.id', 'properties.property_name', 'users.name', 'currency_types.code')
            ->orderBy('properties.property_name')
            ->orderBy('currency_types.code')
            ->selectRaw("
                properties.id as property_id,
                properties.property_name,
                users.name as owner_name,
                currency_types.code as currency,
                SUM(CASE WHEN payments.kind = 'payment' THEN 1 ELSE 0 END) as payments,
                SUM(CASE WHEN payments.kind = 'refund' THEN payments.amount ELSE 0 END) as refunds,
                COUNT(DISTINCT payments.reservation_id) as bookings,
                SUM(CASE WHEN payments.collected_by = 'platform' THEN payments.amount ELSE 0 END) as online,
                SUM(CASE WHEN payments.collected_by = 'property' THEN payments.amount ELSE 0 END) as hotel,
                SUM(CASE WHEN payments.collected_by = 'platform' THEN payments.commission_amount ELSE 0 END) as commission_online,
                SUM(CASE WHEN payments.collected_by = 'property' THEN payments.commission_amount ELSE 0 END) as commission_hotel
            ")
            ->get();

        return $rows->map(function ($row) {
            $online = (float) $row->online;
            $hotel = (float) $row->hotel;
            $commissionOnline = (float) $row->commission_online;
            $commissionHotel = (float) $row->commission_hotel;

            return (object) [
                'property_id' => $row->property_id,
                'property_name' => $row->property_name,
                'owner_name' => $row->owner_name,
                'currency' => $row->currency,
                'bookings' => (int) $row->bookings,
                'payments' => (int) $row->payments,
                'refunds' => round(-(float) $row->refunds, 2), // shown as a positive amount
                'online' => round($online, 2),
                'hotel' => round($hotel, 2),
                'gross' => round($online + $hotel, 2),
                'commission' => round($commissionOnline + $commissionHotel, 2),
                'net_to_hotel' => round($online + $hotel - $commissionOnline - $commissionHotel, 2),
                // > 0: platform pays the hotel; < 0: hotel pays the platform
                'settlement' => round(($online - $commissionOnline) - $commissionHotel, 2),
            ];
        });
    }

    // Totals per currency: [code => totals], LKR first
    public function totals(Collection $rows): Collection
    {
        return $rows->groupBy('currency')
            ->sortKeys()
            ->map(function (Collection $currencyRows, string $currency) {
                $sum = fn ($field) => round($currencyRows->sum($field), 2);

                return (object) [
                    'currency' => $currency,
                    'bookings' => $currencyRows->sum('bookings'),
                    'payments' => $currencyRows->sum('payments'),
                    'refunds' => $sum('refunds'),
                    'online' => $sum('online'),
                    'hotel' => $sum('hotel'),
                    'gross' => $sum('gross'),
                    'commission' => $sum('commission'),
                    'net_to_hotel' => $sum('net_to_hotel'),
                    'settlement' => $sum('settlement'),
                ];
            });
    }
}
