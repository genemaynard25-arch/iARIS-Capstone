<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    protected $fillable = [
        'imported_batch_id',
        'reference_number',
        'external_app_no',
        'permit_no',
        'gwa',
        'age',
        'school_year',
        'level',
        'sub_level',
        'first_name',
        'last_name',
        'gender',
        'feeder_school',
        'school_address',
        'school_classification',
        'learning_modalities',
        'nationality',
        'program_or_track',
        'applicant_type',
        'student_type',
        'scholarship_type',
        'applying_for_scholarship',
        'pwd',
        'ssen',
        'admission_test_status',
        'noa_issued',
        'application_status',
        'email',
        'contact_number',
        'address',
        'date_of_birth',
        'exam_scheduled_at',
        'college',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'noa_issued' => 'boolean',
            'applying_for_scholarship' => 'boolean',
            'pwd' => 'boolean',
            'ssen' => 'boolean',
            'gwa' => 'decimal:2',
            'date_of_birth' => 'date',
            'exam_scheduled_at' => 'datetime',
            'archived_at' => 'datetime',

            // FR-09: applicant data encrypted at rest. Nothing filters on these with raw SQL
            // anywhere in the app (verified) - search is done client-side on the already-
            // decrypted value. Fields used in real where()/groupBy() queries (level, sub_level,
            // school_year, program_or_track, applicant_type, scholarship_type,
            // admission_test_status, application_status) are deliberately left out: Laravel's
            // encrypted cast is non-deterministic ciphertext, so a column with it can never be
            // matched by a plain WHERE and would silently break every real query this session
            // built against them.
            'first_name' => 'encrypted',
            'last_name' => 'encrypted',
            'gender' => 'encrypted',
            'feeder_school' => 'encrypted',
            'email' => 'encrypted',
            'contact_number' => 'encrypted',
            'address' => 'encrypted',
        ];
    }
}