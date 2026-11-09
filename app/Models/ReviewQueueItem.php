<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Not wired up to any controller yet (see ReviewQueueController/SubmissionsController,
// which still run entirely on placeholder data) - the table and model exist ahead of
// the feature so the schema doesn't change again once it's built.
class ReviewQueueItem extends Model
{
    protected $table = 'review_queue';

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
            'import_summary' => 'array',
            'import_sample' => 'array',
            'import_errors' => 'array',
        ];
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
