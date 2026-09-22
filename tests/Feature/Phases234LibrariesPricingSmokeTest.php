<?php

namespace Tests\Feature;

use App\Models\AdvancedCourse;
use App\Models\InstructorProfile;
use App\Models\TutorApplication;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class Phases234LibrariesPricingSmokeTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->buildExtra();
    }

    protected function buildExtra(): void
    {
        Schema::create('library_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->string('kind')->default('materials');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('content_theme')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_library_entitlement')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('order')->default(0);
            $table->unsignedInteger('level_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('advanced_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->nullable();
            $table->string('title')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_after_discount', 10, 2)->nullable();
            $table->decimal('price_egp', 10, 2)->nullable();
            $table->decimal('price_egp_after_discount', 10, 2)->nullable();
            $table->decimal('price_usd', 10, 2)->nullable();
            $table->decimal('price_usd_after_discount', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->nullable();
            $table->foreignId('course_id')->nullable();
            $table->string('title')->nullable();
            $table->string('recording_url')->nullable();
            $table->string('recording_file_path')->nullable();
            $table->string('video_platform')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lecture_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecture_id')->nullable();
            $table->foreignId('library_folder_id')->nullable();
            $table->string('title')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('storage_disk')->nullable();
            $table->boolean('is_visible_to_student')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('library_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_folder_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('audience', 32)->default('general');
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->string('title');
            $table->string('content_theme', 40)->default('general');
            $table->text('description')->nullable();
            $table->string('external_url', 2000)->nullable();
            $table->string('file_path', 1000)->nullable();
            $table->string('storage_disk', 40)->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('academic_year_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id');
            $table->foreignId('instructor_id');
            $table->text('assigned_courses')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('student_course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('advanced_course_id');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function test_working_instructor_can_open_phase234_pages(): void
    {
        $teacher = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
        InstructorProfile::create([
            'user_id' => $teacher->id,
            'status' => InstructorProfile::STATUS_APPROVED,
        ]);
        TutorApplication::create([
            'user_id' => $teacher->id,
            'full_name' => $teacher->name,
            'email' => $teacher->email,
            'status' => TutorApplication::STATUS_ACTIVATED,
            'activated_at' => now(),
        ]);

        $course = AdvancedCourse::create([
            'instructor_id' => $teacher->id,
            'title' => 'Course A',
            'price' => 100,
            'price_egp' => 100,
            'price_usd' => 10,
            'is_active' => true,
        ]);

        $this->assertTrue($teacher->fresh()->isAcademyWorkingInstructor());

        // التسعير للإدارة فقط — المدرب ليس له مسار تسعير كورس
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('instructor.courses.pricing.edit'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('instructor.courses.pricing.update'));

        $this->assertTrue(\Illuminate\Support\Facades\Route::has('instructor.libraries.materials.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('instructor.lecture-recordings.index'));

        $this->actingAs($teacher)
            ->get(route('instructor.libraries.materials.index'))
            ->assertOk();
        $this->actingAs($teacher)
            ->get(route('instructor.libraries.videos.index'))
            ->assertOk();
    }
}
