<?php

namespace App\Http\Requests\Admin;

use App\Enums\TekoPackaging;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nameId = $this->input('name.id');
        $nameEn = $this->input('name.en');
        $shortId = $this->input('short.id');
        $shortEn = $this->input('short.en');
        $personalityId = $this->input('personality.id');
        $personalityEn = $this->input('personality.en');
        $servingId = $this->input('serving.id');
        $servingEn = $this->input('serving.en');
        $descId = $this->input('description.id');
        $descEn = $this->input('description.en');
        $highlightsId = $this->input('highlights.id');
        $highlightsEn = $this->input('highlights.en');
        $notesId = $this->input('notes.id');
        $notesEn = $this->input('notes.en');

        $this->merge([
            'name' => [
                'id' => $nameId ?: $nameEn,
                'en' => $nameEn ?: $nameId,
            ],
            'short' => [
                'id' => $shortId ?: $shortEn,
                'en' => $shortEn ?: $shortId,
            ],
            'personality' => [
                'id' => $personalityId ?: $personalityEn,
                'en' => $personalityEn ?: $personalityId,
            ],
            'serving' => [
                'id' => $servingId ?: $servingEn,
                'en' => $servingEn ?: $servingId,
            ],
            'description' => [
                'id' => $descId ?: $descEn,
                'en' => $descEn ?: $descId,
            ],
            'highlights' => [
                'id' => $highlightsId ?: $highlightsEn,
                'en' => $highlightsEn ?: $highlightsId,
            ],
            'notes' => [
                'id' => $notesId ?: $notesEn,
                'en' => $notesEn ?: $notesId,
            ],
        ]);
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
                Rule::unique('products')->where('brand_id', $this->input('brand_id')),
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
