<?php

namespace App\Http\Requests\Admin;

use App\Enums\TekoPackaging;
use App\Models\Product;
use App\Rules\ShopeeUrl;
use App\Support\BilingualLists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $product = $this->route('product');
        foreach (['highlights', 'notes'] as $field) {
            $existing = $product instanceof Product ? $product->$field : [];
            $this->merge([$field => BilingualLists::input($this->input($field, $existing), $existing)]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'translation_english_edits' => ['sometimes', 'array', 'max:120'],
            'translation_english_edits.*' => ['string', 'max:100', 'distinct'],
            'brand_id' => ['required', 'exists:brands,id'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('products')->where('brand_id', $this->input('brand_id'))->ignore($this->route('product')),
            ],
            'group' => ['nullable', 'string', Rule::in(['rtd', 'brew'])],
            'packaging' => ['nullable', 'string', Rule::in(array_column(TekoPackaging::cases(), 'value'))],
            'name.id' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'short.id' => ['required', 'string', 'max:255'],
            'short.en' => ['nullable', 'string', 'max:255'],
            'personality.id' => ['required', 'string', 'max:255'],
            'personality.en' => ['nullable', 'string', 'max:255'],
            'serving.id' => ['nullable', 'string', 'max:255'],
            'serving.en' => ['nullable', 'string', 'max:255'],
            'description.id' => ['required', 'string', 'max:50000'],
            'description.en' => ['nullable', 'string', 'max:50000'],
            'highlights' => ['array', 'max:50'],
            'highlights.*.key' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,80}$/', 'distinct'],
            'highlights.*.id' => ['required', 'string', 'max:2000'],
            'highlights.*.en' => ['nullable', 'string', 'max:2000'],
            'notes' => ['array', 'max:50'],
            'notes.*.key' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,80}$/', 'distinct'],
            'notes.*.id' => ['required', 'string', 'max:2000'],
            'notes.*.en' => ['nullable', 'string', 'max:2000'],
            'translation_revision' => ['nullable', 'uuid'],
            'translation_refresh' => ['boolean'],
            'translation_overwrite_manual' => ['boolean'],
            'color' => ['required', 'string', 'max:20'],
            'color_dark' => ['required', 'string', 'max:20'],
            'shopee_url' => ['nullable', 'string', 'max:2048', new ShopeeUrl],
            'net_weight' => ['nullable', 'string', 'max:80'],
            'seo_title_id' => ['nullable', 'string', 'max:120'],
            'seo_title_en' => ['nullable', 'string', 'max:120'],
            'meta_description_id' => ['nullable', 'string', 'max:160'],
            'meta_description_en' => ['nullable', 'string', 'max:160'],
            'image_alt_id' => ['nullable', 'string', 'max:255'],
            'image_alt_en' => ['nullable', 'string', 'max:255'],
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
