<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCertificationRequest;
use App\Http\Requests\Admin\UpdateCertificationRequest;
use App\Models\ActivityLog;
use App\Models\Certification;
use App\Services\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificationController extends Controller
{
    public function __construct(private readonly ImageProcessor $imageProcessor)
    {
    }

    public function index(Request $request): Response
    {
        $query = Certification::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return Inertia::render('Admin/Certifications/Index', [
            'certifications' => $query->get(),
            'filters'        => $request->only(['category']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Certifications/Form', ['mode' => 'create']);
    }

    public function store(StoreCertificationRequest $request): RedirectResponse
    {
        $cert = Certification::create($request->validated());

        ActivityLog::record('created', 'certifications', $cert->name);

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', "Sertifikasi \"{$cert->name}\" berhasil ditambahkan.");
    }

    public function edit(Certification $certification): Response
    {
        return Inertia::render('Admin/Certifications/Form', [
            'mode'          => 'edit',
            'certification' => $certification,
        ]);
    }

    public function update(UpdateCertificationRequest $request, Certification $certification): RedirectResponse
    {
        $data = $request->validated();

        // Delete old logo WebP if replaced
        if ($certification->logo && $certification->logo !== ($data['logo'] ?? null)) {
            $this->imageProcessor->delete($certification->logo);
        }

        $certification->update($data);

        ActivityLog::record('updated', 'certifications', $certification->name);

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', "Sertifikasi \"{$certification->name}\" berhasil diperbarui.");
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $label = $certification->name;

        if ($certification->logo) {
            $this->imageProcessor->delete($certification->logo);
        }

        // If PDF was uploaded to our server (not external URL), delete it too
        if ($certification->pdf_url && str_starts_with($certification->pdf_url, '/uploads/certifications/')) {
            $path = public_path(ltrim($certification->pdf_url, '/'));
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $certification->delete();

        ActivityLog::record('deleted', 'certifications', $label);

        return redirect()
            ->route('admin.certifications.index')
            ->with('status', "Sertifikasi \"{$label}\" berhasil dihapus.");
    }
}
