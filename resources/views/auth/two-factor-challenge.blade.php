<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Two-Factor Challenge</title>
</head>

<body>
    <h1>Enter your authentication code</h1>

    @if ($errors->any())
    <div style="color: red;">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf
        <label for="code">Authentication Code</label>
        <input type="text" name="code" id="code" autofocus>
        <button type="submit">Verify</button>
    </form>
</body>

</html>