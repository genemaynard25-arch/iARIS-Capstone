<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Admin dashboard.
     *
     * Everything below is placeholder data so the UI can be built first.
     * Once RBAC is in place, replace each value with a query scoped to the
     * logged-in user (e.g. Applicant::visibleTo(auth()->user())->count()).
     * The Blade views only read these variables, so they won't need to change.
     *
     * 'tone' is a Bootstrap colour name (primary, info, warning, danger); the
     * views turn it into classes like bg-warning-subtle text-warning-emphasis.
     */
    public function index()
    {
        // LAMP Office and SHS principal users have their own dashboards. Every sign-in
        // ends up here (login -> splash -> /home), so this sends them to the right place.
        if (auth()->user()->isLamp()) {
            return redirect()->route('lamp.dashboard');
        }
        if (auth()->user()->isShsPrincipal()) {
            return redirect()->route('shs.dashboard');
        }

        return view('home', [
            'academicYear' => '2025–2026',
            'periodLabel' => 'Week of June 8–14, 2026',

            'stats' => [
                [
                    'label' => 'Total Applicants',
                    'value' => 2418,
                    'change' => '+124 this week',
                    'trend' => 'up',
                    'tag' => 'College + SHS',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Paid Reservations',
                    'value' => 1043,
                    'change' => 'of 2,418 applicants',
                    'trend' => 'neutral',
                    'tag' => '43.1% conversion',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Enrolled',
                    'value' => 872,
                    'change' => '+38 this week',
                    'trend' => 'up',
                    'tag' => 'Active enrollees',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Pending Timestamps',
                    'value' => 12,
                    'change' => 'Records updated by applicants',
                    'trend' => 'neutral',
                    'tag' => 'Needs review',
                    'tone' => 'warning',
                ],
            ],

            'recentApplicants' => [
                ['name' => 'Santos, Maria L.', 'reference' => 'APP-2026-0381', 'program' => 'BS Computer Science', 'status' => 'Enrolled', 'tone' => 'primary', 'updated' => 'Jun 8, 9:14 AM'],
                ['name' => 'Reyes, Joshua D.', 'reference' => 'APP-2026-0382', 'program' => 'Senior High – STEM', 'status' => 'Paid', 'tone' => 'info', 'updated' => 'Jun 8, 10:02 AM'],
                ['name' => 'Dela Cruz, Ana', 'reference' => 'APP-2026-0383', 'program' => 'BS Accountancy', 'status' => 'For exam', 'tone' => 'warning', 'updated' => 'Jun 7, 3:45 PM'],
                ['name' => 'Garcia, Mark Anthony', 'reference' => 'APP-2026-0384', 'program' => 'BS Tourism Management', 'status' => 'Enrolled', 'tone' => 'primary', 'updated' => 'Jun 7, 1:20 PM'],
                ['name' => 'Villanueva, Pia R.', 'reference' => 'APP-2026-0385', 'program' => 'Senior High – ABM', 'status' => 'Pending', 'tone' => 'danger', 'updated' => 'Jun 6, 2:30 PM'],
            ],

            'notifications' => [
                'Today, 8:00 AM' => [
                    ['icon' => 'bi-file-earmark-text', 'tone' => 'primary', 'title' => 'New application submitted', 'body' => 'Santos, Maria L. applied for BS Computer Science'],
                ],
                'Yesterday, 3:45 PM' => [
                    ['icon' => 'bi-clock-history', 'tone' => 'warning', 'title' => 'Pending timestamp flagged', 'body' => '5 applicant records need review before encoding'],
                    ['icon' => 'bi-cloud-arrow-up', 'tone' => 'info', 'title' => 'Data import completed', 'body' => '238 records imported from Google Sheets'],
                ],
                'Jun 7, 9:12 AM' => [
                    ['icon' => 'bi-check2-circle', 'tone' => 'primary', 'title' => 'Reservation payment confirmed', 'body' => 'Garcia, Mark Anthony — BS Tourism Management'],
                ],
                'Jun 6, 2:30 PM' => [
                    ['icon' => 'bi-exclamation-triangle', 'tone' => 'danger', 'title' => 'Account access request', 'body' => 'New staff account pending approval'],
                ],
            ],
        ]);
    }
}
