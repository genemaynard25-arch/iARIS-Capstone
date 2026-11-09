{{-- Shared v2 frame for the remaining admin screens; all page data stays with its existing view. --}}
@section('body-class', 'iaris-workspace admin-page ' . $workspacePage)
@section('page-breadcrumb-root', $workspaceGroup)
@section('page-breadcrumb', $workspaceLabel)
@push('styles')
    <link href="{{ asset('css/app-shell.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
@endpush
@section('sidebar-brand')
    @include('partials.auth-logo', ['size' => 44, 'variant' => 'glass'])
@endsection
@section('app-banner')
    @include('partials.institution-bar')
@endsection
