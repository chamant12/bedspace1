<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\RoomNumberController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\DummyGatewayController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PropertyDetailsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin;

// Public (guest-facing) pages
Route::middleware('visitor.currency')->group(function () {
    Route::get('/', [PublicBookingController::class, 'home']);

    // Website pages (CMS)
    Route::get('/about', [SiteController::class, 'about']);
    Route::get('/contact', [SiteController::class, 'contact']);
    Route::post('/contact', [SiteController::class, 'sendContact'])->middleware('throttle:5,1');
    Route::get('/blog', [SiteController::class, 'blog']);
    Route::get('/blog/{slug}', [SiteController::class, 'post'])->where('slug', '[a-z0-9-]+');
    Route::get('/page/{slug}', [SiteController::class, 'page'])->where('slug', '[a-z0-9-]+');
    Route::post('/currency', [CurrencyController::class, 'switchCurrency']);

    // Booking engine
    Route::get('/search', [PublicBookingController::class, 'search']);
    Route::get('/stay/{property_id}', [PublicBookingController::class, 'property']);
    Route::get('/book', [PublicBookingController::class, 'checkout']);
    Route::post('/book', [PublicBookingController::class, 'book'])->middleware('throttle:10,1');
    Route::get('/booking/find', [PublicBookingController::class, 'findBookingForm']);
    Route::post('/booking/find', [PublicBookingController::class, 'findBooking'])->middleware('throttle:10,1');
    Route::get('/booking/{reference}', [PublicBookingController::class, 'booking'])->name('booking.show');
    Route::post('/booking/{reference}/pay', [PublicBookingController::class, 'pay'])->name('booking.pay')->middleware('signed');
    Route::post('/booking/{reference}/cancel', [PublicBookingController::class, 'cancel'])->name('booking.cancel')->middleware('signed');

    // Dummy payment gateway (test page until Sampath Bank IPG)
    Route::get('/payments/dummy/{payment}', [DummyGatewayController::class, 'show'])->name('payments.dummy')->middleware('signed');
    Route::post('/payments/dummy/{payment}/result', [DummyGatewayController::class, 'result'])->name('payments.dummy.result')->middleware('signed');

    // Virtual receptionist chat widget
    Route::get('/chat/status', [ChatController::class, 'status']);
    Route::post('/chat/message', [ChatController::class, 'message'])->middleware('throttle:20,1');
    Route::post('/chat/reset', [ChatController::class, 'reset']);

    // Optional guest accounts
    Route::get('/account/login', [UserLoginController::class, 'customerLogin']);
    Route::post('/account/login', [UserLoginController::class, 'authenticateCustomer'])->middleware('throttle:10,1');
    Route::get('/account/bookings', [PublicBookingController::class, 'myBookings'])->middleware(['auth', 'user.type:customer']);
});


Route::get('/login', [UserLoginController::class, 'login'])->name('login');
Route::post('/logout', [UserLoginController::class, 'logout'])->middleware('auth');
Route::post('login', [UserLoginController::class, 'authenticateUser'])->middleware('throttle:10,1');
Route::get('/signup', [UserLoginController::class, 'propertyOwnerSignup']);
Route::post('/create-property-owner', [UserLoginController::class, 'createPropertyOwner']);

