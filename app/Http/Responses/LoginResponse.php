<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Where to send the user after they sign in (password step, or the 2FA code step).
 *
 * Fortify's default goes straight to the dashboard; we show the splash screen first.
 * The page they were originally heading to (if any) stays in the session, and the
 * splash route picks it up.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        return redirect()->route('splash');
    }
}
