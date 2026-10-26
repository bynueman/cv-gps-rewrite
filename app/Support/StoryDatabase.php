<?php

namespace App\Support;

use Illuminate\Support\ConfigurationUrlParser;
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
            || empty($config['host']) || empty($config['database']) || empty($config['username'])) {
            throw new RuntimeException('Configure the dedicated STORY_DB_* database; website database fallback is forbidden.');
        }

        // Migrator temporarily makes stories the default; compare every other SQL connection instead.
        foreach (config('database.connections', []) as $name => $website) {
            if ($name === 'stories' || ! is_array($website)) {
                continue;
            }
            $website = (new ConfigurationUrlParser)->parseConfiguration($website);
            if (in_array($website['driver'] ?? null, ['mysql', 'mariadb'], true)
                && strcasecmp($config['database'], (string) ($website['database'] ?? '')) === 0) {
                throw new RuntimeException('The story database name must differ from every website SQL database.');
            }
        }
    }
}
