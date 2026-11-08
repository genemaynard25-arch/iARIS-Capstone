<?php

namespace App\Http\Controllers;

use App\Models\CachedInsight;

class ExecutiveController extends Controller
{
    // The academic years in the placeholder data, oldest first (the last one is this year)
    public const YEARS = ['AY 2022–23', 'AY 2023–24', 'AY 2024–25', 'AY 2025–26'];

    /**
     * Executive Office pages (President, Chancellor, Vice Presidents, Vice Chancellors): a read-only, university-wide view.
     *
     * Placeholder data, same idea as the LAMP and SHS dashboards. Later the
     * numbers would come from the admission_stats table (the same data the
     * Analytics page uses). The view only reads these variables.
     *
     * The stat cards' "vs last AY" changes, the unit table's changes and shares,
     * and the funnel percentages are all worked out in the view from these numbers,
     * so they always add up.
     */
    /**
     * Briefing (the Executive Office home page): a short, read-only summary of the
     * same placeholder numbers as Analytics (self::units()), with "Needs your attention"
     * items and a card per unit. Everything on the page is worked out from these numbers
     * in the view, so it always agrees with Analytics.
     */
    public function dashboard()
    {
        return view('executive.dashboard', [
            'academicYear' => '2025–2026',
            'years' => self::YEARS,
            'units' => self::units(),
            // Paid reservations, all units, one per entry in YEARS (the last two match the units' paidPrevious / paid)
            'paidByYear' => [801, 878, 949, 1019],
            // The 3 newest reports the IATO sent to this office (all of them are on the Reports page)
            'reports' => array_slice(self::received(), 0, 3),
            // For the greeting's honorific (Br., Dr.)
            'executives' => self::executives(),
        ]);
    }

    /**
     * Messages with IATO: this office's own conversations (questions, report requests).
     * Placeholder conversations, front end only. 'status':
     *   waiting   waiting for IATO        answered  IATO answered (needs you)        resolved  closed
     * TODO (backend): a messages table shared with IATO (one inbox on IATO's side for every office).
     */
    public function messages()
    {
        return view('executive.messages', ['threads' => self::threads()]);
    }

    /** Conversations IATO answered (the number on the sidebar's Messages link). */
    public static function answeredMessages(): int
    {
        return collect(self::threads())->where('status', 'answered')->count();
    }

    private static function threads(): array
    {
        $iato = fn ($at, $text) => ['from' => 'iato', 'name' => 'Renegado, Randolph', 'role' => 'Super Admin', 'at' => $at, 'text' => $text];
        $me = fn ($at, $text) => ['from' => 'me', 'at' => $at, 'text' => $text];

        return [
                ['id' => 'MSG-0108', 'topic' => 'Question about the data', 'subject' => 'Why did conversion drop this year?', 'status' => 'answered', 'messages' => [
                    $me('Jun 11, 2026 · 9:15 AM', 'The Briefing says applied-to-enrolled conversion slipped to 25.6%. Which units are driving that?'),
                    $iato('Jun 11, 2026 · 2:40 PM', 'Mostly College and iPACE: their applicants grew faster than enrollment. The biggest loss is passers who have not paid yet. We will add a unit breakdown to the June report.'),
                ]],
                ['id' => 'MSG-0105', 'topic' => 'Request a report', 'subject' => 'Applicants by unit for the Board meeting', 'status' => 'waiting', 'messages' => [
                    $me('Jun 12, 2026 · 8:20 AM', 'Please prepare applicants by unit, this AY against last AY, for the Board meeting on Jun 18.'),
                ]],
                ['id' => 'MSG-0101', 'topic' => 'Something looks wrong', 'subject' => 'ETEEAP numbers in the May report', 'status' => 'resolved', 'messages' => [
                    $me('May 31, 2026 · 10:05 AM', 'The May report shows 91 ETEEAP applicants, but the Briefing showed 94. Which is right?'),
                    $iato('May 31, 2026 · 11:30 AM', 'The report was a May 30 snapshot (91). Three more applied on May 31, so 94 is the current count.'),
                    $me('May 31, 2026 · 11:45 AM', 'Thank you, that explains it.'),
                ]],
        ];
    }

