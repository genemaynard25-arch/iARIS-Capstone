<?php

namespace App\Support;

use App\Models\LampRequest;

class LampRequests
{
    public static function sample(): array
    {
        return LampRequest::with(['messages', 'applicant'])->latest('updated_at')->get()->map(fn ($item) => [
            'id' => $item->applicant?->reference_number ?? 'REQUEST-'.$item->id,
            'request_id' => $item->id,
            'name' => $item->name, 'program' => $item->program,
            'iato' => $item->iato_value ?? 'Not recorded', 'lamp' => $item->lamp_value ?? 'Not recorded',
            'reason' => $item->reason, 'status' => $item->status,
            'messages' => $item->messages->map(fn ($m) => [
                'from' => $m->from, 'name' => $m->name, 'role' => $m->role,
                'at' => $m->sent_at->format('M j, Y · g:i A'), 'text' => $m->text,
            ])->all(),
        ])->all();
    }

    public static function waitingCount(): int
    {
        return LampRequest::where('status', 'waiting')->count();
    }

    public static function byScholar(): array
    {
        return collect(self::sample())->keyBy('id')->all();
    }
}
