<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
            'whatsapp' => ['required', 'string', 'max:50'],
            'email_primary' => ['required', 'email', 'max:255'],
            'email_secondary' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'instagram_kuicip' => ['nullable', 'string', 'max:255'],
            'instagram_putriteko' => ['nullable', 'string', 'max:255'],
            'operating_hours' => ['required', 'string', 'max:255'],
            'site_title' => ['required', 'string', 'max:255'],
            'meta_description_default' => ['required', 'string', 'max:160'],
            'og_image_default' => ['nullable', 'string'],
        ];
    }
}
