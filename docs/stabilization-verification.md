# Stabilization and verification — November 10, 2026

## Repository baseline

PR #6 was already merged when this work started. Its merge commit is
`ebfa5b3678bb7ed9388aa2c973b1671564b3d64c`. Its pagination and sidebar changes
remain in the working branch. No force push or migration reset was used.

## Changes

- Fixed invalid doubled namespace separators in IpaceRecordController, which broke route inspection and iPACE records.
- Fixed the empty APP_TIMEZONE example and configuration fallback, which broke Artisan on a fresh setup.
- Serialized LAMP submissions, approvals and IATO confirmations on the applicant; review outcomes also lock the queue item and commit with their audit entry. Send-back and its audit entry are atomic. Unsupported review actions are rejected.
- Prevented IATO confirmation while a LAMP action is pending; confirmation revalidates applicant eligibility. View-only IATO staff cannot confirm.
- Kept Super Admin approval and IATO confirmation separate. Undo still requires review. Persisted pending-review feedback survives refresh and disables further decision buttons.
- Replaced race-prone sequential review reference allocation with random, unique references that fit the existing column. Existing references remain valid.
- Added audit actor name/role snapshots, before/after details, applicant and review references. LAMP review outcomes use the LAMP audit filter. The audit UI uses historical actor snapshots and handles deleted accounts. Normal Eloquent edits/deletes of audit entries are rejected; privileged SQL access remains outside this protection.
- Added additive report/read tables. IATO generates a snapshot, previews its first ten rows, then publishes it. LAMP opens that saved snapshot, downloads the complete CSV and maintains per-user read status. Generation, publishing and download are audited. CSV cells that could act as spreadsheet formulas are escaped.
- Replaced LAMP sample reports and browser-only read flags with database records. Older report snapshots retain their own school year and author.
- Replaced sample LAMP conversations with persisted applicant-linked requests and messages. IATO initiates requests; both offices reply; IATO resolves them. Duplicate open requests and replies to resolved requests are rejected. The Messages page links to the persisted LAMP inbox; the Scholar drawer links directly to the applicant conversation. Conversation changes are audited. These conversations do not silently alter scholarship records.
- Replaced repeated four-year applicant totals with school-year-specific counts and dynamic labels. Missing years show zero. Current funnels/profiles exclude archived and older-year records; paid counts use payment records, and all eligible exam outcomes count as passed. Both iPACE and ETEEAP program groups and learner profiles use imported fields. Removed the CEAS demo override from these aggregations and removed fabricated iPACE received reports and generated-report history.
- Added chunked spreadsheet reading, whole-import transactions and failed-batch status on errors. Re-import matches application number plus school year so earlier years survive. New import reference numbers no longer depend on row counts after deletions. Invalid individual rows remain visible in the existing import error report.
- Preserved iPACE enum values when the older Law migration runs on an already populated database.
- Updated five Symfony packages to patched releases: http-foundation, http-kernel, routing and serializer 7.4.23, and mime 7.4.22. Composer audit reports no advisories. Bootstrap tooling installed for this session is not added to project dependencies.
- Extended the shared bounded pagination helper to the Director applicant table, reset pages after filtering, and show imported program names instead of numeric internal group identifiers.
- Corrected the existing guest-root test to expect the application's login redirect. Added integration coverage; the larger test memory limit accommodates construction of XLSX fixtures.

## Running locally

From a clean local checkout:

```bash
git fetch origin
git switch iato-records-management-system
git pull --ff-only origin iato-records-management-system
composer install
php artisan migrate
php artisan optimize:clear
php artisan route:list
php artisan view:cache
```

Keep your existing APP_KEY and database settings. New tables and nullable audit
snapshot fields are added; existing applicant and scholarship records are not reset.
IATO can open `/iato/lamp-requests` and `/iato/lamp-reports`. LAMP uses its existing
Messages and Reports navigation.

Integration tests require an **isolated, migrated MySQL/MariaDB test database**.
The historical project migrations contain MySQL-specific SQL. The SQLite default
runs the basic tests and skips these integration tests explicitly.

```bash
# Create iaris_test separately using your normal database administration tool.
DB_CONNECTION=mysql DB_DATABASE=iaris_test php artisan migrate --env=testing
DB_CONNECTION=mysql DB_DATABASE=iaris_test php artisan test
```

Set DB_HOST, DB_PORT, DB_USERNAME and DB_PASSWORD using your normal local
configuration as needed. Do not point the integration tests at a live application
database. Tests use transactions and roll their fixture changes back.

## Validation scope and limitations

Validation uses fresh, isolated MariaDB databases and PHP 8.5. Production data and
previously deployed migration histories were unavailable. The synthetic 4,000-row
XLSX follows the documented thirty-column portal layout; the original Excel template
and original stress file were not attached, so those exact files were not verified.
Re-import remains last-row-wins within a school year. Existing duplicate application
numbers are not merged or deleted. Simultaneous independent Excel imports do not yet
have a unique application-number/school-year database constraint.

LAMP requests and reports now persist; other offices still have separate demo
messaging/report implementations. iPACE report delivery is not implemented; its
fabricated received-report entries were removed. Completion counts retain the
application's existing use of completed application status for enrollment; there is
no separate verified enrollment feed. No employer/work-history data is invented.

The browser sandbox did not trust the CDN certificate. UI tests relayed the unchanged
remote asset bytes through curl with normal certificate verification into the browser
context. Application TLS verification was not disabled.

## Executed validation

- PHP 8.5.12; MariaDB 11.8.6; project-locked Laravel 13.32.0.
- All 17 tests pass with 140 assertions against the isolated MariaDB test database.
- Synthetic XLSX stress test: 4,000 records across all six admission levels; re-import leaves 4,000 applicants and 8,000 payment rows. Runtime was approximately 51 seconds for the full suite.
- A deliberately invalid row after a successful 500-row chunk rolls back all applicant/payment changes and marks the batch failed.
- Two independent simultaneous LAMP submission processes return 200 and 409; only one pending request is created.
- Fresh migrations pass. On a third empty database, the latest three migrations (LAMP fields, reports/reads, audit actors) roll back and reapply successfully. No live data was used.
- Playwright/Chromium passes report generation/publication, snapshot preview, persistent per-user read status, decision submission and refresh, approval and refresh, IATO/LAMP replies, Director analytics, and pagination/search reset on the Director and iPACE record tables. No JavaScript page errors were recorded.
- PHP syntax checks, JavaScript syntax checks, Pint, route:list, migrate:status, view:cache, optimize:clear, composer install and composer validate pass.
- Composer audit finds no security advisories after the targeted Symfony patch updates.
- The unconfigured SQLite test command runs two basic tests and explicitly skips the fifteen MySQL integration tests; it does not substitute for the MariaDB run above.
