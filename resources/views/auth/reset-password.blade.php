<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body class="p-5">
    <h1>Reset Password</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control mb-3" value="{{ old('email', $request->email) }}" required>

        <label for="password" class="form-label">New Password</label>
        <input type="password" name="password" id="password" class="form-control mb-3" required>

        <label for="password_confirmation" class="form-label">Confirm New Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control mb-3" required>

        <button type="submit" class="btn btn-success">Reset Password</button>
    </form>
</body>
</html>