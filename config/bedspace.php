<?php

return [

    /*
    | Visitor currency: visitors detected outside the home country get a
    | foreign-currency cookie and see foreign (USD) rates; everyone without
    | that cookie sees local (LKR) rates. Visitors can switch the display
    | currency, but the cookie decides which price list applies.
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
