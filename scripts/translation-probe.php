<?php

use App\Exceptions\TranslationFailure;
use App\Jobs\TranslateCompanyContent;
use App\Models\Brand;
use App\Models\Product;
use App\Services\ContentTranslationWorkflow;
use App\Services\TranslationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Explicit CLI-only, fixed public sample, temporary in-memory DB; never uses the project .env or company records.
if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--live-provider' || count($argv) !== 2) {
    fwrite(STDERR, "Usage: php scripts/translation-probe.php --live-provider (fixed public sample only)\n");
    exit(1);
}
require dirname(__DIR__).'/vendor/autoload.php';

$probe = new class('translationProbe') extends TestCase
{
    public function execute(): int
    {
        try {
            $this->app = $this->createApplication();
            $this->app->make(Kernel::class)->call('migrate', ['--force' => true]);
            if (DB::connection()->getDatabaseName() !== ':memory:'
                || ! app()->environment('testing')) {
                throw new RuntimeException('Probe isolation failed.');
            }
            config(['translations.enabled' => true, 'translations.provider' => 'mymemory']);
            Http::allowStrayRequests(['https://api.mymemory.translated.net/get*']);
            $brand = Brand::create(['key' => 'kuicip', 'name' => 'Kuicip', 'tag_id' => '', 'tag_en' => '', 'story_id' => '', 'story_en' => '', 'href' => '/products/kuicip']);
            $record = app(ContentTranslationWorkflow::class)->save(new Product, [
                'brand_id' => $brand->id, 'slug' => 'local-translation-probe', 'name_id' => 'Kuicip', 'name_en' => 'Kuicip',
                'short_id' => 'Halo dunia', 'short_en' => '', 'personality_id' => '', 'personality_en' => '',
                'description_id' => '', 'description_en' => '', 'highlights' => [], 'notes' => [],
                'color' => '#E29A12', 'color_dark' => '#9C6508', 'is_active' => false, 'placeholder' => true,
            ]);
            $state = $record->fresh()->translationState;
            (new TranslateCompanyContent('products', $record->id, $state->revision))
                ->handle(app(TranslationService::class));
            $stored = $record->fresh();
            $english = $stored->short_en;
            $valid = preg_match('/hello/i', $english) && preg_match('/world/i', $english)
                && $stored->translationState->status !== 'failed';
            echo json_encode(['provider' => 'mymemory', 'sample' => 'Halo dunia', 'stored_english' => $english,
                'status' => $stored->translationState->status, 'real_provider_and_persistence_verified' => (bool) $valid,
                'database' => ':memory:', 'production_accessed' => false], JSON_PRETTY_PRINT).PHP_EOL;

            return $valid ? 0 : 1;
        } catch (Throwable $error) {
            echo json_encode(['real_provider_and_persistence_verified' => false, 'reason' => $error instanceof TranslationFailure ? $error->reason : get_class($error), 'production_accessed' => false]).PHP_EOL;

            return 1;
        } finally {
            foreach (array_keys(DB::getConnections()) as $connection) {
                DB::disconnect($connection);
            }
            $directory = $this->testDirectory ? realpath($this->testDirectory) : false;
            if ($directory && dirname($directory) === realpath(sys_get_temp_dir()) && str_starts_with(basename($directory), 'gpsfood-tests-')) {
                (new Filesystem)->deleteDirectory($directory);
            }
        }
    }
};
exit($probe->execute());
