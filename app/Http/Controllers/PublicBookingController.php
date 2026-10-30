<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Amenity;
use App\Models\District;
use App\Models\MealType;
use App\Models\Occupancy;
use App\Models\Photo;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingQuotes;
use App\Services\Payments;
use App\Cms\Cms;
use App\Models\BlogPost;
use App\Services\CancellationPolicy;
use App\Enums\ReservationStatus;
use App\Services\Reservations;
use App\Support\BusinessDate;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Guest-facing booking flow: search -> property -> checkout -> confirmation.
 * Prices are in the visitor's currency (SetVisitorCurrency middleware).
 */
class PublicBookingController extends Controller
{
    public function __construct(
        private BookingQuotes $quotes,
        private Reservations $reservations,
        private Payments $payments,
        private CancellationPolicy $cancellationPolicy,
    ) {
    }

    // Guest cancels their own booking (signed link from the booking page); the policy decides any refund
    public function cancel(Request $request, string $reference){
        $reservation = Reservation::where('reference', $reference)->firstOrFail();
        abort_unless($this->guestCanCancel($reservation), 403);

        $this->reservations->transition($reservation, ReservationStatus::Cancelled, Auth::user(), 'Cancelled by the guest');
        $reservation->refresh();

        $message = $reservation->refund_due > 0
            ? 'Your booking was cancelled. A refund of '.$reservation->money($reservation->refund_due).' is due under the cancellation policy.'
            : 'Your booking was cancelled.';

        return redirect($this->bookingUrl($reservation))->with('success', $message);
    }

