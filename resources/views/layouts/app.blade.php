<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'iARIS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Palette taken from the login page */
        :root {
            --iaris-green: #059669;
            --iaris-green-dark: #047857;
            --iaris-green-light: #a7f3d0;
            --iaris-green-tint: #f0fdf4;
            --iaris-page-bg: #f5f5f0;
            --iaris-card-bg: #ffffff;
            --iaris-text: #1f2b24;
            --iaris-text-soft: rgba(31, 43, 36, 0.55);
            --iaris-border: #eef1ee;
            --iaris-radius: 13px;
            --iaris-shadow: 0 4px 4px -3px rgba(0, 0, 0, 0.08), 0 0 3px 0 rgba(0, 0, 0, 0.06);
        }

        body {
            background-color: var(--iaris-page-bg);
            color: var(--iaris-text);
        }

        /* Sidebar */
        .iaris-sidebar {
            width: 270px;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--iaris-green), var(--iaris-green-dark));
            color: #fff;
            overflow: hidden;
        }

        @media (min-width: 992px) {
            .iaris-sidebar {
                position: sticky;
                top: 0;
                height: 100vh;
            }
        }

        .iaris-sidebar .offcanvas-body {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 24px 0 16px;
            overflow: hidden;
        }

        .iaris-nav-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }

        .iaris-logo-mark {
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .iaris-logo {
            flex-shrink: 0;
            padding: 0 24px 20px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }

         .iaris-nav-section {
            padding: 0 14px;
            margin-bottom: 12px;
        }

        .iaris-nav-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--iaris-green-light);
            padding: 8px 14px 6px;
        }

        .iaris-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 10px 14px;
            margin: 2px 0;
            border: 0;
            border-radius: 10px;
            background: transparent;
            font-size: 14px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            text-align: left;
            transition: background 0.15s ease;
        }

        .iaris-nav-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .iaris-nav-item.active {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        .iaris-nav-item i {
            width: 18px;
            text-align: center;
        }

        .iaris-sidebar-footer {
            flex-shrink: 0;
            padding: 12px 14px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.18);
        }

        .iaris-sidebar-footer .iaris-nav-item {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Main area */
        .iaris-main {
            flex: 1;
            min-width: 0;
            padding: 28px 32px;
        }

        @media (max-width: 575.98px) {
            .iaris-main {
                padding: 16px;
            }
        }

        .iaris-card {
            background: var(--iaris-card-bg);
            border-radius: var(--iaris-radius);
            box-shadow: var(--iaris-shadow);
            padding: 22px;
            height: 100%;
        }

        .iaris-topbar {
            background: var(--iaris-card-bg);
            border-radius: var(--iaris-radius);
            box-shadow: var(--iaris-shadow);
            padding: 16px 24px;
        }

        .iaris-text-soft {
            color: var(--iaris-text-soft);
        }

        .iaris-status-pill {
            background: var(--iaris-green-tint);
            color: var(--iaris-green-dark);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
        }

        .iaris-pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--iaris-green);
        }

        .iaris-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--iaris-green-dark);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }

        .iaris-card-link {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--iaris-green);
            text-decoration: none;
        }

        .iaris-card-link:hover {
            color: var(--iaris-green-dark);
        }

        /* Tones shared by tags, badges and icons */
        .tone-green { background: #ecfdf5; color: var(--iaris-green-dark); }
        .tone-blue { background: #eaf2ff; color: #2563eb; }
        .tone-orange { background: #fff4e5; color: #c2750c; }
        .tone-red { background: #fdeaea; color: #c0392b; }

        /* Dashboard */
        .stat-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--iaris-text-soft);
        }

        .stat-value {
            font-size: 30px;
            font-weight: 700;
        }

        .stat-change {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--iaris-green);
        }

        .stat-change.neutral {
            color: var(--iaris-text-soft);
        }

        .iaris-tag {
            display: inline-block;
            font-size: 11.5px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 8px;
        }

        .chart-placeholder {
            height: 230px;
            border: 2px dashed var(--iaris-border);
            border-radius: 10px;
            background: var(--iaris-green-tint);
            color: var(--iaris-text-soft);
        }

        .iaris-table th {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--iaris-text-soft);
            border-bottom: 1.5px solid var(--iaris-border);
            padding: 10px 12px;
            white-space: nowrap;
        }

        .iaris-table td {
            font-size: 13.5px;
            padding: 14px 12px;
            border-bottom: 1px solid #f3f5f3;
        }

        .notif-date {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--iaris-green);
            margin: 18px 0 6px;
        }

        .notif-date:first-child {
            margin-top: 0;
        }

        .notif-item + .notif-item {
            border-top: 1px solid #f3f5f3;
        }

        .notif-icon {
            width: 36px;
            height: 36px;
            font-size: 16px;
        }
    </style>
    @stack('styles')
</head>

<body class="m-0">
    <div class="d-flex" style="min-height: 100vh;">

        @include('layouts.partials.sidebar')

        <main class="iaris-main">
            @include('layouts.partials.topbar')

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
