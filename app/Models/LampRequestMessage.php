<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LampRequestMessage extends Model
{
    protected $fillable = [
        'lamp_request_id',
        'from',
        'user_id',
        'name',
        'role',
        'text',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function lampRequest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LampRequest::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
