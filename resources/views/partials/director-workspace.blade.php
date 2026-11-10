@include('partials.admin-workspace', ['workspacePage' => 'director-page director-' . $directorPage . '-page', 'workspaceGroup' => auth()->user()->role === 'dean' ? 'Dean · ' . \App\Http\Controllers\CollegeController::college()['code'] : (auth()->user()->isGradChair() ? 'Program Chair · Graduate Programs' : 'iPACE Director'), 'workspaceLabel' => $directorLabel])
@push('styles')
    <link href="{{ asset('css/director.css') }}" rel="stylesheet">
@endpush
