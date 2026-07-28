<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StudentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('username')) {
            $this->merge(['username' => strtolower((string) $this->input('username'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($student?->id)],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($student?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'class_name' => ['required', 'string', 'max:100'],
            'section' => ['nullable', 'string', 'max:30'],
            'roll_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('student_profiles')->where(fn ($query) => $query
                    ->where('class_name', $this->input('class_name'))
                    ->where('section', $this->input('section')))->ignore($student?->studentProfile?->id),
            ],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
            'password' => [$student ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }
}