    /**
     * Executive analytics: the same university-wide numbers, per unit,
     * with charts the user can filter by unit.
     *
     * Placeholder data. Each unit's numbers add up to the dashboard's totals
     * (3,294 applicants, 842 enrolled, 1,019 paid in AY 2025–26).
     * 'applicants' and 'enrolled' have one number per entry in 'years' (oldest first).
     * 'took', 'passed', 'paid' are this AY; 'paidPrevious' is last AY.
     */
    public function analytics()
    {
        return view('executive.analytics', [
            'academicYear' => '2025–2026',
            'years' => self::YEARS,

            'units' => self::units(),

            'colleges' => ['CITE' => 512, 'CBEAM' => 398, 'CEAS' => 296, 'CON' => 268, 'CIHTM' => 202],

            'insights' => self::insights(),
        ]);
    }

    /**
     * Executive reports: what the IATO sent (with a reply thread each),
     * and report templates to generate from the same numbers as the Analytics page.
     *
     * Placeholder data. Marking as read, replying and generating only happen in the
     * browser for now (they reset on reload); the backend would save them later.
     */
    public function reports()
    {
        // The Reports page is shared with the College offices (resources/views/reports/inbox.blade.php)
        return view('reports.inbox', [
            'labels' => [
                'office' => 'Executive',
                'subtitle' => auth()->user()->isVice()
                    ? 'Reports IATO sends to your office. Open one to read or download it. Questions go to Messages.'
                    : 'Reports from IATO, and reports you can generate yourself. Questions about a report go to Messages.',
                // No reply thread: questions about a report go to Messages
                'askUrl' => url('/executive/messages'),
                // Vice Presidents and Vice Chancellors read reports but don't generate them
                'canGenerate' => ! auth()->user()->isVice(),
                'unit' => 'Unit', 'all' => 'All Units', 'total' => 'All units', 'wide' => 'institution-wide',
                'role' => auth()->user()->roleInfo()[0], 'scope' => ['All ' . count(self::units()) . ' units', 'Institution-wide'],
                'sharedTitle' => 'Shared with the Executive Office', 'locked' => null,
            ],
            // Replies now live in Messages, so a report is just unread or read
            'received' => array_map(fn ($r) => $r['status'] === 'replied' ? ['status' => 'read'] + $r : $r, self::received()),
            'years' => self::YEARS,
            'units' => self::units(),
            'insights' => self::insights(),
            // The Executive Office: who the reports are shared with, and who you can send a copy to
            'people' => self::executives(),

            // Who sent the reports
            'sender' => ['name' => 'Renegado, Randolph', 'role' => 'Super Admin', 'initials' => 'RR'],

            // Report templates. Options: 'currentYearOnly' (no year choice), 'allUnits' (no unit choice),
            // 'compare' (shows "Compare with the previous AY"), 'alwaysCompare', 'ai' (shows "Include an AI summary").
            'templates' => [
                'trends' => ['name' => 'Enrollment Trends (YoY)', 'icon' => 'bi-graph-up-arrow', 'tone' => 'primary',
                    'description' => 'Applicants, enrolled, and conversion by academic year, with year-over-year change.',
                    'tags' => ['Year-over-year', 'Any unit'], 'compare' => true, 'ai' => true],
                'units' => ['name' => 'Applicants by Unit', 'icon' => 'bi-layers', 'tone' => 'info',
                    'description' => 'Applicants for every unit against the previous year, with each unit\'s share of the total.',
                    'tags' => ['All units', 'Share of total'], 'allUnits' => true, 'alwaysCompare' => true, 'ai' => true],
                'funnel' => ['name' => 'Conversion Funnel', 'icon' => 'bi-funnel', 'tone' => 'warning',
                    'description' => 'Applied, took exam, passed, paid, and enrolled, with the drop at each step.',
                    'tags' => ['Current AY only', 'Any unit'], 'currentYearOnly' => true, 'ai' => true],
                'summary' => ['name' => 'Admissions Summary', 'icon' => 'bi-clipboard-data', 'tone' => 'secondary',
                    'description' => 'Applicants, enrolled, and conversion for each unit in a chosen academic year.',
                    'tags' => ['By unit', 'Any AY'], 'compare' => true, 'ai' => true],
                'insights' => ['name' => 'Institutional Insights Summary', 'icon' => 'bi-stars', 'tone' => 'primary',
                    'description' => 'The AI-generated findings from Analytics, as a written report for leadership.',
                    'tags' => ['AI-generated', 'Descriptive only'], 'currentYearOnly' => true, 'allUnits' => true],
            ],

            // Reports this user generated before. 'year' is an index into YEARS; 'to' lists executive initials (or IATO).
            'history' => [
                ['template' => 'trends', 'year' => 2, 'unit' => '', 'compare' => true, 'ai' => true, 'format' => 'PDF', 'to' => ['EF'], 'at' => 'Jun 14, 2025 · 4:10 PM'],
                ['template' => 'units', 'year' => 2, 'unit' => '', 'compare' => true, 'ai' => false, 'format' => 'Excel', 'to' => [], 'at' => 'Jun 3, 2025 · 10:25 AM'],
            ],
        ]);
    }

