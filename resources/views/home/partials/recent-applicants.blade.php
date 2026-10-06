<div class="card border-0 shadow-sm rounded-4 h-100">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            {{-- Pages that reuse this card can pass their own title and "View all" link --}}
            <h2 class="fs-6 fw-bold mb-0">{{ $recentTitle ?? 'Recent Applicants' }}</h2>
            <a href="{{ $recentLink ?? '#' }}" class="small fw-semibold text-decoration-none">View all</a>
        </div>

        @if (count($recentApplicants) === 0)
            <p class="small text-body-secondary text-center py-4 mb-0">No applicants yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-uppercase text-nowrap">
                            <th class="text-body-secondary fw-bold">Applicant</th>
                            <th class="text-body-secondary fw-bold">Program</th>
                            <th class="text-body-secondary fw-bold">Status</th>
                            <th class="text-body-secondary fw-bold">Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentApplicants as $applicant)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $applicant['name'] }}</div>
                                    <div class="small text-body-secondary">{{ $applicant['reference'] }}</div>
                                </td>
                                <td>{{ $applicant['program'] }}</td>
                                <td><span class="badge rounded-pill bg-{{ $applicant['tone'] }}-subtle text-{{ $applicant['tone'] }}-emphasis">{{ $applicant['status'] }}</span></td>
                                <td class="small text-body-secondary text-nowrap">{{ $applicant['updated'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
