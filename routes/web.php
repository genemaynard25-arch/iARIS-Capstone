<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApplicantController;


// Two-factor authentication routes
Route::get('/two-factor', [TwoFactorAuthenticationController::class, 'show']) ->middleware(['auth']);
Route::post('/two-factor/enable', [TwoFactorAuthenticationController::class, 'enable']) ->middleware(['auth']);
Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->middleware('auth');

// Import routes
Route::get('/import', [ImportController::class, 'show'])->middleware('auth');
Route::post('/import', [ImportController::class, 'store'])->middleware('auth');

Route::get('/', function () {
    return auth()->check() ? redirect('/home') : redirect()->route('login');
});

// Splash screen shown right after signing in (see App\Http\Responses\LoginResponse),
// then on to the page the user was heading to, or the dashboard.
Route::get('/loading', function () {
    return view('splash', ['next' => session()->pull('url.intended', url('/home'))]);
})->middleware('auth')->name('splash');
Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');
Route::get('/applicants', [ApplicantController::class, 'index'])->middleware('auth')->name('applicants');

Route::get('/import/{batch}', [ImportController::class, 'results'])->middleware('auth');

// AI Chat routes
Route::get('/ai-chat', [AiChatController::class, 'show'])->middleware('auth');
Route::post('/ai-chat', [AiChatController::class, 'ask'])->middleware('auth');

// Test route for RBAC middleware (DEAN)
Route::get('/records/college', function () {
    return 'You can see College records.';
})->middleware(['auth', 'level:college']);