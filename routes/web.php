<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AiChatController;


// Two-factor authentication routes
Route::get('/two-factor', [TwoFactorAuthenticationController::class, 'show']) ->middleware(['auth']);
Route::post('/two-factor/enable', [TwoFactorAuthenticationController::class, 'enable']) ->middleware(['auth']);
Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->middleware('auth');

// Import routes
Route::get('/import', [ImportController::class, 'show'])->middleware('auth');
Route::post('/import', [ImportController::class, 'store'])->middleware('auth');

Route::get('/', function () {
    return redirect('/login');
});
Route::get('/home', function () {
    return view('home');
}) ->middleware ('auth'); 

Route::get('/import/{batch}', [ImportController::class, 'results'])->middleware('auth');

// AI Chat routes
Route::get('/ai-chat', [AiChatController::class, 'show'])->middleware('auth');
Route::post('/ai-chat', [AiChatController::class, 'ask'])->middleware('auth');