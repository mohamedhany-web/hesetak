<?php

namespace Tests\Unit;

use App\Services\TutorApplicationStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TutorApplicationStorageTest extends TestCase
{
    public function test_store_photo_and_documents_on_resolved_disk(): void
    {
        Storage::fake('public');
        config(['filesystems.public_media_disk' => 'public']);

        $photo = UploadedFile::fake()->image('teacher.jpg', 400, 400);
        $id = UploadedFile::fake()->create('passport.pdf', 120, 'application/pdf');
        $cert = UploadedFile::fake()->image('certificate.png', 600, 400);
        $video = UploadedFile::fake()->create('intro.mp4', 1024, 'video/mp4');

        $photoPath = TutorApplicationStorage::storePhoto($photo);
        $idPath = TutorApplicationStorage::storeIdDocument($id);
        $certPath = TutorApplicationStorage::storeCertificate($cert);
        $videoPath = TutorApplicationStorage::storeVideo($video);

        $this->assertStringStartsWith('tutor-applications/photos/', $photoPath);
        $this->assertStringStartsWith('tutor-applications/ids/', $idPath);
        $this->assertStringStartsWith('tutor-applications/certificates/', $certPath);
        $this->assertStringStartsWith('tutor-applications/videos/', $videoPath);

        Storage::disk('public')->assertExists($photoPath);
        Storage::disk('public')->assertExists($idPath);
        Storage::disk('public')->assertExists($certPath);
        Storage::disk('public')->assertExists($videoPath);

        $this->assertTrue(TutorApplicationStorage::isPdf($idPath));
        $this->assertFalse(TutorApplicationStorage::isPdf($photoPath));

        config(['app.url' => 'https://hissatak.online']);
        $url = TutorApplicationStorage::publicUrl($photoPath);
        $this->assertNotNull($url);
        $this->assertStringContainsString('/media/tutor-applications/photos/', $url);
        $this->assertStringNotContainsString('r2.dev', $url);
    }

    public function test_public_url_normalizes_storage_prefix_and_full_urls(): void
    {
        config(['app.url' => 'https://hissatak.online', 'filesystems.r2_public_url' => 'https://pub-example.r2.dev']);

        $this->assertSame(
            'tutor-applications/photos/toqa.jpg',
            TutorApplicationStorage::storedRelativePath('storage/tutor-applications/photos/toqa.jpg')
        );
        $this->assertSame(
            'tutor-applications/photos/toqa.jpg',
            TutorApplicationStorage::storedRelativePath('https://hissatak.online/storage/tutor-applications/photos/toqa.jpg')
        );
        $this->assertSame(
            'tutor-applications/photos/toqa.jpg',
            TutorApplicationStorage::storedRelativePath('https://pub-example.r2.dev/tutor-applications/photos/toqa.jpg')
        );

        $url = TutorApplicationStorage::publicUrl('https://pub-example.r2.dev/tutor-applications/photos/toqa.jpg');
        $this->assertStringContainsString('/media/tutor-applications/photos/toqa.jpg', (string) $url);
    }

    public function test_inline_data_uri_reads_stored_photo(): void
    {
        Storage::fake('public');
        config(['filesystems.public_media_disk' => 'public']);

        $path = TutorApplicationStorage::storePhoto(UploadedFile::fake()->image('toqa.jpg', 80, 80));
        $uri = TutorApplicationStorage::inlineDataUri($path);

        $this->assertNotNull($uri);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $uri);
    }
}
