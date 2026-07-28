<?php

namespace App\Services\AI;

class NullAiProvider implements AiProvider
{
    public function generate(array $payload): string
    {
        $teacher = $payload['teacher_name'] ?? 'your teacher';
        $memory = $payload['experience'] ?? 'a meaningful classroom moment';
        $language = $payload['language_label'] ?? 'English';
        $type = $payload['content_type_label'] ?? 'Thank-you message';

        return "Language: {$language}\n\n{$type}\n\nDear {$teacher},\n\nThank you for guiding us with patience and care. I still remember {$memory}, and it continues to inspire me. On Guru Purnima, I want to express my gratitude for your kindness, wisdom, and belief in us.\n\nWith respect and thanks,\nA grateful student";
    }
}
