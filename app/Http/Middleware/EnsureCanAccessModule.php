<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if ($module === 'dashboard' && $request->routeIs('home')) {
            if ($request->user()->isLamp()) {
                return redirect()->route('lamp.dashboard');
            }
            if ($request->user()->role === 'registrar') {
                return redirect()->route('registrar.dashboard');
            }
        }

        if (! $request->user()->canAccess($module)) {
            abort(403, 'Your role does not have access to this module.');
        }

        return $next($request);
    }
}
