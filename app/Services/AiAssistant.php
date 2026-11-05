<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiAssistant
{
   public function askQuestion(string $prompt): string
    {
        $response = Http::withHeaders([
            'x-goog-api-key' => config('services.gemini.key'),
            'Content-Type' => 'application/json',
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent', [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ]);

        $text = $response->json('candidates.0.content.parts.0.text');

        if ($text === null) {
            \Log::warning('Gemini did not return usable text', [
                'httpStatus' => $response->status(),
                'error' => $response->json('error'),
            ]);
        }

        return $text ?? 'Sorry, I could not generate a response.';
    }

    /**
     * Used by the floating widget. Feeds the AI the visible text of whatever
     * page the user is on, and lets it reply with a chart spec when useful.
     */
    public function askWithContext(string $message, ?string $context = null): string
    {
        $instructions = 'You are the AI Assistant inside iARIS, a school admissions records system. '
            . 'Answer the user question helpfully and concisely, using ONLY the page context given below '
            . "when relevant. Never invent numbers that aren't in it.\n\n"
            . 'If a chart would genuinely help (comparing a few values, showing a trend), add a new line '
            . 'at the very end of your reply that starts with exactly CHART: followed by compact JSON '
            . 'with no extra formatting, for example:'
            . "\nCHART: {\"type\": \"bar\", \"title\": \"Short title\", \"labels\": [\"Label 1\", \"Label 2\"], \"values\": [10, 20]}"
            . "\nValid type values are bar, line, or pie. Only add this line when a chart genuinely helps "
            . "- most answers don't need one. Never fabricate numbers that aren't in the context.";

        $prompt = $instructions
            . "\n\n--- Current page context ---\n"
            . ($context ?: '(no page context provided)')
            . "\n--- End of page context ---\n\nUser's question: " . $message;

        return $this->askQuestion($prompt);
    }
}