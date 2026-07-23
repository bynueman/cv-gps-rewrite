<?php

namespace App\Support;

/**
 * Fixed taxonomy (not free text, not a DB table) — literal port of the
 * Next.js app's src/lib/articleCategories.ts. Only category_slug is
 * stored on the articles table; id/en labels are resolved from here at
 * render time.
 */
class ArticleCategory
{
    /** @var array<int, array{slug: string, id: string, en: string}> */
    public const CATEGORIES = [
        ["slug" => "produk-resep", "id" => "Produk & Resep", "en" => "Products & Recipes"],
        ["slug" => "kegiatan-perusahaan", "id" => "Kegiatan Perusahaan", "en" => "Company Activities"],
        ["slug" => "edukasi-bahan", "id" => "Edukasi Bahan", "en" => "Ingredient Education"],
        ["slug" => "ekspor-kemitraan", "id" => "Ekspor & Kemitraan", "en" => "Export & Partnership"],
    ];

    public static function slugs(): array
    {
        return array_column(self::CATEGORIES, "slug");
    }

    public static function find(string $slug): ?array
    {
        foreach (self::CATEGORIES as $category) {
            if ($category["slug"] === $slug) {
                return $category;
            }
        }

        return null;
    }

    public static function label(string $slug, string $lang = "id"): string
    {
        return self::find($slug)[$lang] ?? $slug;
    }
}
