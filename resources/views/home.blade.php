<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iARIS Home</title>
</head>
<body>
    <h1>Welcome, you are loggined in!</h1>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type='submit'>Log Out</button>
    </form>
</body>
</html>