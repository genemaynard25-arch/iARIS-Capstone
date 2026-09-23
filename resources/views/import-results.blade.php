<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Import Results</title>
</head>

<body>
    <h1>Import Results</h1>
    <p>{{ $applicants->count() }} applicants imported from "{{ $batch->original_filename }}".</p>

    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>Reference #</th>
                <th>Name</th>
                <th>Gender</th>
                <th>Level</th>
                <th>Sub-Level</th>
                <th>School Year</th>
                <th>Program/Track</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($applicants as $applicant)
            <tr>
                <td>{{ $applicant->reference_number }}</td>
                <td>{{ $applicant->first_name }} {{ $applicant->last_name }}</td>
                <td>{{ $applicant->gender }}</td>
                <td>{{ $applicant->level }}</td>
                <td>{{ $applicant->sub_level }}</td>
                <td>{{ $applicant->school_year }}</td>
                <td>{{ $applicant->program_or_track }}</td>
                <td>{{ $applicant->application_status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p><a href="/import">Import another file</a></p>
</body>

</html>