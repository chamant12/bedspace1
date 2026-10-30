<?php

namespace App\Chat;

use App\Services\GuestApi;

/**
 * The guest-information tools, defined once and used by both
 * - the MCP server (/mcp: tools/list, tools/call), and
 * - the chatbot, which runs them in-process for speed.
 *
 * Definitions use the key "inputSchema", which is what both MCP and the
 * Anthropic PHP SDK expect. All tools are read-only: none can book,
 * change or cancel anything.
 */
class GuestTools
{
    public function __construct(private GuestApi $api)
    {
    }

    public function definitions(): array
    {
        $date = ['type' => 'string', 'description' => 'Date as YYYY-MM-DD'];
        $currency = ['type' => 'string', 'enum' => ['LKR', 'USD'], 'description' => 'Currency for prices. Use the guest\'s currency unless they ask for another.'];

        return [
            [
                'name' => 'list_destinations',
                'description' => 'List the areas (districts of Sri Lanka) where EpickTours has bookable properties.',
                'inputSchema' => ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false],
            ],
            [
                'name' => 'search_properties',
                'description' => 'Find properties with rooms available for a stay, cheapest first, with the lowest total price and a link to each property. Use when the guest asks where they can stay, for prices in an area, or what is available on certain dates.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'check_in' => array_merge($date, ['description' => 'Check-in date (YYYY-MM-DD). Defaults to tomorrow.']),
                        'check_out' => array_merge($date, ['description' => 'Check-out date (YYYY-MM-DD). Defaults to one night after check-in.']),
                        'area' => ['type' => 'string', 'description' => 'District or city name (e.g. "Galle", "Kandy"), or a district_id. Omit for all of Sri Lanka.'],
                        'guests_per_room' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10],
                        'rooms' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10],
                        'currency' => $currency,
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'get_property',
                'description' => 'Get a property\'s public details: location, map link, amenities, room types, cancellation policy, photos and its page URL.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer'],
                        'check_in' => array_merge($date, ['description' => 'Optional check-in date, for the exact free-cancellation date.']),
                    ],
                    'required' => ['property_id'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'check_availability',
                'description' => 'List the rooms, guest counts and meal plans that can be booked at a property for a stay, with exact total prices, discounts, rooms left and a booking link for each option.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer'],
                        'check_in' => $date,
                        'check_out' => $date,
                        'guests_per_room' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10],
                        'rooms' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10],
                        'currency' => $currency,
                    ],
                    'required' => ['property_id', 'check_in', 'check_out'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'get_cancellation_policy',
                'description' => 'Get a property\'s cancellation and refund policy, optionally for a specific check-in date.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer'],
                        'check_in' => $date,
                    ],
                    'required' => ['property_id'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'find_booking',
                'description' => 'Look up the guest\'s own existing booking. Requires BOTH the booking reference and the email address used for the booking, exactly as the guest gives them. Returns status, dates, rooms, total, payment status, cancellation policy and a link to the booking page (where the guest can pay or cancel).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'reference' => ['type' => 'string', 'description' => 'Booking reference, e.g. BS261105A7KQ'],
                        'email' => ['type' => 'string', 'description' => 'Email address used for the booking'],
                    ],
                    'required' => ['reference', 'email'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * Runs a tool. $defaultCurrency is the visitor's currency, used when the
     * input names none. Always returns an array (errors as ['error' => ...]).
     */
    public function call(string $name, array $input, string $defaultCurrency = 'LKR'): array
    {
        $int = fn ($key) => isset($input[$key]) && is_numeric($input[$key]) ? (int) $input[$key] : null;
        $str = fn ($key) => isset($input[$key]) && is_scalar($input[$key]) && trim((string) $input[$key]) !== '' ? trim((string) $input[$key]) : null;
        $currency = $str('currency') ?? $defaultCurrency;

        try {
            return match ($name) {
                'list_destinations' => ['destinations' => $this->api->destinations()],
                'search_properties' => $this->api->searchProperties($str('check_in'), $str('check_out'), $str('area'),
                    $int('guests_per_room'), $int('rooms') ?? 1, $currency),
                'get_property' => $int('property_id') ? $this->api->property($int('property_id'), $str('check_in'))
                    : ['error' => 'property_id is required.'],
                'check_availability' => $int('property_id') ? $this->api->availability($int('property_id'), $str('check_in'), $str('check_out'),
                    $int('guests_per_room'), $int('rooms') ?? 1, $currency) : ['error' => 'property_id is required.'],
                'get_cancellation_policy' => $int('property_id') ? $this->api->cancellationPolicy($int('property_id'), $str('check_in'))
                    : ['error' => 'property_id is required.'],
                'find_booking' => ($str('reference') && $str('email')) ? $this->api->findBooking($str('reference'), $str('email'))
                    : ['error' => 'Both the booking reference and the email address are required.'],
                default => ['error' => "Unknown tool: $name"],
            };
        } catch (\Throwable $e) {
            report($e);
            return ['error' => 'The information could not be retrieved right now.'];
        }
    }
}