    /**
     * Reports the IATO sent to this office, newest first. Also used by the dashboard's
     * "Reports from IATO" card and the sidebar's unread badge.
     * 'status' is new (unread), read or replied. 'highlights' are [label, value, note].
     * In 'thread', 'from' => 'me' is this office; 'iato' is the IATO.
     */
    public static function received(): array
    {
        return [
            [
                'id' => 'r1', 'type' => 'trends', 'typeLabel' => 'Enrollment Trends', 'ay' => 'AY 2025–26',
                'title' => 'Enrollment Trends Report — AY 2025–26',
                'sent' => '2026-06-12 08:00', 'status' => 'new',
                'highlights' => [['Applicants', '3,294', '+9.2% vs last AY'], ['Enrolled', '842', '+6.9% vs last AY'], ['Applied → Enrolled', '25.6%', '0.5 pts lower than last AY']],
                'head' => ['Academic Year', 'Applicants', 'YoY', 'Enrolled', 'YoY', 'Conversion'],
                'rows' => [
                    ['AY 2022–23', '2,610', '—', '640', '—', '24.5%'],
                    ['AY 2023–24', '2,820', '+8.0%', '722', '+12.8%', '25.6%'],
                    ['AY 2024–25', '3,016', '+7.0%', '788', '+9.1%', '26.1%'],
                    ['AY 2025–26', '3,294', '+9.2%', '842', '+6.9%', '25.6%'],
                ],
                'note' => 'Year-over-year change is calculated against the previous academic year.',
                'thread' => [
                    ['from' => 'iato', 'text' => 'Good morning, Dr. Suarez. Attached is the Enrollment Trends Report for AY 2025–26 with year-over-year change across all units. It is also shared with the Executive Office.', 'at' => 'Jun 12, 2026 · 8:00 AM'],
                ],
            ],
            [
                'id' => 'r2', 'type' => 'units', 'typeLabel' => 'Applicants by Unit', 'ay' => 'AY 2025–26',
                'title' => 'Applicants by Unit — AY 2025–26 (as of May 30)',
                'sent' => '2026-05-30 10:00', 'status' => 'read',
                'highlights' => [['Applicants (May 30)', '3,204', '+6.2% vs AY 2024–25'], ['Fastest growth', 'iPACE', '+19.4%'], ['Largest unit', 'College', '50.9% of applicants']],
                'head' => ['Unit', 'AY 2024–25', 'AY 2025–26 (to date)', 'Change'],
                'rows' => [
                    ['College', '1,562', '1,631', '+4.4%'],
                    ['Integrated School', '694', '724', '+4.3%'],
                    ['Graduate Programs', '262', '276', '+5.3%'],
                    ['College of Law', '168', '181', '+7.7%'],
                    ['iPACE', '252', '301', '+19.4%'],
                    ['ETEEAP', '78', '91', '+16.7%'],
                    ['All units', '3,016', '3,204', '+6.2%'],
                ],
                'note' => 'Snapshot as of May 30, 2026. Final AY 2025–26 totals are in the June report.',
                'thread' => [
                    ['from' => 'iato', 'text' => 'Mid-year snapshot of applicants per unit, for your reference ahead of the June report.', 'at' => 'May 30, 2026 · 10:00 AM'],
                ],
            ],
            [
                'id' => 'r3', 'type' => 'conv', 'typeLabel' => 'Conversion', 'ay' => 'AY 2025–26',
                'title' => 'Conversion Funnel Summary — AY 2025–26',
                'sent' => '2026-05-15 09:00', 'status' => 'replied',
                'highlights' => [['Applied', '3,058', 'as of May 15'], ['Paid reservations', '884', '28.9% of applicants'], ['Enrolled', '703', '23.0% of applicants']],
                'head' => ['Stage', 'Count', '% of applied'],
                'rows' => [
                    ['Applied', '3,058', '100.0%'],
                    ['Took exam', '2,291', '74.9%'],
                    ['Passed', '1,902', '62.2%'],
                    ['Paid', '884', '28.9%'],
                    ['Enrolled', '703', '23.0%'],
                ],
                'note' => 'Counts reflect applicants recorded in iARIS as of May 15, 2026.',
                'thread' => [
                    ['from' => 'iato', 'text' => 'Here is the conversion funnel summary from application to enrollment.', 'at' => 'May 15, 2026 · 9:00 AM'],
                    ['from' => 'me', 'text' => 'Thank you. In the next report, could you show where passers are dropping off, broken down by unit?', 'at' => 'May 15, 2026 · 2:40 PM'],
                    ['from' => 'iato', 'text' => 'Noted, Dr. Suarez. The unit-level breakdown will be in the June report.', 'at' => 'May 16, 2026 · 8:15 AM'],
                ],
            ],
            [
                'id' => 'r4', 'type' => 'trends', 'typeLabel' => 'Enrollment Trends', 'ay' => 'AY 2024–25',
                'title' => 'Enrollment Trends Report — AY 2024–25',
                'sent' => '2025-06-14 08:30', 'status' => 'replied',
                'highlights' => [['Applicants', '3,016', '+7.0% vs last AY'], ['Enrolled', '788', '+9.1% vs last AY'], ['Applied → Enrolled', '26.1%', '0.5 pts higher than last AY']],
                'head' => ['Academic Year', 'Applicants', 'YoY', 'Enrolled', 'YoY', 'Conversion'],
                'rows' => [
                    ['AY 2022–23', '2,610', '—', '640', '—', '24.5%'],
                    ['AY 2023–24', '2,820', '+8.0%', '722', '+12.8%', '25.6%'],
                    ['AY 2024–25', '3,016', '+7.0%', '788', '+9.1%', '26.1%'],
                ],
                'note' => 'Year-over-year change is calculated against the previous academic year.',
                'thread' => [
                    ['from' => 'iato', 'text' => 'Enrollment Trends Report for AY 2024–25 is attached.', 'at' => 'Jun 14, 2025 · 8:30 AM'],
                    ['from' => 'me', 'text' => 'Received, thank you. Noted the growth in iPACE.', 'at' => 'Jun 14, 2025 · 4:05 PM'],
                ],
            ],
            [
                'id' => 'r5', 'type' => 'units', 'typeLabel' => 'Applicants by Unit', 'ay' => 'AY 2024–25',
                'title' => 'Applicants by Unit — AY 2024–25',
                'sent' => '2025-06-02 09:30', 'status' => 'read',
                'highlights' => [['Applicants', '3,016', '+7.0% vs last AY'], ['Fastest growth', 'iPACE', '+28.6%'], ['Largest unit', 'College', '51.8% of applicants']],
                'head' => ['Unit', 'AY 2023–24', 'AY 2024–25', 'Change'],
                'rows' => [
                    ['College', '1,493', '1,562', '+4.6%'],
                    ['Integrated School', '668', '694', '+3.9%'],
                    ['Graduate Programs', '244', '262', '+7.4%'],
                    ['College of Law', '156', '168', '+7.7%'],
                    ['iPACE', '196', '252', '+28.6%'],
                    ['ETEEAP', '63', '78', '+23.8%'],
                    ['All units', '2,820', '3,016', '+7.0%'],
                ],
                'note' => 'Final applicant counts for AY 2024–25.',
                'thread' => [
                    ['from' => 'iato', 'text' => 'Applicants per unit for AY 2024–25.', 'at' => 'Jun 2, 2025 · 9:30 AM'],
                ],
            ],
            [
                'id' => 'r6', 'type' => 'summary', 'typeLabel' => 'Admissions Summary', 'ay' => 'AY 2023–24',
                'title' => 'Admissions Summary — AY 2023–24',
                'sent' => '2024-06-10 08:00', 'status' => 'replied',
                'highlights' => [['Applicants', '2,820', '+8.0% vs last AY'], ['Enrolled', '722', '+12.8% vs last AY'], ['Applied → Enrolled', '25.6%', '1.1 pts higher than last AY']],
                'head' => ['Unit', 'Applicants', 'Enrolled', 'Conversion'],
                'rows' => [
                    ['College', '1,493', '382', '25.6%'],
                    ['Integrated School', '668', '172', '25.7%'],
                    ['Graduate Programs', '244', '58', '23.8%'],
                    ['College of Law', '156', '40', '25.6%'],
                    ['iPACE', '196', '52', '26.5%'],
                    ['ETEEAP', '63', '18', '28.6%'],
                    ['All units', '2,820', '722', '25.6%'],
                ],
                'note' => 'Summary of admissions outcomes by unit.',
                'thread' => [
                    ['from' => 'iato', 'text' => 'Admissions summary for AY 2023–24.', 'at' => 'Jun 10, 2024 · 8:00 AM'],
                    ['from' => 'me', 'text' => 'Thank you, noted.', 'at' => 'Jun 10, 2024 · 1:20 PM'],
                ],
            ],
        ];
    }

