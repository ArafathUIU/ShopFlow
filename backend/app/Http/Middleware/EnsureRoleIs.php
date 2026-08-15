<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to one or more roles, e.g. `->middleware('role:admin,manager')`.
 */
class EnsureRoleIs
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user() ?? Auth::guard('web')->user();

        if (! $user || ! in_array($user->role->value, $roles, true)) {
            abort(403, 'You are not authorized to perform this action.');
        }

        return $next($request);
    }
}
