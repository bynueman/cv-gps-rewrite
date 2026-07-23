<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ContactMessage::query()->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('is_read', $request->string('status') === 'read');
        }

        return Inertia::render('Admin/Messages/Index', [
            'messages' => $query->get(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(ContactMessage $message): Response
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return Inertia::render('Admin/Messages/Show', [
            'message' => $message,
        ]);
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $label = "{$message->name} ({$message->email})";
        $message->delete();

        ActivityLog::record('deleted', 'messages', $label);

        return redirect()->route('admin.messages.index')->with('status', 'Pesan berhasil dihapus.');
    }
}