    /**
     * Applicants per unit. 'applicants' and 'enrolled' have one number per entry in YEARS.
     * 'took', 'passed', 'paid' are this AY; 'paidPrevious' is last AY.
     * The units add up to the dashboard's totals (3,294 applicants, 842 enrolled, 1,019 paid).
     * Used by the Analytics and Reports pages.
     */
    private static function units(): array
    {
        return [
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
        ];
    }

    // AI insight cards (Analytics page) and the "Institutional Insights Summary" report
    private static function insights(): array
    {
        // Same keys as the Analytics page's AI insights (AnalyticsController), so real
        // AI output could replace these later. 'tone' is a Bootstrap colour.
        return [
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
        ];
    }

    // The Executive Office: who can see these pages. Later: users whose role is 'chancellor'.
    public static function executives(): array
    {
        // Who else sees this page (names as in the mockups). Later: the users with an executive role (User::isExecutive()).
        return [
            ['name' => 'Br. Edmundo L. Fernandez FSC', 'initials' => 'EF', 'title' => 'President', 'tone' => 'danger'],
            ['name' => 'Dr. Teodosia Merlin Suarez', 'initials' => 'TS', 'title' => 'Chancellor', 'tone' => 'primary'],
            ['name' => 'Br. Cyrill Kino Escolano FSC', 'initials' => 'CE', 'title' => 'Vice President for Administration', 'tone' => 'danger'],
            ['name' => 'Br. Agustin Boquer FSC', 'initials' => 'AB', 'title' => 'Vice President for Advancement', 'tone' => 'danger'],
            ['name' => 'Ms. Luz Magpantay', 'initials' => 'LM', 'title' => 'Vice President for Finance', 'tone' => 'danger'],
            ['name' => 'Dr. Tessie Palma', 'initials' => 'TP', 'title' => 'Vice Chancellor for Academics', 'tone' => 'info'],
            ['name' => 'Dr. Sheila Maloles', 'initials' => 'SM', 'title' => 'Vice Chancellor for Research, Innovation & Internationalization', 'tone' => 'secondary'],
            ['name' => 'Mr. Wenifredo Angeles', 'initials' => 'WA', 'title' => 'Vice Chancellor for Mission', 'tone' => 'warning'],
        ];
    }
}
