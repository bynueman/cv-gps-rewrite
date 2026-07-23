<?php

namespace App\Support;

use Stevebauman\Purify\Facades\Purify;

/**
 * Sanitizes rich-text HTML from the admin editor before it's stored —
 * literal port of the original Next.js app's sanitize-html allowlist
 * (see config/purify.php "article" config set). Defense in depth even
 * though only the single admin account can write this content (it's
 * rendered back out via dangerouslySetInnerHTML on public pages).
 *
 * HTMLPurifier's URI.AllowedSchemes only constrains href/src destinations;
 * it doesn't force rel/target on every <a>, so a DOMDocument post-pass
 * guarantees exact parity with the original's transformTags behavior
 * rather than relying on HTMLPurifier directives for it.
 */
class ArticleHtmlSanitizer
{
    public static function clean(string $html): string
    {
        $purified = Purify::config('article')->clean($html);

        return self::forceAnchorRelTarget($purified);
    }

    private static function forceAnchorRelTarget(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $anchor->setAttribute('rel', 'noopener noreferrer');
            $anchor->setAttribute('target', '_blank');
        }

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $inner = '';
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $inner .= $dom->saveHTML($child);
        }

        return $inner;
    }
}
