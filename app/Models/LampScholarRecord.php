<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LampScholarRecord extends Model
{
    protected $fillable = [
        'applicant_id',
        'external_app_no',
        'scholarship_type',
        'status',
        'confirmed_by',
        'confirmed_at',
        'notes',
        'decision', 'checked_by', 'checked_at', 'iato_status',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
