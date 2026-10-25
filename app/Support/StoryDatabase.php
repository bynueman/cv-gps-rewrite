<?php

namespace App\Support;

use RuntimeException;

class StoryDatabase
{
    public static function isLocalPreview(): bool
    {
        if (! app()->environment('local') || config('stories.local_preview') !== true) {
            return false;
        }
        $root = realpath(base_path('storage/app/private/gpsfood-local-preview'));
        if (! $root || dirname($root) !== realpath(base_path('storage/app/private'))
            || ! is_file($root.'/.preview-owned') || realpath(app()->environmentPath()) !== $root
            || realpath(storage_path()) !== $root.DIRECTORY_SEPARATOR.'storage'
            || realpath(public_path()) !== $root.DIRECTORY_SEPARATOR.'public'
            || realpath(app()->bootstrapPath()) !== $root.DIRECTORY_SEPARATOR.'bootstrap') {
            return false;
        }

        return in_array(config('database.default'), ['sqlite', 'stories'], true)
            && config('database.connections.sqlite.driver') === 'sqlite'
            && config('database.connections.stories.driver') === 'sqlite'
            && realpath((string) config('database.connections.sqlite.database')) === $root.DIRECTORY_SEPARATOR.'website.sqlite'
            && realpath((string) config('database.connections.stories.database')) === $root.DIRECTORY_SEPARATOR.'stories.sqlite';
    }

    public static function ensureConfigured(): void
    {
        if (self::isLocalPreview()) {
            return;
        }
        $config = config('database.connections.stories');
        $driver = $config['driver'] ?? null;
        if (app()->environment(['local', 'testing']) && $driver === 'sqlite'
            && ($config['database'] ?? null) === ':memory:') {
            return;
        }
        if (! in_array($driver, ['mysql', 'mariadb'], true)
            || empty($config['host']) || empty($config['database']) || empty($config['username'])
            || (in_array(config('database.connections.'.config('database.default').'.driver'), ['mysql', 'mariadb'], true)
                && $config['database'] === config('database.connections.'.config('database.default').'.database')
                && $config['host'] === config('database.connections.'.config('database.default').'.host')
                && (string) ($config['port'] ?? '3306') === (string) config('database.connections.'.config('database.default').'.port', '3306'))) {
            throw new RuntimeException('Configure the dedicated STORY_DB_* database; website database fallback is forbidden.');
        }
    }
}
