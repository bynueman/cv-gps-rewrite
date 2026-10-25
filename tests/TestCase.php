<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected ?string $testDirectory = null;

    public function createApplication(): Application
    {
        if ($this->testDirectory === null) {
            $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gpsfood-tests-'.bin2hex(random_bytes(16));
            if (! mkdir($directory, 0700)) {
                throw new RuntimeException('Cannot create an isolated test directory.');
            }
            $this->testDirectory = realpath($directory);
        }

        $files = new Filesystem;

        foreach (['public', 'bootstrap/cache', 'storage/framework/views', 'storage/framework/sessions', 'storage/framework/cache'] as $path) {
            $files->ensureDirectoryExists($this->testDirectory.'/'.$path);
        }

        $app = require dirname(__DIR__).'/bootstrap/app.php';
        $app->useEnvironmentPath($this->testDirectory);
        $app->useBootstrapPath($this->testDirectory.'/bootstrap');
        $app->usePublicPath($this->testDirectory.'/public');
        $app->useStoragePath($this->testDirectory.'/storage');

        if ($app->configurationIsCached()) {
            throw new RuntimeException('Tests must not load cached application configuration.');
        }

        // Set safe configuration before providers and RefreshDatabase can open any connection.
        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            $app['config']->set([
                'app.env' => 'testing',
                'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
                'database.default' => 'sqlite',
                'database.connections' => [
                    'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
                ],
                'cache.default' => 'array',
                'session.driver' => 'array',
                'mail.default' => 'array',
                'queue.default' => 'sync',
                'logging.default' => 'null',
                'view.compiled' => $this->testDirectory.'/storage/framework/views',
            ]);
            $app->detectEnvironment(fn () => 'testing');
        });

        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
        $app->make(Kernel::class)->bootstrap();

        Http::preventStrayRequests();
        Mail::fake();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            if ($this->testDirectory !== null) {
                $directory = realpath($this->testDirectory);
                if ($directory !== false
                    && dirname($directory) === realpath(sys_get_temp_dir())
                    && str_starts_with(basename($directory), 'gpsfood-tests-')) {
                    (new Filesystem)->deleteDirectory($directory);
                }
                $this->testDirectory = null;
            }
        }
    }
}
