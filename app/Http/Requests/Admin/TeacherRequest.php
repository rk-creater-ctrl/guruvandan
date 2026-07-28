<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeacherRequest extends FormRequest
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
        $teacherId = $this->route('teacher')?->id;
        $userId = $this->route('teacher')?->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                $this->user()?->isSuperAdmin() ? 'required' : 'prohibited',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('teachers', 'slug')->ignore($teacherId)],
            'designation' => ['nullable', 'string', 'max:255'],
            'short_intro' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'joining_year' => ['nullable', 'integer', 'min:1950', 'max:'.((int) date('Y') + 1)],
            'location' => ['nullable', 'string', 'max:255'],
            'banner_title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'quote' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=5000,max_height=5000'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:8192', 'dimensions:max_width=7000,max_height=4000'],
            'remove_photo' => ['nullable', 'boolean'],
            'remove_cover_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'password' => [$this->user()?->isSuperAdmin() && ! $teacherId ? 'required' : 'nullable', $this->user()?->isSuperAdmin() ? 'confirmed' : 'prohibited', Password::defaults()],
            'must_change_password' => ['nullable', 'boolean'],
        ];
    }
}
