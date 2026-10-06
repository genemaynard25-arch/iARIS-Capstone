<?php

namespace App\Http\Controllers;

class LawRecordController extends Controller
{
    /**
     * College of Law Records page.
     *
     * Same view as the other Records pages (applicants.index), with 'scope' => 'law'.
     * Placeholder people, made up. Law applicants don't fit the College data, so
     * they have their own list with a few extra keys:
     *   group          program code shown in the table and filter (JD, LLM, JSD)
     *   program_label  full program name for the drawer
     *   program        undergraduate / prior degree (the table's second column)
     *   bar            bar exam result ("N/A" for JD applicants, who haven't taken it yet)
     * Status uses the usual names; the view shows "For Exam" as "For Exam / Interview".
     */
    public function index()
    {
        $jd = ['group' => 'JD', 'program_label' => 'Juris Doctor (JD)'];
        $llm = ['group' => 'LLM', 'program_label' => 'Master of Laws (LLM)'];
        $jsd = ['group' => 'JSD', 'program_label' => 'Doctor of Juridical Science (JSD)'];
        $base = ['unit' => 'law', 'unit_label' => 'College of Law'];

        $applicants = [
            $base + $jd + ['id' => 'APP-2026-L001', 'last' => 'Aquino', 'first' => 'Rafael Miguel', 'mi' => 'B', 'program' => 'BS Political Science · DLSL', 'bar' => 'N/A', 'status' => 'Paid', 'applied' => '2026-05-04', 'updated' => '2026-05-28 10:00', 'dob' => '1998-05-10', 'gender' => 'Male', 'email' => 'rm.aquino@example.com', 'contact' => '0917 000 1001', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 8, 2026 · 9:00 AM', 'paid' => '2026-05-28', 'amount' => 3500],
            $base + $jd + ['id' => 'APP-2026-L002', 'last' => 'Reyes', 'first' => 'Carmela', 'mi' => 'S', 'program' => 'AB Philosophy · DLSL', 'bar' => 'N/A', 'status' => 'For Exam', 'applied' => '2026-05-06', 'updated' => '2026-06-04 14:00', 'dob' => '1997-08-03', 'gender' => 'Female', 'email' => 'c.reyes@example.com', 'contact' => '0917 000 1002', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 12, 2026 · 10:00 AM', 'paid' => null, 'amount' => null],
            $base + $llm + ['id' => 'APP-2026-L003', 'last' => 'Santos', 'first' => 'Victorino', 'mi' => 'L', 'program' => 'Juris Doctor · DLSL', 'bar' => 'Passed 2017', 'status' => 'Paid', 'applied' => '2026-05-03', 'updated' => '2026-05-25 09:00', 'dob' => '1990-03-15', 'gender' => 'Male', 'email' => 'v.santos@example.com', 'contact' => '0917 000 1003', 'address' => 'San Jose, Batangas', 'exam' => 'Jun 6, 2026 · 8:00 AM', 'paid' => '2026-05-25', 'amount' => 3500],
            $base + $jd + ['id' => 'APP-2026-L004', 'last' => 'Dela Torre', 'first' => 'Maria Josefa', 'mi' => 'P', 'program' => 'BS Criminology · PLM', 'bar' => 'N/A', 'status' => 'Pending', 'applied' => '2026-05-14', 'updated' => '2026-05-14 15:00', 'dob' => '1999-11-22', 'gender' => 'Female', 'email' => 'mj.delatorre@example.com', 'contact' => '0917 000 1004', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            $base + $jsd + ['id' => 'APP-2026-L005', 'last' => 'Cruz', 'first' => 'Benigno Antonio', 'mi' => 'R', 'program' => 'Master of Laws · DLSL', 'bar' => 'Passed 2006', 'status' => 'For Exam', 'applied' => '2026-05-07', 'updated' => '2026-06-05 11:00', 'dob' => '1982-01-08', 'gender' => 'Male', 'email' => 'ba.cruz@example.com', 'contact' => '0917 000 1005', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 14, 2026 · 1:00 PM', 'paid' => null, 'amount' => null],
            $base + $jd + ['id' => 'APP-2026-L006', 'last' => 'Mendoza', 'first' => 'Clarissa Joy', 'mi' => 'T', 'program' => 'BS Psychology · DLSL', 'bar' => 'N/A', 'status' => 'Pending', 'applied' => '2026-05-20', 'updated' => '2026-05-20 08:45', 'dob' => '2000-09-19', 'gender' => 'Female', 'email' => 'cj.mendoza@example.com', 'contact' => '0917 000 1006', 'address' => 'Calamba, Laguna', 'exam' => null, 'paid' => null, 'amount' => null],
            $base + $llm + ['id' => 'APP-2026-L007', 'last' => 'Villanueva', 'first' => 'Teodoro', 'mi' => 'G', 'program' => 'Juris Doctor · UST', 'bar' => 'Passed 2019', 'status' => 'For Exam', 'applied' => '2026-05-16', 'updated' => '2026-06-03 13:30', 'dob' => '1993-12-01', 'gender' => 'Male', 'email' => 't.villanueva@example.com', 'contact' => '0917 000 1007', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 9:00 AM', 'paid' => null, 'amount' => null],
            $base + $jd + ['id' => 'APP-2026-L008', 'last' => 'Panganiban', 'first' => 'Andrea', 'mi' => 'M', 'program' => 'AB Communication · DLSL', 'bar' => 'N/A', 'status' => 'Paid', 'applied' => '2026-05-09', 'updated' => '2026-06-01 16:10', 'dob' => '2001-04-27', 'gender' => 'Female', 'email' => 'a.panganiban@example.com', 'contact' => '0917 000 1008', 'address' => 'Malvar, Batangas', 'exam' => 'Jun 9, 2026 · 10:00 AM', 'paid' => '2026-06-01', 'amount' => 3500],
        ];

        return view('applicants.index', [
            'scope' => 'law',
            'academicYear' => '2025–2026',
            'periodLabel' => 'June 2026',
            'applicants' => $applicants,
            'groupOrder' => ['JD', 'LLM', 'JSD'],
        ]);
    }
}
