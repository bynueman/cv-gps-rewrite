<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CompanyContent
{
    public const TYPES = ['products', 'articles', 'brands', 'settings'];

    public const SETTINGS = ['site_title', 'meta_description_default', 'operating_hours'];

    public static function model(string $type): string
    {
        return match ($type) {
            'products' => Product::class, 'articles' => Article::class, 'brands' => Brand::class, 'settings' => Setting::class,
            default => throw new InvalidArgumentException('Unsupported company content type.'),
        };
    }

    public static function units(Model $record): array
    {
        $fields = match ($record->getTable()) {
            'products' => ['name', 'short', 'personality', 'serving', 'description', 'seo_title', 'meta_description', 'image_alt'],
            'articles' => ['title', 'excerpt', 'body', 'tags'],
            'brands' => ['tag', 'story'],
            'settings' => in_array($record->key, self::SETTINGS, true) ? ['value'] : [],
            default => throw new InvalidArgumentException('Only company-owned public content is translatable.'),
        };
        $units = [];
        foreach ($fields as $field) {
            $source = $record->getAttribute($field === 'value' ? 'value' : $field.'_id');
            if ($record instanceof Product && trim((string) $source) === '') {
                $source = match ($field) {
                    'seo_title', 'image_alt' => $record->name_id,
                    'meta_description' => $record->short_id,
                    default => $source,
                };
            }
            $units[$field] = ['source' => (string) $source, 'target' => (string) $record->getAttribute($field.'_en'),
                'attribute' => $field.'_en', 'html' => $field === 'body',
                'limit' => match ($field) {
                    'seo_title' => 120, 'meta_description' => 160, 'name', 'title', 'short', 'personality', 'serving', 'image_alt' => 255, default => 50000
                }];
        }
        if ($record instanceof Product) {
            foreach (['highlights', 'notes'] as $list) {
                foreach (BilingualLists::keyed(is_array($record->$list) ? $record->$list : []) as $item) {
                    $units[$list.'.'.$item['key']] = ['source' => $item['id'], 'target' => $item['en'],
                        'attribute' => $list, 'key' => $item['key'], 'html' => false, 'limit' => 2000];
                }
            }
        }

        return $units;
    }

    public static function write(Model $record, string $path, string $target): void
    {
        $unit = self::units($record)[$path] ?? null;
        if (! $unit) {
            return;
        }
        if (isset($unit['key'])) {
            $items = BilingualLists::keyed($record->{$unit['attribute']});
            foreach ($items as &$item) {
                if ($item['key'] === $unit['key']) {
                    $item['en'] = $target;
                }
            }
            unset($item);
            $record->{$unit['attribute']} = $items;
        } else {
            $record->{$unit['attribute']} = $target;
        }
    }
}
