<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CachedInsight extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'generated_at' => 'datetime',
        ];
    }
}