<?php

namespace Tests\Feature\Admin;

use App\Models\Certification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CertificationPdfDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        File::ensureDirectoryExists(public_path('uploads/certifications'));
        $this->actingAs(User::factory()->create());
    }

    #[DataProvider('validPdfPaths')]
    public function test_deleting_a_certification_removes_its_local_pdf(string $url): void
    {
        $path = public_path('uploads/certifications/legacy certificate.PDF');
        File::put($path, '%PDF-1.4');

        $this->deleteCertification($url);

        $this->assertFileDoesNotExist($path);
    }

    public static function validPdfPaths(): array
    {
        return [
            'legacy filename' => ['/uploads/certifications/legacy certificate.PDF'],
            'normalized path' => ['/uploads/certifications/./legacy certificate.PDF'],
        ];
    }

    #[DataProvider('unsafePdfPaths')]
    public function test_unsafe_stored_paths_never_delete_other_files(?string $url): void
    {
        $paths = [
            public_path('uploads/protected.pdf'),
            public_path('protected.pdf'),
            $this->testDirectory.'/protected.pdf',
            public_path('uploads/certifications-backup/protected.pdf'),
            public_path('uploads/certifications/protected.txt'),
        ];

        foreach ($paths as $path) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, 'test-owned sentinel');
        }

        $this->deleteCertification($url);

        foreach ($paths as $path) {
            $this->assertSame('test-owned sentinel', File::get($path));
        }
    }

    public static function unsafePdfPaths(): array
    {
        return [
            'parent' => ['/uploads/certifications/../protected.pdf'],
            'public root' => ['/uploads/certifications/../../protected.pdf'],
            'outside public' => ['/uploads/certifications/../../../protected.pdf'],
            'windows separator' => ['/uploads/certifications/..\\protected.pdf'],
            'encoded traversal' => ['/uploads/certifications/%2e%2e/protected.pdf'],
            'double encoded traversal' => ['/uploads/certifications/%252e%252e/protected.pdf'],
            'similar directory prefix' => ['/uploads/certifications/../certifications-backup/protected.pdf'],
            'non PDF' => ['/uploads/certifications/protected.txt'],
            'external URL' => ['https://example.com/uploads/certifications/protected.pdf'],
            'absolute path' => ['/protected.pdf'],
            'null byte' => ["/uploads/certifications/protected.pdf\0"],
            'missing file' => ['/uploads/certifications/missing.pdf'],
            'empty' => [null],
        ];
    }

    public function test_a_symlink_cannot_delete_a_pdf_outside_the_upload_directory(): void
    {
        $outside = public_path('uploads/protected.pdf');
        $link = public_path('uploads/certifications/linked.pdf');
        File::put($outside, 'test-owned sentinel');

        if (! @symlink($outside, $link)) {
            $this->markTestSkipped('Creating symlinks requires permission on this platform.');
        }

        $this->deleteCertification('/uploads/certifications/linked.pdf');

        $this->assertSame('test-owned sentinel', File::get($outside));
    }

    private function deleteCertification(?string $url): void
    {
        $certification = Certification::create([
            'name' => 'Test PDF', 'category' => 'legal', 'pdf_url' => $url,
        ]);

        $this->delete(route('admin.certifications.destroy', $certification))
            ->assertRedirect(route('admin.certifications.index'));

        $this->assertDatabaseMissing('certifications', ['id' => $certification->id]);
    }
}
