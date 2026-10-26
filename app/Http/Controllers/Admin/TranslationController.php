<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TranslationFailure;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ContentTranslationWorkflow;
use App\Services\TranslationService;
use App\Support\CompanyContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TranslationController extends Controller
{
    public function translate(Request $request, TranslationService $translator): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', Rule::in(['id', 'en'])], 'to' => ['required', 'different:from', Rule::in(['id', 'en'])],
            'fields' => ['required', 'array:name,short,personality,serving,description,highlights,notes,seo_title,meta_description,image_alt,title,excerpt,body,tags', 'max:14'],
            'fields.*' => ['nullable', 'string', 'max:50000'],
        ]);
        try {
            $translations = $translator->translateBatch($data['fields'], $data['from'], $data['to']);

            return response()->json(['success' => true, 'translations' => $translations]);
        } catch (TranslationFailure $failure) {
            return response()->json(['success' => false, 'error' => 'Terjemahan belum tersedia. Konten asli tidak diubah.', 'reason' => $failure->reason], 503);
        }
    }

    private function record(Request $request): Model
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['products', 'articles', 'settings'])],
            'id' => ['required', 'integer', 'min:1'],
        ]);
        abort_if($data['type'] === 'settings' && $request->user()->role !== 'superadmin', 403);
        $record = CompanyContent::model($data['type'])::findOrFail($data['id']);
        abort_if($record instanceof Setting && ! in_array($record->key, CompanyContent::SETTINGS, true), 404);

        return $record;
    }

    public function status(Request $request): JsonResponse
    {
        $record = $this->record($request);

        return response()->json([...ContentTranslationWorkflow::view($record), 'english' => array_map(fn ($unit) => $unit['target'], CompanyContent::units($record))])->header('Cache-Control', 'no-store');
    }

    public function retry(Request $request, ContentTranslationWorkflow $workflow): JsonResponse
    {
        $record = $this->record($request);
        $data = $request->validate(['overwrite_manual' => ['required', 'boolean'], 'revision' => ['nullable', 'uuid']]);
        $workflow->save($record, [], ['translation_revision' => $data['revision'] ?? null,
            'translation_refresh' => true, 'translation_overwrite_manual' => $data['overwrite_manual']]);

        return response()->json(ContentTranslationWorkflow::view($record))->header('Cache-Control', 'no-store');
    }
}
