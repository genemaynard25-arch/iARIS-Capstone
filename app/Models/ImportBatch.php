<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uploaded_by',
        'original_filename',
        'row_count',
        'status',
    ];

    // The applicants this upload saved
    public function applicants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Applicant::class, 'imported_batch_id');
    }
}