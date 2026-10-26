<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BilingualLists
{
    public static function keyed(?array $items): array
    {
        $result = [];
        foreach ($items ?? [] as $index => $item) {
            if (! is_array($item)) {
                $item = ['id' => (string) $item, 'en' => ''];
            }
            $result[] = [
                'key' => $item['key'] ?? 'legacy-'.sha1($index.':'.json_encode($item)),
                'id' => (string) ($item['id'] ?? ''),
                'en' => (string) ($item['en'] ?? ''),
            ];
        }

        return $result;
    }

    public static function input(mixed $input, ?array $existing = null): array
    {
        if (! is_array($input)) {
            throw ValidationException::withMessages(['lists' => 'Daftar harus berupa kumpulan item.']);
        }
        $old = self::keyed($existing);
        if (! array_key_exists('id', $input) && ! array_key_exists('en', $input)) {
            return array_values(array_map(fn ($item) => is_array($item)
                ? ['key' => $item['key'] ?? (string) Str::uuid(), 'id' => $item['id'] ?? '', 'en' => $item['en'] ?? '']
                : $item, $input));
        }

        if ((isset($input['id']) && ! is_string($input['id'])) || (isset($input['en']) && ! is_string($input['en']))) {
            throw ValidationException::withMessages(['lists' => 'Baris ID dan English harus berupa teks.']);
        }
        $id = preg_split('/\R/u', (string) ($input['id'] ?? ''));
        $en = preg_split('/\R/u', (string) ($input['en'] ?? ''));
        $sourceChanged = $old && implode("\n", array_column($old, 'id')) !== implode("\n", $id);
        $englishUnchanged = implode("\n", array_column($old, 'en')) === implode("\n", $en);
        if ($sourceChanged && $englishUnchanged) {
            $removed = array_filter($old, fn ($row) => ! in_array($row['id'], $id, true));
            $added = array_diff($id, array_column($old, 'id'));
            if (($removed && $added) || count(array_unique(array_column($old, 'id'))) !== count($old)) {
                throw ValidationException::withMessages(['lists' => 'Perubahan daftar lama ambigu. Gunakan editor item beridentitas agar English manual tidak hilang.']);
            }
        }
        $used = [];
        $result = [];
        foreach (range(0, ($sourceChanged && $englishUnchanged ? count($id) : max(count($id), count($en))) - 1) as $index) {
            $source = $id[$index] ?? '';
            $target = $en[$index] ?? '';
            if (trim($source) === '' && trim($target) === '') {
                continue;
            }
            $matched = null;
            foreach ($old as $i => $row) {
                if (! isset($used[$i]) && $row['id'] === $source) {
                    $matched = $row;
                    $used[$i] = true;
                    break;
                }
            }
            // Old textarea clients cannot reliably associate an edited/deleted line with another EN line.
            if ($sourceChanged && $englishUnchanged) {
                $target = $matched['en'] ?? '';
            }
            $result[] = ['key' => $matched['key'] ?? (string) Str::uuid(), 'id' => $source, 'en' => $target];
        }

        return $result;
    }
}
