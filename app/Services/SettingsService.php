<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingsService
{
    private ?Collection $cached = null;

    public function all(): Collection
    {
        return $this->cached ??= Setting::query()->pluck('value', 'key');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key, $default);
    }

    public function put(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)]
        );

        $this->cached = null;
    }
}
