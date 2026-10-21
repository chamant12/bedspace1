<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Works out the visitor's country from a header set by the CDN in front of
 * the server (Cloudflare's CF-IPCountry by default). Swap this class for a
 * GeoIP database lookup (e.g. MaxMind GeoLite2) if no CDN is used.
 */
class VisitorCountry
{
    // ISO 3166-1 alpha-2 code, or null when unknown
    public function detect(Request $request): ?string
    {
        $country = strtoupper((string) $request->header(config('bedspace.country_header')));

        // Cloudflare uses XX for unknown and T1 for Tor
        if (!preg_match('/^[A-Z]{2}$/', $country) || in_array($country, ['XX', 'T1'])) {
            return null;
        }

        return $country;
    }
}
