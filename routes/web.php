<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\AccessRequestController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\CollegeRecordController;
use App\Http\Controllers\EteeapRecordController;
use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\GraduateRecordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IatoMessagesController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ImportSchoolYearController;
use App\Http\Controllers\IpaceRecordController;
use App\Http\Controllers\IsRecordController;
use App\Http\Controllers\LampCheckController;
use App\Http\Controllers\LampController;
use App\Http\Controllers\LampConversationController;
use App\Http\Controllers\LampReportController;
use App\Http\Controllers\LawRecordController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ReviewQueueController;
use App\Http\Controllers\ScholarController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShsController;
use App\Http\Controllers\SubmissionsController;
use App\Http\Controllers\SuccessionController;
use App\Http\Controllers\ToicController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

// Two-factor authentication routes
Route::get('/two-factor', [TwoFactorAuthenticationController::class, 'show'])->middleware(['auth']);
Route::post('/two-factor/enable', [TwoFactorAuthenticationController::class, 'enable'])->middleware(['auth']);
Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->middleware('auth');

// Import routes
Route::get('/import', [ImportController::class, 'show'])->middleware(['auth', 'module:import']);
Route::post('/import', [ImportController::class, 'store'])->middleware(['auth', 'module:import']);

Route::get('/', function () {
    return auth()->check()
        ? redirect(auth()->user()->isLamp() ? '/lamp' : (auth()->user()->role === 'registrar' ? '/registrar' : (auth()->user()->isExecutive() ? '/executive' : '/home')))
        : redirect()->route('login');
});

// Splash screen shown right after signing in (see App\Http\Responses\LoginResponse),
// then on to the page the user was heading to, or the dashboard.
Route::get('/loading', function () {
    $next = session()->pull('url.intended');
    // These roles have their own dashboards; /home checks the shared dashboard permission.
    $dashboard = match (true) {
        auth()->user()->isLamp() => '/lamp',
        auth()->user()->role === 'registrar' => '/registrar',
        auth()->user()->isExecutive() => '/executive',
        default => '/home',
    };
    if (! $next || rtrim(parse_url($next, PHP_URL_PATH) ?? '', '/') === '/home') {
        $next = url($dashboard);
    }

    return view('splash', ['next' => $next ?? url('/home')]);
})->middleware('auth')->name('splash');
Route::get('/home', [HomeController::class, 'index'])->middleware(['auth', 'module:dashboard'])->name('home');
Route::get('/applicants', [ApplicantController::class, 'index'])->middleware(['auth', 'module:applicants'])->name('applicants');
Route::post('/applicants', [ApplicantController::class, 'store'])->middleware(['auth', 'module:college']);
Route::put('/applicants/{applicant:reference_number}', [ApplicantController::class, 'update'])->middleware(['auth', 'module:edit']);
Route::delete('/applicants/{applicant:reference_number}', [ApplicantController::class, 'destroy'])->middleware(['auth', 'module:edit']);
Route::post('/applicants/{applicant:reference_number}/restore', [ApplicantController::class, 'restore'])->middleware(['auth', 'module:edit']);
Route::patch('/applicants/{applicant:reference_number}/exam-status', [ApplicantController::class, 'updateExamStatus'])->middleware(['auth', 'module:edit']);
Route::get('/reports', [ReportController::class, 'index'])->middleware('auth')->name('reports'); // now redirects to the Reports tab of /analytics
// Analytics & Reports: one page with tabs. The controller checks the 'analytics' / 'reports' permissions per tab.
Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('auth')->name('analytics');
Route::post('/analytics/reanalyze', [AnalyticsController::class, 'reanalyze'])->middleware('auth');
Route::get('/analytics/reports/{key}/pdf', [ReportController::class, 'pdf'])->middleware(['auth', 'module:reports'])->name('reports.pdf');
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
// Request Access: the login page's form (no auth - anyone can ask to be reviewed) and the
// admin's Approve / Decline on the Access Requests tab of User Accounts.
Route::post('/access-requests', [AccessRequestController::class, 'store'])->middleware('throttle:6,1')->name('access-requests.store');
Route::post('/access-requests/{accessRequest}/approve', [AccessRequestController::class, 'approve'])->middleware(['auth', 'admin']);
Route::post('/access-requests/{accessRequest}/decline', [AccessRequestController::class, 'decline'])->middleware(['auth', 'admin']);
Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware(['auth', 'module:audit'])->name('audit-logs');
// Review Queue (front end only for now) and Super User Succession
Route::get('/review-queue', [ReviewQueueController::class, 'index'])->middleware(['auth', 'admin'])->name('review-queue');
Route::post('/review-queue/{item}/approve', [ReviewQueueController::class, 'approve'])->middleware(['auth', 'admin']);
Route::post('/review-queue/{item}/send-back', [ReviewQueueController::class, 'sendBack'])->middleware(['auth', 'admin']);
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
// No dean/registrar-equivalent role exists for these yet, so 'module:' (no 'level:') - IATO staff only.
Route::get('/records/law', [LawRecordController::class, 'index'])->middleware(['auth', 'module:law'])->name('records.law');
Route::get('/records/ipace', [IpaceRecordController::class, 'index'])->middleware(['auth', 'module:ipace'])->name('records.ipace');
Route::get('/records/eteeap', [EteeapRecordController::class, 'index'])->middleware(['auth', 'module:eteeap'])->name('records.eteeap');
// Scholars mixes College and IS students, so it needs its own rule (LAMP Office + IATO).
Route::get('/records/scholars', [ScholarController::class, 'index'])->middleware(['auth', 'module:scholars'])->name('records.scholars');
Route::get('/records/graduate', [GraduateRecordController::class, 'index'])->middleware(['auth', 'module:graduate'])->name('records.graduate');

