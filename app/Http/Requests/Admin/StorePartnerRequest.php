<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(['ritel', 'hotel', 'restaurant', 'oleh-oleh', 'cakery'])],
            'logo' => ['nullable', 'string'],
            'sort_order' => ['integer'],
            'is_active' => ['boolean'],
        ];
    }
}
