<?php

namespace App\Http\Controllers;

class GraduateRecordController extends Controller
{
    /**
     * Graduate Programs Records page (master's and doctoral).
     *
     * Same view as the other Records pages (applicants.index), with 'scope' => 'graduate'.
     * Placeholder people, made up. Keys that differ from the College data:
     *   group          program code shown in the table and filter (MBA, MEd, ...)
     *   program_label  full program name for the drawer
     *   program        level: Master's or Doctoral (the table's second column)
     *   degree         the degree they already have
     */
    public function index()
    {
        // Code => [full name, level]
        $programs = [
            'MBA' => ['Master of Business Administration', "Master's"],
            'MEd' => ['Master of Education', "Master's"],
            'MIT' => ['Master of Information Technology', "Master's"],
            'MPA' => ['Master of Public Administration', "Master's"],
            'PhD' => ['Doctor of Philosophy in Education', 'Doctoral'],
        ];

        $applicants = [
            ['group' => 'MBA', 'id' => 'APP-2026-G001', 'last' => 'Bautista', 'first' => 'Ramon Jose', 'mi' => 'A', 'degree' => 'BS Business Administration · DLSL', 'status' => 'Paid', 'applied' => '2026-05-05', 'updated' => '2026-06-01 09:00', 'dob' => '1990-06-15', 'gender' => 'Male', 'email' => 'rj.bautista@example.com', 'contact' => '0917 000 3001', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 10, 2026 · 9:00 AM', 'paid' => '2026-06-01', 'amount' => 3000],
            ['group' => 'MEd', 'id' => 'APP-2026-G002', 'last' => 'Fernandez', 'first' => 'Maricel', 'mi' => 'D', 'degree' => 'BSEd · DLSL', 'status' => 'For Exam', 'applied' => '2026-05-08', 'updated' => '2026-06-03 14:00', 'dob' => '1988-03-22', 'gender' => 'Female', 'email' => 'm.fernandez@example.com', 'contact' => '0917 000 3002', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 14, 2026 · 10:00 AM', 'paid' => null, 'amount' => null],
            ['group' => 'MIT', 'id' => 'APP-2026-G003', 'last' => 'Garcia', 'first' => 'Eduardo', 'mi' => 'M', 'degree' => 'BS Computer Science · DLSL', 'status' => 'Pending', 'applied' => '2026-05-12', 'updated' => '2026-05-12 16:15', 'dob' => '1992-09-11', 'gender' => 'Male', 'email' => 'e.garcia@example.com', 'contact' => '0917 000 3003', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['group' => 'MPA', 'id' => 'APP-2026-G004', 'last' => 'Mendoza', 'first' => 'Luisa Patricia', 'mi' => 'C', 'degree' => 'BS Political Science · DLSL', 'status' => 'Paid', 'applied' => '2026-05-03', 'updated' => '2026-05-30 11:00', 'dob' => '1985-01-05', 'gender' => 'Female', 'email' => 'lp.mendoza@example.com', 'contact' => '0917 000 3004', 'address' => 'Santo Tomas, Batangas', 'exam' => 'Jun 8, 2026 · 8:00 AM', 'paid' => '2026-05-30', 'amount' => 3000],
            ['group' => 'PhD', 'id' => 'APP-2026-G005', 'last' => 'Torres', 'first' => 'Andrei Benedict', 'mi' => 'V', 'degree' => 'Master of Education · DLSL', 'status' => 'For Exam', 'applied' => '2026-05-06', 'updated' => '2026-06-04 15:30', 'dob' => '1978-04-28', 'gender' => 'Male', 'email' => 'ab.torres@example.com', 'contact' => '0917 000 3005', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 16, 2026 · 1:00 PM', 'paid' => null, 'amount' => null],
            ['group' => 'MBA', 'id' => 'APP-2026-G006', 'last' => 'Villanueva', 'first' => 'Rosario Anne', 'mi' => 'B', 'degree' => 'BS Accountancy · DLSL', 'status' => 'Pending', 'applied' => '2026-05-18', 'updated' => '2026-05-18 09:45', 'dob' => '1994-07-14', 'gender' => 'Female', 'email' => 'ra.villanueva@example.com', 'contact' => '0917 000 3006', 'address' => 'Calamba, Laguna', 'exam' => null, 'paid' => null, 'amount' => null],
            ['group' => 'PhD', 'id' => 'APP-2026-G007', 'last' => 'Lopez', 'first' => 'Cecilia', 'mi' => 'R', 'degree' => 'Master of Arts in Education · UST', 'status' => 'Paid', 'applied' => '2026-05-02', 'updated' => '2026-05-27 13:20', 'dob' => '1980-11-30', 'gender' => 'Female', 'email' => 'c.lopez@example.com', 'contact' => '0917 000 3007', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 6, 2026 · 9:00 AM', 'paid' => '2026-05-27', 'amount' => 3000],
            ['group' => 'MIT', 'id' => 'APP-2026-G008', 'last' => 'Agustin', 'first' => 'Paolo', 'mi' => 'S', 'degree' => 'BS Information Technology · DLSL', 'status' => 'For Exam', 'applied' => '2026-05-15', 'updated' => '2026-06-02 10:10', 'dob' => '1996-02-08', 'gender' => 'Male', 'email' => 'p.agustin@example.com', 'contact' => '0917 000 3008', 'address' => 'Malvar, Batangas', 'exam' => 'Jun 15, 2026 · 1:00 PM', 'paid' => null, 'amount' => null],
        ];

        // Fill in what every graduate row shares, using the program code
        $applicants = array_map(fn ($a) => $a + [
            'unit' => 'graduate',
            'unit_label' => 'Graduate Programs',
            'program' => $programs[$a['group']][1],
            'program_label' => "{$programs[$a['group']][0]} ({$a['group']})",
        ], $applicants);

        return view('applicants.index', [
            'scope' => 'graduate',
            'academicYear' => '2025–2026',
            'periodLabel' => 'June 2026',
            'applicants' => $applicants,
            'groupOrder' => array_keys($programs),
        ]);
    }
}
