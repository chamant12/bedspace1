<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to one user type: ->middleware('user.type:admin')
 * or ->middleware('user.type:owner'). Users of another type are sent to
 * their own dashboard (or get a 403 for AJAX requests).
 */
class EnsureUserType
{
    const TYPES = [
        'admin' => User::TYPE_ADMIN,
        'owner' => User::TYPE_PROPERTY_OWNER,
        'customer' => User::TYPE_CUSTOMER,
        'staff' => User::TYPE_STAFF,
    ];

    // ->middleware('user.type:admin,owner') allows either type
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();
        $allowed = array_map(fn ($type) => self::TYPES[$type], $types);

        if (!$user || !in_array((int) $user->user_type_id, $allowed, true)) {
            if ($request->expectsJson()) {
                abort(403);
            }
            return redirect($user ? $user->dashboardUrl() : route('login'));
        }

        return $next($request);
    }
}
