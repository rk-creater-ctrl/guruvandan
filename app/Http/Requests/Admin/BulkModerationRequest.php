<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkModerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'tribute_ids' => ['required', 'array', 'min:1', 'max:100'],
            'tribute_ids.*' => ['integer', 'distinct', 'exists:tributes,id'],
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:1500'],
        ];
    }
}
