<?php

namespace App\Services\AI;

use Throwable;

class AiMessageGenerator
{
    public function __construct(
        private readonly AiProvider $provider,
        private readonly ?AiProvider $fallback = null,
    ) {}

    public function generate(array $payload): string
    {
        try {
            return $this->provider->generate($payload);
        } catch (Throwable $exception) {
            if (! $this->fallback) {
                throw $exception;
            }

            return $this->fallback->generate($payload);
        }
    }
}
