<?php

namespace App\Services\AI;

interface AiProvider
{
    public function generate(array $payload): string;
}
