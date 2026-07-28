<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:255'],
            'platform_tagline' => ['required', 'string', 'max:255'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:1000'],
            'school_email' => ['nullable', 'email:rfc', 'max:255'],
            'school_phone' => ['nullable', 'string', 'max:30'],
            'principal_name' => ['required', 'string', 'max:255'],
            'school_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'principal_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'event_title' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'event_venue' => ['nullable', 'string', 'max:255'],
            'chief_guest' => ['nullable', 'string', 'max:255'],
            'celebration_message' => ['nullable', 'string', 'max:2000'],
            'certificate_title' => ['required', 'string', 'max:255'],
            'certificate_text' => ['required', 'string', 'max:2000'],
            'certificate_footer' => ['nullable', 'string', 'max:500'],
            'certificate_signature_label' => ['nullable', 'string', 'max:100'],
            'certificate_template' => ['required', 'in:classic,floral,minimal'],
            'ai_enabled' => ['nullable', 'boolean'],
            'ai_rate_limit' => ['required', 'integer', 'min:1', 'max:60'],
            'ai_fallback_enabled' => ['nullable', 'boolean'],
            'upload_image_kb' => ['required', 'integer', 'min:256', 'max:20480'],
            'upload_audio_kb' => ['required', 'integer', 'min:512', 'max:51200'],
            'upload_video_kb' => ['required', 'integer', 'min:1024', 'max:204800'],
            'upload_allowed_types' => ['required', 'array', 'min:1'],
            'upload_allowed_types.*' => ['distinct', 'in:jpg,jpeg,png,webp,mp3,wav,m4a,mp4,webm'],
            'reveal_enabled' => ['nullable', 'boolean'],
            'reveal_at' => ['nullable', 'date'],
        ];
    }
}
