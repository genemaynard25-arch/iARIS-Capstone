<!DOCTYPE html>
<html>

<head>
    <title>Two-Factor Authentication</title>
</head>

<body>
    <h1>Two-Factor Authentication</h1>

    @if ($errors->any())
    <div style="color: red;">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (!$enabled)
    <p>Two-factor authentication is not enabled yet.</p>
    <form method="POST" action="/two-factor/enable">
        @csrf
        <button type="submit">Enable Two-Factor Authentication</button>
    </form>
    @elseif (!$confirmed)
    <p>Scan this with an authenticator app (like Google Authenticator), then enter the 6-digit code below.</p>
    <div>{!! $qrCode !!}</div>

    <form method="POST" action="/two-factor/confirm">
        @csrf
        <label for="code">Authentication Code</label>
        <input type="text" name="code" id="code" required>
        <button type="submit">Confirm</button>
    </form>
    @else
    <p>Two-factor authentication is enabled and confirmed.</p>
    @endif
</body>

</html>