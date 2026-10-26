<?php

namespace Tests\Feature\Admin;

use App\Exceptions\TranslationFailure;
use App\Jobs\TranslateCompanyContent;
use App\Models\Article;
use App\Models\Brand;
use App\Models\ContentTranslation;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\ContentTranslationWorkflow;
use App\Services\TranslationService;
use App\Support\CompanyContent;
use App\Support\LocalizedContent;
use Database\Seeders\BrandSeeder;
use Illuminate\Database\MySqlConnection;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SmartBilingualCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
        Queue::fake([TranslateCompanyContent::class]);
        $translations = [
            'Camilan singkong' => 'Cassava snack', 'Gurih' => 'Savoury', 'Camilan singkong renyah.' => 'Crispy cassava snack.',
            'Singkong lokal pilihan' => 'Selected local cassava', 'Kemasan ziplock 70 gr' => '70 gr ziplock packaging',
            'Diiris tipis, digoreng renyah' => 'Thinly sliced and fried until crispy', 'Singkong lokal baru' => 'New local cassava',
            'Halo dunia' => 'Hello world', 'Selamat pagi' => 'Good morning', 'Cerita perusahaan' => 'Company story',
            'Ringkasan perusahaan' => 'Company summary', 'Baca berita' => 'Read the news', 'Lihat foto' => 'View the photo',
            'Jam buka' => 'Opening hours', 'Deskripsi perusahaan' => 'Company description', 'Judul baru' => 'New title',
            'Singkong pilihan' => 'Selected cassava',
        ];
        Http::fake(['https://api.mymemory.translated.net/*' => function ($request) use ($translations) {
            return isset($translations[$request['q']])
                ? Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => $translations[$request['q']]]])
                : Http::response(['responseStatus' => 503, 'responseData' => ['translatedText' => '']], 503);
        }]);
    }

    private function fakeProvider(mixed $responses): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($responses);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'brand_id' => Brand::where('key', 'kuicip')->firstOrFail()->id, 'slug' => 'original',
            'name' => ['id' => 'Original (NO MSG)', 'en' => ''],
            'short' => ['id' => 'Camilan singkong', 'en' => ''], 'personality' => ['id' => 'Gurih', 'en' => ''],
            'description' => ['id' => 'Camilan singkong renyah.', 'en' => ''], 'serving' => ['id' => '', 'en' => ''],
            'highlights' => [], 'notes' => [], 'color' => '#E29A12', 'color_dark' => '#9C6508', 'is_active' => true,
        ], $overrides);
    }

    private function create(array $overrides = []): Product
    {
        $this->actingAs(User::factory()->create())->post(route('admin.products.store'), $this->payload($overrides))
            ->assertRedirect(route('admin.products.index'));

        return Product::where('slug', $overrides['slug'] ?? 'original')->firstOrFail();
    }

    private function finish($record): void
    {
        foreach (range(1, 10) as $attempt) {
            $state = ContentTranslation::where('content_type', $record->getTable())->where('content_id', $record->id)->firstOrFail();
            if (! in_array($state->status, ['pending', 'processing'], true)) {
                return;
            }
            (new TranslateCompanyContent($record->getTable(), $record->id, $state->revision))->handle(app(TranslationService::class));
        }
        $this->fail('Translation did not finish within the bounded test worker loop.');
    }

    public function test_single_save_queues_only_public_company_identifiers_then_persists_english(): void
    {
        $product = $this->create();
        Http::assertNothingSent();
        $this->assertSame('', $product->name_en);
        Queue::assertPushed(TranslateCompanyContent::class, fn ($job) => $job->contentType === 'products' && $job->contentId === $product->id);
        $this->finish($product);
        $product->refresh();
        $this->assertSame('Original (NO MSG)', $product->name_en);
        $this->assertSame('Cassava snack', $product->short_en);
        $this->assertSame('Original (NO MSG)', $product->seo_title_en);
        $this->assertSame('Cassava snack', $product->meta_description_en);
        $this->assertSame('ready', $product->translationState->status);
    }

    public function test_six_source_notes_and_two_english_notes_keep_all_six_slots_and_fill_only_missing_targets(): void
    {
        $product = $this->create(['notes' => [
            'id' => "Gurih asin, ringan\nCocok untuk semua umur\nNo MSG\nNot Oily\nGluten Free\nTanpa Pengawet",
            'en' => "Light and savoury\nSuitable for all ages",
        ]]);
        $source = array_column($product->notes, 'id');
        $this->assertCount(6, $product->notes);
        $this->assertSame('', $product->notes[2]['en']);
        $this->get('/products/kuicip/original')->assertOk()->assertInertia(fn ($page) => $page->has('product.notes', 6)->where('product.name.id', 'Original (NO MSG)'));
        $this->finish($product);
        $product->refresh();
        $this->assertSame($source, array_column($product->notes, 'id'));
        $this->assertSame(['Light and savoury', 'Suitable for all ages', 'No MSG', 'Not Oily', 'Gluten Free', 'No preservatives'], array_column($product->notes, 'en'));
        $this->assertCount(6, array_unique(array_column($product->notes, 'key')));
        $this->assertSame('needs_review', $product->translationState->status);
    }

    public function test_blank_middle_english_lines_are_not_shifted_onto_other_sources(): void
    {
        $product = $this->create(['highlights' => ['id' => "Singkong lokal pilihan\nKemasan ziplock 70 gr\nDiiris tipis, digoreng renyah", 'en' => "Selected local cassava\n\nThinly sliced and fried until crispy"]]);
        $this->assertSame('', $product->highlights[1]['en']);
        $this->assertSame('Thinly sliced and fried until crispy', $product->highlights[2]['en']);
        $this->finish($product);
        $this->assertSame('70 gr ziplock packaging', $product->fresh()->highlights[1]['en']);
    }

    public function test_stable_list_keys_preserve_manual_english_after_reorder_and_deletion(): void
    {
        $items = [
            ['key' => 'first', 'id' => 'Singkong lokal pilihan', 'en' => 'Approved first'],
            ['key' => 'second', 'id' => 'Kemasan ziplock 70 gr', 'en' => 'Approved second'],
            ['key' => 'third', 'id' => 'Diiris tipis, digoreng renyah', 'en' => 'Approved third'],
        ];
        $product = $this->create(['highlights' => $items]);
        $this->patch(route('admin.products.update', $product->id), $this->payload(['highlights' => [$items[2], $items[0]]]))
            ->assertRedirect();
        $product->refresh();
        $this->assertSame(['third', 'first'], array_column($product->highlights, 'key'));
        $this->assertSame(['Approved third', 'Approved first'], array_column($product->highlights, 'en'));
    }

    public function test_editing_one_generated_item_marks_only_that_item_stale_and_additions_translate(): void
    {
        $product = $this->create(['highlights' => [['key' => 'one', 'id' => 'Singkong lokal pilihan', 'en' => ''], ['key' => 'two', 'id' => 'Kemasan ziplock 70 gr', 'en' => '']]]);
        $this->finish($product);
        $product->refresh();
        $items = $product->highlights;
        $items[0]['id'] = 'Singkong lokal baru';
        $items[] = ['key' => 'new', 'id' => 'Diiris tipis, digoreng renyah', 'en' => ''];
        app(ContentTranslationWorkflow::class)->save($product, ['highlights' => $items]);
        $fields = $product->fresh()->translationState->fields;
        $this->assertSame('pending', $fields['highlights.one']['status']);
        $this->assertSame('ready', $fields['highlights.two']['status']);
        $this->assertSame('pending', $fields['highlights.new']['status']);
        $this->finish($product);
        $this->assertSame(['New local cassava', '70 gr ziplock packaging', 'Thinly sliced and fried until crispy'], array_column($product->fresh()->highlights, 'en'));
    }

    public function test_manual_override_is_retained_on_source_edit_and_old_jobs_cannot_overwrite_it(): void
    {
        $product = $this->create();
        $state = $product->translationState;
        $oldJob = new TranslateCompanyContent('products', $product->id, $state->revision);
        app(ContentTranslationWorkflow::class)->save($product, ['name_en' => 'Brand approved name']);
        app(ContentTranslationWorkflow::class)->save($product, ['name_id' => 'Judul baru']);
        $oldJob->handle(app(TranslationService::class));
        $product->refresh();
        $this->assertSame('Brand approved name', $product->name_en);
        $this->assertSame('manual', $product->translationState->fields['name']['mode']);
        $this->assertSame('stale', $product->translationState->fields['name']['status']);
        Http::assertNothingSent();
    }

    public function test_unknown_legacy_name_missing_no_msg_is_preserved_but_public_fallback_keeps_identity(): void
    {
        $product = $this->create(['name' => ['id' => 'Original (NO MSG)', 'en' => 'Original']]);
        $this->assertSame('Original', $product->name_en);
        $pair = LocalizedContent::pair($product->fresh(), 'name');
        $this->assertTrue($pair['en_stale']);
        $this->assertSame('Original (NO MSG)', LocalizedContent::english($product->fresh(), 'name'));
    }

    public function test_provider_failure_keeps_the_saved_source_and_reports_failed_status(): void
    {
        $this->fakeProvider(['https://api.mymemory.translated.net/*' => Http::response(['responseStatus' => 503], 503)]);
        $product = $this->create();
        $this->finish($product);
        $product->refresh();
        $this->assertSame('Camilan singkong', $product->short_id);
        $this->assertSame('', $product->short_en);
        $this->assertSame('failed', $product->translationState->status);
        $this->getJson(route('admin.translations.status', ['type' => 'products', 'id' => $product->id]))->assertOk()->assertJsonPath('status', 'failed');
        $this->assertLessThanOrEqual(2, Http::recorded()->count());
    }

    public function test_http_200_provider_quota_error_is_not_successful_translation(): void
    {
        $this->fakeProvider(['https://api.mymemory.translated.net/*' => Http::response(['responseStatus' => 403, 'responseDetails' => 'DAILY QUOTA LIMIT', 'responseData' => ['translatedText' => 'MYMEMORY WARNING']])]);
        try {
            app(TranslationService::class)->translateText('Halo dunia');
            $this->fail('Expected quota rejection.');
        } catch (TranslationFailure $failure) {
            $this->assertSame('quota', $failure->reason);
        }
        $this->assertSame(1, Http::recorded()->count());
    }

    public function test_timeout_is_bounded_and_unchanged_indonesian_is_not_cached_as_success(): void
    {
        $attempts = 0;
        $this->fakeProvider(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('simulated timeout');
        });
        try {
            app(TranslationService::class)->translateText('Halo dunia');
            $this->fail('Expected timeout.');
        } catch (TranslationFailure $failure) {
            $this->assertSame('timeout', $failure->reason);
        }
        $this->assertSame(2, $attempts);
        $this->fakeProvider(['https://api.mymemory.translated.net/*' => Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => 'Halo dunia']])]);
        $this->expectException(TranslationFailure::class);
        app(TranslationService::class)->translateText('Halo dunia');
    }

    public function test_cache_deduplicates_repeated_source_and_protects_quantities_and_claims(): void
    {
        $translator = app(TranslationService::class);
        $this->assertSame('Hello world', $translator->translateText('Halo dunia'));
        $this->assertSame('Hello world', $translator->translateText('Halo dunia'));
        $this->assertSame(1, Http::recorded()->count());
        $this->fakeProvider(['https://api.mymemory.translated.net/*' => Http::response(['responseStatus' => 200, 'responseData' => ['translatedText' => 'Cassava snack with NO MSG']])]);
        $this->expectException(TranslationFailure::class);
        $translator->translateText('Camilan singkong');
    }

    public function test_nested_rich_html_preserves_structure_links_images_and_sanitization(): void
    {
        $html = '<h2>Halo dunia</h2><p>Selamat pagi <strong>Kuicip</strong></p><ul><li>Singkong pilihan</li></ul><p><a href="https://example.test/read">Baca berita</a><img src="/uploads/articles/demo.webp" alt="Lihat foto"></p><script>alert(1)</script>';
        $translator = app(TranslationService::class);
        try {
            $translated = $translator->translateHtml($html);
        } catch (TranslationFailure $failure) {
            $this->assertSame('budget', $failure->reason);
            $translator->resetBudget();
            $translated = $translator->translateHtml($html);
        }
        $this->assertStringContainsString('<h2>Hello world</h2>', $translated);
        $this->assertStringContainsString('<strong>Kuicip</strong>', $translated);
        $this->assertStringContainsString('<li>Selected cassava</li>', $translated);
        $this->assertStringContainsString('https://example.test/read', $translated);
        $this->assertStringContainsString('demo.webp', $translated);
        $this->assertStringNotContainsString('<script', $translated);
        $this->assertStringContainsString('noopener noreferrer', $translated);
    }

    public function test_article_and_company_setting_generate_stored_english_without_translating_contacts(): void
    {
        $article = app(ContentTranslationWorkflow::class)->save(new Article, ['slug' => 'company', 'date' => '2026-10-26', 'category_slug' => 'edukasi-bahan',
            'title_id' => 'Cerita perusahaan', 'title_en' => '', 'excerpt_id' => 'Ringkasan perusahaan', 'excerpt_en' => '', 'body_id' => '<p>Halo dunia</p>', 'body_en' => '', 'published' => true]);
        $this->finish($article);
        $this->assertSame('Company story', $article->fresh()->title_en);
        $this->assertSame('<p>Hello world</p>', $article->fresh()->body_en);
        $setting = app(ContentTranslationWorkflow::class)->save(new Setting(['key' => 'operating_hours']), ['value' => 'Jam buka', 'value_en' => '']);
        $this->finish($setting);
        $this->assertSame('Opening hours', $setting->fresh()->value_en);
        $title = app(ContentTranslationWorkflow::class)->save(new Setting(['key' => 'site_title']), ['value' => 'Cerita perusahaan', 'value_en' => '']);
        $this->finish($title);
        $description = app(ContentTranslationWorkflow::class)->save(new Setting(['key' => 'meta_description_default']), ['value' => 'Deskripsi perusahaan', 'value_en' => '']);
        $this->finish($description);
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->where('site.metadata.title.en', 'Company story')
            ->where('site.metadata.description.en', 'Company description')
            ->where('site.company.hours.en', 'Opening hours'));

        Setting::set('email_primary', 'private@example.test');
        $this->assertSame([], CompanyContent::units(Setting::where('key', 'email_primary')->firstOrFail()));
    }

    public function test_repair_default_is_read_only_and_apply_requires_current_review_and_backup(): void
    {
        $product = $this->create(['notes' => ['id' => "No MSG\nTanpa Pengawet", 'en' => '']]);
        ContentTranslation::query()->delete();
        Queue::fake([TranslateCompanyContent::class]);
        $before = $product->fresh()->getRawOriginal();
        Artisan::call('translations:repair', ['--type' => ['products'], '--id' => $product->id]);
        $output = Artisan::output();
        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('length_mismatch', $output);
        $this->assertStringContainsString('missing_en_metadata', $output);
        $this->assertSame($before, $product->fresh()->getRawOriginal());
        $this->assertDatabaseCount('content_translations', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
        preg_match('/Review token: ([a-f0-9]{64})/', $output, $match);
        $this->assertSame(1, Artisan::call('translations:repair', ['--type' => ['products'], '--id' => $product->id, '--apply' => true]));
        $this->assertSame(0, Artisan::call('translations:repair', ['--type' => ['products'], '--id' => $product->id, '--apply' => true, '--backup-confirmed' => true, '--review' => $match[1]]));
        $this->finish($product);
        $this->assertSame($before['name_id'], $product->fresh()->name_id);
        $this->assertSame(['No MSG', 'No preservatives'], array_column($product->fresh()->notes, 'en'));
        Queue::fake([TranslateCompanyContent::class]);
        Artisan::call('translations:repair', ['--type' => ['products'], '--id' => $product->id]);
        $report = Artisan::output();
        $this->assertStringContainsString('records eligible for generation: 0', $report);
        preg_match('/Review token: ([a-f0-9]{64})/', $report, $repeat);
        $this->assertSame(0, Artisan::call('translations:repair', ['--type' => ['products'], '--id' => $product->id, '--apply' => true, '--backup-confirmed' => true, '--review' => $repeat[1]]));
        Queue::assertNothingPushed();

    }

    public function test_ugc_and_official_identity_modules_are_not_translatable_and_settings_require_superadmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'editor']));
        $this->getJson(route('admin.translations.status', ['type' => 'stories', 'id' => 1]))->assertUnprocessable();
        $this->postJson(route('admin.translate'), ['from' => 'id', 'to' => 'en', 'fields' => ['private_story' => 'must not leave server']])->assertUnprocessable();
        $setting = Setting::create(['key' => 'site_title', 'value' => 'GPS FOOD']);
        $this->getJson(route('admin.translations.status', ['type' => 'settings', 'id' => $setting->id]))->assertForbidden();
        $this->assertSame(1, Artisan::call('translations:repair', ['--type' => ['stories']]));
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_duplicate_list_keys_and_stale_edit_revisions_are_rejected(): void
    {
        $product = $this->create();
        $oldRevision = $product->translationState->revision;
        app(ContentTranslationWorkflow::class)->save($product, ['short_id' => 'Judul baru']);
        $this->patchJson(route('admin.products.update', $product->id), $this->payload(['translation_revision' => $oldRevision]))
            ->assertUnprocessable()->assertJsonValidationErrors('translation_revision');
        $this->postJson(route('admin.products.store'), $this->payload(['slug' => 'invalid', 'notes' => [
            ['key' => 'same', 'id' => 'One', 'en' => ''], ['key' => 'same', 'id' => 'Two', 'en' => ''],
        ]]))->assertUnprocessable()->assertJsonValidationErrors('notes.0.key');
        $this->postJson(route('admin.products.store'), $this->payload(['slug' => 'invalid', 'notes' => ['id' => ['invalid'], 'en' => '']]))
            ->assertUnprocessable()->assertJsonValidationErrors('lists');
    }

    public function test_invalid_indonesian_and_missing_csrf_never_queue_or_call_the_provider(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson(route('admin.products.store'), $this->payload(['name' => ['id' => '', 'en' => '']]))
            ->assertUnprocessable()->assertJsonValidationErrors('name.id');
        $middleware = \Mockery::mock(ValidateCsrfToken::class, [$this->app, $this->app['encrypter']])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $middleware->shouldReceive('runningUnitTests')->andReturn(false);
        $this->app->instance(ValidateCsrfToken::class, $middleware);
        $this->postJson(route('admin.products.store'), $this->payload())->assertStatus(419);
        $this->assertDatabaseCount('products', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_malformed_provider_data_quantity_changes_and_daily_budget_are_rejected(): void
    {
        foreach ([
            [[], 'provider'],
            [['responseStatus' => 200, 'responseData' => ['translatedText' => '100 gr ziplock packaging']], 'quantity'],
        ] as [$response, $reason]) {
            $this->fakeProvider(['https://api.mymemory.translated.net/*' => Http::response($response)]);
            try {
                (new TranslationService)->translateText('Kemasan ziplock 70 gr');
                $this->fail('Expected invalid provider response rejection.');
            } catch (TranslationFailure $failure) {
                $this->assertSame($reason, $failure->reason);
            }
        }
        config(['translations.characters_per_day' => 1]);
        $this->fakeProvider([]);
        try {
            (new TranslationService)->translateText('Halo dunia');
            $this->fail('Expected daily quota rejection.');
        } catch (TranslationFailure $failure) {
            $this->assertSame('quota', $failure->reason);
        }
        Http::assertNothingSent();
    }

    public function test_ambiguous_legacy_list_edits_are_rejected_without_losing_manual_english(): void
    {
        $product = $this->create(['notes' => ['id' => "Singkong lokal pilihan\nKemasan ziplock 70 gr", 'en' => "Approved first\nApproved second"]]);
        $before = $product->notes;
        $this->patchJson(route('admin.products.update', $product->id), $this->payload([
            'notes' => ['id' => "Singkong lokal baru\nKemasan ziplock 70 gr", 'en' => "Approved first\nApproved second"],
        ]))->assertUnprocessable();
        $this->assertSame($before, $product->fresh()->notes);
    }

    public function test_explicit_manual_overwrite_retains_a_backup_and_default_retry_protects_it(): void
    {
        $product = $this->create(['short' => ['id' => 'Camilan singkong', 'en' => 'Approved company wording']]);
        $this->finish($product);
        $revision = $product->fresh()->translationState->revision;
        $this->postJson(route('admin.translations.retry'), ['type' => 'products', 'id' => $product->id, 'revision' => $revision, 'overwrite_manual' => false])->assertOk();
        $this->finish($product);
        $this->assertSame('Approved company wording', $product->fresh()->short_en);
        $revision = $product->fresh()->translationState->revision;
        $this->postJson(route('admin.translations.retry'), ['type' => 'products', 'id' => $product->id, 'revision' => $revision, 'overwrite_manual' => true])->assertOk();
        $this->finish($product);
        $product->refresh();
        $this->assertSame('Cassava snack', $product->short_en);
        $this->assertSame('Approved company wording', $product->translationState->fields['short']['previous_manual']);
        $this->getJson(route('admin.translations.status', ['type' => 'products', 'id' => $product->id]))
            ->assertOk()->assertJsonMissingPath('fields.short.previous_manual');
    }

    public function test_queue_is_atomic_with_source_save_and_wrong_database_configuration_is_not_used(): void
    {
        $manager = new QueueManager($this->app);
        (new QueueServiceProvider($this->app))->registerConnectors($manager);
        Queue::swap($manager);
        try {
            DB::transaction(function () {
                $record = app(ContentTranslationWorkflow::class)->save(new Setting(['key' => 'operating_hours']), ['value' => 'Jam buka', 'value_en' => '']);
                $this->assertDatabaseCount('jobs', 1);
                $this->assertSame('pending', $record->fresh()->translationState->status);
                throw new \RuntimeException('rollback probe');
            });
        } catch (\RuntimeException $error) {
            $this->assertSame('rollback probe', $error->getMessage());
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseMissing('settings', ['key' => 'operating_hours']);
        config(['queue.connections.database.connection' => 'stories']);
        $record = app(ContentTranslationWorkflow::class)->save(new Setting(['key' => 'operating_hours']), ['value' => 'Jam buka', 'value_en' => '']);
        $this->assertSame('Jam buka', $record->fresh()->value);
        $this->assertSame('queue_configuration', $record->fresh()->translationState->fields['value']['reason']);
        $this->assertDatabaseCount('jobs', 0);
        Http::assertNothingSent();
    }

    public function test_null_revision_cannot_overwrite_an_existing_setting_and_malformed_repair_is_read_only(): void
    {
        $record = app(ContentTranslationWorkflow::class)->save(new Setting(['key' => 'operating_hours']), ['value' => 'Jam buka', 'value_en' => 'Approved hours']);
        try {
            app(ContentTranslationWorkflow::class)->save($record, ['value_en' => 'Stale browser edit'], ['translation_revision' => null]);
            $this->fail('Expected stale revision rejection.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('translation_revision', $error->errors());
        }
        $this->assertSame('Approved hours', $record->fresh()->value_en);
        $product = $this->create();
        $product->update(['notes' => [['id' => ['invalid'], 'en' => 'Approved text']]]);
        Queue::fake([TranslateCompanyContent::class]);
        $before = $product->fresh()->getRawOriginal();
        $this->assertSame(0, Artisan::call('translations:repair', ['--type' => ['products'], '--id' => $product->id]));
        $this->assertStringContainsString('malformed_or_unsupported', Artisan::output());
        $this->assertSame($before, $product->fresh()->getRawOriginal());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_local_rate_limit_defers_work_and_glossary_preserves_edge_whitespace(): void
    {
        $this->assertSame("  Kuicip\n", app(TranslationService::class)->translateText("  Kuicip\n"));
        $this->assertSame(' No preservatives ', app(TranslationService::class)->translateText(' Tanpa Pengawet '));
        RateLimiter::clear('cms-translation:requests');
        foreach (range(1, 12) as $attempt) {
            RateLimiter::hit('cms-translation:requests', 60);
        }
        $product = $this->create();
        $state = $product->fresh()->translationState;
        (new TranslateCompanyContent('products', $product->id, $state->revision))->handle(app(TranslationService::class));
        $this->assertSame('pending', $product->fresh()->translationState->status);
        Http::assertNothingSent();
        Queue::assertPushed(TranslateCompanyContent::class, 2);
        RateLimiter::clear('cms-translation:requests');
        $this->finish($product);
        $this->assertSame('Cassava snack', $product->fresh()->short_en);
    }

    public function test_resaving_a_form_opened_before_worker_completion_preserves_generated_english(): void
    {
        $product = $this->create(['notes' => [['key' => 'one', 'id' => 'Singkong lokal pilihan', 'en' => '']]]);
        $oldNotes = $product->notes;
        $this->finish($product);
        $product->refresh();
        $expected = $product->short_en;
        $calls = Http::recorded()->count();
        $this->patch(route('admin.products.update', $product->id), $this->payload([
            'notes' => $oldNotes, 'translation_revision' => $product->translationState->revision, 'translation_english_edits' => [],
        ]))->assertRedirect();
        $product->refresh();
        $this->assertSame($expected, $product->short_en);
        $this->assertSame('Selected local cassava', $product->notes[0]['en']);
        $this->assertSame('ready', $product->translationState->status);
        $this->assertSame($calls, Http::recorded()->count());

        $this->patch(route('admin.products.update', $product->id), $this->payload([
            'notes' => $oldNotes, 'short' => ['id' => 'Camilan singkong', 'en' => 'Approved new wording'],
            'translation_revision' => $product->translationState->revision, 'translation_english_edits' => ['short'],
        ]))->assertRedirect();
        $this->assertSame('Approved new wording', $product->fresh()->short_en);
        $this->assertSame('manual', $product->fresh()->translationState->fields['short']['mode']);

        $article = app(ContentTranslationWorkflow::class)->save(new Article, ['slug' => 'resave', 'date' => '2026-10-26', 'category_slug' => 'edukasi-bahan',
            'title_id' => 'Cerita perusahaan', 'title_en' => '', 'excerpt_id' => 'Ringkasan perusahaan', 'excerpt_en' => '', 'body_id' => '<p>Halo dunia</p>', 'body_en' => '', 'published' => false]);
        $this->finish($article);
        $this->patch(route('admin.articles.update', $article->id), [
            'slug' => 'resave', 'date' => '2026-10-26', 'category_slug' => 'edukasi-bahan',
            'title' => ['id' => 'Cerita perusahaan', 'en' => ''], 'excerpt' => ['id' => 'Ringkasan perusahaan', 'en' => ''],
            'body' => ['id' => '<p>Halo dunia</p>', 'en' => ''], 'published' => false,
            'translation_revision' => $article->fresh()->translationState->revision, 'translation_english_edits' => [],
        ])->assertRedirect();
        $this->assertSame('<p>Hello world</p>', $article->fresh()->body_en);
    }

    public function test_real_database_worker_processes_only_translations_and_duplicate_delivery_does_not_translate_twice(): void
    {
        $manager = new QueueManager($this->app);
        (new QueueServiceProvider($this->app))->registerConnectors($manager);
        Queue::swap($manager);
        $product = $this->create();
        DB::table('jobs')->insert([
            'queue' => 'default', 'payload' => '{}', 'attempts' => 0,
            'reserved_at' => null, 'available_at' => time(), 'created_at' => time(),
        ]);
        $options = ['connection' => 'database', '--queue' => 'translations', '--once' => true,
            '--max-time' => 50, '--max-jobs' => 1, '--timeout' => 40, '--tries' => 1, '--memory' => 128];
        $this->assertSame(0, Artisan::call('queue:work', $options));
        $product->refresh();
        $this->assertSame('Cassava snack', $product->short_en);
        $this->assertSame('ready', $product->translationState->status);
        $calls = Http::recorded()->count();
        $this->assertGreaterThan(0, $calls);
        $this->assertSame(0, DB::table('jobs')->where('queue', 'translations')->count());
        $this->assertSame(1, DB::table('jobs')->where('queue', 'default')->count());
        TranslateCompanyContent::dispatch('products', $product->id, $product->translationState->revision)
            ->onConnection('database')->onQueue('translations');
        $this->assertSame(0, Artisan::call('queue:work', $options));
        $this->assertSame($calls, Http::recorded()->count());
        $this->assertSame('ready', $product->fresh()->translationState->status);
        $this->assertDatabaseCount('failed_jobs', 0);
    }

    public function test_v3_migration_compiles_as_additive_mysql_ddl_without_opening_a_mysql_connection(): void
    {
        $connection = new class(function () {
            throw new \RuntimeException('Offline DDL check must not connect.');
        }, 'offline_release', '', [
            'driver' => 'mysql', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
        ]) extends MySqlConnection
        {

            public function getServerVersion(): string
            {
                return '8.0.36';
            }

            public function isMaria(): bool
            {
                return false;
            }
        };
        $connection->useDefaultSchemaGrammar();
        $original = Schema::getFacadeRoot();
        try {
            Schema::swap($connection->getSchemaBuilder());
            $migration = require base_path('database/migrations/2026_10_26_110000_create_content_translations_table.php');
            $queries = $connection->pretend(fn () => $migration->up());
            $sql = str_replace(chr(96), '', implode("\n", array_column($queries, 'query')));
            $this->assertStringContainsString('create table content_translations', $sql);
            $this->assertStringContainsString('fields json not null', $sql);
            $this->assertStringContainsString('add value_en text null', $sql);
            $this->assertStringNotContainsString('stories', strtolower($sql));
            $this->assertDoesNotMatchRegularExpression('/\b(drop|delete|update|truncate)\b/i', $sql);
            $this->assertCount(3, $queries);
        } finally {
            Schema::swap($original);
        }
    }
}
