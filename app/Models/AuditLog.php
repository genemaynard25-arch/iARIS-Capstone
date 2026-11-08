<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $guarded = ['id'];
    public const UPDATED_AT = null;

    protected $casts = [
        'diff' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $module, string $title, ?string $details = null, ?string $description = null, ?array $diff = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'title' => $title,
            'details' => $details,
            'description' => $description,
            'diff' => $diff,
            'ip_address' => request()?->ip(),
        ]);
    }
}