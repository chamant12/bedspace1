<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Monthly income from completed payments, in LKR (each payment's own
 * exchange-rate snapshot), per property. Months follow the business
 * timezone; a payment counts in the month it was received.
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
     * One row per property with payments in the month.
     * @param int|null $ownerId limit to one owner's properties
     */
    public function byProperty(string $month, ?int $ownerId = null): Collection
    {
        [$from, $to] = $this->monthRange($month);

        $rows = Payment::query()
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->join('properties', 'properties.id', '=', 'reservations.property_id')
            ->leftJoin('users', 'users.id', '=', 'properties.property_owner_id')
            ->where('payments.status', Payment::COMPLETED)
            ->where('payments.paid_at', '>=', $from)
            ->where('payments.paid_at', '<', $to)
            ->when($ownerId, fn ($q) => $q->where('properties.property_owner_id', $ownerId))
            ->groupBy('properties.id', 'properties.property_name', 'users.name')
            ->orderBy('properties.property_name')
            ->selectRaw("
                properties.id as property_id,
                properties.property_name,
                users.name as owner_name,
                SUM(CASE WHEN payments.kind = 'payment' THEN 1 ELSE 0 END) as payments,
                SUM(CASE WHEN payments.kind = 'refund' THEN payments.amount_lkr ELSE 0 END) as refunds_lkr,
                COUNT(DISTINCT payments.reservation_id) as bookings,
                SUM(CASE WHEN payments.collected_by = 'platform' THEN payments.amount_lkr ELSE 0 END) as online_lkr,
                SUM(CASE WHEN payments.collected_by = 'property' THEN payments.amount_lkr ELSE 0 END) as hotel_lkr,
                SUM(CASE WHEN payments.collected_by = 'platform' THEN payments.commission_lkr ELSE 0 END) as commission_online_lkr,
                SUM(CASE WHEN payments.collected_by = 'property' THEN payments.commission_lkr ELSE 0 END) as commission_hotel_lkr
            ")
            ->get();

        return $rows->map(function ($row) {
            $online = (float) $row->online_lkr;
            $hotel = (float) $row->hotel_lkr;
            $commissionOnline = (float) $row->commission_online_lkr;
            $commissionHotel = (float) $row->commission_hotel_lkr;

            return (object) [
                'property_id' => $row->property_id,
                'property_name' => $row->property_name,
                'owner_name' => $row->owner_name,
                'bookings' => (int) $row->bookings,
                'payments' => (int) $row->payments,
                'refunds_lkr' => round(-(float) $row->refunds_lkr, 2), // shown as a positive amount
                'online_lkr' => round($online, 2),
                'hotel_lkr' => round($hotel, 2),
                'gross_lkr' => round($online + $hotel, 2),
                'commission_lkr' => round($commissionOnline + $commissionHotel, 2),
                'net_to_hotel_lkr' => round($online + $hotel - $commissionOnline - $commissionHotel, 2),
                // > 0: platform pays the hotel; < 0: hotel pays the platform
                'settlement_lkr' => round(($online - $commissionOnline) - $commissionHotel, 2),
            ];
        });
    }

    public function totals(Collection $rows): object
    {
        $sum = fn ($field) => round($rows->sum($field), 2);

        return (object) [
            'bookings' => $rows->sum('bookings'),
            'payments' => $rows->sum('payments'),
            'refunds_lkr' => $sum('refunds_lkr'),
            'online_lkr' => $sum('online_lkr'),
            'hotel_lkr' => $sum('hotel_lkr'),
            'gross_lkr' => $sum('gross_lkr'),
            'commission_lkr' => $sum('commission_lkr'),
            'net_to_hotel_lkr' => $sum('net_to_hotel_lkr'),
            'settlement_lkr' => $sum('settlement_lkr'),
        ];
    }
}
