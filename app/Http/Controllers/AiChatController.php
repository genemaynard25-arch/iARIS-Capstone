<?php

namespace App\Http\Controllers;

use App\Services\AiAssistant;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    // The floating AI widget (partials/ai-widget) on every page sends its questions here.
    // (The old full-page AI Assistant at /ai-chat was removed: the widget does the same job.)
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
