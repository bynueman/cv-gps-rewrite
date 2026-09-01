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

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'issuer'      => ['nullable', 'string', 'max:255'],
            'category'    => ['required', 'string', Rule::in(['legal', 'halal', 'bpom', 'pirt', 'other'])],
            'logo'        => ['nullable', 'string'],
            'pdf_url'     => ['nullable', 'string', 'max:2048'],
            'valid_until' => ['nullable', 'date'],
            'sort_order'  => ['integer', 'min:0'],
            'is_active'   => ['boolean'],
        ];
    }
}
