<?php

namespace App\Services;

use App\Exceptions\TranslationFailure;
use App\Support\ArticleHtmlSanitizer;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class TranslationService
{
    private int $requests = 0;

    public function resetBudget(): void
    {
        $this->requests = 0;
    }

    public static function hash(string $text): string
    {
        return hash('sha256', str_replace(["\r\n", "\r"], "\n", trim($text)));
    }

    public function translateText(string $text, string $from = 'id', string $to = 'en'): string
    {
        if (! in_array($from, ['id', 'en'], true) || ! in_array($to, ['id', 'en'], true) || $from === $to) {
            throw new TranslationFailure('language');
        }
        if (trim($text) === '') {
            return '';
        }
        if (strlen($text) > 50000) {
            throw new TranslationFailure('size');
        }
        preg_match('/^(\s*)([\s\S]*?)(\s*)$/u', $text, $edges);
        $glossary = config('translations.glossary', []);
        if ($from === 'id' && array_key_exists(trim($text), $glossary)) {
            return $edges[1].$glossary[trim($text)].$edges[3];
        }
        foreach ($glossary as $source => $target) {
            if (strcasecmp(trim($text), $from === 'id' ? $source : $target) === 0) {
                return $edges[1].($from === 'id' ? $target : $source).$edges[3];
            }
        }
        if (preg_match('/^[\pN\pP\s]+$/u', $text) || filter_var(trim($text), FILTER_VALIDATE_URL)) {
            return $text;
        }

        $parts = [];
        $chunk = '';
        foreach (preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $word) {
            if (strlen($word) > 500) {
                throw new TranslationFailure('segment_size');
            }
            if (strlen($chunk.$word) > 500) {
                $parts[] = $chunk;
                $chunk = '';
            }
            $chunk .= $word;
        }
        if ($chunk !== '') {
            $parts[] = $chunk;
        }

        $result = '';
        foreach ($parts as $part) {
            preg_match('/^(\s*)([\s\S]*?)(\s*)$/u', $part, $edges);
            $result .= $edges[1].$this->segment($edges[2], $from, $to).$edges[3];
        }

        return $result;
    }

    private function segment(string $text, string $from, string $to): string
    {
        if ($text === '') {
            return '';
        }
        $key = 'cms-translation:'.hash('sha256', config('translations.provider').'|'.config('translations.glossary_version')."|$from|$to|".self::hash($text));
        $cached = Cache::get($key);
        if (is_string($cached) && trim($cached) !== '') {
            return $cached;
        }
        if (! config('translations.enabled') || config('translations.provider') !== 'mymemory') {
            throw new TranslationFailure('disabled');
        }

        for ($attempt = 0; $attempt < 2; $attempt++) {
            if ($this->requests >= config('translations.requests_per_job', 4)) {
                throw new TranslationFailure('budget');
            }
            $this->requests++;
            $this->reserve(mb_strlen($text));
            try {
                $response = Http::connectTimeout(2)->timeout(config('translations.timeout', 5))
                    ->get('https://api.mymemory.translated.net/get', ['q' => $text, 'langpair' => "$from|$to"]);
            } catch (ConnectionException) {
                if ($attempt === 0) {
                    continue;
                }
                throw new TranslationFailure('timeout');
            } catch (Throwable) {
                throw new TranslationFailure('provider');
            }
            $data = $response->json();
            if (! is_array($data)) {
                throw new TranslationFailure('provider');
            }
            if ($response->status() === 429 || in_array((int) ($data['responseStatus'] ?? 0), [403, 429], true)
                || preg_match('/quota|limit|too many|next available/i', (is_string($data['responseDetails'] ?? null) ? $data['responseDetails'] : ''))) {
                throw new TranslationFailure('quota');
            }
            if ($response->serverError() && $attempt === 0) {
                continue;
            }
            $translated = $data['responseData']['translatedText'] ?? null;
            if (! $response->successful() || (int) ($data['responseStatus'] ?? 0) !== 200
                || ! is_string($translated) || trim($translated) === '') {
                throw new TranslationFailure('provider');
            }
            $translated = html_entity_decode(trim($translated), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (self::hash($text) === self::hash($translated)) {
                throw new TranslationFailure('unchanged');
            }
            $this->assertMeaning($text, $translated);
            Cache::put($key, $translated, now()->addDays(config('translations.cache_days', 30)));

            return $translated;
        }

        throw new TranslationFailure('provider');
    }

    private function reserve(int $characters): void
    {
        $allowed = RateLimiter::attempt('cms-translation:requests', 12, fn () => true, 60);
        if (! $allowed) {
            throw new TranslationFailure('rate_limited');
        }
        try {
            Cache::lock('cms-translation:quota-lock', 5)->block(2, function () use ($characters) {
                $key = 'cms-translation:characters:'.now('UTC')->format('Y-m-d');
                $used = (int) Cache::get($key, 0);
                if ($used + $characters > config('translations.characters_per_day', 4000)) {
                    throw new TranslationFailure('quota');
                }
                Cache::put($key, $used + $characters, now()->addDay());
            });
        } catch (TranslationFailure $e) {
            throw $e;
        } catch (Throwable) {
            throw new TranslationFailure('storage');
        }
    }

    private function assertMeaning(string $source, string $target): void
    {
        foreach (config('translations.protected_terms', []) as $term) {
            if (stripos($source, $term) !== false && stripos($target, $term) === false) {
                throw new TranslationFailure('protected_term');
            }
        }
        preg_match_all('/\d+(?:[.,]\d+)*/u', $source, $before);
        preg_match_all('/\d+(?:[.,]\d+)*/u', $target, $after);
        sort($before[0]);
        sort($after[0]);
        if ($before[0] !== $after[0]) {
            throw new TranslationFailure('quantity');
        }
        foreach ([
            '/no\s*msg|tanpa\s*msg/i', '/gluten[ -]*free|bebas\s*gluten/i',
            '/organic|organik/i', '/halal/i', '/no preservatives|without preservatives|tanpa pengawet/i',
            '/\b(?:cure(?:s|d)?|heal(?:s|ed|ing)?|menyembuhkan|mengobati)\b/i',
        ] as $claim) {
            if (preg_match($claim, $target) && ! preg_match($claim, $source)) {
                throw new TranslationFailure('new_claim');
            }
        }
    }

    public function translateBatch(array $fields, string $from = 'id', string $to = 'en'): array
    {
        $result = [];
        foreach ($fields as $key => $value) {
            $value = (string) $value;
            if ($key === 'body') {
                $result[$key] = $this->translateHtml($value, $from, $to);
            } elseif (in_array($key, ['highlights', 'notes'], true)) {
                $result[$key] = implode("\n", array_map(fn ($line) => $this->translateText($line, $from, $to), explode("\n", $value)));
            } else {
                $result[$key] = $this->translateText($value, $from, $to);
            }
        }

        return $result;
    }

    public function translateHtml(string $html, string $from = 'id', string $to = 'en'): string
    {
        $html = ArticleHtmlSanitizer::clean($html);
        if (trim(strip_tags($html)) === '') {
            return $html;
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTML('<?xml encoding="UTF-8"><div id="cms-translation-root">'.$html.'</div>', LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new DOMXPath($dom);
            $root = $dom->getElementById('cms-translation-root');
            foreach ($xpath->query('.//text()[not(ancestor::code) and not(ancestor::pre)] | .//@alt | .//@title', $root) as $node) {
                if (trim($node->nodeValue) !== '') {
                    $node->nodeValue = $this->translateText($node->nodeValue, $from, $to);
                }
            }
            $result = '';
            foreach ($root->childNodes as $node) {
                $result .= $dom->saveHTML($node);
            }

            return ArticleHtmlSanitizer::clean($result);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
