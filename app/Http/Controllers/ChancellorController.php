<?php

namespace App\Http\Controllers;

use App\Models\CachedInsight;

class ChancellorController extends Controller
{
    /**
     * Chancellor / President dashboard: a read-only, university-wide view.
     *
     * Placeholder data, same idea as the LAMP and SHS dashboards. Later the
     * numbers would come from the admission_stats table (the same data the
     * Analytics page uses). The view only reads these variables.
     *
     * The stat cards' "vs last AY" changes, the unit table's changes and shares,
     * and the funnel percentages are all worked out in the view from these numbers,
     * so they always add up.
     */
    public function dashboard()
    {
        // "Key Takeaways": the AI insights the Analytics page already saved
        // (its "Re-analyze" button), or these examples until someone runs it.
        $takeaways = CachedInsight::where('key', 'analytics')->first()?->payload ?? [
            ['icon' => 'bi-graph-up-arrow', 'tone' => 'primary', 'title' => 'Adult-learner programs are growing fastest',
                'body' => 'iPACE (+23.8%) and ETEEAP (+20.5%) lead growth, though together they are still only 12.4% of all applicants.'],
            ['icon' => 'bi-funnel', 'tone' => 'warning', 'title' => 'Conversion slipped slightly',
                'body' => 'Applicants grew 9.2% but enrollment grew 6.9%, so applied-to-enrolled conversion moved from 26.1% to 25.6%.'],
            ['icon' => 'bi-mortarboard', 'tone' => 'info', 'title' => 'CITE is the largest college',
                'body' => 'CITE has 512 applicants, about 30.5% of all College applicants.'],
        ];

        return view('chancellor.dashboard', [
            'academicYear' => '2025–2026',

            // Whole university, one entry per academic year (oldest first; the last is this year)
            'years' => [
                ['label' => 'AY 2022–23', 'applicants' => 2610, 'paid' => 801, 'enrolled' => 640],
                ['label' => 'AY 2023–24', 'applicants' => 2820, 'paid' => 878, 'enrolled' => 722],
                ['label' => 'AY 2024–25', 'applicants' => 3016, 'paid' => 949, 'enrolled' => 788],
                ['label' => 'AY 2025–26', 'applicants' => 3294, 'paid' => 1019, 'enrolled' => 842],
            ],

            // Applicants per unit: last AY and this AY
            'units' => [
                ['name' => 'College', 'previous' => 1562, 'current' => 1676],
                ['name' => 'Integrated School', 'previous' => 694, 'current' => 742],
                ['name' => 'Graduate Programs', 'previous' => 262, 'current' => 284],
                ['name' => 'College of Law', 'previous' => 168, 'current' => 186],
                ['name' => 'iPACE', 'previous' => 252, 'current' => 312],
                ['name' => 'ETEEAP', 'previous' => 78, 'current' => 94],
            ],

            // This AY, from application to enrollment
            'funnel' => [
                'Applied' => 3294,
                'Took exam' => 2486,
                'Passed' => 2094,
                'Paid' => 1019,
                'Enrolled' => 842,
            ],

            // College applicants this AY, per college
            'colleges' => ['CITE' => 512, 'CBEAM' => 398, 'CEAS' => 296, 'CON' => 268, 'CIHTM' => 202],

            'takeaways' => $takeaways,

            // Reports the IATO sent to this office (see the Reports page)
            'reports' => [
                ['title' => 'Enrollment Trends Report — AY 2025–26', 'from' => 'Renegado, Randolph', 'date' => 'Jun 12, 2026', 'new' => true],
                ['title' => 'Applicants by Unit — Mid-year', 'from' => 'Renegado, Randolph', 'date' => 'May 30, 2026', 'new' => false],
                ['title' => 'Enrollment Trends Report — AY 2024–25', 'from' => 'Renegado, Randolph', 'date' => 'Jun 14, 2025', 'new' => false],
            ],

            // Who else sees this page. Later: users whose role is 'chancellor' (or president / vice chancellor).
            'executives' => [
                ['name' => 'Br. Edmundo L. Fernandez FSC', 'initials' => 'EF', 'title' => 'President', 'tone' => 'danger'],
                ['name' => 'Dr. Merlin Teodosia C. Suarez', 'initials' => 'MS', 'title' => 'Chancellor', 'tone' => 'primary'],
                ['name' => 'Dr. Tessie Palma', 'initials' => 'TP', 'title' => 'Vice Chancellor for Academics', 'tone' => 'info'],
                ['name' => 'Dr. Sheila Maloles', 'initials' => 'SM', 'title' => 'Vice Chancellor for Research, Innovation & Internationalization', 'tone' => 'secondary'],
                ['name' => 'Mr. Wenifredo Angeles', 'initials' => 'WA', 'title' => 'Vice Chancellor for Mission', 'tone' => 'warning'],
            ],
        ]);
    }
}
