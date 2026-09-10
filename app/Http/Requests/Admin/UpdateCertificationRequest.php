<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'issuer' => $this->input('issuer') ?: null,
            'valid_until' => $this->input('valid_until') ?: null,
            'pdf_url' => $this->input('pdf_url') ?: null,
            'logo' => $this->input('logo') ?: null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(['legal', 'halal', 'bpom', 'pirt', 'other'])],
            'logo' => ['nullable', 'string'],
            'pdf_url' => ['nullable', 'string', 'max:2048'],
            'valid_until' => ['nullable', 'date'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
