<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Opt-in local automation; run `php artisan schedule:work` during localhost tests.
Schedule::command('admissions:import-folder')
    ->everyMinute()
    ->withoutOverlapping(1440)
    ->when(fn () => (bool) config('admissions-import.enabled'));
