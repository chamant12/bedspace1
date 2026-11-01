<?php

namespace App\Services;

use App\Models\HotelPayout;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * What the platform owes hotels: for each online payment it collected,
 * the amount less the platform commission, until the admin records a
 * payout covering it. Payments of bookings with a refund due (or already
 * refunded) are left out: that money goes back to the guest.
 *
 * Amounts stay in the payment's currency: LKR and USD are owed and paid
 * out separately, never converted.
 */
class HotelPayouts
{
    private function unpaid()
    {
        return Payment::query()
            ->where('payments.status', Payment::COMPLETED)
            ->where('payments.collected_by', 'platform')
            ->whereNull('payments.hotel_payout_id')
            ->whereIn('payments.reservation_id', fn ($q) => $q->select('id')->from('reservations')->where('refund_status', '!=', 'due'));
    }

    // Amount owed to the hotel for one payment, in the payment's currency
    public static function payable(Payment $payment): float
    {
        return round((float) $payment->amount - (float) $payment->commission_amount, 2);
    }

    // One row per property and currency with an unpaid balance
    public function byProperty(?int $ownerId = null): Collection
    {
        $rows = $this->unpaid()
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->join('properties', 'properties.id', '=', 'reservations.property_id')
            ->join('currency_types', 'currency_types.id', '=', 'payments.currency_type_id')
            ->when($ownerId, fn ($q) => $q->where('properties.property_owner_id', $ownerId))
            ->groupBy('reservations.property_id', 'currency_types.code')
            ->selectRaw('reservations.property_id, currency_types.code as currency, COUNT(payments.id) as payments, SUM(payments.amount - payments.commission_amount) as payable')
            ->get();

        $properties = Property::with('propertyOwner')->whereIn('id', $rows->pluck('property_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'property' => $properties[$row->property_id],
            'currency' => $row->currency,
            'payments' => (int) $row->payments,
            'payable' => round((float) $row->payable, 2),
        ])->filter(fn ($row) => $row->payable != 0)->sortBy(fn ($row) => $row->property->property_name.'|'.$row->currency)->values();
    }

    public function paymentsFor(Property $property): Collection
    {
        return $this->unpaid()
            ->with('reservation', 'currency')
            ->whereIn('reservation_id', fn ($q) => $q->select('id')->from('reservations')->where('property_id', $property->id))
            ->orderBy('paid_at')
            ->get();
    }

    /**
     * The platform paid the hotel for the selected online payments (all in one currency).
     * @param int[] $paymentIds
     */
    public function pay(Property $property, array $paymentIds, ?string $reference, ?string $notes, User $by): HotelPayout
    {
        return DB::transaction(function () use ($property, $paymentIds, $reference, $notes, $by) {
            $payments = $this->unpaid()
                ->whereIn('id', $paymentIds)
                ->whereIn('reservation_id', fn ($q) => $q->select('id')->from('reservations')->where('property_id', $property->id))
                ->lockForUpdate()
                ->get();

            if ($payments->isEmpty() || $payments->count() !== count(array_unique($paymentIds))) {
                throw ValidationException::withMessages(['payments' => 'Some selected payments are already paid out, refunded or do not belong to this property.']);
            }
            if ($payments->pluck('currency_type_id')->unique()->count() > 1) {
                throw ValidationException::withMessages(['payments' => 'Pay out LKR and USD payments separately.']);
            }

            $payout = new HotelPayout();
            $payout->property_id = $property->id;
            $payout->currency_type_id = $payments->first()->currency_type_id;
            $payout->amount = round($payments->sum(fn ($payment) => self::payable($payment)), 2);
            $payout->reference = $reference;
            $payout->notes = $notes;
            $payout->paid_at = now();
            $payout->recorded_by_user_id = $by->id;
            $payout->save();

            Payment::whereIn('id', $payments->pluck('id'))->update(['hotel_payout_id' => $payout->id, 'updated_at' => now()]);

            return $payout;
        });
    }
}
