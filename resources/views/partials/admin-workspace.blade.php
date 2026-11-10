{{-- Shared v2 frame for admin and IATO Staff screens; all page data stays with its existing view. --}}
@section('body-class', 'admin-page ' . $workspacePage)
@section('page-breadcrumb-root', auth()->user()->role === 'iato_staff' ? match ($workspaceGroup) { 'Admin' => 'Staff', 'System' => 'My Work', default => $workspaceGroup } : $workspaceGroup)
@section('page-breadcrumb', $workspaceLabel)
@push('styles')
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
@endpush
@section('sidebar-brand')
    @include('partials.auth-logo', ['size' => 44, 'variant' => 'glass'])
@endsection
@section('app-banner')
    @include('partials.institution-bar')
@endsection
