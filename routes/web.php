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
use App\Http\Controllers\EteeapRecordController;
use App\Http\Controllers\ReviewQueueController;
use App\Http\Controllers\SuccessionController;
use App\Http\Controllers\ScholarController;
use App\Http\Controllers\GraduateRecordController;
use App\Http\Controllers\LampController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\SubmissionsController;
use App\Http\Controllers\ShsController;
use App\Http\Controllers\AdmissionStatsImportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ChancellorController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CollegeController;

// Admission Stats Import routes
Route::get('/import/admission-stats', [AdmissionStatsImportController::class, 'show'])->middleware('auth');
Route::post('/import/admission-stats', [AdmissionStatsImportController::class, 'store'])->middleware('auth');
Route::get('/import/admission-stats/{batch}', [AdmissionStatsImportController::class, 'results'])->middleware('auth');

// Two-factor authentication routes
Route::get('/two-factor', [TwoFactorAuthenticationController::class, 'show']) ->middleware(['auth']);
Route::post('/two-factor/enable', [TwoFactorAuthenticationController::class, 'enable']) ->middleware(['auth']);
Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->middleware('auth');

// Import routes
Route::get('/import', [ImportController::class, 'show'])->middleware(['auth', 'module:import']);
Route::post('/import', [ImportController::class, 'store'])->middleware(['auth', 'module:import']);

Route::get('/', function () {
    return auth()->check() ? redirect('/home') : redirect()->route('login');
});

// Splash screen shown right after signing in (see App\Http\Responses\LoginResponse),
// then on to the page the user was heading to, or the dashboard.
Route::get('/loading', function () {
    return view('splash', ['next' => session()->pull('url.intended', url('/home'))]);
})->middleware('auth')->name('splash');
Route::get('/home', [HomeController::class, 'index'])->middleware(['auth', 'module:dashboard'])->name('home');
Route::get('/applicants', [ApplicantController::class, 'index'])->middleware(['auth', 'module:applicants'])->name('applicants');
Route::get('/reports', [ReportController::class, 'index'])->middleware('auth')->name('reports'); // now redirects to the Reports tab of /analytics
// Analytics & Reports: one page with tabs. The controller checks the 'analytics' / 'reports' permissions per tab.
Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('auth')->name('analytics');
Route::post('/analytics/reanalyze', [AnalyticsController::class, 'reanalyze'])->middleware('auth');
Route::get('/access-control', [AccessControlController::class, 'index'])->middleware('auth', 'admin')->name('access-control');
Route::post('/access-control/temp-admin', [AccessControlController::class, 'assignTempAdmin'])->middleware(['auth', 'admin']);
Route::post('/access-control/temp-admin/revoke', [AccessControlController::class, 'revokeTempAdmin'])->middleware(['auth', 'admin']);
Route::post('/access-control/permissions', [AccessControlController::class, 'savePermissions'])->middleware(['auth', 'admin']);
Route::get('/accounts', [AccountController::class, 'index'])->middleware(['auth', 'admin'])->name('accounts');
Route::post('/accounts', [AccountController::class, 'store'])->middleware(['auth', 'admin']);
Route::put('/accounts/{user}', [AccountController::class, 'update'])->middleware(['auth', 'admin']);
Route::post('/accounts/{user}/reset-password', [AccountController::class, 'resetPassword'])->middleware(['auth', 'admin']);
Route::post('/accounts/{user}/status', [AccountController::class, 'setStatus'])->middleware(['auth', 'admin']);
Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->middleware(['auth', 'admin']);
Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware(['auth', 'module:audit'])->name('audit-logs');
// Review Queue (front end only for now) and Super User Succession
Route::get('/review-queue', [ReviewQueueController::class, 'index'])->middleware(['auth', 'admin'])->name('review-queue');
// IATO Staff: their own edits and imports in the Review Queue
Route::get('/submissions', [SubmissionsController::class, 'index'])->middleware('auth')->name('submissions');
Route::get('/succession', [SuccessionController::class, 'index'])->middleware(['auth', 'admin'])->name('succession');

Route::get('/import/{batch}', [ImportController::class, 'results'])->middleware(['auth', 'module:import']);

