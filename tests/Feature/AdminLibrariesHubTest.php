<?php

namespace Tests\Feature;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\Lecture;
use App\Models\LectureMaterial;
use App\Models\LibraryVideo;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class AdminLibrariesHubTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->buildLibrariesSchema();
    }

    protected function buildLibrariesSchema(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('slug')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->unsignedInteger('level_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('slug')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('advanced_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('academic_year_id')->nullable();
            $table->foreignId('academic_subject_id')->nullable();
            $table->foreignId('instructor_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advanced_course_id');
            $table->foreignId('parent_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('curriculum_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id');
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lectures', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('course_id')->nullable();
            $table->foreignId('instructor_id')->nullable();
            $table->string('recording_url')->nullable();
            $table->string('recording_file_path')->nullable();
            $table->string('video_platform')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lecture_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecture_id')->nullable();
            $table->unsignedBigInteger('library_folder_id')->nullable();
            $table->string('title')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('storage_disk', 32)->default('public');
            $table->string('content_theme', 40)->nullable();
            $table->string('experience_mode', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_visible_to_student')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('library_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->string('kind')->default('materials');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->string('description_ar')->nullable();
            $table->string('description_en')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('content_theme', 40)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_library_entitlement')->default(true);
            $table->timestamps();
        });

        Schema::create('library_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_folder_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('audience', 32)->default('general');
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->string('title');
            $table->string('series_title')->nullable();
            $table->string('age_label', 40)->nullable();
            $table->string('content_theme', 40)->default('general');
            $table->text('description')->nullable();
            $table->string('external_url', 2000)->nullable();
            $table->string('file_path', 1000)->nullable();
            $table->string('storage_disk', 40)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('mime_type', 120)->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('course_id')->nullable();
            $table->foreignId('instructor_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('live_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id');
            $table->string('title')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('storage_disk')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('status')->default('ready');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    protected function admin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_library_routes_are_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.libraries.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.libraries.materials.presign'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.libraries.materials.proxy'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.libraries.videos.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.libraries.videos.presign'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.libraries.videos.proxy'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('instructor.libraries.videos.proxy'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.libraries.curriculum.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.libraries.curriculum.course'));
    }

    public function test_admin_can_open_libraries_hub_and_sections(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.libraries.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.libraries.materials.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.libraries.videos.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.libraries.curriculum.index'))->assertOk();
    }

    public function test_admin_can_upload_and_toggle_material(): void
    {
        config(['filesystems.lecture_materials_disk' => 'public', 'filesystems.public_media_disk' => 'public']);
        Storage::fake('public');
        $admin = $this->admin();
        $course = AdvancedCourse::create(['title' => 'كورس تجريبي', 'is_active' => true]);
        $lecture = Lecture::create(['title' => 'محاضرة 1', 'course_id' => $course->id]);

        $file = UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('admin.libraries.materials.store'), [
                'lecture_id' => $lecture->id,
                'title' => 'مذكرة الوحدة',
                'sort_order' => 1,
                'is_visible_to_student' => 1,
                'file' => $file,
            ])
            ->assertRedirect(route('admin.libraries.materials.index'));

        $material = LectureMaterial::query()->first();
        $this->assertNotNull($material);
        $this->assertTrue((bool) $material->is_visible_to_student);
        $this->assertSame('public', $material->storage_disk);
        Storage::disk('public')->assertExists($material->file_path);

        $this->actingAs($admin)
            ->post(route('admin.libraries.materials.toggle', $material))
            ->assertRedirect();

        $this->assertFalse((bool) $material->fresh()->is_visible_to_student);
    }

    public function test_material_create_page_uses_direct_r2_upload_when_ready(): void
    {
        config([
            'filesystems.disks.r2' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
                'bucket' => 'hesetak',
                'endpoint' => 'https://account.r2.cloudflarestorage.com',
            ],
            'filesystems.lecture_materials_disk' => 'r2',
            'filesystems.public_media_disk' => 'r2',
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('admin.libraries.materials.create'))
            ->assertOk()
            ->assertSee('presign-upload', false)
            ->assertSee('proxy-upload', false)
            ->assertSee('جاري الرفع إلى Cloudflare', false);
    }

    public function test_material_presign_is_disabled_when_r2_keys_are_missing(): void
    {
        config([
            'filesystems.disks.r2' => [
                'key' => '',
                'secret' => '',
                'bucket' => '',
                'endpoint' => '',
            ],
            'filesystems.lecture_materials_disk' => 'r2',
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->postJson(route('admin.libraries.materials.presign'), [
                'filename' => 'notes.pdf',
                'content_type' => 'application/pdf',
                'file_size' => 1200,
            ])
            ->assertStatus(422)
            ->assertJson(['direct_upload' => false]);
    }

    public function test_admin_can_create_and_publish_video(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.libraries.videos.store'), [
                'title' => 'درس تجريبي',
                'external_url' => 'https://example.com/video.mp4',
                'is_published' => 1,
                'sort_order' => 0,
                'duration_seconds' => 0,
            ])
            ->assertRedirect(route('admin.libraries.videos.index'));

        $video = LibraryVideo::query()->first();
        $this->assertNotNull($video);
        $this->assertTrue((bool) $video->is_published);

        $this->actingAs($admin)
            ->post(route('admin.libraries.videos.toggle', $video))
            ->assertRedirect();

        $this->assertFalse((bool) $video->fresh()->is_published);
    }

    public function test_video_create_page_includes_proxy_fallback(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('admin.libraries.videos.create'))
            ->assertOk()
            ->assertSee('presign-upload', false)
            ->assertSee('proxy-upload', false)
            ->assertSee('الرفع المباشر حُجب', false);
    }

    public function test_video_proxy_rejects_invalid_token(): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->create('clip.pdf', 80, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('admin.libraries.videos.proxy'), [
                'upload_token' => str_repeat('a', 64),
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_admin_can_view_curriculum_course(): void
    {
        $admin = $this->admin();
        $year = AcademicYear::create(['name' => 'سنة 1', 'order' => 1, 'is_active' => true]);
        $subject = AcademicSubject::create([
            'academic_year_id' => $year->id,
            'name' => 'رياضيات',
            'order' => 1,
            'is_active' => true,
        ]);
        $course = AdvancedCourse::create([
            'title' => 'جبر',
            'academic_subject_id' => $subject->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.libraries.curriculum.course', $course))
            ->assertOk()
            ->assertSee('جبر', false);
    }
}
