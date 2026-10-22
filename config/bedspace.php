<?php

return [

    // Business day boundary: "today" / "past dates" for rates and bookings.
    // The app itself stays on UTC so stored timestamps are unaffected.
    'timezone' => env('BEDSPACE_TIMEZONE', 'Asia/Colombo'),

    /*
    | Visitor currency: visitors detected outside the home country get a
    | foreign-currency cookie and see foreign (USD) rates by default; everyone
    | without that cookie sees local (LKR) rates. Visitors may switch currency
    | for the session; the cookie decides the default on every visit.
    */
    'home_country' => env('BEDSPACE_HOME_COUNTRY', 'LK'),
    'local_currency' => 'LKR',
    'foreign_currency' => 'USD',

    'currency_cookie' => 'bedspace_currency',
    'currency_cookie_minutes' => 60 * 24 * 365 * 2,

    // Request header holding the visitor's ISO country code. Cloudflare adds
    // CF-IPCountry; only trust it when the server is reachable through Cloudflare only.
    'country_header' => env('GEOIP_COUNTRY_HEADER', 'CF-IPCountry'),

];
