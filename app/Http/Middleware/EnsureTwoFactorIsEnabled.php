<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// MFA is required, not optional: iARIS holds applicants' personal data. Signed-in users without
// a confirmed authenticator are sent to set one up before they can reach any other page.
class EnsureTwoFactorIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->two_factor_confirmed_at && ! $request->is('two-factor*', 'logout')) {
            return redirect('/two-factor')->with('mfaRequired', true);
        }

        return $next($request);
    }
}
