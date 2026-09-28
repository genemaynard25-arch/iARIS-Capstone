<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
</head>
<body class="p-5">
    <h1>Forgot Your Password?</h1>
    <p class="text-muted">Enter your email and we'll send you a link to reset it.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control mb-3" required>
        <button type="submit" class="btn btn-success">Send Reset Link</button>
    </form>
</body>
</html>