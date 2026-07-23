<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Models\ActivityLog;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Partner::query()->orderBy('category')->orderBy('sort_order')->orderBy('name');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return Inertia::render('Admin/Partners/Index', [
            'partners' => $query->get(),
            'filters' => $request->only(['category']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Partners/Form', ['mode' => 'create']);
    }

    public function store(StorePartnerRequest $request): RedirectResponse
    {
        $partner = Partner::create($request->validated());

        ActivityLog::record('created', 'partners', $partner->name);

        return redirect()->route('admin.partners.index')->with('status', "Mitra \"{$partner->name}\" berhasil ditambahkan.");
    }

    public function edit(Partner $partner): Response
    {
        return Inertia::render('Admin/Partners/Form', [
            'mode' => 'edit',
            'partner' => $partner,
        ]);
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        $partner->update($request->validated());

        ActivityLog::record('updated', 'partners', $partner->name);

        return redirect()->route('admin.partners.index')->with('status', "Mitra \"{$partner->name}\" berhasil diperbarui.");
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $label = $partner->name;
        $partner->delete();

        ActivityLog::record('deleted', 'partners', $label);

        return redirect()->route('admin.partners.index')->with('status', "Mitra \"{$label}\" berhasil dihapus.");
    }
}
