<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages that change property data (room types, rates, inventory, amenities,
 * photos, exchange rates) need the "edit property details" permission:
 * always the owner, staff only when the owner granted it. Per-property
 * checks are still done by PropertyPolicy.
 */
class EnsureCanEditProperties
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user->isAdmin() || $user->canEditProperties())) {
            if ($request->expectsJson()) {
                abort(403);
            }
            return redirect('/dashboard')->with('error', 'You do not have permission to edit property details.');
        }

        return $next($request);
    }
}
