<?php

namespace App\Http\Controllers;

use App\Models\AdmissionStat;

class ReportController extends Controller
{
    public function index()
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
                        'key' => 'trends', 'title' => 'Enrollment Trends — Chancellor / President', 'icon' => 'bi-graph-up-arrow', 'tone' => 'danger',
                        'description' => 'Year-on-year applicant comparison across all levels/programs: total this SY vs last SY, with % change per level/program.',
                        'recipient' => 'Chancellor / President',
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
            ['report' => 'trends', 'name' => 'Enrollment Trends Report', 'meta' => 'All levels/programs', 'recipient' => 'Chancellor / President', 'period' => 'Current SY', 'format' => 'PDF', 'by' => '—', 'at' => 'Not yet generated'],
        ];

        return view('reports.index', [
            'sections' => $sections,
            'history' => $history,
            'periods' => ['Current School Year', 'Previous School Year'],
        ]);
    }
}