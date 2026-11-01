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

    // Platform (Bedspace) commission in % of each payment. Changed by DevOps at the
    // admin's request; each booking keeps the rate in force when it was made.
    'commission_rate' => (float) env('BEDSPACE_COMMISSION_RATE', 5),

    // Default cancellation policy: cancellations within this many days before
    // check-in are not refunded. Each property may set its own number of days.
    'free_cancellation_days' => (int) env('BEDSPACE_FREE_CANCELLATION_DAYS', 3),

    // Online payment gateway: "dummy" (test page, no real money) until Sampath Bank IPG is integrated.
    // The dummy gateway refuses to run in production unless explicitly allowed.
    'payment_gateway' => env('PAYMENT_GATEWAY', 'dummy'),
    'allow_dummy_gateway_in_production' => (bool) env('ALLOW_DUMMY_GATEWAY_IN_PRODUCTION', false),

    // MCP server (/api/mcp): optional bearer token; empty = open (read-only public data)
    'mcp_token' => env('MCP_TOKEN'),

    /*
    | Website chatbot (hotel receptionist) powered by Claude. Stays in
    | "coming soon" mode until CHATBOT_ENABLED=true, an ANTHROPIC_API_KEY is
    | set and the SDK is installed (composer require anthropic-ai/sdk guzzlehttp/guzzle).
    */
    'chatbot' => [
        'enabled' => (bool) env('CHATBOT_ENABLED', false),
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('CHATBOT_MODEL', 'claude-opus-5-5'),
        'effort' => env('CHATBOT_EFFORT', 'low'),          // low suits chat; raise only if answers need more care
        'max_tokens' => (int) env('CHATBOT_MAX_TOKENS', 2048), // per reply; receptionist answers are short
        'max_tool_rounds' => 6,                             // tool calls per guest message
        'history_messages' => 20,                           // earlier messages sent with each question
        'max_messages_per_visitor_per_day' => (int) env('CHATBOT_DAILY_LIMIT_PER_VISITOR', 40),
        'max_messages_per_day' => (int) env('CHATBOT_DAILY_LIMIT', 2000), // whole site: a hard budget guard

        // USD per million tokens, for the cost estimate on the admin Chatbot page (check current pricing)
        'prices' => [
            'claude-opus-5-5' => ['input' => 4.00, 'output' => 20.00, 'cache_read' => 0.20],
            'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00, 'cache_read' => 0.20],
            'claude-haiku-5-5' => ['input' => 0.10, 'output' => 0.50, 'cache_read' => 0.01], // cache read assumed ~10% of input; confirm
        ],
    ],

    /*
    | What a photo shows (photos.tag), chosen by the owner when uploading.
    | Keys are stored; labels are shown to owners and guests. Add new tags at
    | the end; do not rename keys that are already in use.
    */
    'photo_tags' => [
        'property' => [
            'entrance' => 'Entrance',
            'exterior' => 'Exterior / outdoor',
            'lobby' => 'Lobby',
            'reception' => 'Reception',
            'dining' => 'Dining area',
            'restaurant' => 'Restaurant',
            'bar' => 'Bar / lounge',
            'pool' => 'Swimming pool',
            'garden' => 'Garden',
            'spa' => 'Spa & wellness',
            'gym' => 'Fitness centre',
            'beach' => 'Beach',
            'view' => 'View',
            'common' => 'Common areas',
            'kids' => "Kids' area",
            'events' => 'Meeting / event space',
            'parking' => 'Parking',
            'other' => 'Other',
        ],
        'roomType' => [
            'bed' => 'Bed / bedroom',
            'bathroom' => 'Bathroom',
            'amenities' => 'Room amenities',
            'living' => 'Living / seating area',
            'balcony' => 'Balcony / terrace',
            'view' => 'View from the room',
            'kitchenette' => 'Kitchenette',
            'other' => 'Other',
        ],
    ],

    /*
    | Amenity groups (amenities.category), in display order. Room amenities
    | follow the groups used by Expedia. Admins pick one when adding an amenity.
    */
    'amenity_categories' => [
        'roomType' => [
            'bedroom' => 'Bedroom',
            'bathroom' => 'Bathroom',
            'entertainment' => 'Entertainment',
            'food' => 'Food and drink',
            'internet' => 'Internet',
            'comfort' => 'Comfort',
            'practical' => 'Practical',
            'outdoor' => 'Outdoor space and view',
            'accessibility' => 'Accessibility',
            'other' => 'Other',
        ],
        'property' => [
            'general' => 'General',
            'wellness' => 'Wellness and leisure',
            'food' => 'Food and drink',
            'services' => 'Services',
            'internet' => 'Internet and entertainment',
            'other' => 'Other',
        ],
    ],

    'currency_cookie' => 'bedspace_currency',
    'currency_cookie_minutes' => 60 * 24 * 365 * 2,

    // Request header holding the visitor's ISO country code. Cloudflare adds
    // CF-IPCountry; only trust it when the server is reachable through Cloudflare only.
    'country_header' => env('GEOIP_COUNTRY_HEADER', 'CF-IPCountry'),

];
