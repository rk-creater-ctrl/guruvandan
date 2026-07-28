<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiAiProvider implements AiProvider
{
    public function generate(array $payload): string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        $prompt = sprintf(
            'Write a %s in %s for teacher %s. Include this memory: %s. Desired length: %s. Keep the tone heartfelt, respectful, and suitable for Guru Purnima.',
            $payload['content_type_label'] ?? 'thank-you message',
            $payload['language_label'] ?? 'English',
            $payload['teacher_name'] ?? 'Teacher',
            $payload['experience'] ?? 'a meaningful memory',
            $payload['desired_length'] ?? 'medium'
        );

        $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
            ->connectTimeout(5)
            ->timeout(15)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ]);

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if (! $response->successful() || ! $text) {
            throw new RuntimeException('AI provider could not generate a response right now.');
        }

        return mb_substr(trim($text), 0, 6000);
    }
}
