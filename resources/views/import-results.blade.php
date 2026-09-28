@extends('layouts.app')

@section('title', 'iARIS — Import Results')
@section('page-title', 'Import Results')
@section('page-subtitle', 'Batch #' . $batch->id . ' · ' . $batch->original_filename)

@php($levels = ['is' => 'Integrated School', 'college' => 'College', 'graduate_school' => 'Graduate School', 'eteeap' => 'ETEEAP'])

@section('content')
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 d-flex flex-wrap align-items-center gap-3">
            <div class="icon-circle rounded-circle bg-primary text-white fs-5 d-flex align-items-center justify-content-center">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">Import complete</div>
                <div class="small text-body-secondary">
                    {{ $applicants->count() }} {{ Str::plural('applicant', $applicants->count()) }} imported from "{{ $batch->original_filename }}".
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ url('/import') }}" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i> Import another file</a>
                <a href="{{ url('/home') }}" class="btn btn-primary">Go to dashboard</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-3">Imported Applicants</h2>

            @if ($applicants->isEmpty())
                <p class="small text-body-secondary text-center py-4 mb-0">No applicants were imported from this file.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead>
                            <tr class="text-uppercase text-nowrap">
                                <th class="text-body-secondary fw-bold">Reference #</th>
                                <th class="text-body-secondary fw-bold">Name</th>
                                <th class="text-body-secondary fw-bold">Gender</th>
                                <th class="text-body-secondary fw-bold">Level</th>
                                <th class="text-body-secondary fw-bold">School Year</th>
                                <th class="text-body-secondary fw-bold">Program / Track</th>
                                <th class="text-body-secondary fw-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($applicants as $applicant)
                                <tr>
                                    <td class="text-nowrap font-monospace">{{ $applicant->reference_number }}</td>
                                    <td class="fw-semibold">{{ $applicant->last_name }}, {{ $applicant->first_name }}</td>
                                    <td class="text-capitalize">{{ $applicant->gender }}</td>
                                    <td>
                                        {{ $levels[$applicant->level] ?? Str::headline($applicant->level) }}
                                        @if ($applicant->sub_level)
                                            <span class="text-body-secondary">· {{ strtoupper($applicant->sub_level) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $applicant->school_year }}</td>
                                    <td>{{ $applicant->program_or_track ?: '—' }}</td>
                                    <td><span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ Str::headline($applicant->application_status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
