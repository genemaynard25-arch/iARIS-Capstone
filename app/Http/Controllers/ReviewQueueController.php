<?php

namespace App\Http\Controllers;

class ReviewQueueController extends Controller
{
    /**
     * Review Queue (Super Admin / Temporary Admin): changes waiting for approval before
     * other users (Deans, Registrar, LAMP, leadership) can see them.
     *
     * Placeholder items, made up: nothing records pending changes yet. Approve / Send back
     * work on the page only. TODO (backend): a review_queue table that staff edits, imports,
     * LAMP updates and applicant-portal updates write to, plus approve / send-back routes.
     *
     * type: edit (staff edit), import (batch import), lamp (LAMP Office), self (applicant update)
     * status: waiting, approved, sent (sent back)
     * changes: [field, before, after]; imports have summary / sample / errors instead.
     */
    public function index()
    {
        return view('review-queue.index', ['items' => self::items()]);
    }

    /** How many items are waiting (for the sidebar badge). */
    public static function waitingCount(): int
    {
        return collect(self::items())->where('status', 'waiting')->count();
    }

    public static function items(): array
    {
        $at = fn ($daysAgo, $time) => now()->subDays($daysAgo)->format('Y-m-d') . ' ' . $time;

        return [
            ['id' => 'RQ-0412', 'type' => 'edit', 'title' => 'Payment recorded', 'subject' => 'Santos, Maria Isabel L.', 'ref' => 'APP-2026-0381', 'unit' => 'College · CITE',
                'by' => 'Cruz, Jasmine', 'role' => 'IATO Staff', 'at' => $at(0, '10:14'), 'status' => 'waiting',
                'note' => 'Applicant paid the reservation at the cashier this morning. OR No. 58213.',
                'changes' => [['Status', 'For Exam', 'Paid'], ['Payment date', '—', now()->format('M j, Y')], ['Reservation amount', '—', '₱2,000.00'], ['OR number', '—', '58213']]],
            ['id' => 'RQ-0411', 'type' => 'import', 'title' => 'Batch import', 'subject' => 'applicants_batch_college.xlsx', 'ref' => 'College Applicants', 'unit' => 'College',
                'by' => 'Cruz, Jasmine', 'role' => 'IATO Staff', 'at' => $at(1, '16:30'), 'status' => 'waiting',
                'note' => 'Batch from the online application form.',
                'summary' => ['total' => 142, 'new' => 128, 'updated' => 11, 'errors' => 3],
                'sample' => [['Reyes, Joshua D.', 'BS Information Technology', 'Updated'], ['Castillo, Bea Francesca M.', 'BS Information Systems', 'New'], ['Navarro, Carlo Emmanuel T.', 'BS Computer Engineering', 'New'], ['Aquino, Matthew Jose B.', 'BS Computer Science', 'New']],
                'errors' => [[37, 'Villareal, Pia V.', 'Email', 'p.villareal@dlsl', 'Invalid email format'], [88, 'Ramos, Joshua Benedict T.', 'Program', 'BS Info Tech', 'Program not recognized'], [121, 'Dizon, Katrina Joy P.', 'Date applied', '05/32/2026', 'Not a real date']]],
            ['id' => 'RQ-0410', 'type' => 'lamp', 'title' => 'Scholarship type changed', 'subject' => 'Torres, Nicole A.', 'ref' => 'APP-2026-0389', 'unit' => 'Scholarships',
                'by' => 'Fernandez, Liza', 'role' => 'LAMP Office', 'at' => $at(0, '09:58'), 'status' => 'waiting',
                'note' => 'LAMP records show a DLSL Scholarship, not CHED. Correcting to match.',
                'changes' => [['Scholarship type', 'CHED Scholar', 'DLSL Scholar'], ['LAMP cross-check', 'Discrepancy', 'Match']]],
            ['id' => 'RQ-0409', 'type' => 'self', 'title' => 'Contact details updated by applicant', 'subject' => 'Villareal, Pia V.', 'ref' => 'APP-2026-0385', 'unit' => 'College · CITE',
                'by' => 'Applicant', 'role' => 'Online portal', 'at' => $at(1, '21:05'), 'status' => 'waiting',
                'note' => 'Submitted through the applicant portal.',
                'changes' => [['Contact no.', '0917 000 0385', '0917 000 0358'], ['Address', 'Tanauan City, Batangas', 'Brgy. Sambat, Tanauan City, Batangas']]],
            ['id' => 'RQ-0408', 'type' => 'edit', 'title' => 'Strand corrected', 'subject' => 'Soriano, Andrea P.', 'ref' => 'APP-IS-2026-0102', 'unit' => 'Integrated School · SHS',
                'by' => 'Cruz, Jasmine', 'role' => 'IATO Staff', 'at' => $at(1, '14:20'), 'status' => 'waiting',
                'note' => 'Parent called to say the form was filled in wrong.',
                'changes' => [['Strand', 'ABM', 'STEM']]],
            ['id' => 'RQ-0407', 'type' => 'import', 'title' => 'Batch import', 'subject' => 'is_applicants_walkin.csv', 'ref' => 'Integrated School Applicants', 'unit' => 'Integrated School',
                'by' => 'Renegado, Randolph', 'role' => 'Super Admin', 'at' => $at(2, '15:00'), 'status' => 'waiting',
                'note' => 'Kinder and Grade 1 walk-in applicants.',
                'summary' => ['total' => 48, 'new' => 48, 'updated' => 0, 'errors' => 0],
                'sample' => [['Del Rosario, Sofia H.', 'Kindergarten', 'New'], ['Evangelista, Lucas R.', 'Grade 1', 'New'], ['Dela Rosa, Kyla Marie F.', 'Grade 2', 'New']],
                'errors' => []],
            ['id' => 'RQ-0406', 'type' => 'edit', 'title' => 'Exam schedule set', 'subject' => 'Mendoza, Clarissa Joy T.', 'ref' => 'APP-2026-L006', 'unit' => 'College of Law',
                'by' => 'Cruz, Jasmine', 'role' => 'IATO Staff', 'at' => $at(2, '11:40'), 'status' => 'approved', 'reviewed' => 'Approved by you · ' . now()->subDays(2)->format('M j') . ', 1:05 PM',
                'note' => '', 'changes' => [['Status', 'Pending', 'For Exam / Interview'], ['Exam schedule', 'Not yet scheduled', now()->addDays(8)->format('M j, Y') . ' · 1:00 PM']]],
            ['id' => 'RQ-0405', 'type' => 'lamp', 'title' => 'Scholar confirmed', 'subject' => 'Santos, Maria Isabel L.', 'ref' => 'APP-2026-0381', 'unit' => 'Scholarships',
                'by' => 'Fernandez, Liza', 'role' => 'LAMP Office', 'at' => $at(2, '10:00'), 'status' => 'approved', 'reviewed' => 'Approved by you · ' . now()->subDays(2)->format('M j') . ', 10:30 AM',
                'note' => '', 'changes' => [['LAMP cross-check', 'Pending Check', 'Match']]],
            ['id' => 'RQ-0404', 'type' => 'edit', 'title' => 'Payment recorded', 'subject' => 'Francisco, Gabriel N.', 'ref' => 'APP-IS-2026-0105', 'unit' => 'Integrated School · SHS',
                'by' => 'Cruz, Jasmine', 'role' => 'IATO Staff', 'at' => $at(3, '09:15'), 'status' => 'sent', 'reviewed' => 'Sent back by you · ' . now()->subDays(3)->format('M j') . ', 10:02 AM',
                'note' => '', 'reason' => 'The amount should be ₱1,500.00 for Integrated School, not ₱2,000.00. Please re-check the receipt.',
                'changes' => [['Status', 'For Exam', 'Paid'], ['Reservation amount', '—', '₱2,000.00']]],
        ];
    }
}
