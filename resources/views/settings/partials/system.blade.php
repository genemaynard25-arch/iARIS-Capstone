{{--
    System settings for the Super Admin: Academic Year, Reservation Fees, Review & Reports, Data Retention, Backups.
    FRONT END ONLY: each card checks its fields and the toast says nothing was saved (there's no settings table yet).
    The script is in settings/index.blade.php ([data-system] forms).
--}}
@php($note = 'Not saved to the server yet: needs the backend.')

{{-- ========== ACADEMIC YEAR ========== --}}
<div class="tab-pane fade" id="section-year" role="tabpanel">
    <form class="card border-0 shadow-sm rounded-4" data-system="year" novalidate>
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-0">Academic year</h2>
            <p class="small text-body-secondary">Every page, chart and report starts from the current academic year.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="sysYear" class="form-label small fw-bold text-uppercase text-body-secondary">Current academic year</label>
                    <select class="form-select" id="sysYear">
                        @foreach ($academicYears as $year)
                            <option>AY {{ $year }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Switch when the new admissions cycle opens. Past years stay available for comparison.</div>
                </div>
                <div class="col-md-6">
                    <div class="form-label small fw-bold text-uppercase text-body-secondary">Admissions cycle</div>
                    <div class="d-grid gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="cycle" id="cycleOpen" checked>
                            <label class="form-check-label" for="cycleOpen">Open — accepting applications</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="cycle" id="cycleClosed">
                            <label class="form-check-label" for="cycleClosed">Closed — view only</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('settings.partials.save-footer')
    </form>
</div>

{{-- ========== RESERVATION FEES ========== --}}
<div class="tab-pane fade" id="section-fees" role="tabpanel">
    <form class="card border-0 shadow-sm rounded-4" data-system="fees" novalidate>
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-0">Reservation fees</h2>
            <p class="small text-body-secondary">Shown on applicant records and used to confirm payments.</p>
            <div class="row g-3">
                @foreach (['Integrated School' => 1500, 'College, iPACE' => 2000, 'ETEEAP' => 2500, 'Graduate Programs' => 3000, 'College of Law' => 3500] as $unit => $fee)
                    <div class="col-sm-6 col-xl-4">
                        <label for="fee{{ $loop->index }}" class="form-label small fw-bold text-uppercase text-body-secondary">{{ $unit }}</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="fee{{ $loop->index }}" value="{{ $fee }}" min="0" step="50" required>
                            <div class="invalid-feedback">Enter an amount of ₱0 or more.</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @include('settings.partials.save-footer')
    </form>
</div>

{{-- ========== REVIEW & REPORTS ========== --}}
<div class="tab-pane fade" id="section-review" role="tabpanel">
    <form class="card border-0 shadow-sm rounded-4" data-system="review" novalidate>
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-0">Review &amp; reports</h2>
            <p class="small text-body-secondary">How data moves from IATO to the other offices.</p>
            <div class="list-group">
                @foreach ([
                    ['reviewFirst', 'Require review before records are visible', 'Staff edits and new imports wait for your approval. Deans, the Registrar, LAMP and leadership only see approved data.', true],
                    ['staffApprove', 'Let IATO Staff approve their own imports', 'When off, only the Super Admin (or the Temporary Admin) can approve.', false],
                    ['aiSummary', 'Add the AI summary to new reports by default', 'Report forms start with the AI summary ticked. You can still untick it per report.', true],
                ] as [$id, $title, $text, $on])
                    <label class="list-group-item d-flex gap-3 align-items-start py-3" for="{{ $id }}">
                        <span class="flex-grow-1">
                            <span class="d-block fw-semibold">{{ $title }}</span>
                            <span class="d-block small text-body-secondary">{{ $text }}</span>
                        </span>
                        <span class="form-switch m-0 p-0"><input class="form-check-input m-0" type="checkbox" role="switch" id="{{ $id }}" @checked($on)></span>
                    </label>
                @endforeach
            </div>
            <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle me-1"></i> Signing out after a period of inactivity is handled by the server and is not changed here.</p>
        </div>
        @include('settings.partials.save-footer')
    </form>
</div>

{{-- ========== DATA RETENTION ========== --}}
<div class="tab-pane fade" id="section-retention" role="tabpanel">
    <form class="card border-0 shadow-sm rounded-4" data-system="retention" novalidate>
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-0">Data retention</h2>
            <p class="small text-body-secondary">What happens to records once they are no longer needed. Follows the Data Privacy Act of 2012.</p>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase">
                            <th class="text-body-secondary">Record type</th>
                            <th class="text-body-secondary">Keep for</th>
                            <th class="text-body-secondary">Then</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- [record, note, choices, chosen, then] --}}
                        @foreach ([
                            ['Applicants who enrolled', 'Handed over to the Registrar', ['2 years', '3 years', '5 years'], '3 years', 'Archive'],
                            ['Applicants who did not enroll', 'Pending, failed, or never paid', ['1 year', '2 years', '3 years'], '2 years', 'Anonymize for statistics'],
                            ['Scholar records', 'Including LAMP cross-checks', ['3 years', '5 years'], '5 years', 'Archive'],
                            ['Imported source files', 'The original Excel and CSV uploads', ['30 days', '90 days', '1 year'], '90 days', 'Delete'],
                            ['Audit logs', 'Cannot be deleted early', ['5 years', '7 years'], '7 years', 'Archive'],
                        ] as [$record, $sub, $choices, $chosen, $then])
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $record }}</div>
                                    <div class="small text-body-secondary">{{ $sub }}</div>
                                </td>
                                <td style="min-width: 130px;">
                                    <select class="form-select form-select-sm" aria-label="Keep {{ strtolower($record) }} for">
                                        @foreach ($choices as $choice)
                                            <option @selected($choice === $chosen)>{{ $choice }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><span class="badge rounded-pill bg-body-tertiary text-body border">{{ $then }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-body-secondary mt-3 mb-0">Anonymized records keep their counts for Analytics and AI insights, but names and contact details are removed.</p>
        </div>
        @include('settings.partials.save-footer')
    </form>
</div>

{{-- ========== BACKUPS ========== --}}
<div class="tab-pane fade" id="section-backups" role="tabpanel">
    <form class="card border-0 shadow-sm rounded-4" data-system="backups" novalidate>
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-0">Backups</h2>
            <p class="small text-body-secondary">Encrypted copies of the iARIS database, stored on the DLSL server.</p>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="backupSchedule" class="form-label small fw-bold text-uppercase text-body-secondary">Automatic backup</label>
                    <select class="form-select" id="backupSchedule">
                        <option>Daily at 11:00 PM</option>
                        <option>Weekly, Sundays 11:00 PM</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="backupKeep" class="form-label small fw-bold text-uppercase text-body-secondary">Keep backups for</label>
                    <select class="form-select" id="backupKeep">
                        <option>30 days</option>
                        <option>90 days</option>
                    </select>
                </div>
            </div>
            <div class="small fw-bold text-uppercase text-body-secondary mb-2">Recent backups</div>
            {{-- TODO (backend): list the real backups once the server makes them --}}
            <div class="rounded-3 bg-body-tertiary border text-center text-body-secondary small py-4">
                <i class="bi bi-cloud-slash fs-4 d-block mb-1"></i>
                No backups yet. Automatic backups start once they're set up on the server.
            </div>
        </div>
        <div class="card-footer bg-transparent border-top d-flex flex-wrap align-items-center justify-content-end gap-2 p-3">
            <span class="small text-body-secondary me-auto"><i class="bi bi-info-circle me-1"></i>{{ $note }}</span>
            <span class="small fw-semibold text-warning-emphasis d-none" data-dirty><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Unsaved changes</span>
            <span class="d-inline-block" tabindex="0" title="Coming soon: needs the backend">
                <button type="button" class="btn btn-light border fw-semibold" disabled><i class="bi bi-cloud-arrow-up me-1"></i> Back up now</button>
            </span>
            <button type="submit" class="btn btn-primary fw-semibold" data-save disabled><i class="bi bi-floppy me-1"></i> Save changes</button>
        </div>
    </form>
</div>
