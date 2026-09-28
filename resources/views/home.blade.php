@extends('layouts.app')

@section('title', 'iARIS — Dashboard')
@section('page-title', 'Admissions Overview')
@section('page-subtitle', "Academic Year {$academicYear} · {$periodLabel}")

@section('content')
    @include('home.partials.stats')
    @include('home.partials.charts')

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            @include('home.partials.recent-applicants')
        </div>
        <div class="col-12 col-xl-5">
            @include('home.partials.notifications')
        </div>
    </div>
@endsection
