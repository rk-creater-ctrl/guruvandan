<?php

namespace App\Services;

use Carbon\Carbon;

class RevealService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function isRevealed(): bool
    {
        if ((bool) $this->settings->get('reveal_enabled', false)) {
            return true;
        }

        $scheduledAt = $this->settings->get('reveal_at');
        if (! $scheduledAt) {
            return false;
        }

        return Carbon::parse($scheduledAt)->isPast();
    }
}
