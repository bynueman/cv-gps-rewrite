<?php

namespace Tests\Support;

use App\Models\Brand;
use App\Models\Product;
use App\Support\StoryDatabase;
use Database\Seeders\BrandSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

// Development harness only: never loaded by normal HTTP or production bootstrap.
class LocalPreview extends TestCase
{
    public static function directory(): string
    {
        return dirname(__DIR__, 2).'/storage/app/private/gpsfood-local-preview';
    }

    public static function initialize(): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new RuntimeException('Setup requires the local CLI.');
        }
        $root = self::directory();
        if (file_exists($root) && ! is_file($root.'/.preview-owned')) {
            throw new RuntimeException('Refusing to adopt an existing unowned directory.');
        }
        if (! is_dir($root) && ! mkdir($root, 0700, true)) {
            throw new RuntimeException('Cannot create preview directory.');
        }
        if (realpath($root) !== realpath(dirname($root)).DIRECTORY_SEPARATOR.'gpsfood-local-preview') {
            throw new RuntimeException('Preview directory must not be a symlink.');
        }
        foreach (['.preview-owned' => 'gpsfood-local-preview-v1', 'website.sqlite' => '', 'stories.sqlite' => '',
            'app-key' => 'base64:'.base64_encode(random_bytes(32))] as $name => $content) {
            if (! file_exists($root.'/'.$name)) {
                $handle = fopen($root.'/'.$name, 'x');
                if (! $handle) {
                    throw new RuntimeException('Cannot create preview-owned file.');
                }
                fwrite($handle, $content);
                fclose($handle);
                chmod($root.'/'.$name, 0600);
            }
        }
    }

    public static function boot(): Application
    {
        if (! in_array(PHP_SAPI, ['cli', 'cli-server'], true)
            || (PHP_SAPI === 'cli-server' && ! in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true))) {
            throw new RuntimeException('Preview is loopback/CLI only.');
        }
        $root = realpath(self::directory());
        if (! $root || $root !== realpath(dirname(self::directory())).DIRECTORY_SEPARATOR.'gpsfood-local-preview'
            || ! is_file($root.'/.preview-owned')) {
            throw new RuntimeException('Run local-preview.php setup first.');
        }
        $preview = new self('localPreview');
        $preview->testDirectory = $root;
        $app = $preview->createApplication();
        foreach (array_keys(DB::getConnections()) as $name) {
            DB::purge($name);
        }
        $connection = fn ($name) => ['driver' => 'sqlite', 'database' => $root.'/'.$name.'.sqlite',
            'prefix' => '', 'foreign_key_constraints' => true, 'busy_timeout' => 5000];
        config(['app.env' => 'local', 'app.debug' => false, 'app.url' => 'http://127.0.0.1:8766',
            'app.key' => trim(file_get_contents($root.'/app-key')),
            'database.connections' => ['sqlite' => $connection('website'), 'stories' => $connection('stories')],
            'queue.connections.database.connection' => 'sqlite', 'queue.failed.database' => 'sqlite',
            'session.driver' => 'file', 'session.cookie' => 'gpsfood_local_preview_session',
            'session.secure' => false, 'cache.default' => 'file',
            'stories.enabled' => true, 'stories.policies_approved' => true, 'stories.local_preview' => true,
            'stories.antispam_provider' => 'local',
            'stories.policy_version' => 'LOCAL-DEMO-NOT-APPROVED',
            'stories.abuse_key' => hash('sha256', file_get_contents($root.'/app-key')),
            'stories.turnstile_secret' => 'local-preview-fake-verifier', 'stories.turnstile_hostname' => 'localhost']);
        $app->detectEnvironment(fn () => 'local');
        if (! StoryDatabase::isLocalPreview()) {
            throw new RuntimeException('Preview database isolation failed; no mutations permitted.');
        }
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => fn ($request) => Http::response([
            'success' => $request['response'] === 'gpsfood-local-preview-token', 'hostname' => 'localhost', 'action' => 'story_submit',
        ])]);
        $app['files']->ensureDirectoryExists(public_path('build'));
        if (is_file($app->basePath('public/build/manifest.json'))) {
            copy($app->basePath('public/build/manifest.json'), public_path('build/manifest.json'));
        }

        return $app;
    }

    public static function prepareDatabases(Application $app): void
    {
        if (! StoryDatabase::isLocalPreview()) {
            throw new RuntimeException('Isolation required.');
        }
        $kernel = $app->make(Kernel::class);
        foreach ([['sqlite', 'database/migrations'], ['stories', 'database/migrations/stories']] as [$connection, $path]) {
            if ($kernel->call('migrate', ['--database' => $connection, '--path' => $path, '--force' => true]) !== 0) {
                throw new RuntimeException('Preview migration failed.');
            }
        }
        if (Brand::count() === 0) {
            (new BrandSeeder)->run();
        }
        if (Product::count() === 0) {
            (new ProductSeeder)->run();
        }
        LocalStoryDemo::seed();
    }
}
