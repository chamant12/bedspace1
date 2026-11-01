<?php

namespace App\Services;

use App\Models\CommissionSettlement;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Commission the hotels owe the platform: the commission on every payment
 * a hotel collected itself (pay at hotel), until the admin records a
 * settlement covering it. (Online payments are collected by the platform,
 * which keeps the commission and pays out the rest.)
 *
 * Commission is owed in the currency of the payment: LKR and USD dues are
 * kept apart and settled separately, never converted.
 */
class CommissionDues
{
    private function unsettled()
    {
        return Payment::query()
            ->where('payments.status', Payment::COMPLETED)
            ->where('payments.collected_by', 'property')
            ->whereNull('payments.commission_settlement_id')
            ->where('payments.commission_amount', '!=', 0); // refunds carry negative commission
    }

    // One row per property and currency with dues: property, currency, payments, due
    public function byProperty(?int $ownerId = null): Collection
    {
        $rows = $this->unsettled()
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->join('properties', 'properties.id', '=', 'reservations.property_id')
            ->join('currency_types', 'currency_types.id', '=', 'payments.currency_type_id')
            ->when($ownerId, fn ($q) => $q->where('properties.property_owner_id', $ownerId))
            ->groupBy('reservations.property_id', 'currency_types.code')
            ->selectRaw('reservations.property_id, currency_types.code as currency, COUNT(payments.id) as payments, SUM(payments.commission_amount) as due')
            ->get();

        $properties = Property::with('propertyOwner')->whereIn('id', $rows->pluck('property_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'property' => $properties[$row->property_id],
            'currency' => $row->currency,
            'payments' => (int) $row->payments,
            'due' => round((float) $row->due, 2),
        ])->sortBy(fn ($row) => $row->property->property_name.'|'.$row->currency)->values();
    }

    // Unsettled payments of one property, oldest first
    public function paymentsFor(Property $property): Collection
    {
        return $this->unsettled()
            ->with('reservation', 'currency')
            ->whereIn('reservation_id', fn ($q) => $q->select('id')->from('reservations')->where('property_id', $property->id))
            ->orderBy('paid_at')
            ->get();
    }

    /**
     * The hotel paid the commission on the selected payments (all in one currency).
     * @param int[] $paymentIds
     */
    public function settle(Property $property, array $paymentIds, ?string $reference, ?string $notes, User $by): CommissionSettlement
    {
        return DB::transaction(function () use ($property, $paymentIds, $reference, $notes, $by) {
            $payments = $this->unsettled()
                ->whereIn('id', $paymentIds)
                ->whereIn('reservation_id', fn ($q) => $q->select('id')->from('reservations')->where('property_id', $property->id))
                ->lockForUpdate()
                ->get();

            if ($payments->isEmpty() || $payments->count() !== count(array_unique($paymentIds))) {
                throw ValidationException::withMessages(['payments' => 'Some selected commissions are already settled or do not belong to this property.']);
            }
            if ($payments->pluck('currency_type_id')->unique()->count() > 1) {
                throw ValidationException::withMessages(['payments' => 'Settle LKR and USD commissions separately.']);
            }

            $settlement = new CommissionSettlement();
            $settlement->property_id = $property->id;
            $settlement->currency_type_id = $payments->first()->currency_type_id;
            $settlement->amount = round((float) $payments->sum('commission_amount'), 2);
            $settlement->reference = $reference;
            $settlement->notes = $notes;
            $settlement->settled_at = now();
            $settlement->recorded_by_user_id = $by->id;
            $settlement->save();

            Payment::whereIn('id', $payments->pluck('id'))->update(['commission_settlement_id' => $settlement->id, 'updated_at' => now()]);

            return $settlement;
        });
    }
}
