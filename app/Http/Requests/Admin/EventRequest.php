<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:255'],
            'chief_guest' => ['nullable', 'string', 'max:255'],
            'livestream_url' => ['nullable', 'url', 'max:500'],
            'schedule' => ['nullable', 'array'],
            'schedule.*.start_time' => ['required_with:schedule', 'date_format:H:i'],
            'schedule.*.title' => ['required_with:schedule', 'string', 'max:255'],
            'schedule.*.detail' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
