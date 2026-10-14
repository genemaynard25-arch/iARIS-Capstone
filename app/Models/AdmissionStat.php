<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionStat extends Model
{
    // Only ever filled by AdmissionStatsImport
    // so $guarded instead of listing all 64 fillable columns by name.
    protected $guarded = ['id'];
}