// Password reset (all account types)
Route::get('/forgot-password', [PasswordResetController::class, 'requestForm']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

Route::get('/admin/login', [UserLoginController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin/login', [UserLoginController::class, 'authenticateAdmin'])->middleware('throttle:10,1');

// Platform admin area
Route::prefix('admin')->middleware(['auth', 'user.type:admin'])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index']);
    Route::get('/properties', [Admin\PropertyController::class, 'index']);
    Route::post('/properties/{property_id}/toggle-active', [Admin\PropertyController::class, 'toggleActive']);
    Route::get('/reports/income', [ReportController::class, 'adminIncome']);
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy']);
    Route::get('/reports/bookings', [ReportController::class, 'bookings']);
    Route::get('/audit', [AuditLogController::class, 'index']);
    Route::get('/chatbot', [Admin\ChatbotController::class, 'index']);

    // Website content (CMS)
    Route::get('/cms', [Admin\CmsController::class, 'index']);
    Route::get('/cms/settings', [Admin\CmsController::class, 'settings']);
    Route::post('/cms/settings', [Admin\CmsController::class, 'saveSettings']);
    Route::post('/cms/pages', [Admin\CmsController::class, 'createPage']);
    Route::get('/cms/pages/{page_id}', [Admin\CmsController::class, 'editPage']);
    Route::post('/cms/pages/{page_id}', [Admin\CmsController::class, 'savePage']);
    Route::post('/cms/pages/{page_id}/toggle', [Admin\CmsController::class, 'togglePage']);
    Route::post('/cms/sections/{section_id}', [Admin\CmsController::class, 'saveSection']);
    Route::get('/cms/blog', [Admin\BlogPostController::class, 'index']);
    Route::get('/cms/blog/new', [Admin\BlogPostController::class, 'create']);
    Route::post('/cms/blog', [Admin\BlogPostController::class, 'store']);
    Route::get('/cms/blog/{post_id}', [Admin\BlogPostController::class, 'edit']);
    Route::put('/cms/blog/{post_id}', [Admin\BlogPostController::class, 'update']);
    Route::post('/cms/blog/{post_id}/toggle', [Admin\BlogPostController::class, 'togglePublish']);
    Route::get('/cms/messages', [Admin\ContactMessageController::class, 'index']);
    Route::get('/cms/messages/{message_id}', [Admin\ContactMessageController::class, 'show']);
    Route::post('/cms/messages/{message_id}/status', [Admin\ContactMessageController::class, 'setStatus']);
    Route::get('/commission-dues', [Admin\CommissionDueController::class, 'index']);
    Route::get('/commission-dues/{property_id}', [Admin\CommissionDueController::class, 'show']);
    Route::post('/commission-dues/{property_id}/settle', [Admin\CommissionDueController::class, 'settle']);
    Route::get('/payouts', [Admin\HotelPayoutController::class, 'index']);
    Route::get('/payouts/{property_id}', [Admin\HotelPayoutController::class, 'show']);
    Route::post('/payouts/{property_id}/pay', [Admin\HotelPayoutController::class, 'pay']);
    Route::get('/owners', [Admin\OwnerController::class, 'index']);
    Route::get('/owners/new', [Admin\OwnerController::class, 'create']);
    Route::post('/owners', [Admin\OwnerController::class, 'store']);
    Route::post('/owners/{user_id}/approve', [Admin\OwnerController::class, 'approve']);
    Route::get('/owners/{user_id}', [Admin\OwnerController::class, 'edit'])->whereNumber('user_id');
    Route::put('/owners/{user_id}', [Admin\OwnerController::class, 'update'])->whereNumber('user_id');
    Route::post('/owners/{user_id}/toggle-status', [Admin\OwnerController::class, 'toggleStatus']);
    Route::get('/amenities', [Admin\AmenityController::class, 'index']);
    Route::post('/amenities', [Admin\AmenityController::class, 'store']);
    Route::put('/amenities/{amenity_id}', [Admin\AmenityController::class, 'update']);
    Route::post('/amenities/{amenity_id}/toggle-active', [Admin\AmenityController::class, 'toggleActive']);
});

// My profile (dashboard users; also available while an owner awaits approval)
Route::middleware(['auth', 'user.type:admin,owner,staff'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::put('/profile', [ProfileController::class, 'update']);
});

// Owner / staff dashboard (also shown while the owner awaits approval)
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'user.type:owner,staff']);

// Pages shared by several user types; access is decided by policies (admin passes all)
Route::middleware(['auth', 'partner.approved'])->group(function () {
    Route::get('/properties/{property_id}/details', [PropertyDetailsController::class, 'show']);
    Route::get('/rates/{property_id}/view', [RateController::class, 'viewRates']);
    Route::get('/api/view-base-rates/{property_id}/{roomType_id}/{from_date}/{to_date}', [RateController::class, 'getBaseRatesReadonly']);
    Route::get('/inventory/{property_id}/view', [InventoryController::class, 'view']);
    Route::get('/api/view-inventory/{property_id}/{roomType_id}/{from_date}/{to_date}', [InventoryController::class, 'gridReadonly']);

    // Reservations: owners and staff see their properties', admins all
    Route::middleware('user.type:admin,owner,staff')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::get('/reservations/new', [ReservationController::class, 'create'])->middleware('user.type:owner,staff');
        Route::post('/reservations', [ReservationController::class, 'store'])->middleware('user.type:owner,staff');
        Route::get('/reservations/{reference}', [ReservationController::class, 'show']);
        Route::post('/reservations/{reference}/status', [ReservationController::class, 'changeStatus']);
        Route::post('/reservations/{reference}/check-in', [ReservationController::class, 'checkIn']);
        Route::post('/reservations/{reference}/payments', [ReservationController::class, 'recordPayment']);
        Route::post('/reservations/{reference}/refunds', [ReservationController::class, 'refund']);
        Route::get('/guests', [GuestController::class, 'index']);
        Route::get('/guests/{guest_id}', [GuestController::class, 'show']);
    });
});

// Owner and staff: every partner may see their properties
Route::middleware(['auth', 'user.type:owner,staff', 'partner.approved'])->group(function () {
    Route::get('/my-properties', [PropertyController::class, 'listMyProperties']);
});

