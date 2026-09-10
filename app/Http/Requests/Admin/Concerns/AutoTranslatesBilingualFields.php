<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Services\TranslationService;

trait AutoTranslatesBilingualFields
{
    /**
     * Automatically translates missing language fields (id <-> en) when saving.
     *
     * @param  array<string>  $fields
     * @param  bool  $isHtmlBody  whether 'body' field contains HTML
     */
    protected function autoTranslateFields(array $fields, bool $isHtmlBody = false): void
    {
        $isEmpty = function ($val, bool $isHtml = false): bool {
            if ($val === null) {
                return true;
            }
            $str = (string) $val;
            if ($isHtml) {
                return trim(strip_tags($str)) === '';
            }

            return trim($str) === '';
        };

        $toTranslateToEn = [];
        $toTranslateToId = [];

        foreach ($fields as $field) {
            $isHtml = $isHtmlBody && $field === 'body';
            $idVal = (string) $this->input("{$field}.id", '');
            $enVal = (string) $this->input("{$field}.en", '');

            $idEmpty = $isEmpty($idVal, $isHtml);
            $enEmpty = $isEmpty($enVal, $isHtml);

            if (! $idEmpty && $enEmpty) {
                $toTranslateToEn[$field] = $idVal;
            } elseif (! $enEmpty && $idEmpty) {
                $toTranslateToId[$field] = $enVal;
            }
        }

        $translator = app(TranslationService::class);

        $translatedEn = ! empty($toTranslateToEn)
            ? $translator->translateBatch($toTranslateToEn, 'id', 'en')
            : [];

        $translatedId = ! empty($toTranslateToId)
            ? $translator->translateBatch($toTranslateToId, 'en', 'id')
            : [];

        $merged = [];
        foreach ($fields as $field) {
            $isHtml = $isHtmlBody && $field === 'body';
            $idVal = (string) $this->input("{$field}.id", '');
            $enVal = (string) $this->input("{$field}.en", '');

            $idEmpty = $isEmpty($idVal, $isHtml);
            $enEmpty = $isEmpty($enVal, $isHtml);

            $finalId = ! $idEmpty ? $idVal : ($translatedId[$field] ?? $enVal);
            $finalEn = ! $enEmpty ? $enVal : ($translatedEn[$field] ?? $idVal);

            $merged[$field] = [
                'id' => $finalId,
                'en' => $finalEn,
            ];
        }

        $this->merge($merged);
    }
}
