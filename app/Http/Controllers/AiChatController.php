<?php

namespace App\Http\Controllers;

use App\Services\AiAssistant;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function show()
    {
        return view('ai-chat');

    }

    public function ask(Request $request, AiAssistant $ai)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $reply = $ai->askQuestion($request->message);

        return back()->with([
            'question' => $request->message,
            'reply' => $reply,
        ]);
    }

     public function widgetAsk(Request $request, AiAssistant $ai)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'context' => 'nullable|string|max:6000',
        ]);

        $reply = $ai->askWithContext($request->message, $request->context);

        return response()->json(['reply' => $reply]);
    }
}
