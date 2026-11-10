<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Support\AdmissionStats;
use App\Support\AnalyticsFilterData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    // Reports is now a tab of the Analytics & Reports page. Old links to /reports land on that tab.
    public function index()
    {
        return redirect('/analytics?tab=reports');
    }

    // Real PDF, built from the same data() below - one of the Admissions/Institutional Reports
    // templates, not the Analytics tab's free-form report builder (still front end only; see
    // resources/views/analytics/partials/reports-script.blade.php).
    public function pdf(string $key)
    {
        $validated = request()->validate(['period' => 'nullable|in:Current School Year,Previous School Year']);
        $data = $this->data($validated['period'] ?? 'Current School Year');
        $report = collect($data['sections'])
            ->pluck('reports')->flatten(1)->firstWhere('key', $key);

        abort_unless($report, 404);

        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'schoolYear' => $data['schoolYear'],
            'generatedBy' => auth()->user()->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(Str::slug($report['title']) . '.pdf');
    }

    // The report templates, history and periods. AnalyticsController passes these to the Reports tab.
    // NOTE: the "Admissions Reports" section below has real applicant names/scores — the Reports
    // tab is NOT safe for the AI widget to read (see AnalyticsController::index()'s aggregateOnlyPage).
    public function data(string $period = 'Current School Year'): array
    {
        $schoolYear = $period === 'Previous School Year' ? AdmissionStats::previousSchoolYear() : AdmissionStats::currentSchoolYear();
        $byLevel = AdmissionStats::byLevel();
        if ($period === 'Previous School Year') {
            $feed = AnalyticsFilterData::make();
            $yearIndex = array_search($schoolYear, $feed['years'], true);
            $previousYear = $yearIndex === false ? null : ($feed['years'][$yearIndex + 1] ?? null);
            $byLevel = collect(AdmissionStats::LEVEL_LABELS)->map(function ($label, $level) use ($feed, $schoolYear, $previousYear) {
                $current = collect($feed['rows'])->where('year', $schoolYear)->where('department', $label);
                $previous = $previousYear ? collect($feed['rows'])->where('year', $previousYear)->where('department', $label)->sum('applicants') : 0;
                $count = $current->sum('applicants');
                return ['level_program' => $label, 'level' => $level, 'current' => $count, 'previous' => $previous,
                    'pct_change' => $previous ? ($count - $previous) / $previous * 100 : null,
                    'enrolled' => $current->sum('enrolled'), 'reserved' => $current->sum('reserved')];
            })->values();
        }
        $passed = ['Passed', 'primary'];

        // Exam passers for one level, in the report's [#, Name, App No., ...extra columns..., Result] shape.
        // $extra maps each applicant to the columns between App No. and Result.
        $passersFor = function (string $level) use ($schoolYear): \Illuminate\Support\Collection {
            if (! $schoolYear) {
                return collect();
            }

            return Applicant::where('level', $level)
                ->where('school_year', $schoolYear)
                ->whereNull('archived_at')
                ->whereIn('admission_test_status', ['passed', 'qualified_other_degree', 'reconsidered'])
                ->get();
        };

        $isPassers = $passersFor('is');
        $collegePassers = $passersFor('college');
        $gradPassers = $passersFor('graduate_school');
        $eteeapPassers = $passersFor('eteeap');

        $resultLabel = fn (Applicant $a) => [\App\Support\ApplicantRecords::testLabel($a->admission_test_status), 'primary'];
        $name = fn (Applicant $a) => "{$a->last_name}, {$a->first_name}";

        $sections = [
            [
                'title' => 'Admissions Reports',
                'description' => 'Entrance exam results per academic unit, for the selected school year'
                    . ($schoolYear ? " ({$schoolYear})" : '') . ', computed from your imported applicants',
                'reports' => [
                    [
                        'key' => 'is-exam', 'title' => 'Integrated School — Exam Passers', 'icon' => 'bi-building', 'tone' => 'primary',
                        'description' => 'IS applicants (K–12) who passed the DLSL entrance exam, with grade level and strand/track.',
                        'recipient' => 'IS Registrar',
                        'columns' => ['#', 'Name', 'App No.', 'Grade/Sub-Level', 'Strand / Track', 'Result'],
                        'rows' => $isPassers->values()->map(fn (Applicant $a, $i) => [
                            $i + 1, $name($a), $a->reference_number,
                            \App\Support\ApplicantRecords::IS_SUB_LEVELS[$a->sub_level] ?? '—',
                            $a->program_or_track ?: '—', $resultLabel($a),
                        ])->all(),
                        'note' => $isPassers->isEmpty() ? 'No IS exam passers in the current school year yet.' : null,
                    ],
                    [
                        'key' => 'college-exam', 'title' => 'College — Exam Passers', 'icon' => 'bi-mortarboard', 'tone' => 'primary',
                        'description' => 'College applicants who passed the DLSL entrance exam, organized by college and program.',
                        'recipient' => 'College Registrar',
                        'columns' => ['#', 'Name', 'App No.', 'College', 'Program', 'Result'],
                        'rows' => $collegePassers->values()->map(fn (Applicant $a, $i) => [
                            $i + 1, $name($a), $a->reference_number, $a->college ?: '—', $a->program_or_track ?: '—', $resultLabel($a),
                        ])->all(),
                        'note' => $collegePassers->isEmpty() ? 'No College exam passers in the current school year yet.' : null,
                    ],
                    [
                        'key' => 'grad-exam', 'title' => 'Graduate Programs — Exam Passers', 'icon' => 'bi-journal-bookmark', 'tone' => 'info',
                        'description' => "Applicants who passed for Graduate Programs (Master's / Doctoral).",
                        'recipient' => 'Graduate School Head',
                        'columns' => ['#', 'Name', 'App No.', 'Program', 'Result'],
                        'rows' => $gradPassers->values()->map(fn (Applicant $a, $i) => [
                            $i + 1, $name($a), $a->reference_number, $a->program_or_track ?: '—', $resultLabel($a),
                        ])->all(),
                        'note' => $gradPassers->isEmpty() ? 'No Graduate Programs exam passers in the current school year yet.' : null,
                    ],
                    [
                        'key' => 'eteeap-exam', 'title' => 'ETEEAP — Exam Passers', 'icon' => 'bi-briefcase', 'tone' => 'info',
                        'description' => 'ETEEAP applicants who passed / were cleared on their competency assessment.',
                        'recipient' => 'ETEEAP Head',
                        'columns' => ['#', 'Name', 'App No.', 'Program', 'Result'],
                        'rows' => $eteeapPassers->values()->map(fn (Applicant $a, $i) => [
                            $i + 1, $name($a), $a->reference_number, $a->program_or_track ?: '—', $resultLabel($a),
                        ])->all(),
                        'note' => $eteeapPassers->isEmpty() ? 'No ETEEAP exam passers in the current school year yet.' : null,
                    ],
                    [
                        'key' => 'law-exam', 'title' => 'College of Law — Exam Passers', 'icon' => 'bi-bank2', 'tone' => 'warning',
                        'description' => 'Applicants who passed the Law Aptitude Examination.',
                        'recipient' => 'College of Law Head',
                        'columns' => ['#', 'Name', 'App No.', 'Score', 'Rank', 'Result'],
                        'rows' => [
                            [1, 'Ramos, Bea L.', 'APP-LAW-2026-001', '95.0', '1st', $passed],
                        ],
                        'note' => 'Sample data — College of Law applicants aren\'t imported yet (still placeholder records, see Records → College of Law).',
                    ],
                    [
                        'key' => 'ipace-exam', 'title' => 'iPACE — Exam Passers', 'icon' => 'bi-laptop', 'tone' => 'info',
                        'description' => 'Applicants accepted into the iPACE (online / asynchronous) program.',
                        'recipient' => 'iPACE Head',
                        'columns' => ['#', 'Name', 'App No.', 'Course', 'Schedule Track', 'Result'],
                        'rows' => [
                            [1, 'Aquino, Leo T.', 'APP-IPACE-2026-001', 'BS Information Technology', 'Asynchronous', $passed],
                        ],
                        'note' => 'Sample data — iPACE applicants aren\'t imported yet (still placeholder records, see Records → iPACE).',
                    ],
                    [
                        'key' => 'scholars', 'title' => 'Scholars — IATO vs LAMP Cross-Check', 'icon' => 'bi-award', 'tone' => 'danger',
                        'description' => 'Applicants who checked Yes for a scholarship, compared against LAMP Office data.',
                        'recipient' => 'LAMP Office',
                        'columns' => ['#', 'Name', 'Exam Result', 'LAMP Status'],
                        'rows' => ($schoolYear ? Applicant::where('school_year', $schoolYear)->whereNull('archived_at')->where('applying_for_scholarship', true)->get() : collect())
                            ->values()->map(fn (Applicant $a, $i) => [
                                $i + 1, $name($a), [\App\Support\ApplicantRecords::testLabel($a->admission_test_status), 'primary'], 'Pending Check',
                            ])->all(),
                        'note' => 'The specific scholarship type is LAMP\'s own call (see Scholar Records) — this report only tracks Yes/No + exam result, same as the Scholars page.',
                    ],
                ],
            ],
            [
                'title' => 'Institutional Reports',
                'description' => 'Enrollment trends and per-level summaries, computed from your imported applicants',
                'reports' => [
                    [
                        'key' => 'trends', 'title' => 'Enrollment Trends — Executive Office', 'icon' => 'bi-graph-up-arrow', 'tone' => 'danger',
                        'description' => 'Year-on-year applicant comparison across all levels: total this SY vs last SY, with % change per level.',
                        'recipient' => 'Executive Office',
                        'columns' => ['Level', 'Previous SY', 'Current SY', 'Change', '% Change'],
                        'rows' => $byLevel->map(function (array $row) {
                            $change = $row['current'] - $row['previous'];
                            $pctLabel = $row['pct_change'] === null ? 'N/A' : ($row['pct_change'] >= 0 ? '+' : '') . number_format($row['pct_change'], 1) . '%';
                            $tone = $row['pct_change'] === null ? 'secondary' : ($row['pct_change'] >= 0 ? 'primary' : 'danger');

                            return [
                                $row['level_program'],
                                number_format($row['previous']),
                                number_format($row['current']),
                                ($change >= 0 ? '+' : '') . number_format($change),
                                [$pctLabel, $tone],
                            ];
                        })->values()->all(),
                        'note' => $byLevel->isEmpty() ? 'No admission data imported yet.' : 'Computed from your imported applicants.',
                    ],
                    [
                        'key' => 'dept', 'title' => 'Enrolled vs Reserved per Level', 'icon' => 'bi-person-video3', 'tone' => 'info',
                        'description' => 'Officially enrolled students per level compared against how many paid their reservation fee.',
                        'recipient' => 'Dean / Program Chair',
                        'columns' => ['Level', 'Enrolled', 'Paid Reservation', '% Converted'],
                        'rows' => $byLevel->map(function (array $row) {
                            $pct = $row['reserved'] > 0 ? number_format(($row['enrolled'] / $row['reserved']) * 100, 1) . '%' : 'N/A';

                            return [$row['level_program'], number_format($row['enrolled']), number_format($row['reserved']), $pct];
                        })->values()->all(),
                        'note' => 'There\'s no enrollment target set anywhere in iARIS yet — this compares actual enrolled vs actual paid-reservation counts, not a target.',
                    ],
                ],
            ],
        ];

        if ($period === 'Previous School Year') {
            foreach ($sections[0]['reports'] as &$report) {
                if (in_array($report['key'], ['law-exam', 'ipace-exam'])) {
                    $report['rows'] = [];
                    $report['note'] = 'Historical data for this preview report is unavailable.';
                } elseif (empty($report['rows'])) {
                    $report['note'] = $schoolYear ? 'No matching records in the selected school year.' : 'No previous school year has been imported.';
                }
            }
            unset($report);
        }

        $history = [
            ['report' => 'trends', 'name' => 'Enrollment Trends Report', 'meta' => 'All levels/programs', 'recipient' => 'Executive Office', 'period' => 'Current SY', 'format' => 'PDF', 'by' => '—', 'at' => 'Not yet generated'],
        ];

        return [
            'schoolYear' => $schoolYear,
            'sections' => $sections,
            'history' => $history,
            'periods' => ['Current School Year', 'Previous School Year'],
        ];
    }
}
