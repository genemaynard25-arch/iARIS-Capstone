@extends('layouts.app')
@section('title', 'iARIS — LAMP Requests')
@section('page-title', 'LAMP Requests')
@section('page-subtitle', 'Scholarship conversations between IATO and LAMP')
@section('content')
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
    @if($isIato)
        <div class="d-flex gap-3 mb-3"><a href="{{ url('/messages') }}">All messages</a><a href="{{ route('lamp-reports.manage') }}">Send reports to LAMP</a></div>
        <form method="POST" action="{{ route('lamp-requests.store') }}" class="card card-body mb-4">
            @csrf
            <h2 class="h5">Ask LAMP to check an applicant</h2>
            <label class="form-label" for="reference">Applicant reference number</label>
            <input id="reference" name="reference" class="form-control mb-2" required maxlength="30" value="{{ old('reference', $selected) }}">
            <label class="form-label" for="reason">Reason</label><input id="reason" name="reason" class="form-control mb-2" required maxlength="255" value="{{ old('reason') }}">
            <label class="form-label" for="message">Message</label><textarea id="message" name="text" class="form-control mb-3" required maxlength="5000">{{ old('text') }}</textarea>
            <button class="btn btn-primary align-self-start">Send to LAMP</button>
        </form>
    @endif
    @forelse($requests as $item)
        <article class="card card-body mb-3" id="request-{{ $item->id }}">
            <h2 class="h5">{{ $item->name }} · {{ $item->applicant?->reference_number ?? 'Applicant unavailable' }}</h2>
            <p>{{ $item->reason }} · <strong>{{ ucfirst($item->status) }}</strong></p>
            @foreach($item->messages as $message)
                <div class="border rounded p-3 mb-2"><div class="small text-body-secondary">{{ $message->name }} · {{ $message->role }} · {{ $message->sent_at->format('M j, Y g:i A') }}</div><div style="white-space: pre-wrap">{{ $message->text }}</div></div>
            @endforeach
            @if($item->status !== 'resolved' && $item->applicant_id)
                <form method="POST" action="{{ route('lamp-requests.reply', $item) }}">@csrf
                    <label class="form-label" for="reply-{{ $item->id }}">Reply</label><textarea id="reply-{{ $item->id }}" name="text" class="form-control mb-2" required maxlength="5000"></textarea>
                    <button class="btn btn-primary">Send reply</button>
                </form>
                @if($isIato)
                    <form method="POST" action="{{ route('lamp-requests.resolve', $item) }}" class="mt-2">@csrf <button class="btn btn-outline-success">Mark resolved</button></form>
                @endif
            @endif
        </article>
    @empty
        <div class="alert alert-info">No scholarship requests have been sent.</div>
    @endforelse
@endsection
