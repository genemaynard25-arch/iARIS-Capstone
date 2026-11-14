<?php

return [
    // Disabled until an administrator explicitly configures a local importer.
    'enabled' => env('ADMISSIONS_IMPORT_ENABLED', false),
    'user_id' => env('ADMISSIONS_IMPORT_USER_ID'),
    'school_year' => env('ADMISSIONS_IMPORT_SCHOOL_YEAR'),
    'path' => storage_path('app/private/admissions-import'),
    'settle_seconds' => 10,
];
