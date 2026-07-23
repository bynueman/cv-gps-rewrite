<?php

namespace App\Http\Middleware;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
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
                'company' => config('company'),
                'brandAssets' => config('brand_assets'),
                'brands' => BrandResource::collection(Brand::query()->orderBy('id')->get())->resolve(),
            ],
        ];
    }
}