    private function guestCanCancel(Reservation $reservation): bool
    {
        return in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)
            && $reservation->check_in->toDateString() >= BusinessDate::today();
    }

    // Retry / continue an online payment for a pending booking (signed link from the booking page)
    public function pay(string $reference){
        $reservation = Reservation::where('reference', $reference)->firstOrFail();
        return redirect()->away($this->payments->startOnline($reservation));
    }

    // Home page: CMS content, search form, featured stays and the latest blog posts
    public function home(Request $request){
        $stay = $this->stayFromRequest($request);

        // Up to 6 recently added active properties, priced for one night from tomorrow
        $tomorrow = CarbonImmutable::parse(BusinessDate::today())->addDay();
        $featuredStays = Property::with('city.district')->where('is_active', true)->latest()->take(6)->get()
            ->map(fn ($property) => [
                'property' => $property,
                'price' => $this->quotes->lowestPrice($property, $tomorrow->toDateString(), $tomorrow->addDay()->toDateString(),
                    $this->currency($request), null, 1),
                'photo' => $this->coverPhoto($property)?->thumb_url ?? asset('theme/tour-package-1.jpg'),
                'url' => url('/stay/'.$property->id),
            ]);

        return view('welcome', [
            'page' => Cms::page('home'),
            'stay' => $stay,
            'districts' => $this->districtsWithProperties(),
            'occupancies' => Occupancy::orderBy('occupancy')->get(),
            'featuredStays' => $featuredStays,
            'posts' => BlogPost::published()->orderByDesc('published_at')->take(3)->get(),
        ]);
    }

    public function search(Request $request){
        $stay = $this->stayFromRequest($request);
        $districts = $this->districtsWithProperties();

        // ?area=Kandy (destination links): use the matching district, otherwise match city names
        $area = trim((string) $request->query('area', ''));
        if ($area !== '' && !$stay['district_id']) {
            $stay['district_id'] = District::where('district_name', 'like', '%'.$area.'%')->value('id');
        }

        $results = [];
        $error = null;
        if ($request->filled('check_in') || $request->filled('district_id') || $area !== '') {
            $error = $this->quotes->validateStay($stay['check_in'], $stay['check_out']);
            if (!$error) {
                $properties = Property::with('city.district')
                    ->where('is_active', true)
                    ->when($stay['district_id'], fn ($q) => $q->whereIn('city_id', function ($sub) use ($stay) {
                        $sub->select('id')->from('cities')->where('district_id', $stay['district_id']);
                    }))
                    ->when(!$stay['district_id'] && $area !== '', fn ($q) => $q->whereIn('city_id', function ($sub) use ($area) {
                        $sub->select('id')->from('cities')->where('city_name', 'like', '%'.$area.'%');
                    }))
                    ->orderBy('property_name')
                    ->get();

                foreach ($properties as $property) {
                    // Properties without complete rates or free rooms for the dates are hidden
                    $price = $this->quotes->lowestPrice($property, $stay['check_in'], $stay['check_out'],
                        $this->currency($request), $stay['guests'], $stay['rooms']);
                    if ($price !== null) {
                        $results[] = ['property' => $property, 'price' => $price, 'photo' => $this->coverPhoto($property)];
                    }
                }
                usort($results, fn ($a, $b) => $a['price'] <=> $b['price']);
            }
        }

        // Location searches ("Hotels in Kandy"): a few available properties picked at random,
        // each with a random sample of its photos
        $location = $stay['district_id'] ? District::whereKey($stay['district_id'])->value('district_name') : ($area !== '' ? $area : null);
        $featured = $location
            ? $this->featuredForLocation($request, $stay['district_id'] ? 'district:'.$stay['district_id'] : 'area:'.mb_strtolower($area), $results)
            : collect();

        return view('public.search', compact('stay', 'districts', 'results', 'error', 'location', 'featured') + [
            'occupancies' => Occupancy::orderBy('occupancy')->get(),
        ]);
    }

    public function property(Request $request, $property_id){
        $property = Property::with('city.district', 'primaryContact')->where('is_active', true)->findOrFail($property_id);
        $stay = $this->stayFromRequest($request);

        $error = $this->quotes->validateStay($stay['check_in'], $stay['check_out']);
        $offers = $error ? [] : $this->quotes->offers($property, $stay['check_in'], $stay['check_out'],
            $this->currency($request), $stay['guests'], $stay['rooms']);

        $photos = Photo::where(['upload_type' => 'property', 'type_id' => $property->id, 'is_active' => true])->orderBy('sort_order')->get();
        // Room photos, shown with each room type while the guest picks guests and meal plan
        $roomPhotos = Photo::where(['upload_type' => 'roomType', 'is_active' => true])
            ->whereIn('type_id', collect($offers)->map(fn ($offer) => $offer['roomType']->id))
            ->orderBy('sort_order')->orderBy('id')->get()
            ->groupBy('type_id');
        $amenities = Amenity::whereIn('id', function ($q) use ($property) {
            $q->select('amenity_id')->from('property_amenity_xrefs')->where(['property_id' => $property->id, 'is_active' => true]);
        })->where('is_active', true)->orderBy('amenity')->get();

        return view('public.property', compact('property', 'stay', 'error', 'offers', 'photos', 'roomPhotos', 'amenities') + [
            'occupancies' => Occupancy::orderBy('occupancy')->get(),
            'cancellationSummary' => $error
                ? $this->cancellationPolicy->summary($property)
                : $this->cancellationPolicy->statementForStay($property, $stay['check_in']),
        ]);
    }

    public function checkout(Request $request){
        $choice = $this->choiceFromRequest($request);
        $quote = $this->quoteChoice($request, $choice);

        if (!$quote) {
            return redirect('/stay/'.$choice['property']->id.'?'.http_build_query($this->stayQuery($choice)))
                ->with('error', 'Sorry, that room is no longer available for these dates. Please choose again.');
        }

        return view('public.checkout', $choice + [
            'quote' => $quote,
            'roomPhoto' => Photo::where(['upload_type' => 'roomType', 'type_id' => $choice['roomType']->id, 'is_active' => true])
                ->orderBy('sort_order')->orderBy('id')->first(),
            'account' => $this->customer(),
            'paymentMethods' => PaymentMethod::available(),
            'cancellationPolicy' => $this->cancellationPolicy->statementForStay($choice['property'], $choice['check_in']),
        ]);
    }

    public function book(Request $request){
        $choice = $this->choiceFromRequest($request);
        $account = $this->customer();

        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:50'],
            'guest_address' => ['nullable', 'string', 'max:500'],
            'guest_country' => ['nullable', 'string', 'max:100'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(array_map(fn ($m) => $m->value, PaymentMethod::available()))],
            'accept_terms' => ['accepted'],
            'create_account' => ['nullable', 'boolean'],
            'password' => ['nullable', 'required_if:create_account,1', 'string', 'min:8'],
        ], [
            'accept_terms.accepted' => 'Please accept the booking terms and cancellation policy.',
        ]);

        $reservation = DB::transaction(function () use ($request, $choice, $data, &$account) {
            // Optional guest account, created together with the booking
            if (!$account && $request->boolean('create_account')) {
                if (User::where('email', $data['guest_email'])->exists()) {
                    throw ValidationException::withMessages(['guest_email' => 'An account with this email already exists. Please sign in, or book without creating an account.']);
                }
                $account = new User();
                $account->name = $data['guest_name'];
                $account->email = $data['guest_email'];
                $account->phone = $data['guest_phone'];
                $account->password = Hash::make($data['password']);
                $account->user_role_id = 1;
                $account->user_type_id = User::TYPE_CUSTOMER;
                $account->save();
            }

            return $this->reservations->create(
                $choice['property'],
                [[
                    'roomType' => $choice['roomType'],
                    'occupancy_id' => $choice['occupancy']->id,
                    'mealType_id' => $choice['mealType']->id,
                    'rooms' => $choice['rooms'],
                ]],
                $choice['check_in'],
                $choice['check_out'],
                $this->currency($request),
                [
                    'name' => $data['guest_name'],
                    'email' => $data['guest_email'],
                    'phone' => $data['guest_phone'],
                    'address' => $data['guest_address'] ?? null,
                    'country' => $data['guest_country'] ?? null,
                    'special_requests' => $data['special_requests'] ?? null,
                ],
                PaymentMethod::from($data['payment_method']),
                $account,
            );
        });

        if ($account && !Auth::check()) {
            Auth::login($account);
            $request->session()->regenerate();
        }

        // Online: rooms are held while the guest pays; the booking is confirmed by the payment
        if ($reservation->payment_method === PaymentMethod::Online) {
            return redirect()->away($this->payments->startOnline($reservation));
        }

        return redirect($this->bookingUrl($reservation))->with('success', 'Your booking is confirmed. A confirmation has been emailed to '.$reservation->guest_email.'.');
    }

    // Booking details; reachable through a signed link (confirmation page, email lookup) or by the guest's account
    public function booking(Request $request, string $reference){
        $reservation = Reservation::with('property.primaryContact', 'rooms.roomType', 'rooms.occupancy', 'rooms.mealType', 'rooms.discounts', 'currency')
            ->where('reference', $reference)->firstOrFail();

        $ownAccount = Auth::check() && $reservation->user_id && (int) $reservation->user_id === (int) Auth::id();
        abort_unless($request->hasValidSignature() || $ownAccount, 403);

        // Pending online booking that can still be paid
        $payUrl = ($reservation->status === ReservationStatus::Pending && $reservation->expires_at?->isFuture())
            ? URL::temporarySignedRoute('booking.pay', $reservation->expires_at, ['reference' => $reservation->reference])
            : null;

        $cancelUrl = $this->guestCanCancel($reservation)
            ? URL::temporarySignedRoute('booking.cancel', now()->addHours(2), ['reference' => $reservation->reference])
            : null;
        $cancellationPolicy = $this->cancellationPolicy->statementForReservation($reservation);
        $refundableNow = $this->cancellationPolicy->isRefundable($reservation);

        return view('public.booking', compact('reservation', 'payUrl', 'cancelUrl', 'cancellationPolicy', 'refundableNow'));
    }

    // Guest account: their bookings
    public function myBookings(){
        $reservations = Reservation::with('property', 'currency')
            ->where('user_id', Auth::id())
            ->orderByDesc('check_in')
            ->get();

        return view('account.bookings', compact('reservations'));
    }

    public function findBookingForm(){
        return view('public.findBooking');
    }

    // Reference + email -> signed link to the booking
    public function findBooking(Request $request){
        $request->validate([
            'reference' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email'],
        ]);

        $reservation = Reservation::where('reference', strtoupper(trim($request->reference)))->first();
        if (!$reservation || strcasecmp(trim($reservation->guest_email), trim($request->email)) !== 0) {
            return back()->withInput()->with('error', 'No booking was found with that reference and email address.');
        }

        return redirect($this->bookingUrl($reservation));
    }

    // Districts that have at least one active property
    private function districtsWithProperties()
    {
        return District::whereIn('id', function ($query) {
            $query->select('cities.district_id')->from('properties')
                ->join('cities', 'cities.id', '=', 'properties.city_id')
                ->where('properties.is_active', true);
        })->orderBy('district_name')->get();
    }

    private function bookingUrl(Reservation $reservation): string
    {
        return URL::temporarySignedRoute('booking.show', now()->addDays(30), ['reference' => $reservation->reference]);
    }

    // check_in, check_out, guests (per room), rooms; defaults: tomorrow, 1 night, any occupancy, 1 room
    private function stayFromRequest(Request $request): array
    {
        $tomorrow = CarbonImmutable::parse(BusinessDate::today())->addDay();
        return [
            'check_in' => (string) $request->input('check_in', $tomorrow->toDateString()),
            'check_out' => (string) $request->input('check_out', $tomorrow->addDay()->toDateString()),
            'guests' => $request->filled('guests') ? max(1, (int) $request->guests) : null,
            'rooms' => min(BookingQuotes::MAX_ROOMS, max(1, (int) $request->input('rooms', 1))),
            'district_id' => $request->input('district_id'),
        ];
    }

    private function choiceFromRequest(Request $request): array
    {
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'occupancy_id' => ['required', 'integer'],
            'mealType_id' => ['required', 'integer'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d'],
            'rooms' => ['required', 'integer', 'min:1', 'max:'.BookingQuotes::MAX_ROOMS],
        ]);

        $property = Property::where('is_active', true)->findOrFail($request->property_id);
        $roomType = RoomType::with('property')->where('is_active', true)->findOrFail($request->roomType_id);
        abort_unless((int) $roomType->property?->property_owner_id === (int) $property->property_owner_id, 404);

        return [
            'property' => $property,
            'roomType' => $roomType,
            'occupancy' => Occupancy::findOrFail($request->occupancy_id),
            'mealType' => MealType::findOrFail($request->mealType_id),
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'rooms' => (int) $request->rooms,
        ];
    }

    private function quoteChoice(Request $request, array $choice): ?array
    {
        return $this->quotes->quote($choice['property'], $choice['roomType'], $choice['occupancy']->id, $choice['mealType']->id,
            $choice['check_in'], $choice['check_out'], $choice['rooms'], $this->currency($request));
    }

    private function stayQuery(array $choice): array
    {
        return ['check_in' => $choice['check_in'], 'check_out' => $choice['check_out'], 'rooms' => $choice['rooms']];
    }

    private function currency(Request $request)
    {
        return $request->attributes->get('visitorCurrency');
    }

    private function customer(): ?User
    {
        $user = Auth::user();
        return ($user && (int) $user->user_type_id === User::TYPE_CUSTOMER) ? $user : null;
    }

    /**
     * Up to 3 random results that have photos, each with up to 3 random photos.
     * The picks are kept in the session per location, so refreshing shows the
     * same set until the session ends; a pick that is no longer available
     * (other dates, photo removed) is replaced by a new random one.
     */
    private function featuredForLocation(Request $request, string $locationKey, array $results)
    {
        $byId = collect($results)->keyBy(fn ($result) => $result['property']->id);
        $photos = Photo::where(['upload_type' => 'property', 'is_active' => true])
            ->whereIn('type_id', $byId->keys())
            ->get()
            ->groupBy('type_id');

        $sessionKey = 'featured_picks.'.md5($locationKey);
        $saved = $request->session()->get($sessionKey, []); // [property id => [photo ids]]

        // Keep saved picks that are still available, with their still-active photos
        $picks = [];
        foreach ($saved as $propertyId => $photoIds) {
            $active = $photos->get($propertyId, collect())->whereIn('id', $photoIds)->pluck('id')->all();
            if ($byId->has($propertyId) && $active) {
                $picks[$propertyId] = $active;
            }
        }

        // Top up with random new picks
        $candidates = $byId->keys()->filter(fn ($id) => $photos->has($id) && !isset($picks[$id]))->shuffle();
        foreach ($candidates->take(3 - count($picks)) as $propertyId) {
            $picks[$propertyId] = $photos->get($propertyId)->shuffle()->take(3)->pluck('id')->all();
        }

        if ($picks !== $saved) {
            $request->session()->put($sessionKey, $picks);
        }

        return collect($picks)->map(fn ($photoIds, $propertyId) => $byId->get($propertyId) + [
            'samples' => $photos->get($propertyId)->whereIn('id', $photoIds)
                ->sortBy(fn ($photo) => array_search($photo->id, $photoIds))->values(),
        ])->values();
    }

    private function coverPhoto(Property $property): ?Photo
    {
        return Photo::where(['upload_type' => 'property', 'type_id' => $property->id, 'is_active' => true])->orderBy('sort_order')->first();
    }
}
