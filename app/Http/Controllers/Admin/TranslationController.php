<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TranslationController extends Controller
{
    public function __construct(private readonly TranslationService $translationService) {}

    public function translate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from' => ['required', 'string', 'in:id,en'],
            'to' => ['required', 'string', 'in:id,en'],
            'fields' => ['required', 'array'],
            'fields.*' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $fields = (array) $request->input('fields', []);

        $translations = $this->translationService->translateBatch($fields, $from, $to);

        return response()->json([
            'success' => true,
            'translations' => $translations,
        ]);
    }
}
