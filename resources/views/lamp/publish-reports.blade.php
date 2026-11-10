@extends('layouts.app')
@section('title', 'iARIS — Send Reports to LAMP')
@section('page-title', 'Send Reports to LAMP')
@section('page-subtitle', 'Generate a snapshot of scholarship applicants, then publish it to LAMP')
@section('content')
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
    <a href="{{ route('lamp-requests.index') }}">LAMP requests</a>
    <form method="POST" action="{{ route('lamp-reports.store') }}" class="card card-body my-3">@csrf
        <label for="report-title" class="form-label">Title</label><input id="report-title" name="title" class="form-control mb-2" required maxlength="255" value="{{ old('title') }}">
        <label for="school-year" class="form-label">School year (YYYY-YYYY)</label><input id="school-year" name="school_year" class="form-control mb-3" required pattern="[0-9]{4}-[0-9]{4}" value="{{ old('school_year', \App\Models\Applicant::max('school_year')) }}">
        <button class="btn btn-primary align-self-start">Generate report</button>
    </form>
    @forelse($reports as $report)
        <div class="card card-body mb-2"><strong>{{ $report->title }}</strong><p>{{ $report->school_year }} · Prepared by {{ $report->generated_by_name }}</p>
            <details class="mb-3"><summary>Preview snapshot ({{ count(json_decode($report->rows, true)) }} applicants)</summary>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Reference</th><th>Applicant</th><th>Program</th><th>Exam</th><th>LAMP decision</th><th>IATO status</th></tr></thead><tbody>
                    @foreach(array_slice(json_decode($report->rows, true), 0, 10) as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach
                </tbody></table></div>
            </details>
            @if($report->published_at) <span>Sent to LAMP {{ $report->published_at }}</span>
            @else <form method="POST" action="{{ route('lamp-reports.publish', $report->id) }}">@csrf <button class="btn btn-success">Publish to LAMP</button></form> @endif
        </div>
    @empty <p>No reports generated.</p> @endforelse
@endsection
