<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Shared upload pipeline for every admin module with an image field
 * (Produk, Artikel, Mitra, Pengaturan). Every accepted file is
 * re-encoded to WebP — auto-orient bakes EXIF rotation into the pixel
 * data on read, and the re-encoded output carries no EXIF metadata, so
 * this doubles as a strip. Real image decode (not just the client's MIME
 * claim) is what actually gates a renamed/spoofed file.
 */
class ImageProcessor
{
    private const ALLOWED_MEDIA_TYPES = [
        'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/x-jpeg',
        'image/png', 'image/x-png', 'image/webp', 'image/x-webp',
    ];

    /** Folders an upload is allowed to land in — deliberately not free-form input. */
    public const CONTEXTS = ['products', 'articles', 'partners', 'settings', 'certifications'];

    /**
     * @throws \RuntimeException if the file doesn't decode as a real image
     */
    public function assertValidImage(UploadedFile $file): void
    {
        try {
            $image = Image::read($file->getRealPath());
        } catch (\Throwable) {
            throw new \RuntimeException('File bukan gambar yang valid.');
        }

        if (! in_array($image->origin()->mediaType(), self::ALLOWED_MEDIA_TYPES, true)) {
            throw new \RuntimeException('File bukan gambar yang valid.');
        }
    }

    /**
     * Standard 3-variant pipeline (content/thumb/og) for product, article,
     * and settings-OG images.
     *
     * @return array{image: string, imageThumb: string, imageOg: string, sizeBefore: int, sizeAfter: int}
     */
    public function processStandard(UploadedFile $file, string $context): array
    {
        $context = $this->sanitizeContext($context);
        $dir = $this->ensureDir($context);
        $id = Str::uuid()->toString();

        $contentPath = "{$dir}/{$id}-content.webp";
        $thumbPath = "{$dir}/{$id}-thumb.webp";
        $ogPath = "{$dir}/{$id}-og.webp";

        Image::read($file->getRealPath())->scaleDown(width: 1280)->toWebp(quality: 82)->save($contentPath);
        Image::read($file->getRealPath())->scaleDown(width: 640)->toWebp(quality: 80)->save($thumbPath);
        Image::read($file->getRealPath())->cover(1200, 630)->toWebp(quality: 82)->save($ogPath);

        return [
            'image' => "/uploads/{$context}/{$id}-content.webp",
            'imageThumb' => "/uploads/{$context}/{$id}-thumb.webp",
            'imageOg' => "/uploads/{$context}/{$id}-og.webp",
            'sizeBefore' => $file->getSize(),
            'sizeAfter' => filesize($contentPath),
        ];
    }

    /**
     * Single-variant pipeline for partner logos — max 480px, no forced
     * crop/cover (so non-square marks aren't distorted), transparency
     * preserved (WebP supports alpha; scaleDown never mattes onto a
     * background color).
     *
     * @return array{image: string, sizeBefore: int, sizeAfter: int}
     */
    public function processLogo(UploadedFile $file, string $context): array
    {
        $context = $this->sanitizeContext($context);
        $dir = $this->ensureDir($context);
        $id = Str::uuid()->toString();

        $path = "{$dir}/{$id}-logo.webp";
        Image::read($file->getRealPath())->scaleDown(width: 480, height: 480)->toWebp(quality: 85)->save($path);

        return [
            'image' => "/uploads/{$context}/{$id}-logo.webp",
            'sizeBefore' => $file->getSize(),
            'sizeAfter' => filesize($path),
        ];
    }

    /**
     * Deletes a previously generated image (and its sibling variants, if
     * any share the same UUID prefix) when a record's image is replaced
     * or the record itself is removed. Silently no-ops for null/missing
     * paths — callers don't need to check existence first.
     */
    public function delete(?string $publicPath): void
    {
        if (! $publicPath) {
            return;
        }

        $fullPath = public_path(ltrim($publicPath, '/'));
        if (! str_starts_with(realpath(dirname($fullPath)) ?: '', public_path('uploads'))) {
            return; // refuse to delete anything outside the uploads tree
        }

        // Remove every sibling variant (content/thumb/og/logo) sharing this UUID.
        $prefix = preg_replace('/-(content|thumb|og|logo)\.webp$/', '', $fullPath);
        foreach (['content', 'thumb', 'og', 'logo'] as $variant) {
            $candidate = "{$prefix}-{$variant}.webp";
            if (is_file($candidate)) {
                unlink($candidate);
            }
        }
    }

    private function sanitizeContext(string $context): string
    {
        return in_array($context, self::CONTEXTS, true) ? $context : 'articles';
    }

    private function ensureDir(string $context): string
    {
        $dir = public_path("uploads/{$context}");
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
