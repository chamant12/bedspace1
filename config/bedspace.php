<?php

return [

    /*
    | Visitor currency: guests from the home country see local-currency
    | prices, everyone else sees the foreign currency. The choice is kept in
    | a cookie so it stays the same on later visits.
    */
    'home_country' => env('BEDSPACE_HOME_COUNTRY', 'LK'),
    'local_currency' => 'LKR',
    'foreign_currency' => 'USD',

    // Used while the visitor's country cannot be determined (not stored in the cookie)
    'fallback_currency' => env('BEDSPACE_FALLBACK_CURRENCY', 'LKR'),

    'currency_cookie' => 'bedspace_currency',
    'currency_cookie_minutes' => 60 * 24 * 365 * 2,

    // Request header holding the visitor's ISO country code. Cloudflare adds
    // CF-IPCountry; only trust it when the server is reachable through Cloudflare only.
    'country_header' => env('GEOIP_COUNTRY_HEADER', 'CF-IPCountry'),

];
