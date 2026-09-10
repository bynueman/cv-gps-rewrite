<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Translate a single text string between languages (id <-> en).
     */
    public function translateText(string $text, string $from = 'id', string $to = 'en'): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return '';
        }

        try {
            $response = Http::timeout(12)->get('https://api.mymemory.translated.net/get', [
                'q' => $trimmed,
                'langpair' => "{$from}|{$to}",
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $translated = $data['responseData']['translatedText'] ?? null;
                if ($translated && is_string($translated)) {
                    return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Translation failed', [
                'error' => $e->getMessage(),
                'from' => $from,
                'to' => $to,
            ]);
        }

        return $text;
    }

    /**
     * Translate a map of fields in batch.
     *
     * @param  array<string, string|null>  $fields
     * @return array<string, string>
     */
    public function translateBatch(array $fields, string $from = 'id', string $to = 'en'): array
    {
        $results = [];

        foreach ($fields as $key => $value) {
            if ($value === null || trim((string) $value) === '') {
                $results[$key] = '';

                continue;
            }

            // Handle HTML / rich text (such as article body)
            if ($key === 'body' || strip_tags((string) $value) !== (string) $value) {
                $results[$key] = $this->translateHtml((string) $value, $from, $to);

                continue;
            }

            // For multiline lists (like highlights or notes separated by newlines),
            // translate line by line to preserve list integrity.
            if (in_array($key, ['highlights', 'notes'], true) && str_contains((string) $value, "\n")) {
                $lines = explode("\n", (string) $value);
                $translatedLines = [];
                foreach ($lines as $line) {
                    $trimmedLine = trim($line);
                    $translatedLines[] = $trimmedLine === '' ? '' : $this->translateText($trimmedLine, $from, $to);
                }
                $results[$key] = implode("\n", $translatedLines);

                continue;
            }

            $results[$key] = $this->translateText((string) $value, $from, $to);
        }

        return $results;
    }

    /**
     * Translate rich HTML content by translating individual block elements while preserving HTML structure.
     */
    public function translateHtml(string $html, string $from = 'id', string $to = 'en'): string
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return '';
        }

        // If no HTML tags found, handle as plain text
        if (strip_tags($trimmed) === $trimmed) {
            return $this->translateText($trimmed, $from, $to);
        }

        // Translate content inside block tags: <p>, <h1>-<h6>, <li>, <blockquote>
        $pattern = '/(<(p|h[1-6]|li|blockquote)[^>]*>)(.*?)(<\/\2>)/is';
        if (preg_match($pattern, $trimmed)) {
            return (string) preg_replace_callback($pattern, function (array $matches) use ($from, $to): string {
                $openTag = $matches[1];
                $inner = $matches[3];
                $closeTag = $matches[4];

                $textOnly = trim(strip_tags($inner));
                if ($textOnly === '') {
                    return $matches[0];
                }

                return $openTag.$this->translateText($inner, $from, $to).$closeTag;
            }, $trimmed);
        }

        return $this->translateText($trimmed, $from, $to);
    }
}
