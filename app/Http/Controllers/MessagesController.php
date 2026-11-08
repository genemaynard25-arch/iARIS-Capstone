<?php

namespace App\Http\Controllers;

/**
 * Messages with IATO for the LAMP Office, the Registrars and the SHS Principal: the same page as the
 * Executive Office's (executive/messages.blade.php). The Super Admin and IATO Staff have their own format.
 * Sample threads from the mockups. TODO (backend): read and write the message_threads / messages tables.
 */
class MessagesController extends Controller
{
    // The pages these users come from (the "Messages" link and the note at the bottom go back there)
    private static function base(): string
    {
        $user = auth()->user();

        return $user->isLamp() ? '/lamp' : ($user->role === 'registrar' ? '/registrar' : '/shs');
    }

    public function index()
    {
        return view('executive.messages', [
            'threads' => self::threads(),
            'base' => url(self::base()),
            // Instead of "Other ways to get answers" (these offices have no AI button)
            'note' => ['For a question about one record, use the record itself. Messages are for everything else you need from IATO.', 'Back to the Dashboard'],
        ]);
    }

    /** Conversations IATO answered (the number on the sidebar's Messages link). */
    public static function answeredMessages(): int
    {
        return collect(self::threads())->where('status', 'answered')->count();
    }

    // Your conversations with IATO (as in the mockups)
    private static function threads(): array
    {
        $user = auth()->user();
        if ($user->isLamp()) {
            return [
                ['id' => 'MSG-0113', 'topic' => 'Request a report', 'subject' => 'Scholar list with the new CCJE applicants', 'status' => 'waiting', 'messages' => [
                    ['from' => 'me', 'at' => 'Jun 12, 2026 · 10:40 AM', 'text' => 'Can IATO send an updated list of scholarship applicants that includes CCJE? We need it for the LAMP committee on Monday.'],
                ]],
            ];
        }
        if ($user->role === 'registrar' && $user->assigned_level !== 'is') {
            return [
                ['id' => 'MSG-0114', 'topic' => 'Question about the data', 'subject' => 'When are the June exam results posted?', 'status' => 'answered', 'messages' => [
                    ['from' => 'me', 'at' => 'Jun 12, 2026 · 8:05 AM', 'text' => 'When will the June 6 exam results be in iARIS? Some applicants are asking us about enrollment.'],
                    ['from' => 'iato', 'name' => 'Cruz, Jasmine', 'role' => 'IATO Staff', 'at' => 'Jun 12, 2026 · 9:30 AM', 'text' => 'They will be posted on Jun 15, Ms. Santos. Passers will show as For Exam → Paid once they pay.'],
                ]],
            ];
        }

        return [];
    }
}
