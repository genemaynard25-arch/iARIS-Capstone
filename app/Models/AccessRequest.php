<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'position',
        'department',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'note',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function decider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function name(): string
    {
        return "{$this->last_name}, {$this->first_name}";
    }

    /** How many are waiting (for the sidebar badge). */
    public static function waitingCount(): int
    {
        return self::where('status', 'waiting')->count();
    }
}
