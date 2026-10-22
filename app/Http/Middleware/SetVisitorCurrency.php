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
 * Public pages: decides which prices the visitor sees.
 *
 * - Rate currency: which price list applies (LKR local rates or USD
 *   foreign rates). Decided by the cookie: visitors detected outside the
 *   home country get a USD cookie; everyone without a foreign cookie gets
 *   local rates. The visitor cannot change this.
 * - Display currency: the currency prices are shown in. Defaults to the
 *   rate currency; the visitor may switch it (kept in the session), which
 *   only converts the same price, it does not change the price list.
 *
 * Views receive $rateCurrency and $displayCurrency (CurrencyType); controllers
 * read them from $request->attributes.
 */
class SetVisitorCurrency
{
    const DISPLAY_SESSION_KEY = 'display_currency';

    public function __construct(private VisitorCountry $visitorCountry)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $rateCurrency = $this->rateCurrency($request);

        $displayCode = $request->session()->get(self::DISPLAY_SESSION_KEY);
        $displayCurrency = ($displayCode ? CurrencyType::active()->where('code', $displayCode)->first() : null)
            ?? $rateCurrency;

        $request->attributes->set('rateCurrency', $rateCurrency);
        $request->attributes->set('displayCurrency', $displayCurrency);
        View::share(compact('rateCurrency', 'displayCurrency'));

        return $next($request);
    }

    private function rateCurrency(Request $request): CurrencyType
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

        // No cookie yet: detect once and remember. Unknown country = local rates, not remembered.
        $country = $this->visitorCountry->detect($request);
        if ($country === null) {
            return $local;
        }

        $currency = $country === config('bedspace.home_country') ? $local : $foreign;
        Cookie::queue($cookieName, $currency->code, config('bedspace.currency_cookie_minutes'));

        return $currency;
    }
}
