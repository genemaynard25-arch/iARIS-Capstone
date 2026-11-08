<?php

namespace App\Http\Controllers;

use App\Models\AdmissionStat;

class ReportController extends Controller
{
    // Reports is now a tab of the Analytics & Reports page. Old links to /reports land on that tab.
    public function index()
    {
        return redirect('/analytics?tab=reports');
    }

    // The report templates, history and periods. AnalyticsController passes these to the Reports tab.
    public function data(): array
    {
        $rows = AdmissionStat::all();
        $passed = ['Passed', 'primary'];

        $sections = [
            [
                // Still placeholder: needs individual student names/records, which
                // the admission_stats import deliberately does not have.
                'title' => 'Admissions Reports',
                'description' => 'Entrance exam results and applicant lists per academic unit (sample data — needs student-level records)',
                'reports' => [
                    [
                        'key' => 'is-exam', 'title' => 'Integrated School — Exam Passers', 'icon' => 'bi-building', 'tone' => 'primary',
                        'description' => 'List of IS applicants (K–12) who passed the DLSL entrance exam, with strand and grade level breakdown.',
                        'recipient' => 'IS Registrar',
                        'columns' => ['#', 'Name', 'App No.', 'Grade Level', 'Strand', 'Score', 'Result'],
                        'rows' => [
                            [1, 'Lim, Ethan G.', 'APP-IS-2026-0101', 'Grade 11', 'STEM', '92.5', $passed],
                            [2, 'Soriano, Andrea P.', 'APP-IS-2026-0102', 'Grade 11', 'ABM', '88.0', $passed],
                        ],
                        'note' => 'Sample data — real data needs a student-level export, which admission_stats does not provide.',
                    ],
                    [
                        'key' => 'college-exam', 'title' => 'College — Exam Passers', 'icon' => 'bi-mortarboard', 'tone' => 'primary',
                        'description' => 'List of college applicants who passed the DLSL entrance exam, organized by college and program.',
                        'recipient' => 'College Registrar',
                        'columns' => ['#', 'Name', 'App No.', 'College', 'Program', 'Score', 'Result'],
                        'rows' => [
                            [1, 'Reyes, Mika D.', 'APP-2026-0381', 'CITE', 'BS Computer Science', '94.0', $passed],
                            [2, 'Garcia, Paolo S.', 'APP-2026-0384', 'CIHTM', 'BS Tourism Management', '90.5', $passed],
                        ],
                        'note' => 'Sample data — real data needs a student-level export, which admission_stats does not provide.',
                    ],
                    [
                        'key' => 'grad-exam', 'title' => 'Graduate Programs — Exam Passers', 'icon' => 'bi-journal-bookmark', 'tone' => 'info',
                        'description' => "Applicants who passed for Graduate Programs (Master's / Doctoral), with program and specialization.",
                        'recipient' => 'Graduate School Head',
                        'columns' => ['#', 'Name', 'App No.', 'Program', 'Level', 'Score', 'Result'],
                        'rows' => [
                            [1, 'Dizon, Carla M.', 'APP-GRAD-2026-001', 'Master of Business Administration', "Master's", '91.0', $passed],
                        ],
                        'note' => 'Sample data — real data needs a student-level export, which admission_stats does not provide.',
                    ],
                    [
                        'key' => 'law-exam', 'title' => 'College of Law — Exam Passers', 'icon' => 'bi-bank2', 'tone' => 'warning',
                        'description' => 'Applicants who passed the Law Aptitude Examination, with rankings and qualifying scores.',
                        'recipient' => 'College of Law Head',
                        'columns' => ['#', 'Name', 'App No.', 'Score', 'Rank', 'Result'],
                        'rows' => [
                            [1, 'Ramos, Bea L.', 'APP-LAW-2026-001', '95.0', '1st', $passed],
                        ],
                        'note' => 'Sample data — real data needs a student-level export, which admission_stats does not provide.',
                    ],
                    [
                        'key' => 'ipace-exam', 'title' => 'iPACE — Exam Passers', 'icon' => 'bi-laptop', 'tone' => 'info',
                        'description' => 'Applicants accepted into the iPACE (online / asynchronous) program, with course and schedule track.',
                        'recipient' => 'iPACE Head',
                        'columns' => ['#', 'Name', 'App No.', 'Course', 'Schedule Track', 'Result'],
                        'rows' => [
                            [1, 'Aquino, Leo T.', 'APP-IPACE-2026-001', 'BS Information Technology', 'Asynchronous', $passed],
                        ],
                        'note' => 'Sample data — real data needs a student-level export, which admission_stats does not provide.',
                    ],
                    [
                        'key' => 'scholars', 'title' => 'Scholars — IATO vs LAMP Cross-Check', 'icon' => 'bi-award', 'tone' => 'danger',
                        'description' => 'Compares IATO scholar records against LAMP Office data — flags discrepancies in names, IDs, or status.',
                        'recipient' => 'LAMP Office',
                        'columns' => ['#', 'Name', 'IATO Record', 'LAMP Record', 'Match'],
                        'rows' => [
                            [1, 'Torres, Nicole A.', 'DLSL Scholar', 'DLSL Scholar', ['Match', 'primary']],
                        ],
                        'note' => 'Sample data — real data needs a student-level export, which admission_stats does not provide.',
                    ],
                ],
            ],
            [
                'title' => 'Institutional Reports',
                'description' => 'Enrollment trends and per-program summaries, computed from imported admission data',
                'reports' => [
                    [
                        'key' => 'trends', 'title' => 'Enrollment Trends — Executive Office', 'icon' => 'bi-graph-up-arrow', 'tone' => 'danger',
                        'description' => 'Year-on-year applicant comparison across all levels/programs: total this SY vs last SY, with % change per level/program.',
                        'recipient' => 'Executive Office',
                        'columns' => ['Level / Program', 'Previous SY', 'Current SY', 'Change', '% Change'],
                        'rows' => $rows->map(function (AdmissionStat $row) {
                            $previous = (int) ($row->overall_total_previous ?? 0);
                            $current = (int) ($row->overall_total_current ?? 0);
                            $change = $current - $previous;
                            $pct = $row->overall_pct_change;
                            $pctLabel = $pct === null ? 'N/A' : ($pct >= 0 ? '+' : '') . number_format($pct, 1) . '%';
                            $tone = $pct === null ? 'secondary' : ($pct >= 0 ? 'primary' : 'danger');

                            return [
                                $row->level_program,
                                number_format($previous),
                                number_format($current),
                                ($change >= 0 ? '+' : '') . number_format($change),
                                [$pctLabel, $tone],
                            ];
                        })->values()->all(),
                        'note' => $rows->isEmpty() ? 'No admission data imported yet.' : 'Computed from the latest imported admission data.',
                    ],
                    [
                        'key' => 'dept', 'title' => 'Enrolled vs Target per Level/Program', 'icon' => 'bi-person-video3', 'tone' => 'info',
                        'description' => 'Officially enrolled students per level/program compared against the reservation target (regular applicants only).',
                        'recipient' => 'Dean / Program Chair',
                        'columns' => ['Level / Program', 'Enrolled', 'Target (approx.)', '% Filled'],
                        'rows' => $rows->map(function (AdmissionStat $row) {
                            $enrolled = (int) ($row->officially_enrolled ?? 0);
                            $target = (int) ($row->remaining_slots_mid ?? 0) + (int) ($row->reserved_regular_current ?? 0);
                            $pctFilled = $target > 0 ? number_format(($enrolled / $target) * 100, 1) . '%' : 'N/A';

                            return [$row->level_program, number_format($enrolled), number_format($target), $pctFilled];
                        })->values()->all(),
                        'note' => 'Target is approximated from remaining slots (mid target) plus already-reserved regular applicants.',
                    ],
                ],
            ],
        ];

        $history = [
            ['report' => 'trends', 'name' => 'Enrollment Trends Report', 'meta' => 'All levels/programs', 'recipient' => 'Executive Office', 'period' => 'Current SY', 'format' => 'PDF', 'by' => '—', 'at' => 'Not yet generated'],
        ];

        return [
            'sections' => $sections,
            'history' => $history,
            'periods' => ['Current School Year', 'Previous School Year'],
        ];
    }
}