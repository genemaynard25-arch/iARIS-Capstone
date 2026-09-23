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
        // debbuging line
        // dd(config('services.gemini.key'));    

        return $response->json('candidates.0.content.parts.0.text')
            ?? 'Sorry, I could not generate a response.';
    }
}