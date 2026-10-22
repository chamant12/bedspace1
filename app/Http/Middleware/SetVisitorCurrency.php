<?php

namespace App\Http\Middleware;

use App\Models\CurrencyType;
use App\Services\VisitorCountry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages: decides which currency's rates the visitor sees.
 *
 * The cookie sets the default: visitors detected outside the home country
 * get a USD cookie, everyone without a foreign cookie sees LKR. A visitor
 * may switch currency (kept in the session); the selected currency's own
 * price list is then shown. Foreign guests paying local rates is handled
 * by the hotel's ID check at check-in.
 *
 * Views receive $visitorCurrency (CurrencyType); controllers read it from
 * $request->attributes->get('visitorCurrency').
 */
class SetVisitorCurrency
{
    const SESSION_KEY = 'visitor_currency';

    public function __construct(private VisitorCountry $visitorCountry)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $selected = $request->session()->get(self::SESSION_KEY);
        $visitorCurrency = ($selected ? CurrencyType::active()->where('code', $selected)->first() : null)
            ?? $this->defaultCurrency($request);

        $request->attributes->set('visitorCurrency', $visitorCurrency);
        View::share('visitorCurrency', $visitorCurrency);

        return $next($request);
    }

    private function defaultCurrency(Request $request): CurrencyType
    {
        $cookieName = config('bedspace.currency_cookie');
        $local = CurrencyType::byCode(config('bedspace.local_currency'));
        $foreign = CurrencyType::byCode(config('bedspace.foreign_currency'));

        $cookie = $request->cookie($cookieName);
        if ($cookie === $foreign->code) {
            return $foreign;
        }
        if ($cookie === $local->code) {
            return $local;
        }

        // No cookie yet: detect once and remember. Unknown country = local, not remembered.
        $country = $this->visitorCountry->detect($request);
        if ($country === null) {
            return $local;
        }

        $currency = $country === config('bedspace.home_country') ? $local : $foreign;
        Cookie::queue($cookieName, $currency->code, config('bedspace.currency_cookie_minutes'));

        return $currency;
    }
}
