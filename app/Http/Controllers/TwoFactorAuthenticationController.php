<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;


class TwoFactorAuthenticationController extends Controller
{
    // Display the two-factor authentication settings page
    public function show(Request $request)
    {
        $user = $request->user();

        return view('two-factor', [
            'enabled' => $user->two_factor_secret !== null,
            'confirmed' => $user->two_factor_confirmed_at !== null,
            'qrCode' => $user->two_factor_secret ? $user->twoFactorQrCodeSvg() : null,
        ]);
    }
    // Enable two-factor authentication for the authenticated user
    public function enable (Request $request, EnableTwoFactorAuthentication $enable)
    {
        $enable($request->user());

        return redirect('/two-factor');
    }
    // Confirm the two-factor authentication code for the authenticated user
    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $confirm($request->user(), $request->code);

        return redirect('/two-factor');
    }
}