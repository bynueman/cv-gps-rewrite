<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Contact', [
            'seo' => (new Seo(
                title: 'Hubungi Kami',
                description: 'Untuk pertanyaan produk, distribusi, ekspor, maupun kolaborasi — tim kami siap membantu.',
                canonical: url('/contact'),
            ))->toArray(),
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $contactMessage = ContactMessage::create($request->validated());

        // The message is already durably saved above, so a mail outage
        // (bad SMTP creds, mailer down) shouldn't turn into a 500 for the
        // visitor — log it and let the record stand as the fallback.
        try {
            $recipients = array_filter([
                Setting::get('email_primary', config('company.email')),
                Setting::get('email_secondary', config('company.email_alt')),
            ]);

            Mail::to($recipients)->send(new ContactMessageMail($contactMessage));
        } catch (\Throwable $e) {
            Log::error('Failed to send contact message notification email', [
                'contact_message_id' => $contactMessage->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('status', 'sent');
    }
}
