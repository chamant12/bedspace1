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
    ];

    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();

        if (!$user || (int) $user->user_type_id !== self::TYPES[$type]) {
            if ($request->expectsJson()) {
                abort(403);
            }
            return redirect($user ? $user->dashboardUrl() : route('login'));
        }

        return $next($request);
    }
}
