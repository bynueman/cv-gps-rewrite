<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Passed as an Inertia prop ("seo") by every public controller. Read
 * directly by resources/views/app.blade.php on the server for the
 * first-load HTML response — this is what lets non-JS crawlers and
 * social-preview bots see real title/meta/OG/JSON-LD without any
 * client-side rendering. See the rewrite plan's "Head-rendering
 * architecture" section for why this exists instead of Inertia SSR.
 */
class Seo
{
    public string $title;
    public ?string $ogImage;
    public ?int $ogImageWidth;
    public ?int $ogImageHeight;

    /**
     * @param  string  $title  Page-specific title fragment — the site name
     *                         suffix (" — CV Gama Putra Santosa") is applied
     *                         automatically, so callers should NOT include it.
     * @param  string|null  $ogImage  Falls back to Pengaturan's og_image_default
     *                                (then the static og-default.webp) when omitted.
     * @param  string[]  $tags
     * @param  array<int, array<string, mixed>>  $jsonLd  One or more JSON-LD documents (e.g. NewsArticle, BreadcrumbList).
     */
    public function __construct(
        string $title,
        public string $description,
        public ?string $canonical = null,
        public string $ogType = "website",
        ?string $ogImage = null,
        ?int $ogImageWidth = null,
        ?int $ogImageHeight = null,
        public ?string $publishedTime = null,
        public array $tags = [],
        public string $twitterCard = "summary_large_image",
        public array $jsonLd = [],
    ) {
        $siteName = config('company.name');
        $this->title = str_ends_with($title, $siteName) ? $title : "{$title} — {$siteName}";

        if ($ogImage) {
            $this->ogImage = $ogImage;
            $this->ogImageWidth = $ogImageWidth;
            $this->ogImageHeight = $ogImageHeight;
        } else {
            $fallback = Setting::get('og_image_default', '/images/og-default.webp');
            $this->ogImage = $fallback ? url($fallback) : null;
            $this->ogImageWidth = $fallback ? 1200 : null;
            $this->ogImageHeight = $fallback ? 630 : null;
        }
    }

    public function toArray(): array
    {
        return [
            "title" => $this->title,
            "description" => $this->description,
            "canonical" => $this->canonical,
            "ogType" => $this->ogType,
            "ogImage" => $this->ogImage,
            "ogImageWidth" => $this->ogImageWidth,
            "ogImageHeight" => $this->ogImageHeight,
            "publishedTime" => $this->publishedTime,
            "tags" => $this->tags,
            "twitterCard" => $this->twitterCard,
            "jsonLd" => $this->jsonLd,
        ];
    }
}
