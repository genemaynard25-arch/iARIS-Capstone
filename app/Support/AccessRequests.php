<?php

namespace App\Support;

/**
 * Access requests from the login page's "Request Access" form.
 *
 * Placeholder requests, made up: the form doesn't save anything yet. User Accounts shows them in its
 * Access Requests tab, where Approve / Decline work on the page only.
 * TODO (backend): an access_requests table the login form writes to, and approve / decline routes
 * (approving should create the account with that role and department, like POST /accounts does).
 */
class AccessRequests
{
    public static function sample(): array
    {
        $ago = fn ($days, $time) => now()->subDays($days)->format('Y-m-d') . ' ' . $time;

        return [
            ['id' => 1, 'name' => 'Macaraig, Lorena F.', 'email' => 'l.macaraig@dlsl.edu.ph', 'position' => 'Dean', 'department' => 'CBEAM', 'reason' => 'Track CBEAM applicants for enrollment planning.', 'at' => $ago(0, '08:40'), 'status' => 'waiting'],
            ['id' => 2, 'name' => 'Gatdula, Ernesto V.', 'email' => 'e.gatdula@dlsl.edu.ph', 'position' => 'Dean', 'department' => 'College of Law', 'reason' => 'Monitor JD and LLM applicants this cycle.', 'at' => $ago(1, '15:12'), 'status' => 'waiting'],
            ['id' => 3, 'name' => 'Castro, Rowena L.', 'email' => 'r.castro@dlsl.edu.ph', 'position' => 'Registrar', 'department' => 'IS Registrar', 'reason' => 'Receive the IS exam passers list for enrollment.', 'at' => $ago(1, '10:05'), 'status' => 'waiting'],
            ['id' => 4, 'name' => 'De Guzman, Noel A.', 'email' => 'n.deguzman@dlsl.edu.ph', 'position' => 'iPACE Director', 'department' => 'iPACE (iPACE and ETEEAP)', 'reason' => 'Review iPACE and ETEEAP applicants.', 'at' => $ago(2, '13:30'), 'status' => 'waiting'],
            ['id' => 5, 'name' => 'Recto, Arnel D.', 'email' => 'arnel.recto@gmail.com', 'position' => 'IATO Staff', 'department' => 'Institutional Admissions & Testing Office', 'reason' => 'New IATO staff member.', 'at' => $ago(3, '09:00'), 'status' => 'waiting'],
            ['id' => 6, 'name' => 'Umali, Patricia C.', 'email' => 'p.umali@dlsl.edu.ph', 'position' => 'Program Chair', 'department' => 'CITE', 'reason' => 'Follow BS Information Technology applicants.', 'at' => $ago(6, '11:20'), 'status' => 'approved', 'by' => 'Renegado, Randolph', 'decided' => $ago(5, '08:15')],
            ['id' => 7, 'name' => 'Lopez, Mark R.', 'email' => 'mark.lopez@yahoo.com', 'position' => 'President', 'department' => 'Office of the President', 'reason' => 'Need access.', 'at' => $ago(8, '16:45'), 'status' => 'declined', 'by' => 'Renegado, Randolph', 'decided' => $ago(7, '09:30'), 'note' => 'Not a DLSL email, and the position could not be confirmed.'],
        ];
    }

    /** How many are waiting (for the sidebar badge). */
    public static function waitingCount(): int
    {
        return collect(self::sample())->where('status', 'waiting')->count();
    }
}
