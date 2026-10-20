<?php

namespace Tests\Unit;

use App\Services\CloudflareR2;
use App\Services\CurriculumLibraryStorage;
use App\Services\LectureMaterialStorage;
use Tests\TestCase;

class CloudflareR2DiskResolutionTest extends TestCase
{
    public function test_ready_r2_config_enables_direct_curriculum_upload(): void
    {
        config([
            'filesystems.disks.r2' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
                'bucket' => 'hesetak',
                'endpoint' => 'https://account.r2.cloudflarestorage.com',
            ],
            'filesystems.curriculum_library_disk' => 'r2',
            'filesystems.lecture_materials_disk' => 'r2',
        ]);

        $this->assertTrue(CloudflareR2::isReady());
        $this->assertSame('r2', CurriculumLibraryStorage::resolvedDisk());
        $this->assertTrue(CurriculumLibraryStorage::supportsDirectUpload());
        $this->assertNull(CurriculumLibraryStorage::adminStatusMessage());
        $this->assertSame('r2', LectureMaterialStorage::resolvedDisk());
        $this->assertTrue(LectureMaterialStorage::supportsDirectUpload());
    }

    public function test_missing_r2_keys_fall_back_to_public_and_disable_direct_upload(): void
    {
        config([
            'filesystems.disks.r2' => [
                'key' => '',
                'secret' => '  ',
                'bucket' => 'hesetak',
                'endpoint' => 'https://account.r2.cloudflarestorage.com',
            ],
            'filesystems.curriculum_library_disk' => 'r2',
            'filesystems.lecture_materials_disk' => 'r2',
        ]);

        $this->assertFalse(CloudflareR2::isReady());
        $this->assertContains('AWS_ACCESS_KEY_ID', CloudflareR2::missingFields());
        $this->assertContains('AWS_SECRET_ACCESS_KEY', CloudflareR2::missingFields());
        $this->assertSame('public', CurriculumLibraryStorage::resolvedDisk());
        $this->assertFalse(CurriculumLibraryStorage::supportsDirectUpload());
        $this->assertNotNull(CurriculumLibraryStorage::adminStatusMessage());
        $this->assertSame('public', LectureMaterialStorage::resolvedDisk());
        $this->assertFalse(LectureMaterialStorage::supportsDirectUpload());
    }

    public function test_supports_direct_upload_does_not_require_storage_disk_probe(): void
    {
        $source = file_get_contents(app_path('Services/CurriculumLibraryStorage.php'));

        $this->assertStringNotContainsString('instanceof', $source);
        $this->assertStringContainsString('return self::isR2Ready();', $source);
    }

    public function test_r2_disk_config_does_not_request_public_acl(): void
    {
        $this->assertSame('private', config('filesystems.disks.r2.visibility'));

        $lecture = file_get_contents(app_path('Services/LectureMaterialStorage.php'));
        $this->assertStringNotContainsString("putFileAs(\$dir, \$file, \$name, ['visibility' => 'public'])", $lecture);
    }

    public function test_browser_cors_origins_include_app_url_and_hissatak(): void
    {
        config([
            'app.url' => 'https://hissatak.online',
            'filesystems.disks.r2' => [
                'key' => '',
                'secret' => '',
                'bucket' => '',
                'endpoint' => '',
            ],
        ]);

        $origins = CloudflareR2::browserCorsOrigins(['https://www.hissatak.online/']);
        $this->assertContains('https://hissatak.online', $origins);
        $this->assertContains('https://www.hissatak.online', $origins);
        $this->assertFalse(CloudflareR2::ensureBrowserUploadCors());
    }
}
