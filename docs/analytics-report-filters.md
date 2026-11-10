# Analytics and Reports filter wiring

The Admin/Staff Analytics selectors now share one frontend scope: academic year, department (academic unit), and date range. The scope updates summary cards, charts, funnel, field-count cards/table, chart tables/CSV exports, comparisons, projections, and report-builder scope labels. Selection is preserved in URL query parameters.

`AnalyticsFilterData::make()` supplies anonymous daily totals, grouped by school year, academic unit, application date, and location bucket. It excludes archived applicants. It contains no applicant IDs, names, contact details, or raw addresses. Counts use imported exam outcomes, completed application status, and paid-fee records. The adapter only reads data.

Date rules:

- Dates mean the applicant's application date (`created_at`, populated from APP. DATE by import), not the file-upload date.
- This AY selects all records in the chosen imported school year.
- This Week is Monday through today; This Month is month-start through today.
- This Semester uses January–June / July–December through today until official academic term dates are supplied.
- Date windows are anchored to today in Asia/Manila. Choosing an old school year with This Week can therefore correctly return zero.
- Cached AI insight text is institution-wide; changing a selector does not regenerate AI analysis.
- Applicant field-card percentages are year-over-year change for each field, not a share of total applicants. They use the exact preceding AY and the corresponding date window one calendar year earlier. Missing or zero prior counts show N/A. Cards, table, and CSV use the same calculation.

The Admin/Staff report modal's Current/Previous School Year selector updates its preview. PDF requests carry `period=Current School Year` or `period=Previous School Year`, which `ReportController::pdf()` validates and uses to select report data. No prior-year import means an empty report, not current-year or mockup fallback rows. Current-year Law/iPACE preview templates remain previews; previous-year versions do not reuse their sample people.

Registrar reports use actual period boundaries, reject invalid/unknown dates in bounded periods, exclude future dates, and include applicants with paid reservations even when their status is Enrolled.

Existing Executive unit/year/comparison and received-report search/type/year/status/sort controls, College-office sortable tables, and LAMP report search/type/read-status controls continue to operate on their existing supplied datasets. This work does not connect demo-only office datasets or persist browser-only report history/delivery workflows.
