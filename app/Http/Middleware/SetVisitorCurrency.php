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
 * Public pages: decides which currency the visitor sees prices in.
 * Visitors from the home country get LKR, everyone else USD; the result is
 * stored in a cookie so it is reused on every later visit.
 *
 * Views receive $visitorCurrency (a CurrencyType); controllers can read it
 * with $request->attributes->get('visitorCurrency').
 */
class SetVisitorCurrency
{
    public function __construct(private VisitorCountry $visitorCountry)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('bedspace.currency_cookie');
        $code = $request->cookie($cookieName);
        $currency = $code ? CurrencyType::active()->where('code', $code)->first() : null;

        if (!$currency) {
            $country = $this->visitorCountry->detect($request);

            if ($country !== null) {
                $code = $country === config('bedspace.home_country')
                    ? config('bedspace.local_currency')
                    : config('bedspace.foreign_currency');
                $currency = CurrencyType::byCode($code);
                Cookie::queue($cookieName, $currency->code, config('bedspace.currency_cookie_minutes'));
            } else {
                // Country unknown: show the fallback but do not remember it, so detection is retried
                $currency = CurrencyType::byCode(config('bedspace.fallback_currency'));
            }
        }

        $request->attributes->set('visitorCurrency', $currency);
        View::share('visitorCurrency', $currency);

        return $next($request);
    }
}
