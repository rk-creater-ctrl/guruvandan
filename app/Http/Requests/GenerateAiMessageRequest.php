<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAiMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'teacher_name' => ['required', 'string', 'max:255'],
            'experience' => ['required', 'string', 'max:1500'],
            'language' => ['required', Rule::in(['english', 'hindi', 'hinglish'])],
            'content_type' => ['required', 'in:thank_you_message,poem,letter,short_speech,guru_purnima_wish'],
            'desired_length' => ['required', 'in:short,medium,long'],
        ];
    }
}
