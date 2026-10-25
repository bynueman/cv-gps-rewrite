<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProductSummaryResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return Arr::except(parent::toArray($request), ['description', 'highlights', 'notes']);
    }
}
