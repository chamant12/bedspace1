<?php

namespace App\Http\Controllers;

use App\Models\PropertyRoomType;
use App\Models\RoomType;
use App\Services\ExchangeRates;
use App\Services\CommissionDues;
use App\Models\Reservation;
use App\Services\HotelPayouts;
use App\Enums\PaymentChannel;
use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Support\BusinessDate;

class DashboardController extends Controller
{
    // Owner dashboard: only the logged-in owner's properties
    public function index(ExchangeRates $exchangeRates, CommissionDues $commissionDues, HotelPayouts $hotelPayouts)
    {
        $missingRates = $exchangeRates->missingTodayFor(auth()->user()->id);

        $properties = auth()->user()->properties()
            ->with('city')
            ->orderBy('property_name')
            ->get();

        $propertyIds = $properties->pluck('id');
        $roomCounts = PropertyRoomType::whereIn('property_id', $propertyIds)
            ->where('is_active', true)
            ->selectRaw('property_id, SUM(total_rooms) as rooms')
            ->groupBy('property_id')
            ->pluck('rooms', 'property_id');

        $stats = [
            'properties' => $properties->count(),
            'activeProperties' => $properties->where('is_active', true)->count(),
            // Room types are owner-level
            'roomTypes' => RoomType::whereIn('property_id', $propertyIds)->where('is_active', true)->count(),
            'rooms' => (int) $roomCounts->sum(),
        ];

        // Pay-at-hotel bookings not yet settled: the owner marks them paid, cancelled or no-show
        $offlineToSettle = Reservation::with('property', 'currency')
            ->whereIn('property_id', $propertyIds)
            ->where('payment_method', PaymentMethod::PayAtHotel)
            ->whereIn('status', [ReservationStatus::Confirmed, ReservationStatus::CheckedIn, ReservationStatus::CheckedOut])
            ->where('payment_status', '!=', 'paid')
            ->orderBy('check_in')
            ->take(50)
            ->get();

        $commissionDue = round($commissionDues->byProperty(auth()->id())->sum('due_lkr'), 2);
        $payoutDue = round($hotelPayouts->byProperty(auth()->id())->sum('payable_lkr'), 2);
        $offlineChannels = PaymentChannel::offline();
        $today = BusinessDate::today();

        return view('dashboard', compact('properties', 'stats', 'missingRates', 'roomCounts', 'offlineToSettle', 'commissionDue', 'payoutDue', 'offlineChannels', 'today'));
    }
}
