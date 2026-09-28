{{-- /forgot-password opens the login page with the Forgot Password pop-up on top.
     Once the link has been sent (session status), show the login page with the message instead. --}}
@include('auth.login', ['openForgot' => ! session('status')])
