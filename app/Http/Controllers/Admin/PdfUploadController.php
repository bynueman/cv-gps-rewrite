<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Plain JSON endpoint for PDF uploads from the Certifications admin form.
 * Separate from ImageUploadController because the MIME type and
 * pipeline are different — no Intervention Image involved, just a
 * safe store with UUID-prefixed filename.
 */
class PdfUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'file.required' => 'File tidak ditemukan.',
            'file.mimes'    => 'Format harus PDF.',
            'file.max'      => 'Ukuran file maksimal 20MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $file = $request->file('file');

        // Verify actual MIME is application/pdf (not just the extension)
        $realMime = $file->getMimeType();
        if ($realMime !== 'application/pdf') {
            return response()->json(['error' => 'File bukan PDF yang valid.'], 400);
        }

        $uuid     = Str::uuid()->toString();
        $filename = $uuid . '.pdf';
        $destDir  = public_path('uploads/certifications');

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $file->move($destDir, $filename);

        $url = '/uploads/certifications/' . $filename;

        Log::info('PDF uploaded', ['path' => $url, 'size' => filesize(public_path(ltrim($url, '/')))]);

        return response()->json(['url' => $url]);
    }
}
