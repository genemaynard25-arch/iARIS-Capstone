@extends('layouts.app')

@section('title', 'iARIS — Import Admission Stats')

@section('content')
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h2 class="fs-5 fw-bold mb-3">Import Admission Statistics</h2>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ url('/import/admission-stats') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv" class="form-control mb-3" required>
                <button type="submit" class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>
@endsection