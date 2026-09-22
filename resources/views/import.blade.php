<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Import Applicants</title>
</head>

<body>
    <h1>Import Applicants from Excel</h1>

    @if (session('success'))
    <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if ($errors->any())
    <div style="color: red;">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="/import" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".xlsx,.csv">
        <button type="submit">Upload</button>
    </form>
</body>

</html>