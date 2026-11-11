<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController as FortifyPasswordResetLinkController;
use Laravel\Fortify\Http\Requests\SendPasswordResetLinkRequest;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends FortifyPasswordResetLinkController
{
    public function store(SendPasswordResetLinkRequest $request): Responsable
    {
        try {
            return parent::store($request);
        } catch (TransportExceptionInterface $exception) {
            $user = User::where('email', $request->input('email'))->first();
            if ($user) {
                Password::broker(config('fortify.passwords'))->deleteToken($user);
            }
            Log::warning('Password reset email delivery failed. Check the configured mail transport and credentials.', [
                'mailer' => config('mail.default'),
            ]);

            throw ValidationException::withMessages([
                'email' => 'We could not send the password reset email. Please try again later or contact IATO for help.',
            ])->redirectTo(route('password.request'));
        }
    }
}
