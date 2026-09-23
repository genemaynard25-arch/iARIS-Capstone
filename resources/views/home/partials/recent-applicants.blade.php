<div class="iaris-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="fs-6 fw-bold mb-0">Recent Applicants</h3>
        <a href="#" class="iaris-card-link">View all</a>
    </div>

    @if (count($recentApplicants) === 0)
        <p class="iaris-text-soft small text-center py-4 mb-0">No applicants yet.</p>
    @else
        <div class="table-responsive">
            <table class="table iaris-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Program</th>
                        <th>Status</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentApplicants as $applicant)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $applicant['name'] }}</div>
                                <div class="iaris-text-soft small">{{ $applicant['reference'] }}</div>
                            </td>
                            <td>{{ $applicant['program'] }}</td>
                            <td><span class="iaris-tag fw-bold tone-{{ $applicant['tone'] }}">{{ $applicant['status'] }}</span></td>
                            <td class="iaris-text-soft text-nowrap">{{ $applicant['updated'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>