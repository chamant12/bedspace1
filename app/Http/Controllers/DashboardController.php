<?php

namespace App\Http\Controllers;

use App\Models\Property;
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
    // Owner / staff dashboard: only the properties the user may access
    public function index(ExchangeRates $exchangeRates, CommissionDues $commissionDues, HotelPayouts $hotelPayouts)
    {
        $user = auth()->user();

        // Owners awaiting approval only see a notice
        if (!$user->isApproved()) {
            return view('dashboardPending');
        }

        $missingRates = $user->canEditProperties() ? $exchangeRates->missingTodayFor($user->ownerId()) : collect();

        $properties = Property::with('city')
            ->whereIn('id', $user->accessiblePropertyIds())
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
            'roomTypes' => RoomType::whereIn('property_id', Property::where('property_owner_id', $user->ownerId())->select('id'))->where('is_active', true)->count(),
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

        // Money owed between the owner and the platform: owners only
        $commissionDue = $user->isOwner() ? round($commissionDues->byProperty($user->id)->sum('due_lkr'), 2) : 0;
        $payoutDue = $user->isOwner() ? round($hotelPayouts->byProperty($user->id)->sum('payable_lkr'), 2) : 0;
        $refundsDue = Reservation::whereIn('property_id', $propertyIds)->where('refund_status', 'due')->count();
        $offlineChannels = PaymentChannel::offline();
        $today = BusinessDate::today();

        return view('dashboard', compact('properties', 'stats', 'missingRates', 'roomCounts', 'offlineToSettle', 'commissionDue', 'payoutDue', 'refundsDue', 'offlineChannels', 'today'));
    }
}
