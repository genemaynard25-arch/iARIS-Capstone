<?php

use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return ('welcome, you are logged in!');
}) ->middleware ('auth'); 
