<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Plain JSON endpoint (not an Inertia page) — mirrors the original
 * Next.js /api/admin/upload route so the ported ImageUploadField.tsx
 * can keep using a plain fetch()/axios call. Shared by every admin
 * module's image field (Produk, Artikel, Mitra, Pengaturan); the
 * `context` param only picks the destination folder and (for `logo`)
 * a single-variant pipeline — actual compression lives in ImageProcessor.
 */
class ImageUploadController extends Controller
{
    public function __construct(private readonly ImageProcessor $imageProcessor)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'context' => ['nullable', 'string', 'in:'.implode(',', ImageProcessor::CONTEXTS)],
            'variant' => ['nullable', 'string', 'in:standard,logo'],
        ], [
            'file.required' => 'File tidak ditemukan.',
            'file.mimes' => 'Format harus JPEG, PNG, atau WebP.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $file = $request->file('file');

        // Don't trust the client-supplied MIME type alone — attempt a real
        // decode and inspect the actually-decoded media type, so a
        // renamed/spoofed file (e.g. .txt -> .jpg) can't reach the pipeline.
        try {
            $this->imageProcessor->assertValidImage($file);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $context = $request->string('context', 'articles')->toString();
        $variant = $request->string('variant', 'standard')->toString();

        $result = $variant === 'logo'
            ? $this->imageProcessor->processLogo($file, $context)
            : $this->imageProcessor->processStandard($file, $context);

        Log::info('Image uploaded and compressed', [
            'context' => $context,
            'variant' => $variant,
            'size_before' => $result['sizeBefore'],
            'size_after' => $result['sizeAfter'],
        ]);

        return response()->json([
            'image' => $result['image'],
            'imageThumb' => $result['imageThumb'] ?? null,
            'imageOg' => $result['imageOg'] ?? null,
        ]);
    }
}
