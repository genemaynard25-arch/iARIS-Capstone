<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uploaded_by',
        'source_hash',
        'original_filename',
        'row_count',
        'error_count',
        'errors',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    // The applicants this upload saved
    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class, 'imported_batch_id');
    }
}
