{{-- The reset link from the email opens the login page with the "Set a new password" pop-up on top --}}
@include('auth.login', ['resetPassword' => true])
