<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owners (and their staff) can only work in the system after an admin has
 * approved the owner's account. Until then they only see the dashboard.
 */
class EnsurePartnerApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isPartner() && !$user->isApproved()) {
            if ($request->expectsJson()) {
                abort(403, 'Your account is awaiting approval.');
            }
            return redirect('/dashboard')->with('error', 'Your account is awaiting approval by the Bedspace team. You can add properties once it is approved.');
        }

        return $next($request);
    }
}
