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
 */
class CommissionDues
{
    private function unsettled()
    {
        return Payment::query()
            ->where('payments.status', Payment::COMPLETED)
            ->where('payments.collected_by', 'property')
            ->whereNull('payments.commission_settlement_id')
            ->where('payments.commission_lkr', '!=', 0); // refunds carry negative commission
    }

    // [property_id => ['property', 'payments', 'due_lkr']] for all properties with dues
    public function byProperty(?int $ownerId = null): Collection
    {
        $rows = $this->unsettled()
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->join('properties', 'properties.id', '=', 'reservations.property_id')
            ->when($ownerId, fn ($q) => $q->where('properties.property_owner_id', $ownerId))
            ->groupBy('reservations.property_id')
            ->selectRaw('reservations.property_id, COUNT(payments.id) as payments, SUM(payments.commission_lkr) as due_lkr')
            ->get();

        $properties = Property::with('propertyOwner')->whereIn('id', $rows->pluck('property_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'property' => $properties[$row->property_id],
            'payments' => (int) $row->payments,
            'due_lkr' => round((float) $row->due_lkr, 2),
        ])->sortBy(fn ($row) => $row->property->property_name)->values();
    }

    public function totalFor(Property $property): float
    {
        return round((float) $this->paymentsFor($property)->sum('commission_lkr'), 2);
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
     * The hotel paid the commission on the selected payments.
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

            $settlement = new CommissionSettlement();
            $settlement->property_id = $property->id;
            $settlement->amount_lkr = round((float) $payments->sum('commission_lkr'), 2);
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