// Owner only: properties, staff accounts and income
Route::middleware(['auth', 'user.type:owner', 'partner.approved'])->group(function () {
    Route::get('/add-property', [PropertyController::class, 'addProperty']);
    Route::post('/create-property', [PropertyController::class, 'createProperty']);
    Route::post('/property/{property_id}/toggle-active', [PropertyController::class, 'togglePropertyActive']);
    Route::get('/reports/income', [ReportController::class, 'ownerIncome']);
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy']);
    Route::get('/reports/bookings', [ReportController::class, 'bookings']);
    Route::get('/audit', [AuditLogController::class, 'index']);

    Route::get('/staff', [StaffController::class, 'index']);
    Route::get('/staff/new', [StaffController::class, 'create']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::get('/staff/{user_id}', [StaffController::class, 'edit']);
    Route::put('/staff/{user_id}', [StaffController::class, 'update']);
    Route::post('/staff/{user_id}/toggle-status', [StaffController::class, 'toggleStatus']);
});

// Owner and staff with the "edit property details" permission
Route::middleware(['auth', 'user.type:owner,staff', 'partner.approved', 'can.edit'])->group(function () {
    Route::get('/view-property/{property_id}', [PropertyController::class, 'viewProperty']);
    Route::post('/update-property', [PropertyController::class, 'updateProperty']);
    Route::get('/add-room-type', [RoomTypeController::class, 'addRoomType']);
    Route::post('/create-room-type', [RoomTypeController::class, 'createRoomType']);
    Route::post('/roomType/{roomType_id}/toggle-active', [RoomTypeController::class, 'toggleRoomTypeActive']);
    Route::get('/view-roomType/{roomType_id}', [RoomTypeController::class, 'viewRoomType']);
    Route::post('/update-room-type', [RoomTypeController::class, 'updateRoomType']);
    Route::get('/add-base-rate/{property_id}', [RateController::class, 'addBaseRate']);
    Route::get('/inventory/{property_id}', [InventoryController::class, 'index']);
    Route::get('/rooms/{property_id}', [RoomNumberController::class, 'index']);
    Route::post('/rooms', [RoomNumberController::class, 'store']);
    Route::put('/rooms/{room_id}', [RoomNumberController::class, 'update']);
    Route::post('/rooms/{room_id}/toggle-active', [RoomNumberController::class, 'toggleActive']);
    Route::get('/amenities', [AmenityController::class, 'amenities']);
    Route::post('/addAmenity', [AmenityController::class, 'addAmenity']);
    Route::get('/photos', [PhotoController::class, 'selectPhotoType']);
    Route::get('/photos/property/{property_id}', [PhotoController::class, 'propertyGallery']);
    Route::get('/photos/property/{property_id}/room-type/{roomType_id}', [PhotoController::class, 'roomTypeGallery']);

    // AJAX endpoints used by the Blade pages
    Route::prefix('api')->group(function () {
        Route::get('/get-properties-by-city/{city_id}', [RoomTypeController::class, 'getPropertiesByCity']);
        Route::get('/get-base-rates/{property_id}/{roomType_id}/{from_date}/{to_date}', [RateController::class, 'getBaseRates']);
        Route::post('/save-base-rates', [RateController::class, 'saveBaseRates']);
        Route::post('/reset-base-rates', [RateController::class, 'resetBaseRates']);
        Route::post('/copy-base-rates', [RateController::class, 'copyBaseRates']);
        Route::get('/inventory/{property_id}/{roomType_id}/{from_date}/{to_date}', [InventoryController::class, 'grid']);
        Route::post('/inventory/total', [InventoryController::class, 'saveTotal']);
        Route::post('/inventory/closed', [InventoryController::class, 'saveClosed']);
        Route::post('/inventory/close-range', [InventoryController::class, 'closeRange']);
        Route::post('/remove-amenity/{amenity_id}/{type_id}/{amenityType}', [AmenityController::class, 'removeAmenity']);
        Route::get('/get-amenities/{amenity_type}/{type_id}', [AmenityController::class, 'getAmenities']);
        Route::post('/upload-photos', [PhotoController::class, 'uploadPhotos']);
        Route::post('/update-photo-order', [PhotoController::class, 'updatePhotoOrder']);
        Route::post('/remove-photo/{photo_id}', [PhotoController::class, 'deactivatePhoto']);
    });
});

// Location lookups for the property forms (owners and editing staff)
Route::middleware(['auth', 'user.type:owner,staff', 'partner.approved'])->prefix('api')->group(function () {
    Route::get('/get-districts-by-province/{province_id}', [PropertyController::class, 'getDistrictsByProvince']);
    Route::get('/get-cities-by-district/{district_id}', [PropertyController::class, 'getCitiesByDistrict']);
});
