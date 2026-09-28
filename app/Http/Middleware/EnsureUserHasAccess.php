<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAccess
{
    public function handle(Request $request, Closure $next, string $level): Response
    {
        $user = $request->user();

        if ($user->assigned_level !== null && $user->assigned_level !== $level) {
            abort(403, 'You do not have access to this level\'s data.');
        }

        return $next($request);
    }
}