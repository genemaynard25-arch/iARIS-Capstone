<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewQueueItem extends Model
{
    protected $table = 'review_queue';

    public function getRouteKeyName(): string
    {
        return 'rq_number';
    }

    protected $fillable = [
        'rq_number',
        'type',
        'title',
        'applicant_id',
        'subject',
        'ref',
        'unit',
        'submitted_by',
        'submitted_by_name',
        'submitted_by_role',
        'submitted_at',
        'status',
        'note',
        'changes',
        'pending_action',
        'import_summary',
        'import_sample',
        'import_errors',
        'reviewed_by',
        'reviewed_at',
        'review_reason',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'changes' => 'array',
            'pending_action' => 'array',
            'import_summary' => 'array',
            'import_sample' => 'array',
            'import_errors' => 'array',
        ];
    }

    /** How many are waiting (for the sidebar badge). */
    public static function waitingCount(): int
    {
        return self::where('status', 'waiting')->count();
    }

    public static function nextRqNumber(): string
    {
        $last = self::orderByDesc('id')->value('rq_number');
        $n = $last ? ((int) substr($last, 3)) + 1 : 1;

        return 'RQ-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    public function applicant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function submitter(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
