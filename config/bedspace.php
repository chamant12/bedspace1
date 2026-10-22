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

    // Platform commission (%) on each payment, unless the admin set a rate for the property
    'default_commission_rate' => (float) env('BEDSPACE_COMMISSION_RATE', 10),

    // Online payment gateway: "dummy" (test page, no real money) until Sampath Bank IPG is integrated.
    // The dummy gateway refuses to run in production unless explicitly allowed.
    'payment_gateway' => env('PAYMENT_GATEWAY', 'dummy'),
    'allow_dummy_gateway_in_production' => (bool) env('ALLOW_DUMMY_GATEWAY_IN_PRODUCTION', false),

    'currency_cookie' => 'bedspace_currency',
    'currency_cookie_minutes' => 60 * 24 * 365 * 2,

    // Request header holding the visitor's ISO country code. Cloudflare adds
    // CF-IPCountry; only trust it when the server is reachable through Cloudflare only.
    'country_header' => env('GEOIP_COUNTRY_HEADER', 'CF-IPCountry'),

];
