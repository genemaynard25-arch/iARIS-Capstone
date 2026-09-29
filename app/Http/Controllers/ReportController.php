<?php

namespace App\Http\Controllers;

class ReportController extends Controller
{
    /**
     * Reports page.
     *
     * Placeholder data so the UI can be built first (same idea as HomeController
     * and ApplicantController). Names are made up. When reports are generated for
     * real, the preview rows would come from queries and the history from a
     * reports / audit log table.
     *
     * 'tone' is a Bootstrap colour name. In preview rows, a cell written as
     * ['Passed', 'primary'] is shown as a coloured badge.
     */
    public function index()
    {
        $passed = ['Passed', 'primary'];

        $sections = [
            [
                'title' => 'Admissions Reports',
                'description' => 'Entrance exam results and applicant lists per academic unit',
                'reports' => [
                    [
                        'key' => 'is-exam', 'title' => 'Integrated School — Exam Passers', 'icon' => 'bi-building', 'tone' => 'primary',
                        'description' => 'List of IS applicants (K–12) who passed the DLSL entrance exam, with strand and grade level breakdown.',
                        'recipient' => 'IS Registrar',
                        'columns' => ['#', 'Name', 'App No.', 'Grade Level', 'Strand', 'Score', 'Result'],
                        'rows' => [
                            [1, 'Lim, Ethan G.', 'APP-IS-2026-0101', 'Grade 11', 'STEM', '92.5', $passed],
                            [2, 'Soriano, Andrea P.', 'APP-IS-2026-0102', 'Grade 11', 'ABM', '88.0', $passed],
                            [3, 'Francisco, Gabriel N.', 'APP-IS-2026-0105', 'Grade 11', 'HUMSS', '85.5', $passed],
                        ],
                        'note' => 'Showing 3 sample records. The full report includes all exam passers.',
                    ],
                    [
                        'key' => 'college-exam', 'title' => 'College — Exam Passers', 'icon' => 'bi-mortarboard', 'tone' => 'info',
                        'description' => 'List of college applicants who passed the DLSL entrance exam, organized by college and program.',
                        'recipient' => 'College Registrar',
                        'columns' => ['#', 'Name', 'App No.', 'College', 'Program', 'Score', 'Result'],
                        'rows' => [
                            [1, 'Santos, Maria Isabel L.', 'APP-2026-0381', 'CITE', 'BS Computer Science', '94.0', $passed],
                            [2, 'Garcia, Mark Anthony R.', 'APP-2026-0384', 'CIHTM', 'BS Tourism Management', '90.5', $passed],
                            [3, 'Dela Cruz, Ana Patricia M.', 'APP-2026-0383', 'CBEAM', 'BS Accountancy', '87.0', $passed],
                        ],
                        'note' => 'Showing 3 sample records. The full report includes all exam passers.',
                    ],
                    [
                        'key' => 'grad-exam', 'title' => 'Graduate Programs — Exam Passers', 'icon' => 'bi-person-workspace', 'tone' => 'secondary',
                        'description' => "Applicants who passed for Graduate Programs (Master's / Doctoral), with program and specialization.",
                        'recipient' => 'Graduate School Head',
                        'columns' => ['#', 'Name', 'App No.', 'Program', 'Level', 'Score', 'Result'],
                        'rows' => [
                            [1, 'Ocampo, Rafael T.', 'APP-GRAD-2026-001', 'Master of Business Administration', "Master's", '91.0', $passed],
                            [2, 'Valdez, Irene C.', 'APP-GRAD-2026-002', 'Master of Information Technology', "Master's", '89.5', $passed],
                        ],
                        'note' => 'Showing 2 sample records.',
                    ],
                    [
                        'key' => 'law-exam', 'title' => 'College of Law — Exam Passers', 'icon' => 'bi-bank', 'tone' => 'warning',
                        'description' => 'Applicants who passed the Law Aptitude Examination, with rankings and qualifying scores.',
                        'recipient' => 'College of Law Head',
                        'columns' => ['#', 'Name', 'App No.', 'Score', 'Rank', 'Result'],
                        'rows' => [
                            [1, 'Bautista, Luis E.', 'APP-LAW-2026-001', '95.0', '1st', $passed],
                            [2, 'Salazar, Monica F.', 'APP-LAW-2026-002', '91.5', '2nd', $passed],
                        ],
                        'note' => 'Showing 2 sample records.',
                    ],
                    [
                        'key' => 'ipace-exam', 'title' => 'iPACE — Exam Passers', 'icon' => 'bi-laptop', 'tone' => 'success',
                        'description' => 'Applicants accepted into the iPACE (online / asynchronous) program, with course and schedule track.',
                        'recipient' => 'iPACE Head',
                        'columns' => ['#', 'Name', 'App No.', 'Course', 'Schedule Track', 'Result'],
                        'rows' => [
                            [1, 'Cabrera, Noel D.', 'APP-IPACE-2026-001', 'BS Information Technology', 'Asynchronous', $passed],
                            [2, 'Estrada, Liza M.', 'APP-IPACE-2026-002', 'BS Business Administration', 'Blended', $passed],
                        ],
                        'note' => 'Showing 2 sample records.',
                    ],
                    [
                        'key' => 'scholars', 'title' => 'Scholars — IATO vs LAMP Cross-Check', 'icon' => 'bi-award', 'tone' => 'danger',
                        'description' => 'Compares IATO scholar records against LAMP Office data — flags discrepancies in names, IDs, or status.',
                        'recipient' => 'LAMP Office',
                        'columns' => ['#', 'Name', 'IATO Record', 'LAMP Record', 'Match'],
                        'rows' => [
                            [1, 'Torres, Nicole A.', 'DLSL Scholar', 'DLSL Scholar', ['Match', 'primary']],
                            [2, 'Aquino, Bea S.', 'Academic Scholar', 'Not found', ['Discrepancy', 'danger']],
                            [3, 'Lim, Ethan G.', 'DLSL Scholar', 'DLSL Scholar', ['Match', 'primary']],
                        ],
                        'note' => '1 discrepancy flagged out of 3 shown. The full report includes all scholar records.',
                    ],
                ],
            ],
            [
                'title' => 'Institutional Reports',
                'description' => 'Enrollment trends and department-level summaries for leadership and academic heads',
                'reports' => [
                    [
                        'key' => 'trends', 'title' => 'Enrollment Trends — Chancellor / President', 'icon' => 'bi-graph-up-arrow', 'tone' => 'danger',
                        'description' => 'Year-on-year enrollment comparison across all units: total enrolled this AY vs last AY, with the % change per unit.',
                        'recipient' => 'Chancellor / President',
                        'columns' => ['Unit', 'AY 2024–2025', 'AY 2025–2026', 'Change', '% Change'],
                        'rows' => [
                            ['CITE', '872', '945', '+73', ['+8.4%', 'primary']],
                            ['CBEAM', '1,042', '998', '−44', ['−4.2%', 'danger']],
                            ['CEAS', '634', '671', '+37', ['+5.8%', 'primary']],
                            ['CON', '412', '438', '+26', ['+6.3%', 'primary']],
                            ['CIHTM', '389', '356', '−33', ['−8.5%', 'danger']],
                            ['Integrated School', '2,140', '2,284', '+144', ['+6.7%', 'primary']],
                        ],
                        'note' => 'Compares AY 2024–2025 with AY 2025–2026. The full report includes all units.',
                    ],
                    [
                        'key' => 'dept', 'title' => 'Enrolled Students per Department', 'icon' => 'bi-person-video3', 'tone' => 'info',
                        'description' => 'Enrolled students per college and department: CITE, CEAS, CON, CIHTM, CBEAM, Graduate Programs, College of Law, and iPACE.',
                        'recipient' => 'Dean / Program Chair',
                        'columns' => ['Department', 'Program', 'Enrolled', 'Target', '% Filled'],
                        'rows' => [
                            ['CITE', 'BS Computer Science', '245', '280', '87.5%'],
                            ['CITE', 'BS Information Technology', '312', '320', '97.5%'],
                            ['CBEAM', 'BS Accountancy', '198', '240', '82.5%'],
                            ['CON', 'BS Nursing', '438', '480', '91.3%'],
                            ['CIHTM', 'BS Tourism Management', '178', '200', '89.0%'],
                        ],
                        'note' => 'Showing 5 sample programs. The full report covers all departments.',
                    ],
                ],
            ],
        ];

        $history = [
            ['report' => 'trends', 'name' => 'Enrollment Trends Report', 'meta' => 'AY 2024–2025 vs AY 2025–2026', 'recipient' => 'Chancellor / President', 'period' => 'AY 2025–2026', 'format' => 'PDF', 'by' => 'A. Reyes', 'at' => 'Jun 9, 2026 · 8:30 AM'],
            ['report' => 'college-exam', 'name' => 'College Exam Passers Report', 'meta' => 'All colleges combined', 'recipient' => 'College Registrar', 'period' => 'AY 2025–2026', 'format' => 'XLSX', 'by' => 'M. Cruz', 'at' => 'Jun 8, 2026 · 2:14 PM'],
            ['report' => 'scholars', 'name' => 'Scholars Cross-Check Report', 'meta' => '3 discrepancies flagged', 'recipient' => 'LAMP Office', 'period' => 'AY 2025–2026', 'format' => 'PDF', 'by' => 'M. Cruz', 'at' => 'Jun 7, 2026 · 10:02 AM'],
            ['report' => 'dept', 'name' => 'Enrolled per Department Report', 'meta' => 'CITE, CEAS, CON, CIHTM, CBEAM', 'recipient' => 'Dean / Program Chair', 'period' => 'AY 2025–2026', 'format' => 'XLSX', 'by' => 'A. Reyes', 'at' => 'Jun 6, 2026 · 4:48 PM'],
            ['report' => 'is-exam', 'name' => 'IS Exam Passers Report', 'meta' => 'Grade 7–11 applicants', 'recipient' => 'IS Registrar', 'period' => 'AY 2025–2026', 'format' => 'PDF', 'by' => 'M. Cruz', 'at' => 'Jun 5, 2026 · 9:30 AM'],
        ];

        return view('reports.index', [
            'sections' => $sections,
            'history' => $history,
            'periods' => ['Academic Year 2025–2026', 'Academic Year 2024–2025', '1st Semester 2025–2026', 'This Month — June 2026'],
        ]);
    }
}
