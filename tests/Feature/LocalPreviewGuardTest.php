<?php

namespace Tests\Feature;

use App\Support\StoryDatabase;
use RuntimeException;
use Tests\TestCase;

class LocalPreviewGuardTest extends TestCase
{
    public function test_file_preview_requires_canonical_owned_paths_local_environment_and_distinct_databases(): void
    {
        $base = base_path();
        $storage = storage_path();
        $public = public_path();
        $bootstrap = app()->bootstrapPath();
        $environment = app()->environmentPath();
        $database = config('database');
        $stories = config('stories');
        $root = $this->testDirectory.'/storage/app/private/gpsfood-local-preview';
        foreach (['storage', 'public', 'bootstrap'] as $directory) {
            mkdir($root.'/'.$directory, 0700, true);
        }
        foreach (['.preview-owned', 'website.sqlite', 'stories.sqlite'] as $file) {
            file_put_contents($root.'/'.$file, '');
        }
        try {
            $this->app->setBasePath($this->testDirectory);
            $this->app->useEnvironmentPath($root);
            $this->app->useStoragePath($root.'/storage');
            $this->app->usePublicPath($root.'/public');
            $this->app->useBootstrapPath($root.'/bootstrap');
            $this->app->detectEnvironment(fn () => 'local');
            config(['stories.local_preview' => true, 'database.default' => 'sqlite',
                'database.connections.sqlite' => ['driver' => 'sqlite', 'database' => $root.'/website.sqlite'],
                'database.connections.stories' => ['driver' => 'sqlite', 'database' => $root.'/stories.sqlite']]);
            $this->assertTrue(StoryDatabase::isLocalPreview());
            StoryDatabase::ensureConfigured();
            config(['database.default' => 'stories']);
            $this->assertTrue(StoryDatabase::isLocalPreview());
            StoryDatabase::ensureConfigured();
            $this->app->detectEnvironment(fn () => 'production');
            $this->assertFalse(StoryDatabase::isLocalPreview());
            try {
                StoryDatabase::ensureConfigured();
                $this->fail('Production must reject SQLite preview files.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('fallback is forbidden', $exception->getMessage());
            }
            $this->app->detectEnvironment(fn () => 'local');
            config(['database.connections.stories.database' => $root.'/website.sqlite']);
            $this->assertFalse(StoryDatabase::isLocalPreview());
            config(['database.connections.stories.database' => $root.'/stories.sqlite', 'stories.local_preview' => false]);
            $this->assertFalse(StoryDatabase::isLocalPreview());
            config(['stories.local_preview' => true]);
            $this->app->useEnvironmentPath($this->testDirectory);
            $this->assertFalse(StoryDatabase::isLocalPreview());
        } finally {
            $this->app->setBasePath($base);
            $this->app->useEnvironmentPath($environment);
            $this->app->useStoragePath($storage);
            $this->app->usePublicPath($public);
            $this->app->useBootstrapPath($bootstrap);
            $this->app->detectEnvironment(fn () => 'testing');
            config(['database' => $database, 'stories' => $stories]);
        }
    }
}
