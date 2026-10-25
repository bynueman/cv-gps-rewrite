<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class PdfUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_upload_accepts_the_xsrf_cookie_header_used_by_axios(): void
    {
        $this->enableCsrfProtection();
        $this->actingAs(User::factory()->create());

        $cookie = $this->get(route('admin.certifications.create'))->assertOk()->getCookie('XSRF-TOKEN', false);
        $this->assertNotNull($cookie);

        $url = $this->withHeader('X-XSRF-TOKEN', $cookie->getValue())
            ->post(route('admin.upload-pdf'), ['file' => $this->pdf()])
            ->assertOk()->json('url');

        $this->assertMatchesRegularExpression('~^/uploads/certifications/[a-f0-9-]{36}\.pdf$~', $url);
        $this->assertFileExists(public_path(ltrim($url, '/')));
    }

    public function test_pdf_upload_without_a_csrf_token_is_rejected(): void
    {
        $this->enableCsrfProtection();

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.upload-pdf'), ['file' => $this->pdf()])
            ->assertStatus(419);

        $this->assertDirectoryDoesNotExist(public_path('uploads/certifications'));
    }

    public function test_guest_cannot_upload_a_pdf(): void
    {
        $this->postJson(route('admin.upload-pdf'), ['file' => $this->pdf()])->assertUnauthorized();
    }

    public function test_a_renamed_non_pdf_is_rejected(): void
    {
        $path = $this->testDirectory.'/fake.pdf';
        File::put($path, 'not a PDF');

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.upload-pdf'), [
                'file' => new UploadedFile($path, 'fake.pdf', 'application/pdf', null, true),
            ])->assertStatus(400);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('certificate.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
    }

    private function enableCsrfProtection(): void
    {
        $middleware = Mockery::mock(ValidateCsrfToken::class, [$this->app, $this->app['encrypter']])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $middleware->shouldReceive('runningUnitTests')->andReturn(false);
        $this->app->instance(ValidateCsrfToken::class, $middleware);
    }
}
