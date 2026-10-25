<?php

namespace Tests\Feature;

use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestIsolationTest extends TestCase
{
    public function test_database_and_filesystem_are_isolated_before_tests_run(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertSame(['sqlite'], array_keys(config('database.connections')));
        $this->assertSame($this->testDirectory.'/public', public_path());
        $this->assertSame($this->testDirectory.'/storage', storage_path());
        $this->assertSame($this->testDirectory, app()->environmentPath());
        $this->assertNotSame(realpath(base_path('public')), realpath(public_path()));
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('session.driver'));
        Mail::assertNothingSent();
    }

    public function test_only_explicitly_faked_http_requests_are_allowed(): void
    {
        Http::fake(['https://mock.invalid/*' => Http::response(['ok' => true])]);

        $this->assertTrue(Http::get('https://mock.invalid/translation')->json('ok'));

        $this->expectException(StrayRequestException::class);
        Http::get('https://unexpected.invalid');
    }
}
