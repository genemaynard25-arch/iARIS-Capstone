<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});
Route::get('/home', function () {
    return view('home');
}) ->middleware ('auth'); 
