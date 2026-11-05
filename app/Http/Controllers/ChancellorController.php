<?php

namespace App\Http\Controllers;

use App\Models\CachedInsight;

class ChancellorController extends Controller
{
    /**
     * President / Chancellor dashboard: a read-only, university-wide view.
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

    /**
     * President / Chancellor analytics: the same university-wide numbers, per unit,
     * with charts the user can filter by unit.
     *
     * Placeholder data. Each unit's numbers add up to the dashboard's totals
     * (3,294 applicants, 842 enrolled, 1,019 paid in AY 2025–26).
     * 'applicants' and 'enrolled' have one number per entry in 'years' (oldest first).
     * 'took', 'passed', 'paid' are this AY; 'paidPrevious' is last AY.
     */
    public function analytics()
    {
        return view('chancellor.analytics', [
            'academicYear' => '2025–2026',
            'years' => ['AY 2022–23', 'AY 2023–24', 'AY 2024–25', 'AY 2025–26'],

            'units' => [
                'College' => ['short' => 'College', 'applicants' => [1400, 1493, 1562, 1676], 'enrolled' => [338, 382, 417, 437],
                    'took' => 1240, 'passed' => 1050, 'paid' => 495, 'paidPrevious' => 472, 'confidence' => 'High'],
                'Integrated School' => ['short' => 'Int. School', 'applicants' => [640, 668, 694, 742], 'enrolled' => [160, 172, 180, 190],
                    'took' => 598, 'passed' => 505, 'paid' => 254, 'paidPrevious' => 241, 'confidence' => 'Medium'],
                'Graduate Programs' => ['short' => 'Graduate', 'applicants' => [226, 244, 262, 284], 'enrolled' => [52, 58, 62, 66],
                    'took' => 228, 'passed' => 186, 'paid' => 98, 'paidPrevious' => 91, 'confidence' => 'High'],
                'College of Law' => ['short' => 'Law', 'applicants' => [142, 156, 168, 186], 'enrolled' => [36, 40, 43, 47],
                    'took' => 152, 'passed' => 120, 'paid' => 56, 'paidPrevious' => 52, 'confidence' => 'Medium'],
                'iPACE' => ['short' => 'iPACE', 'applicants' => [150, 196, 252, 312], 'enrolled' => [40, 52, 66, 80],
                    'took' => 207, 'passed' => 182, 'paid' => 92, 'paidPrevious' => 74, 'confidence' => 'Medium'],
                'ETEEAP' => ['short' => 'ETEEAP', 'applicants' => [52, 63, 78, 94], 'enrolled' => [14, 18, 20, 22],
                    'took' => 61, 'passed' => 51, 'paid' => 24, 'paidPrevious' => 19, 'confidence' => 'Medium'],
            ],

            'colleges' => ['CITE' => 512, 'CBEAM' => 398, 'CEAS' => 296, 'CON' => 268, 'CIHTM' => 202],

            // Same keys as the Analytics page's AI insights (AnalyticsController), so real
            // AI output could replace these later. 'tone' is a Bootstrap colour.
            'insights' => [
                ['type' => 'Growth', 'icon' => 'bi-graph-up-arrow', 'tone' => 'primary', 'title' => 'Adult-learner programs are surging',
                    'stat' => '+108%', 'statLabel' => 'iPACE applicants since AY 2022–23',
                    'body' => 'iPACE grew from 150 to 312 applicants. ETEEAP is up 81% over the same period (52 to 94). Both are still small shares of the total.',
                    'tags' => ['iPACE', 'ETEEAP', '3-year trend']],
                ['type' => 'Alert', 'icon' => 'bi-exclamation-triangle', 'tone' => 'danger', 'title' => 'Conversion dipped for the first time in four years',
                    'stat' => '25.6%', 'statLabel' => 'applied-to-enrolled, down from 26.1%',
                    'body' => 'Conversion rose from 24.5% to 25.6% to 26.1% before slipping to 25.6%. Applicants grew 9.2% but enrollment grew 6.9%.',
                    'tags' => ['Institution-wide', 'Conversion']],
                ['type' => 'Warning', 'icon' => 'bi-funnel', 'tone' => 'warning', 'title' => 'The biggest drop-off comes after passing the exam',
                    'stat' => '51.3%', 'statLabel' => 'of exam passers did not pay a reservation',
                    'body' => '1,075 of 2,094 applicants who passed did not complete payment, the largest loss at any funnel stage.',
                    'tags' => ['Funnel', 'Pass to Paid']],
                ['type' => 'Positive', 'icon' => 'bi-check-circle', 'tone' => 'info', 'title' => 'Integrated School converts applicants best',
                    'stat' => '34.2%', 'statLabel' => 'applied-to-paid, vs 30.9% institution-wide',
                    'body' => 'Integrated School turns 254 of 742 applicants into paid reservations, the strongest rate among all units.',
                    'tags' => ['Integrated School', 'Conversion']],
                ['type' => 'Neutral', 'icon' => 'bi-pie-chart', 'tone' => 'secondary', 'title' => 'College share of applicants is edging down',
                    'stat' => '50.9%', 'statLabel' => 'of all applicants, from 53.6% in AY 2022–23',
                    'body' => 'College volume still grew 19.7% over three years, but faster growth in other units is lowering its share.',
                    'tags' => ['College', 'Share of total']],
                ['type' => 'Growth', 'icon' => 'bi-bank', 'tone' => 'primary', 'title' => 'College of Law applications rose three years in a row',
                    'stat' => '+31.0%', 'statLabel' => 'since AY 2022–23',
                    'body' => 'Law applicants went 142 → 156 → 168 → 186. This is steady growth rather than a one-year spike.',
                    'tags' => ['College of Law', '3-year trend']],
            ],
        ]);
    }
}
