<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\LampRequest;
use App\Models\LampScholarRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LampConversationController extends Controller
{
    private function authorizeOffice(Request $request, bool $iatoOnly = false): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->role === 'iato_staff' || (! $iatoOnly && $user->isLamp()), 403);
        if ($request->isMethod('post') && $user->role === 'iato_staff' && ! $user->isAdmin()) {
            abort_unless($user->access_type === 'editor' && $user->canAccess('edit'), 403);
        }
    }

    public function index(Request $request)
    {
        $this->authorizeOffice($request);

        return view('lamp.conversations', [
            'requests' => LampRequest::with(['messages', 'applicant'])->latest('updated_at')->get(),
            'isIato' => ! $request->user()->isLamp(),
            'selected' => $request->query('applicant', ''),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeOffice($request, true);
        $data = $request->validate(['reference' => 'required|string|max:30', 'reason' => 'required|string|max:255', 'text' => 'required|string|max:5000']);
        $item = DB::transaction(function () use ($request, $data) {
            $applicant = Applicant::where('reference_number', $data['reference'])->lockForUpdate()->firstOrFail();
            abort_if($applicant->archived_at, 422, 'Applicant is archived.');
            abort_if(LampRequest::where('applicant_id', $applicant->id)->where('status', '!=', 'resolved')->exists(), 409, 'An open request already exists for this applicant.');
            $item = LampRequest::create([
                'applicant_id' => $applicant->id, 'name' => "{$applicant->last_name}, {$applicant->first_name}",
                'program' => $applicant->program_or_track, 'iato_value' => $applicant->scholarship_type,
                'lamp_value' => LampScholarRecord::where('applicant_id', $applicant->id)->value('scholarship_type'),
                'reason' => $data['reason'], 'status' => 'waiting',
            ]);
            $this->addMessage($request, $item, $data['text']);
            AuditLog::record('create', 'LAMP', 'LAMP Request Created', "Request {$item->id} for {$applicant->reference_number}: {$data['reason']}", $request->user()->roleInfo()[0]);

            return $item;
        });

        return $request->expectsJson() ? response()->json(['id' => $item->id], 201) : back()->with('success', 'Request sent to LAMP.');
    }

    public function reply(Request $request, LampRequest $conversation)
    {
        $this->authorizeOffice($request);
        $data = $request->validate(['text' => 'required|string|max:5000']);
        DB::transaction(function () use ($request, $conversation, $data) {
            $item = LampRequest::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_if($item->status === 'resolved', 409, 'This request is resolved.');
            abort_unless($item->applicant_id, 422, 'The applicant no longer exists.');
            $this->addMessage($request, $item, $data['text']);
            $item->update(['status' => $request->user()->isLamp() ? 'replied' : 'waiting']);
            AuditLog::record('edit', 'LAMP', 'LAMP Request Reply', "Reply to request {$item->id}, applicant {$item->applicant_id}.", $request->user()->roleInfo()[0]);
        });

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'Reply sent.');
    }

    public function resolve(Request $request, LampRequest $conversation)
    {
        $this->authorizeOffice($request, true);
        DB::transaction(function () use ($request, $conversation) {
            $item = LampRequest::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_if($item->status === 'resolved', 409, 'This request is already resolved.');
            $before = $item->status;
            $item->update(['status' => 'resolved']);
            AuditLog::record('edit', 'LAMP', 'LAMP Request Resolved', "Resolved request {$item->id}, applicant {$item->applicant_id}.", $request->user()->roleInfo()[0], [['field' => 'status', 'before' => $before, 'after' => 'resolved']]);
        });

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'Request resolved.');
    }

    private function addMessage(Request $request, LampRequest $item, string $text): void
    {
        $item->messages()->create([
            'from' => $request->user()->isLamp() ? 'lamp' : 'iato', 'user_id' => $request->user()->id,
            'name' => $request->user()->name, 'role' => $request->user()->roleInfo()[0],
            'text' => $text, 'sent_at' => now(),
        ]);
    }
}
