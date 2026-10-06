<?php

namespace App\Http\Controllers;

class IpaceRecordController extends Controller
{
    /**
     * iPACE Records page (Individualized Program for Adult and Continuing Education).
     *
     * Same view as the other Records pages (applicants.index), with 'scope' => 'ipace'.
     * Placeholder people, made up. Keys that differ from the College data:
     *   group          program code shown in the table and filter (BSIT, BSCS, ...)
     *   program_label  full program name for the drawer
     *   program        delivery mode: Online or Blended (the table's second column)
     *   working        working student? Yes / No
     *   employer       employer and position (only shown in the drawer)
     */
    public function index()
    {
        $programs = [
            'BSIT' => 'BS Information Technology',
            'BSCS' => 'BS Computer Science',
            'BSBA' => 'BS Business Administration',
            'BSN' => 'BS Nursing',
            'BEED' => 'Bachelor of Elementary Education',
        ];

        $applicants = [
            ['group' => 'BSIT', 'id' => 'APP-2026-I001', 'last' => 'Lim', 'first' => 'Kenneth Andrew', 'mi' => 'P', 'program' => 'Online', 'year' => '2nd Year', 'working' => 'Yes', 'employer' => 'BDO Unibank · IT Associate', 'status' => 'Paid', 'applied' => '2026-05-05', 'updated' => '2026-06-01 09:00', 'dob' => '1999-04-12', 'gender' => 'Male', 'email' => 'ka.lim@example.com', 'contact' => '0917 000 2001', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 10, 2026 · 9:00 AM', 'paid' => '2026-06-01', 'amount' => 2000],
            ['group' => 'BSBA', 'id' => 'APP-2026-I002', 'last' => 'Garcia', 'first' => 'Joanna Marie', 'mi' => 'T', 'program' => 'Blended', 'year' => '1st Year', 'working' => 'Yes', 'employer' => 'SM Lipa · Sales Associate', 'status' => 'For Exam', 'applied' => '2026-05-09', 'updated' => '2026-06-03 14:00', 'dob' => '2001-07-30', 'gender' => 'Female', 'email' => 'jm.garcia@example.com', 'contact' => '0917 000 2002', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 15, 2026 · 10:00 AM', 'paid' => null, 'amount' => null],
            ['group' => 'BSCS', 'id' => 'APP-2026-I003', 'last' => 'Ramos', 'first' => 'Aldrich Jose', 'mi' => 'B', 'program' => 'Online', 'year' => '3rd Year', 'working' => 'No', 'employer' => null, 'status' => 'Paid', 'applied' => '2026-05-03', 'updated' => '2026-05-28 10:00', 'dob' => '2000-02-14', 'gender' => 'Male', 'email' => 'aj.ramos@example.com', 'contact' => '0917 000 2003', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 8, 2026 · 8:00 AM', 'paid' => '2026-05-28', 'amount' => 2000],
            ['group' => 'BSN', 'id' => 'APP-2026-I004', 'last' => 'Torres', 'first' => 'Angelica Mae', 'mi' => 'D', 'program' => 'Blended', 'year' => '1st Year', 'working' => 'Yes', 'employer' => 'Adventist Medical Center · Nursing Aide', 'status' => 'Pending', 'applied' => '2026-05-16', 'updated' => '2026-05-16 15:00', 'dob' => '2002-10-05', 'gender' => 'Female', 'email' => 'am.torres@example.com', 'contact' => '0917 000 2004', 'address' => 'Santo Tomas, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['group' => 'BEED', 'id' => 'APP-2026-I005', 'last' => 'Villanueva', 'first' => 'Rodel Santos', 'mi' => 'C', 'program' => 'Online', 'year' => '2nd Year', 'working' => 'Yes', 'employer' => 'DepEd Lipa · Teacher Aide', 'status' => 'For Exam', 'applied' => '2026-05-11', 'updated' => '2026-06-04 11:00', 'dob' => '1998-06-20', 'gender' => 'Male', 'email' => 'rs.villanueva@example.com', 'contact' => '0917 000 2005', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 18, 2026 · 9:00 AM', 'paid' => null, 'amount' => null],
            ['group' => 'BSIT', 'id' => 'APP-2026-I006', 'last' => 'Macaraig', 'first' => 'Lorena', 'mi' => 'F', 'program' => 'Blended', 'year' => '1st Year', 'working' => 'Yes', 'employer' => 'Lipa City Hall · Clerk', 'status' => 'Pending', 'applied' => '2026-05-19', 'updated' => '2026-05-19 10:20', 'dob' => '1995-09-03', 'gender' => 'Female', 'email' => 'l.macaraig@example.com', 'contact' => '0917 000 2006', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['group' => 'BSBA', 'id' => 'APP-2026-I007', 'last' => 'Hernandez', 'first' => 'Mario', 'mi' => 'V', 'program' => 'Online', 'year' => '3rd Year', 'working' => 'Yes', 'employer' => 'Self-employed · Online Seller', 'status' => 'Paid', 'applied' => '2026-05-07', 'updated' => '2026-05-30 15:45', 'dob' => '1990-01-22', 'gender' => 'Male', 'email' => 'm.hernandez@example.com', 'contact' => '0917 000 2007', 'address' => 'San Pablo, Laguna', 'exam' => 'Jun 9, 2026 · 1:00 PM', 'paid' => '2026-05-30', 'amount' => 2000],
            ['group' => 'BSCS', 'id' => 'APP-2026-I008', 'last' => 'Ilagan', 'first' => 'Patricia', 'mi' => 'R', 'program' => 'Online', 'year' => '1st Year', 'working' => 'No', 'employer' => null, 'status' => 'For Exam', 'applied' => '2026-05-13', 'updated' => '2026-06-02 09:30', 'dob' => '2003-03-17', 'gender' => 'Female', 'email' => 'p.ilagan@example.com', 'contact' => '0917 000 2008', 'address' => 'Rosario, Batangas', 'exam' => 'Jun 16, 2026 · 9:00 AM', 'paid' => null, 'amount' => null],
        ];

        // Fill in what every iPACE row shares
        $applicants = array_map(fn ($a) => $a + [
            'unit' => 'ipace',
            'unit_label' => 'iPACE',
            'program_label' => "{$programs[$a['group']]} ({$a['group']})",
        ], $applicants);

        return view('applicants.index', [
            'scope' => 'ipace',
            'academicYear' => '2025–2026',
            'periodLabel' => 'June 2026',
            'applicants' => $applicants,
            'groupOrder' => array_keys($programs),
        ]);
    }
}
