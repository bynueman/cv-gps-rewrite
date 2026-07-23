<?php

namespace App\Http\Middleware;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * `site` mirrors what the original Next.js app statically imported
     * from src/lib/content.ts (company, logos, brands) — here it's
     * config-driven + a tiny DB query instead, shared on every request
     * so Header/Footer (mounted on every public page) always have it.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'site' => [
                'company' => fn () => $this->resolveCompany(),
                'brandAssets' => config('brand_assets'),
                'brands' => BrandResource::collection(Brand::query()->orderBy('id')->get())->resolve(),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Only queried for authenticated (admin) requests — the public
            // site shares this same middleware, and this count powers the
            // sidebar's Pesan Masuk badge only.
            ...($request->user() ? [
                'unreadMessagesCount' => fn () => ContactMessage::where('is_read', false)->count(),
            ] : []),
        ];
    }

    /**
     * Contact/company info shown in Header/Footer on every public page.
     * Pengaturan (admin) writes to the `settings` table; config/company.php
     * supplies the fallback for any key an admin hasn't overridden yet, so
     * a fresh install still renders sensible defaults.
     *
     * @return array<string, mixed>
     */
    private function resolveCompany(): array
    {
        $whatsapp = Setting::get('whatsapp', config('company.whatsapp'));

        return [
            'name' => config('company.name'),
            'short_name' => config('company.short_name'),
            'location' => config('company.location'),
            'address' => Setting::get('address', config('company.address')),
            'email' => Setting::get('email_primary', config('company.email')),
            'email_alt' => Setting::get('email_secondary', config('company.email_alt')),
            'whatsapp' => $whatsapp,
            'whatsapp_href' => 'https://wa.me/'.preg_replace('/\D/', '', (string) $whatsapp),
            'hours' => [
                'id' => Setting::get('operating_hours', config('company.hours.id')),
                'en' => config('company.hours.en'),
            ],
            'map_embed_url' => config('company.map_embed_url'),
        ];
    }
}
