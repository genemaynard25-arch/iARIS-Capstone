<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CollegeRecordController;
use App\Http\Controllers\IsRecordController;
use App\Http\Controllers\LawRecordController;
use App\Http\Controllers\IpaceRecordController;
use App\Http\Controllers\ScholarController;
use App\Http\Controllers\GraduateRecordController;
use App\Http\Controllers\LampController;
use App\Http\Controllers\ShsController;


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
Route::get('/reports', [ReportController::class, 'index'])->middleware('auth')->name('reports');
Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('auth')->name('analytics');
Route::get('/access-control', [AccessControlController::class, 'index'])->middleware('auth')->name('access-control');
Route::get('/accounts', [AccountController::class, 'index'])->middleware('auth')->name('accounts');

Route::get('/import/{batch}', [ImportController::class, 'results'])->middleware('auth');

// AI Chat routes
Route::get('/ai-chat', [AiChatController::class, 'show'])->middleware('auth');
Route::post('/ai-chat', [AiChatController::class, 'ask'])->middleware('auth');

// College Records. 'level:college' is the team's RBAC check: users with a
// different assigned_level (e.g. Integrated School) get a 403.
Route::get('/records/college', [CollegeRecordController::class, 'index'])->middleware(['auth', 'level:college'])->name('records.college');
Route::get('/records/is', [IsRecordController::class, 'index'])->middleware(['auth', 'level:is'])->name('records.is');
// TODO (RBAC): no 'law', 'ipace' or 'graduate' level exists yet, so only sign-in is checked for these.
// Scholars mixes College and IS students, so it needs its own rule (LAMP Office + IATO).
Route::get('/records/law', [LawRecordController::class, 'index'])->middleware('auth')->name('records.law');
Route::get('/records/ipace', [IpaceRecordController::class, 'index'])->middleware('auth')->name('records.ipace');
Route::get('/records/scholars', [ScholarController::class, 'index'])->middleware('auth')->name('records.scholars');
Route::get('/records/graduate', [GraduateRecordController::class, 'index'])->middleware('auth')->name('records.graduate');

// LAMP Office pages (their own sidebar: see layouts/app.blade.php)
// TODO (RBAC): limit to users whose role is 'lamp', plus IATO admins
Route::middleware('auth')->prefix('lamp')->name('lamp.')->group(function () {
    Route::get('/', [LampController::class, 'dashboard'])->name('dashboard');
    Route::get('/scholars', [LampController::class, 'scholars'])->name('scholars');
    Route::get('/reports', [LampController::class, 'reports'])->name('reports');
});

// SHS principal's dashboard (their own sidebar: see layouts/app.blade.php)
// TODO (RBAC): limit to the SHS principal, plus IATO admins
Route::get('/shs', [ShsController::class, 'dashboard'])->middleware('auth')->name('shs.dashboard');
