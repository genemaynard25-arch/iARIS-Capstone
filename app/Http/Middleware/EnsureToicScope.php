<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureToicScope
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isToic()) {
            return $next($request);
        }

        if ($request->is('/', 'home')) {
            return redirect()->route('toic.dashboard');
        }

        // Support pages and personal account flows remain available to TOIC.
        if ($request->is(
            'toic', 'toic/*', 'settings', 'loading',
            'login', 'logout', 'forgot-password', 'reset-password', 'reset-password/*',
            'two-factor', 'two-factor/*', 'two-factor-challenge', 'user/*'
        )) {
            return $next($request);
        }

        abort(403, 'TOIC can only access system support and personal account pages.');
    }
}
