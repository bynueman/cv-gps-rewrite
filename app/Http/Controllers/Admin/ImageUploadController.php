<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Plain JSON endpoint (not an Inertia page) — mirrors the original
 * Next.js /api/admin/upload route so the ported ImageUploadField.tsx
 * can keep using a plain fetch() call.
 */
class ImageUploadController extends Controller
{
    private const ALLOWED_MEDIA_TYPES = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/x-jpeg', 'image/png', 'image/x-png', 'image/webp', 'image/x-webp'];

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
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
            $image = Image::read($file->getRealPath());
        } catch (\Throwable) {
            return response()->json(['error' => 'File bukan gambar yang valid.'], 400);
        }

        if (! in_array($image->origin()->mediaType(), self::ALLOWED_MEDIA_TYPES, true)) {
            return response()->json(['error' => 'File bukan gambar yang valid.'], 400);
        }

        $dir = public_path('uploads/news');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $id = Str::uuid()->toString();
        $contentName = "{$id}-content.webp";
        $thumbName = "{$id}-thumb.webp";
        $ogName = "{$id}-og.webp";

        // autoOrientation (config/image.php) bakes EXIF rotation into the
        // pixel data on read; strip=>true means the re-encoded WebP output
        // carries no EXIF metadata.
        Image::read($file->getRealPath())->scaleDown(width: 1280)->toWebp(quality: 82)->save("{$dir}/{$contentName}");
        Image::read($file->getRealPath())->scaleDown(width: 640)->toWebp(quality: 80)->save("{$dir}/{$thumbName}");
        Image::read($file->getRealPath())->cover(1200, 630)->toWebp(quality: 82)->save("{$dir}/{$ogName}");

        return response()->json([
            'image' => "/uploads/news/{$contentName}",
            'imageThumb' => "/uploads/news/{$thumbName}",
            'imageOg' => "/uploads/news/{$ogName}",
        ]);
    }
}
