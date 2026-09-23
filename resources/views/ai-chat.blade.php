<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iARIS AI Assistant</title>
</head>
<body>
     <h1>Ask the AI Assistant</h1>

    <form method="POST" action="/ai-chat">
        @csrf
        <textarea name="message" class="form-control mb-3" rows="3" placeholder="Ask something..." required></textarea>
        <button type="submit" class="btn btn-success">Send</button>
    </form>

    @if (session('question'))
        <div class="mt-4">
            <p><strong>You asked:</strong> {{ session('question') }}</p>
            <p><strong>AI replied:</strong> {{ session('reply') }}</p>
        </div>
    @endif
</body>
</html>