// LAMP Office pages (their own sidebar: see layouts/app.blade.php). Admins can still preview
// (role:isLamp lets isAdmin() through too - see EnsureUserHasRole).
Route::middleware(['auth', 'role:isLamp'])->prefix('lamp')->name('lamp.')->group(function () {
    Route::get('/', [LampController::class, 'dashboard'])->name('dashboard');
    Route::get('/scholars', [LampController::class, 'scholars'])->name('scholars');
    Route::get('/reports', [LampController::class, 'reports'])->name('reports');
});

// College Registrar pages (their own sidebar: see layouts/app.blade.php)
Route::middleware(['auth', 'role:registrar'])->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/', [RegistrarController::class, 'dashboard'])->name('dashboard');
    Route::get('/enrollment', [RegistrarController::class, 'enrollment'])->name('enrollment');
    Route::get('/reports', [RegistrarController::class, 'reports'])->name('reports');
    Route::redirect('/questions', '/registrar/messages');
});

// SHS principal's dashboard (their own sidebar: see layouts/app.blade.php)
Route::get('/shs', [ShsController::class, 'dashboard'])->middleware(['auth', 'role:isShsPrincipal'])->name('shs.dashboard');

// IATO's Messages (Super Admin and IATO Staff): every conversation with the other offices
Route::get('/messages', [IatoMessagesController::class, 'index'])->middleware('auth')->name('messages');

// Messages with IATO for the LAMP Office, the Registrars and the SHS Principal (one page, three addresses)
Route::post('/lamp/checks/{applicant}', [LampCheckController::class, 'update'])->middleware(['auth', 'role:isLamp'])->name('lamp.checks.update');
Route::post('/iato/lamp-checks/{applicant}/confirm', [LampCheckController::class, 'confirm'])->middleware('auth')->name('iato.lamp-checks.confirm');
Route::get('/lamp/messages', [LampConversationController::class, 'index'])->middleware(['auth', 'role:isLamp'])->name('lamp.messages');
Route::get('/registrar/messages', [MessagesController::class, 'index'])->middleware(['auth', 'role:registrar'])->name('registrar.messages');
Route::get('/shs/messages', [MessagesController::class, 'index'])->middleware(['auth', 'role:isShsPrincipal'])->name('shs.messages');

// Executive Office: President, Chancellor, Vice Presidents, Vice Chancellors (their own sidebar: see layouts/app.blade.php)
Route::get('/executive', [ExecutiveController::class, 'dashboard'])->middleware(['auth', 'role:isExecutive'])->name('executive.dashboard');
Route::get('/executive/analytics', [ExecutiveController::class, 'analytics'])->middleware(['auth', 'role:isExecutive'])->name('executive.analytics');
Route::get('/executive/reports', [ExecutiveController::class, 'reports'])->middleware(['auth', 'role:isExecutive'])->name('executive.reports');
Route::get('/executive/messages', [ExecutiveController::class, 'messages'])->middleware(['auth', 'role:isExecutive'])->name('executive.messages');
// The old /chancellor addresses still work
Route::redirect('/chancellor', '/executive');
Route::get('/chancellor/{page}', fn ($page) => redirect('/executive/'.$page))->where('page', 'analytics|reports|messages');

// Settings, for every role (sections depend on the role: see SettingsController)
Route::get('/settings', [SettingsController::class, 'index'])->middleware('auth')->name('settings');

