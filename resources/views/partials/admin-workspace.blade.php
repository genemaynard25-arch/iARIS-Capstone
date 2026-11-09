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
    <div class="ac-institution-bar"><span><strong>De La Salle Lipa</strong><span class="mx-2 opacity-50">|</span>Institutional Admissions and Testing Office</span><span class="d-none d-md-inline">iARIS · Administration</span></div>
@endsection
