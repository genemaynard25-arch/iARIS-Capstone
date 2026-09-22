<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwoFactorAuthenticationController;

// Two-factor authentication routes
Route::get('/two-factor', [TwoFactorAuthenticationController::class, 'show']) ->middleware(['auth']);
Route::post('/two-factor/enable', [TwoFactorAuthenticationController::class, 'enable']) ->middleware(['auth']);
Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->middleware('auth');

Route::get('/', function () {
    return redirect('/login');
});
Route::get('/home', function () {
    return view('home');
}) ->middleware ('auth'); 