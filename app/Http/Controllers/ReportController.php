<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\LampScholarRecord;
use App\Support\AdmissionStats;
use App\Support\AnalyticsFilterData;
use App\Support\ApplicantRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    // Reports is now a tab of the Analytics & Reports page. Old links to /reports land on that tab.
    public function index()
    {
        return redirect('/analytics?tab=reports');
    }

    // Legacy PDF endpoint; saved exports use ReportExportController.
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

        return $pdf->download(Str::slug($report['title']).'.pdf');
    }

    // The report templates, history and periods. AnalyticsController passes these to the Reports tab.
    // NOTE: the "Admissions Reports" section below has real applicant names/scores — the Reports
    // tab is NOT safe for the AI widget to read (see AnalyticsController::index()'s aggregateOnlyPage).
    public function data(string $period = 'Current School Year', ?string $selectedYear = null): array
    {
        $anchor = $selectedYear ?? AdmissionStats::currentSchoolYear();
        $previous = fn ($year) => $year ? ((int) substr($year, 0, 4) - 1).'-'.((int) substr($year, 5, 4) - 1) : null;
        $schoolYear = $period === 'Previous School Year' ? $previous($anchor) : $anchor;
        $priorYear = $previous($schoolYear);
        $feed = AnalyticsFilterData::make();
        $byLevel = collect(AdmissionStats::LEVEL_LABELS)->map(function ($label, $level) use ($feed, $schoolYear, $priorYear) {
            $current = collect($feed['rows'])->where('year', $schoolYear)->where('department', $label);
            $prior = collect($feed['rows'])->where('year', $priorYear)->where('department', $label)->sum('applicants');
            $count = $current->sum('applicants');

            return ['level_program' => $label, 'level' => $level, 'current' => $count, 'previous' => $prior,
                'pct_change' => $prior ? ($count - $prior) / $prior * 100 : null,
                'enrolled' => $current->sum('enrolled'), 'reserved' => $current->sum('reserved')];
        })->values();

        // Exam passers for one level, in the report's [#, Name, App No., ...extra columns..., Result] shape.
        // $extra maps each applicant to the columns between App No. and Result.
        $passersFor = function (string $level) use ($schoolYear): Collection {
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
        $lawPassers = $passersFor('law');
        $ipacePassers = $passersFor('ipace');
        $lampChecks = LampScholarRecord::whereNotNull('decision')->get()->keyBy('applicant_id');

        $resultLabel = fn (Applicant $a) => [ApplicantRecords::testLabel($a->admission_test_status), 'primary'];
        $name = fn (Applicant $a) => "{$a->last_name}, {$a->first_name}";

        $sections = [
            [
                'title' => 'Admissions Reports',
                'description' => 'Entrance exam results per academic unit, for the selected school year'
                    .($schoolYear ? " ({$schoolYear})" : '').', computed from your imported applicants',
                'reports' => [
                    [
                        'key' => 'is-exam', 'title' => 'Integrated School — Exam Passers', 'icon' => 'bi-building', 'tone' => 'primary',
                        'description' => 'IS applicants (K–12) who passed the DLSL entrance exam, with grade level and strand/track.',
                        'recipient' => 'IS Registrar',
                        'columns' => ['#', 'Name', 'App No.', 'Grade/Sub-Level', 'Strand / Track', 'Result'],
                        'rows' => $isPassers->values()->map(fn (Applicant $a, $i) => [
                            $i + 1, $name($a), $a->reference_number,
                            ApplicantRecords::IS_SUB_LEVELS[$a->sub_level] ?? '—',
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
                        'rows' => $lawPassers->values()->map(fn (Applicant $a, $i) => [
                            $i + 1, $name($a), $a->reference_number, 'Not recorded', 'Not recorded', $resultLabel($a),
                        ])->all(),
                        'note' => 'Imported Law passers. Scores and ranks are not collected by the import.',

                    ],
                    [
                        'key' => 'ipace-exam', 'title' => 'iPACE — Exam Passers', 'icon' => 'bi-laptop', 'tone' => 'info',
                        'description' => 'Applicants accepted into the iPACE (online / asynchronous) program.',
                        'recipient' => 'iPACE Head',
                        'columns' => ['#', 'Name', 'App No.', 'Course', 'Schedule Track', 'Result'],
                        'rows' => $ipacePassers->values()->map(fn (Applicant $a, $i) => [
                            $i + 1, $name($a), $a->reference_number, $a->program_or_track ?? 'Not recorded', $a->learning_modalities ?? 'Not recorded', $resultLabel($a),
                        ])->all(),
                        'note' => 'Imported iPACE passers for the selected school year.',

                    ],
                    [
                        'key' => 'scholars', 'title' => 'Scholars — IATO vs LAMP Cross-Check', 'icon' => 'bi-award', 'tone' => 'danger',
                        'description' => 'Applicants who checked Yes for a scholarship, compared against LAMP Office data.',
                        'recipient' => 'LAMP Office',
                        'columns' => ['#', 'Name', 'Exam Result', 'LAMP Status'],
                        'rows' => ($schoolYear ? Applicant::where('school_year', $schoolYear)->whereNull('archived_at')->where('applying_for_scholarship', true)->get() : collect())
                            ->values()->map(fn (Applicant $a, $i) => [
                                $i + 1, $name($a), [ApplicantRecords::testLabel($a->admission_test_status), 'primary'], match ($lampChecks->get($a->id)?->decision) {
                                    'yes' => 'Confirmed scholar', 'no' => 'Not a scholar', default => 'Pending Check'
                                },
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
                            $pctLabel = $row['pct_change'] === null ? 'N/A' : ($row['pct_change'] >= 0 ? '+' : '').number_format($row['pct_change'], 1).'%';
                            $tone = $row['pct_change'] === null ? 'secondary' : ($row['pct_change'] >= 0 ? 'primary' : 'danger');

                            return [
                                $row['level_program'],
                                number_format($row['previous']),
                                number_format($row['current']),
                                ($change >= 0 ? '+' : '').number_format($change),
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
                            $pct = $row['reserved'] > 0 ? number_format(($row['enrolled'] / $row['reserved']) * 100, 1).'%' : 'N/A';

                            return [$row['level_program'], number_format($row['enrolled']), number_format($row['reserved']), $pct];
                        })->values()->all(),
                        'note' => 'There\'s no enrollment target set anywhere in iARIS yet — this compares actual enrolled vs actual paid-reservation counts, not a target.',
                    ],
                ],
            ],
        ];

        $history = ReportExportController::history();

        return [
            'schoolYear' => $schoolYear,
            'sections' => $sections,
            'history' => $history,
            'periods' => ['Current School Year', 'Previous School Year'],
        ];
    }
}