// The floating AI widget's questions (the widget is on every page: see layouts/app.blade.php)
Route::post('/ai-widget/ask', [AiChatController::class, 'widgetAsk'])->middleware('auth');

// College Records. 'level:college' is the team's RBAC check: users with a
// different assigned_level (e.g. Integrated School) get a 403.
Route::get('/records/college', [CollegeRecordController::class, 'index'])->middleware(['auth', 'level:college', 'module:college'])->name('records.college');
Route::get('/records/is', [IsRecordController::class, 'index'])->middleware(['auth', 'level:is', 'module:is'])->name('records.is');
// TODO (RBAC): no 'law', 'ipace' or 'graduate' level exists yet, so only sign-in is checked for these.
// Scholars mixes College and IS students, so it needs its own rule (LAMP Office + IATO).
Route::get('/records/law', [LawRecordController::class, 'index'])->middleware('auth')->name('records.law');
Route::get('/records/ipace', [IpaceRecordController::class, 'index'])->middleware('auth')->name('records.ipace');
Route::get('/records/eteeap', [EteeapRecordController::class, 'index'])->middleware('auth')->name('records.eteeap');
Route::get('/records/scholars', [ScholarController::class, 'index'])->middleware(['auth', 'module:scholars'])->name('records.scholars');
Route::get('/records/graduate', [GraduateRecordController::class, 'index'])->middleware('auth')->name('records.graduate');

// LAMP Office pages (their own sidebar: see layouts/app.blade.php)
// TODO (RBAC): limit to users whose role is 'lamp', plus IATO admins
Route::middleware('auth')->prefix('lamp')->name('lamp.')->group(function () {
    Route::get('/', [LampController::class, 'dashboard'])->name('dashboard');
    Route::get('/scholars', [LampController::class, 'scholars'])->name('scholars');
    Route::get('/reports', [LampController::class, 'reports'])->name('reports');
});

// College Registrar pages (their own sidebar: see layouts/app.blade.php)
// TODO (RBAC): limit to users whose role is 'registrar', plus IATO admins
Route::middleware('auth')->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/', [RegistrarController::class, 'dashboard'])->name('dashboard');
    Route::get('/enrollment', [RegistrarController::class, 'enrollment'])->name('enrollment');
    Route::get('/reports', [RegistrarController::class, 'reports'])->name('reports');
    Route::get('/questions', [RegistrarController::class, 'questions'])->name('questions');
});

// SHS principal's dashboard (their own sidebar: see layouts/app.blade.php)
// TODO (RBAC): limit to the SHS principal, plus IATO admins
Route::get('/shs', [ShsController::class, 'dashboard'])->middleware('auth')->name('shs.dashboard');

// President / Chancellor dashboard (their own sidebar: see layouts/app.blade.php)
// TODO (RBAC): limit to the Chancellor's office, plus IATO admins
Route::get('/chancellor', [ChancellorController::class, 'dashboard'])->middleware('auth')->name('chancellor.dashboard');
Route::get('/chancellor/analytics', [ChancellorController::class, 'analytics'])->middleware('auth')->name('chancellor.analytics');
Route::get('/chancellor/reports', [ChancellorController::class, 'reports'])->middleware('auth')->name('chancellor.reports');
Route::get('/chancellor/messages', [ChancellorController::class, 'messages'])->middleware('auth')->name('chancellor.messages');

// Settings, for every role (sections depend on the role: see SettingsController)
Route::get('/settings', [SettingsController::class, 'index'])->middleware('auth')->name('settings');

// College dashboard for Deans and Program Chairs (their own sidebar: see layouts/app.blade.php)
// TODO (RBAC): limit to deans and program chairs, plus IATO admins
Route::get('/college', [CollegeController::class, 'dashboard'])->middleware('auth')->name('college.dashboard');
Route::get('/college/applicants', [CollegeController::class, 'applicants'])->middleware('auth')->name('college.applicants');
Route::get('/college/analytics', [CollegeController::class, 'analytics'])->middleware('auth')->name('college.analytics');
Route::get('/college/reports', [CollegeController::class, 'reports'])->middleware('auth')->name('college.reports');
