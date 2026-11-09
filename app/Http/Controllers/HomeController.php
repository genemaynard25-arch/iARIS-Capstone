<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ImportBatch;
use App\Models\User;
use App\Support\AdmissionStats;
use App\Support\ApplicantRecords;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    /**
     * Admin dashboard, from the database: AdmissionStats (the totals, same as Analytics, both
     * computed live from applicants), applicants (this week's chart, recent applicants, pending count), import_batches and
     * users (notifications). Applicants by Location has no data in the database yet.
     *
     * 'tone' is a Bootstrap colour name (primary, info, warning, danger); the
     * views turn it into classes like bg-warning-subtle text-warning-emphasis.
     */
    public function index()
    {
        // LAMP Office, SHS principal, Chancellor, Dean and Program Chair users have their own dashboards. Every sign-in
        // ends up here (login -> splash -> /home), so this sends them to the right place.
        if (auth()->user()->isLamp()) {
            return redirect()->route('lamp.dashboard');
        }
        if (auth()->user()->role === 'registrar') {
            return redirect()->route('registrar.dashboard');
        }
        if (auth()->user()->isShsPrincipal()) {
            return redirect()->route('shs.dashboard');
        }
        if (auth()->user()->isExecutive()) {
            return redirect()->route('executive.dashboard');
        }
        if (auth()->user()->isToic()) {
            return redirect()->route('toic.dashboard');
        }
        if (auth()->user()->isDirector()) {
            return redirect()->route('director.dashboard');
        }
        if (auth()->user()->isPrincipal()) {
            return redirect()->route('principal.dashboard');
        }
        if (auth()->user()->isDeanOrChair()) {
            return redirect()->route('college.dashboard');
        }

        return view('home', [
            'academicYear' => ApplicantRecords::academicYear(),
            'periodLabel' => 'Week of ' . now()->startOfWeek()->format('M j') . '–' . now()->endOfWeek()->format('M j, Y'),
            'stats' => $this->stats(),
            'weekChart' => $this->applicationsThisWeek(),
            'recentApplicants' => $this->recentApplicants(),
            'notifications' => $this->notifications(),
            'staffStrip' => $this->staffStrip(),
        ]);
    }

    /**
     * IATO Staff only: their Temporary Admin access (if any) and their submissions.
     * null for everyone else.
     */
    private function staffStrip(): ?array
    {
        $user = auth()->user();
        if ($user->role !== 'iato_staff') {
            return null;
        }

        $tempAdmin = \App\Models\TempAdmin::active()->where('user_id', $user->id)->with('grantedBy')->latest('starts_at')->first();

        return [
            'tempAdmin' => $tempAdmin ? [
                'until' => $tempAdmin->expires_at->format('M j, Y · g:i A'),
                'by' => $tempAdmin->grantedBy?->name ?? 'the Super Admin',
                'since' => $tempAdmin->starts_at->format('M j'),
            ] : null,
            'submissions' => SubmissionsController::counts(),
        ];
    }

    /**
     * The four cards. Totals come from App\Support\AdmissionStats (the same numbers as Analytics);
     * "Pending Applications" counts the applicants table rows still pooling / not submitted.
     */
    private function stats(): array
    {
        $summary = AdmissionStats::summary();
        $total = $summary['overallCurrent'];
        $previous = $summary['overallPrevious'];
        $reserved = AdmissionStats::byLevel()->sum('reserved');
        $enrolled = $summary['enrolledCurrent'];
        $change = $total - $previous;
        $pending = Applicant::whereNull('archived_at')->whereIn('application_status', ['pooling', 'not_submitted'])->count();
        $addedThisWeek = Applicant::where('created_at', '>=', now()->startOfWeek())->count();
        $none = $total === 0;

        return [
            [
                'label' => 'Total Applicants',
                'value' => $total,
                'change' => $none ? 'No admission data imported yet' : ($previous ? ($change >= 0 ? '+' : '') . number_format($change) . ' vs last SY' : 'No previous SY to compare'),
                'trend' => ! $none && $previous && $change > 0 ? 'up' : 'neutral',
                'tag' => 'All levels',
                'tone' => 'primary',
            ],
            [
                'label' => 'Paid Reservations',
                'value' => $reserved,
                'change' => 'of ' . number_format($total) . ' applicants',
                'trend' => 'neutral',
                'tag' => ($total ? number_format($reserved / $total * 100, 1) : '0.0') . '% conversion',
                'tone' => 'info',
            ],
            [
                'label' => 'Enrolled',
                'value' => $enrolled,
                'change' => 'Officially enrolled',
                'trend' => 'neutral',
                'tag' => 'Active enrollees',
                'tone' => 'primary',
            ],
            [
                'label' => 'Pending Applications',
                'value' => $pending,
                'change' => '+' . number_format($addedThisWeek) . ' applicants added this week',
                'trend' => $addedThisWeek ? 'up' : 'neutral',
                'tag' => 'Needs action',
                'tone' => 'warning',
            ],
        ];
    }

    /** Applicants added per day this week (Mon–Sun), College vs Integrated School, from the applicants table. */
    private function applicationsThisWeek(): array
    {
        $start = now()->startOfWeek();
        $counts = Applicant::where('created_at', '>=', $start)
            ->whereIn('level', ['college', 'is'])
            ->get(['level', 'created_at'])
            ->groupBy(fn ($a) => $a->level . '|' . $a->created_at->format('Y-m-d'))
            ->map->count();
        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        return [
            'labels' => $days->map(fn ($d) => $d->format('D'))->all(),
            'college' => $days->map(fn ($d) => $counts['college|' . $d->format('Y-m-d')] ?? 0)->all(),
            'is' => $days->map(fn ($d) => $counts['is|' . $d->format('Y-m-d')] ?? 0)->all(),
        ];
    }

    /** The five newest applicants in the applicants table. */
    private function recentApplicants(): array
    {
        $tones = ['Pending' => 'danger', 'For Exam' => 'warning', 'Passed' => 'primary', 'Not Qualified' => 'secondary', 'Paid' => 'info'];

        $paid = ApplicantRecords::paidFees('reservation_fee');

        return Applicant::whereNull('archived_at')->latest()->take(5)->get()
            ->map(function (Applicant $a) use ($tones, $paid) {
                $status = ApplicantRecords::status($a, isset($paid[$a->id]));
                $program = $a->program_or_track ?: '—';

                return [
                    'name' => "{$a->last_name}, {$a->first_name}",
                    'reference' => $a->reference_number,
                    'program' => $a->level === 'is' ? "IS – {$program}" : $program,
                    'status' => $status,
                    'tone' => $tones[$status],
                    'updated' => ($a->updated_at ?? $a->created_at)?->format('M j, g:i A') ?? '—',
                ];
            })->all();
    }

    /**
     * Real events, newest first, grouped by day: Excel imports (import_batches)
     * and account requests waiting for approval (users with status Pending).
     */
    private function notifications(): array
    {
        $imports = ImportBatch::latest('created_at')->take(5)
            ->withCount(['applicants as saved_count'])
            ->get()->map(fn (ImportBatch $b) => [
            'at' => Carbon::parse($b->created_at),
            'icon' => $b->status === 'failed' ? 'bi-exclamation-triangle' : 'bi-cloud-arrow-up',
            'tone' => $b->status === 'failed' ? 'danger' : 'info',
            'title' => match ($b->status) { 'failed' => 'Data import failed', 'processing' => 'Data import in progress', default => 'Data import completed' },
            'body' => number_format($b->saved_count) . ' records from ' . $b->original_filename,
        ]);

        // Only admins can approve accounts, so only they hear about requests
        $requests = ! auth()->user()->isAdmin() ? collect() : User::where('status', 'Pending')->latest()->take(5)->get()->map(fn (User $u) => [
            'at' => $u->created_at ?? now(),
            'icon' => 'bi-person-plus',
            'tone' => 'warning',
            'title' => 'Account access request',
            'body' => "{$u->name} is waiting for approval",
        ]);

        return $imports->concat($requests)
            ->sortByDesc('at')
            ->take(6)
            ->groupBy(fn ($n) => $n['at']->isToday() ? 'Today' : ($n['at']->isYesterday() ? 'Yesterday' : $n['at']->format('M j')))
            ->map(fn ($items) => $items->map(fn ($n) => $n + ['time' => $n['at']->format('g:i A')])->values()->all())
            ->all();
    }
}
