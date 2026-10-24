<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelPayout;
use App\Models\Property;
use App\Services\HotelPayouts;
use Illuminate\Http\Request;

// What the platform owes hotels for online payments, and payouts made
class HotelPayoutController extends Controller
{
    public function __construct(private HotelPayouts $payouts)
    {
    }

    public function index(){
        $rows = $this->payouts->byProperty();
        $total = round($rows->sum('payable_lkr'), 2);
        $recent = HotelPayout::with('property', 'recordedBy')->latest('paid_at')->take(20)->get();

        return view('admin.payouts', compact('rows', 'total', 'recent'));
    }

    public function show($property_id){
        $property = Property::with('propertyOwner')->findOrFail($property_id);
        $payments = $this->payouts->paymentsFor($property);
        $history = HotelPayout::with('recordedBy')->where('property_id', $property->id)->latest('paid_at')->get();

        return view('admin.payoutsProperty', compact('property', 'payments', 'history'));
    }

    public function pay(Request $request, $property_id){
        $property = Property::findOrFail($property_id);
        $data = $request->validate([
            'payment_ids' => ['required', 'array', 'min:1'],
            'payment_ids.*' => ['integer'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_ids.required' => 'Select the payments being paid out.',
        ]);

        $payout = $this->payouts->pay($property, array_map('intval', $data['payment_ids']),
            $data['reference'] ?? null, $data['notes'] ?? null, $request->user());

        return redirect()->back()->with('success', 'Payout of LKR '.number_format($payout->amount_lkr, 2).' to '.$property->property_name.' recorded.');
    }
}
