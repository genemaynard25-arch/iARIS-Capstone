<!DOCTYPE html>
<html lang="en">

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iARIS Login</title>
</head>

<body class="m-0">
    <div class="d-flex" style="min-height: 100vh;">
        <div class="d-none d-md-flex flex-column p-5"
            style="width: 45%; background: linear-gradient(135deg, #059669, #047857); position: relative; overflow: hidden;">

            <!-- Left panel -->
            <div style="position: absolute; top: -60px; right: -60px; width: 220px; height: 220px; background: rgba(255,255,255,0.08); border-radius: 30px;"></div>
            <div style="position: absolute; bottom: -80px; left: -80px; width: 260px; height: 260px; background: rgba(255,255,255,0.06); border-radius: 30px;"></div>
            <div class="d-flex align-items-center mb-5">
                <div class="d-flex align-items-center justify-content-center rounded-3"
                    style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.15);">
                    <span class="text-white fw-bold">i</span>
                </div>
                <div class="ms-2">
                    <div class="text-white fw-bold fs-5">iARIS</div>
                    <div class="text-white-50 small">IATO · De La Salle Lipa</div>
                </div>
            </div>

            <h1 class="text-white fw-bold mb-3"> Admissions records, organized for everyone who needs them.</h1>
            <p class="text-white-50">
                iARIS gives the Institutional Admissions and Testing Office, college deans, the registrar, and institutional leadership a shared, real-time view of admissions data — each from the perspective that matters to their role.
            </p>

            <div class="mt-auto">
                <div class="text-white-50 small mb-2">Who uses iARIS</div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill" style="background-color: rgba(255,255,255,0.15);">IATO Admin</span>
                    <span class="badge rounded-pill" style="background-color: rgba(255,255,255,0.15);">LAMP Office</span>
                    <span class="badge rounded-pill" style="background-color: rgba(255,255,255,0.15);">Registrar</span>
                    <span class="badge rounded-pill" style="background-color: rgba(255,255,255,0.15);">Chancellor / President</span>
                    <span class="badge rounded-pill" style="background-color: rgba(255,255,255,0.15);">Dean / Program Chair</span>
                </div>
            </div>

        </div>
        <div class="d-flex flex-column justify-content-center p-5" style="width: 55%; background-color: #f5f5f0;">

        <!-- Right panel -->
        <div style="max-width: 420px; width: 100%; margin: 0 auto;">
            <h2 class="fw-bold mb-1">Welcome Back</h2>
            <p class="text-muted mb-4">Sign in with your given credentials from admissions to access your dashboard</p>   

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <!-- Email Address -->
               <label for="email" class="form-label fw-semibold">Email Address</label>
               <div class="input-group mb-3">
                    <span class="input-group-text bg-white" id="email"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" 
                    placeholder="Enter your email" required>
                </div>
                <!-- Password -->
               <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white" id="password"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" 
                    placeholder="Enter your password" required>
                    <span class="input-group-text bg-white"><i class="bi bi-eye"></i></span>
                </div>
                <!-- Check box Remember me -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" type="form-check-input" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Keep me signed in</label>
                    </div>
                    <a href="#" class="text-success text-decoration-none small">Forgot Password?</a>
                </div>
                <!-- Login Button -->
                <button type="submit" class="btn btn-success w-100 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign in</button>
            </form>

             <div class="mt-4 p-3 rounded-3 small text-muted" style="background-color: #f0fdf4;">
                Your dashboard is automatically tailored to your role — IATO staff see the full system, while deans, the registrar, LAMP office, and institutional leadership see only the data relevant to their office.
            </div>

            <p class="text-muted small text-center mt-4">© 2026 Institutional Admissions and Testing Office · De La Salle Lipa</p>
        </div>
        </div>
    </div>
</body>

</html>