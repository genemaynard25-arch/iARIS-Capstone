<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;

class ShsController extends Controller
{
    /**
     * SHS principal's dashboard: the admin dashboard, but only Senior High.
     *
     * Placeholder data, same idea as HomeController. The stats, stat cards and
     * notifications use the same keys as the admin dashboard, so this page reuses
     * its partials (home/partials/stats, recent-applicants, notifications).
     * The recent applicants are the Senior High people from ApplicantController::sample().
     */
    public function dashboard()
    {
        $statusTones = ['Pending' => 'danger', 'For Exam' => 'warning', 'Paid' => 'info', 'Enrolled' => 'primary'];

        $recent = collect(ApplicantController::sample())
            ->whereIn('level', ['Grade 11', 'Grade 12'])
            ->sortByDesc('updated')
            ->map(fn ($a) => [
                'name' => "{$a['last']}, {$a['first']} {$a['mi']}.",
                'reference' => $a['id'],
                'program' => "{$a['level']} – {$a['program']}",
                'status' => $a['status'],
                'tone' => $statusTones[$a['status']],
                'updated' => Carbon::parse($a['updated'])->format('M j, g:i A'),
            ])
            ->values()
            ->all();

        return view('shs.dashboard', [
            'academicYear' => '2025–2026',
            'periodLabel' => 'Week of June 8–14, 2026',

            'stats' => [
                ['label' => 'SHS Applicants', 'value' => 742, 'change' => '+34 this week', 'trend' => 'up', 'tag' => 'Grades 11 & 12', 'tone' => 'primary'],
                ['label' => 'Paid Reservations', 'value' => 398, 'change' => 'of 742 applicants', 'trend' => 'neutral', 'tag' => '53.6% conversion', 'tone' => 'info'],
                ['label' => 'Enrolled', 'value' => 351, 'change' => '+19 this week', 'trend' => 'up', 'tag' => 'Active enrollees', 'tone' => 'primary'],
                ['label' => 'Slots Remaining', 'value' => 89, 'change' => 'across all strands', 'trend' => 'neutral', 'tag' => 'Monitor closely', 'tone' => 'warning'],
            ],

            // Applications per day this week, by grade
            'weekly' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                'Grade 11' => [22, 28, 30, 34, 26, 18],
                'Grade 12' => [10, 12, 14, 16, 11, 7],
            ],

            // Applicants per strand, and the slots each strand has
            'strands' => [
                ['code' => 'STEM', 'name' => 'Science, Technology, Engineering & Math', 'applicants' => 228, 'slots' => 240],
                ['code' => 'ABM', 'name' => 'Accountancy, Business & Management', 'applicants' => 178, 'slots' => 200],
                ['code' => 'HUMSS', 'name' => 'Humanities & Social Sciences', 'applicants' => 140, 'slots' => 160],
                ['code' => 'TVL', 'name' => 'Technical-Vocational-Livelihood', 'applicants' => 112, 'slots' => 140],
                ['code' => 'GAS', 'name' => 'General Academic Strand', 'applicants' => 84, 'slots' => 120],
            ],

            'recentApplicants' => $recent,

            'notifications' => [
                'Today, 8:00 AM' => [
                    ['icon' => 'bi-file-earmark-text', 'tone' => 'primary', 'title' => 'New SHS application', 'body' => 'Soriano, Andrea P. applied for Grade 11 – ABM'],
                ],
                'Yesterday, 3:45 PM' => [
                    ['icon' => 'bi-exclamation-triangle', 'tone' => 'warning', 'title' => 'STEM strand nearing capacity', 'body' => '228 of 240 slots filled — monitor closely'],
                ],
                'Jun 7, 9:12 AM' => [
                    ['icon' => 'bi-bar-chart-line', 'tone' => 'info', 'title' => 'Weekly report ready', 'body' => 'SHS admissions summary for Jun 1–7 is now available'],
                ],
                'Jun 6, 2:30 PM' => [
                    ['icon' => 'bi-clock-history', 'tone' => 'danger', 'title' => 'Pending reservation reminder', 'body' => 'Dimaculangan, Rafael S. has an unpaid reservation'],
                ],
            ],
        ]);
    }
}
