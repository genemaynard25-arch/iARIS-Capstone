<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// The real backing table for App\Support\LampRequests, which is still placeholder data
// only. Named LampRequest (singular, Eloquent convention) to avoid confusion with that
// support class.
class LampRequest extends Model
{
    protected $fillable = [
        'applicant_id',
        'name',
        'program',
        'iato_value',
        'lamp_value',
        'reason',
        'status',
    ];

    public function applicant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LampRequestMessage::class)->orderBy('sent_at');
    }
}
