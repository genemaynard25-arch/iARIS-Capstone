@include('partials.admin-workspace', ['workspacePage' => 'director-page president-page president-' . $presidentPage . '-page', 'workspaceGroup' => auth()->user()->roleInfo()[0], 'workspaceLabel' => $presidentLabel])
@push('styles')
    <link href="{{ asset('css/director.css') }}" rel="stylesheet">
    <link href="{{ asset('css/president.css') }}" rel="stylesheet">
@endpush
