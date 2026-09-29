<?php

namespace App\Http\Controllers;

class AnalyticsController extends Controller
{
    /**
     * Analytics page.
     *
     * Placeholder numbers so the UI can be built first (same idea as the other
     * controllers). When the backend is ready, each array can be replaced by a
     * query that returns the same shape; the charts are drawn from these arrays.
     */
    public function index()
    {
        return view('analytics.index', [
            'filters' => [
                'ranges' => ['This Week', 'This Month', 'This Semester', 'This AY'],
                'departments' => ['CITE', 'CEAS', 'CON', 'CIHTM', 'CBEAM', 'Graduate Programs', 'College of Law', 'iPACE', 'Integrated School'],
                'years' => ['AY 2025–2026', 'AY 2024–2025', 'AY 2023–2024', 'AY 2022–2023'],
            ],

            // 'good' says whether this change is good news (decides green vs red)
            'stats' => [
                ['label' => 'Total applicants', 'value' => '2,418', 'change' => '+8.4%', 'good' => true, 'tone' => 'primary'],
                ['label' => 'Exam pass rate', 'value' => '76.3%', 'change' => '+2.1%', 'good' => true, 'tone' => 'info'],
                ['label' => 'Conversion rate', 'value' => '43.1%', 'change' => '−1.8%', 'good' => false, 'tone' => 'warning'],
                ['label' => 'Total enrolled', 'value' => '872', 'change' => '+6.2%', 'good' => true, 'tone' => 'primary'],
            ],

            'charts' => [
                'trends' => [
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'values' => [280, 340, 420, 460, 400, 320],
                ],
                'programs' => [
                    'labels' => ['BS Information Technology', 'BS Computer Science', 'BS Nursing', 'BS Accountancy', 'BS Tourism Management'],
                    'values' => [312, 278, 245, 198, 178],
                ],
                'funnel' => [
                    'labels' => ['Applied', 'Took Exam', 'Passed Exam', 'Paid', 'Enrolled'],
                    'values' => [2418, 1983, 1513, 1043, 872],
                ],
                'locations' => [
                    'labels' => ['Lipa City', 'Batangas City', 'Other CALABARZON', 'Others'],
                    'values' => [967, 629, 508, 314],
                ],
            ],

            // Applicants per department per academic year, for the comparison builder
            'comparison' => [
                'CITE' => ['AY 2022–2023' => 780, 'AY 2023–2024' => 826, 'AY 2024–2025' => 872, 'AY 2025–2026' => 945],
                'CBEAM' => ['AY 2022–2023' => 1010, 'AY 2023–2024' => 1068, 'AY 2024–2025' => 1042, 'AY 2025–2026' => 998],
                'CEAS' => ['AY 2022–2023' => 590, 'AY 2023–2024' => 612, 'AY 2024–2025' => 634, 'AY 2025–2026' => 671],
                'CON' => ['AY 2022–2023' => 380, 'AY 2023–2024' => 398, 'AY 2024–2025' => 412, 'AY 2025–2026' => 438],
                'CIHTM' => ['AY 2022–2023' => 452, 'AY 2023–2024' => 425, 'AY 2024–2025' => 389, 'AY 2025–2026' => 356],
                'Graduate Programs' => ['AY 2022–2023' => 121, 'AY 2023–2024' => 130, 'AY 2024–2025' => 139, 'AY 2025–2026' => 148],
                'College of Law' => ['AY 2022–2023' => 74, 'AY 2023–2024' => 78, 'AY 2024–2025' => 81, 'AY 2025–2026' => 86],
                'iPACE' => ['AY 2022–2023' => 38, 'AY 2023–2024' => 44, 'AY 2024–2025' => 51, 'AY 2025–2026' => 56],
                'Integrated School' => ['AY 2022–2023' => 1980, 'AY 2023–2024' => 2060, 'AY 2024–2025' => 2140, 'AY 2025–2026' => 2284],
            ],

            // 'tone' is a Bootstrap colour: primary = good news, danger = needs attention, warning = watch, info = neutral
            'insights' => [
                ['type' => 'Growth', 'icon' => 'bi-graph-up-arrow', 'tone' => 'primary', 'title' => 'CITE enrollment has grown steadily over 3 years', 'body' => 'CITE records the highest applicant growth among the colleges. Year-on-year increase averages 8.4%, driven mainly by BS Information Technology and BS Computer Science.', 'stat' => '+8.4%', 'statLabel' => 'Average YoY growth, AY 2022–23 to AY 2025–26', 'trend' => 'Consistent', 'tags' => ['CITE', 'BSIT', 'BSCS']],
                ['type' => 'Needs attention', 'icon' => 'bi-exclamation-triangle', 'tone' => 'danger', 'title' => 'CIHTM enrollment declined for 2 consecutive years', 'body' => 'CIHTM dropped about 8.5% per year over the last 2 AYs, most visibly in BS Tourism Management. This may warrant a review of program offerings or marketing.', 'stat' => '−8.5%', 'statLabel' => 'Average decline per AY, AY 2023–24 to AY 2025–26', 'trend' => 'Declining', 'tags' => ['CIHTM', 'BS Tourism']],
                ['type' => 'Conversion drop', 'icon' => 'bi-funnel', 'tone' => 'warning', 'title' => 'Payment is the biggest drop-off point in the funnel', 'body' => '36.8% of applicants who pass the entrance exam do not pay their reservation — the largest single drop-off, suggesting friction between results release and payment deadlines.', 'stat' => '36.8%', 'statLabel' => 'Drop-off at payment after passing the exam', 'trend' => 'High drop-off', 'tags' => ['Funnel', 'Payment stage', 'All units']],
                ['type' => 'Strong pass rate', 'icon' => 'bi-mortarboard', 'tone' => 'primary', 'title' => 'College of Nursing keeps the highest exam pass rate', 'body' => 'CON applicants reach the highest entrance exam pass rate at 84.2%, followed by CITE at 79.1%, reflecting strong preparedness over the past 3 AYs.', 'stat' => '84.2%', 'statLabel' => 'CON exam pass rate, 3-year average', 'trend' => 'Highest', 'tags' => ['CON', 'Exam pass rate']],
                ['type' => 'Location trend', 'icon' => 'bi-geo-alt', 'tone' => 'info', 'title' => 'Applicants from outside Lipa City are increasing', 'body' => 'Lipa City remains the top source at 40%, but applicants from CALABARZON outside Batangas grew from 14% to 21% over 3 years, suggesting wider regional reach.', 'stat' => '+7 pts', 'statLabel' => 'CALABARZON share, AY 2022–23 vs AY 2025–26', 'trend' => 'Growing', 'tags' => ['Location', 'CALABARZON']],
                ['type' => 'Seasonal pattern', 'icon' => 'bi-calendar-range', 'tone' => 'warning', 'title' => 'Applications peak in March–April every year', 'body' => 'Across all 4 AYs, March and April account for about 42% of annual applications. Staffing and exam scheduling should plan for this peak.', 'stat' => '42%', 'statLabel' => 'Applications in Mar–Apr, consistent across 4 AYs', 'trend' => 'Peak season', 'tags' => ['Seasonality', 'March', 'April']],
            ],

            'projection' => [
                'year' => 'AY 2026–2027',
                'items' => [
                    ['dept' => 'CITE', 'current' => 945, 'projected' => 1024, 'change' => '+8.4%', 'up' => true],
                    ['dept' => 'CBEAM', 'current' => 998, 'projected' => 958, 'change' => '−4.0%', 'up' => false],
                    ['dept' => 'CEAS', 'current' => 671, 'projected' => 710, 'change' => '+5.8%', 'up' => true],
                    ['dept' => 'CON', 'current' => 438, 'projected' => 465, 'change' => '+6.2%', 'up' => true],
                    ['dept' => 'CIHTM', 'current' => 356, 'projected' => 325, 'change' => '−8.7%', 'up' => false],
                    ['dept' => 'Integrated School', 'current' => 2284, 'projected' => 2437, 'change' => '+6.7%', 'up' => true],
                    ['dept' => 'Graduate Programs', 'current' => 148, 'projected' => 162, 'change' => '+9.5%', 'up' => true],
                    ['dept' => 'College of Law', 'current' => 86, 'projected' => 91, 'change' => '+5.8%', 'up' => true],
                ],
            ],

            'lastAnalyzed' => 'Jun 12, 2026 · 6:00 AM',
        ]);
    }
}
