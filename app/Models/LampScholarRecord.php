<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Not wired up to any controller yet - every scholar shows "Pending Check" in
// ScholarController::sample()/LampController because there's no real second dataset
// to compare against. This is that dataset, ahead of the feature being built.
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

    public function applicant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function confirmedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
