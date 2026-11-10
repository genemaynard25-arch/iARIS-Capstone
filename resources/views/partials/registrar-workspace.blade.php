@include('partials.admin-workspace', ['workspacePage' => 'registrar-page ' . $registrarPage, 'workspaceGroup' => auth()->user()->assigned_level === 'is' ? 'IS Registrar' : 'College Registrar', 'workspaceLabel' => $registrarLabel])
@push('styles')
    <link href="{{ asset('css/registrar.css') }}" rel="stylesheet">
@endpush
