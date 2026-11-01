<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionSettlement;
use App\Models\Property;
use App\Services\CommissionDues;
use Illuminate\Http\Request;

// Commission owed by hotels on payments they collected, and settlements of it
class CommissionDueController extends Controller
{
    public function __construct(private CommissionDues $dues)
    {
    }

    public function index(){
        $rows = $this->dues->byProperty();
        // Per currency, never combined: [code => amount]
        $totals = $rows->groupBy('currency')->sortKeys()->map(fn ($group) => round($group->sum('due'), 2));
        $settlements = CommissionSettlement::with('property', 'recordedBy', 'currency')->latest('settled_at')->take(20)->get();

        return view('admin.commissionDues', compact('rows', 'totals', 'settlements'));
    }

    public function show($property_id){
        $property = Property::with('propertyOwner')->findOrFail($property_id);
        $payments = $this->dues->paymentsFor($property);
        $settlements = CommissionSettlement::with('recordedBy', 'currency')->where('property_id', $property->id)->latest('settled_at')->get();

        return view('admin.commissionDuesProperty', compact('property', 'payments', 'settlements'));
    }

    public function settle(Request $request, $property_id){
        $property = Property::findOrFail($property_id);
        $data = $request->validate([
            'payment_ids' => ['required', 'array', 'min:1'],
            'payment_ids.*' => ['integer'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_ids.required' => 'Select the commissions the hotel has paid.',
        ]);

        $settlement = $this->dues->settle($property, array_map('intval', $data['payment_ids']),
            $data['reference'] ?? null, $data['notes'] ?? null, $request->user());

        return redirect()->back()->with('success', 'Settlement of '.$settlement->money().' recorded for '.$property->property_name.'.');
    }
}
