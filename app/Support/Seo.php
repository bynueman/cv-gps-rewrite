<?php

namespace App\Support;

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
    /**
     * @param  string[]  $tags
     * @param  array<int, array<string, mixed>>  $jsonLd  One or more JSON-LD documents (e.g. NewsArticle, BreadcrumbList).
     */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $canonical = null,
        public string $ogType = "website",
        public ?string $ogImage = null,
        public ?int $ogImageWidth = null,
        public ?int $ogImageHeight = null,
        public ?string $publishedTime = null,
        public array $tags = [],
        public string $twitterCard = "summary_large_image",
        public array $jsonLd = [],
    ) {
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
