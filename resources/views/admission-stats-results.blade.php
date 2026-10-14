@extends('layouts.app')

@section('title', 'iARIS — Admission Stats Import Results')

@section('content')
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h2 class="fs-5 fw-bold mb-1">Import Results</h2>
            <p class="small text-body-secondary mb-3">{{ $batch->original_filename }} — {{ $stats->count() }} rows imported</p>

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Level / Program</th>
                            <th>Female</th>
                            <th>Male</th>
                            <th>Submitted</th>
                            <th>Took Test</th>
                            <th>Passed</th>
                            <th>Reserved (Total)</th>
                            <th>Enrolled</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats as $stat)
                            <tr>
                                <td>{{ $stat->level_program }}</td>
                                <td>{{ $stat->female_applicants }}</td>
                                <td>{{ $stat->male_applicants }}</td>
                                <td>{{ $stat->submitted_current }}</td>
                                <td>{{ $stat->took_test_current }}</td>
                                <td>{{ $stat->passed_current }}</td>
                                <td>{{ $stat->total_reserved_current }}</td>
                                <td>{{ $stat->officially_enrolled }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection