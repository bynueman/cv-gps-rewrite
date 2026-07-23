<?php

namespace App\Http\Requests\Admin;

use App\Enums\TekoPackaging;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'brand_id' => ['required', 'exists:brands,id'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('products')->where('brand_id', $this->input('brand_id'))->ignore($this->route('product')),
            ],
            'group' => ['nullable', 'string', Rule::in(['rtd', 'brew'])],
            'packaging' => ['nullable', 'string', Rule::in(array_column(TekoPackaging::cases(), 'value'))],
            'name.id' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'short.id' => ['required', 'string', 'max:255'],
            'short.en' => ['required', 'string', 'max:255'],
            'personality.id' => ['required', 'string', 'max:255'],
            'personality.en' => ['required', 'string', 'max:255'],
            'serving.id' => ['nullable', 'string', 'max:255'],
            'serving.en' => ['nullable', 'string', 'max:255'],
            'description.id' => ['required', 'string'],
            'description.en' => ['required', 'string'],
            'highlights.id' => ['nullable', 'string'],
            'highlights.en' => ['nullable', 'string'],
            'notes.id' => ['nullable', 'string'],
            'notes.en' => ['nullable', 'string'],
            'color' => ['required', 'string', 'max:20'],
            'color_dark' => ['required', 'string', 'max:20'],
            'image' => ['nullable', 'string'],
            'image_thumb' => ['nullable', 'string'],
            'image_og' => ['nullable', 'string'],
            'featured' => ['boolean'],
            'placeholder' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}