// College dashboard for Deans and Program Chairs (their own sidebar: see layouts/app.blade.php)
Route::get('/college', [CollegeController::class, 'dashboard'])->middleware(['auth', 'role:isDeanOrChair'])->name('college.dashboard');
Route::get('/college/applicants', [CollegeController::class, 'applicants'])->middleware(['auth', 'role:isDeanOrChair'])->name('college.applicants');
Route::get('/college/analytics', [CollegeController::class, 'analytics'])->middleware(['auth', 'role:isDeanOrChair'])->name('college.analytics');
Route::get('/college/reports', [CollegeController::class, 'reports'])->middleware(['auth', 'role:isDeanOrChair'])->name('college.reports');
Route::get('/college/messages', [CollegeController::class, 'messages'])->middleware(['auth', 'role:isDeanOrChair'])->name('college.messages');

// Integrated School Principal: the same pages as a Dean, by level (their own sidebar: see layouts/app.blade.php)
// TOIC (system support): how iARIS runs, never applicant data (their own sidebar: see layouts/app.blade.php)
Route::middleware(['auth', 'role:isToic'])->prefix('toic')->name('toic.')->group(function () {
    Route::get('/', [ToicController::class, 'dashboard'])->name('dashboard');
    Route::get('/issues', [ToicController::class, 'issuesPage'])->name('issues');
    Route::get('/security', [ToicController::class, 'security'])->name('security');
    Route::get('/docs', [ToicController::class, 'docs'])->name('docs');
    Route::get('/budget', [ToicController::class, 'budget'])->name('budget');
    Route::get('/reports', [ToicController::class, 'reports'])->name('reports');
    Route::get('/messages', [ToicController::class, 'messages'])->name('messages');
});

// iPACE Director (also heads ETEEAP): the same pages as a Dean, for both units
Route::middleware(['auth', 'role:isDirector'])->prefix('director')->name('director.')->group(function () {
    Route::get('/', [CollegeController::class, 'dashboard'])->name('dashboard');
    Route::get('/applicants', [CollegeController::class, 'applicants'])->name('applicants');
    Route::get('/analytics', [CollegeController::class, 'analytics'])->name('analytics');
    Route::get('/reports', [CollegeController::class, 'reports'])->name('reports');
    Route::get('/messages', [CollegeController::class, 'messages'])->name('messages');
});

Route::middleware(['auth', 'role:isPrincipalOffice'])->prefix('principal')->name('principal.')->group(function () {
    Route::get('/', [CollegeController::class, 'dashboard'])->name('dashboard');
    Route::get('/applicants', [CollegeController::class, 'applicants'])->name('applicants');
    Route::get('/analytics', [CollegeController::class, 'analytics'])->name('analytics');
    Route::get('/reports', [CollegeController::class, 'reports'])->name('reports');
    Route::get('/messages', [CollegeController::class, 'messages'])->name('messages');
});

Route::middleware('auth')->group(function () {
    Route::get('/iato/lamp-requests', [LampConversationController::class, 'index'])->name('lamp-requests.index');
    Route::post('/iato/lamp-requests', [LampConversationController::class, 'store'])->name('lamp-requests.store');
    Route::post('/lamp/requests/{conversation}/reply', [LampConversationController::class, 'reply'])->name('lamp-requests.reply');
    Route::post('/lamp/requests/{conversation}/resolve', [LampConversationController::class, 'resolve'])->name('lamp-requests.resolve');
    Route::get('/iato/lamp-reports', [LampReportController::class, 'index'])->name('lamp-reports.manage');
    Route::post('/iato/lamp-reports', [LampReportController::class, 'store'])->name('lamp-reports.store');
    Route::post('/iato/lamp-reports/{report}/publish', [LampReportController::class, 'publish'])->name('lamp-reports.publish');
    Route::post('/lamp/reports/{report}/read', [LampReportController::class, 'show'])->name('lamp-reports.read');
    Route::get('/lamp/reports/{report}/download', [LampReportController::class, 'download'])->name('lamp-reports.download');
});

Route::post('/analytics/reports/generate', [ReportExportController::class, 'generate'])->middleware(['auth', 'module:reports'])->name('reports.generate');
Route::get('/analytics/reports/generated/{report}', [ReportExportController::class, 'download'])->middleware(['auth', 'module:reports'])->name('reports.generated.download');

Route::get('/analytics/reports/{key}/preview', [ReportExportController::class, 'preview'])->middleware(['auth', 'module:reports'])->name('reports.preview');

Route::post('/import/{batch}/school-year', [ImportSchoolYearController::class, 'update'])->middleware(['auth', 'admin'])->name('imports.school-year.update');
