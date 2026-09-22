<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    protected $fillable = [
        'imported_batch_id',
        'reference_number',
        'school_year',
        'level',
        'sub_level',
        'first_name',
        'last_name',
        'gender',
        'feeder_school',
        'program_or_track',
        'applicant_type',
        'admission_test_status',
        'noa_issued',
        'application_status',
    ];
}