<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // سيرفرات البث (VPS)
        Schema::create('live_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain'); // e.g. live.hesetak.com
            $table->enum('provider', ['jitsi', 'custom'])->default('jitsi');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->string('ip_address')->nullable();
            $table->unsignedInteger('max_participants')->default(100);
            $table->unsignedInteger('current_load')->default(0);
            $table->json('config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // إعدادات نظام البث المباشر
        Schema::create('live_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->timestamps();
        });

        // جلسات البث المباشر
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('server_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('room_name')->unique();
            $table->enum('status', ['scheduled', 'live', 'ended', 'cancelled'])->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('max_participants')->default(100);
            $table->boolean('is_recorded')->default(false);
            $table->boolean('allow_chat')->default(true);
            $table->boolean('allow_screen_share')->default(true);
            $table->boolean('require_enrollment')->default(true);
            $table->boolean('mute_on_join')->default(true);
            $table->boolean('video_off_on_join')->default(true);
            $table->string('password')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('advanced_courses')->onDelete('set null');
            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('live_servers')->onDelete('set null');

            $table->index(['status', 'scheduled_at']);
            $table->index('instructor_id');
            $table->index('course_id');
        });

        // تسجيل الحضور
        Schema::create('session_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('role_in_session', ['instructor', 'student', 'guest'])->default('student');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('live_sessions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['session_id', 'user_id']);
        });

        // تسجيلات الجلسات
        Schema::create('live_recordings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('title')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->enum('status', ['processing', 'ready', 'failed', 'deleted'])->default('processing');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('live_sessions')->onDelete('cascade');
        });

        // إدراج الإعدادات الافتراضية
        \DB::table('live_settings')->insert([
            ['key' => 'default_server_id', 'value' => null, 'type' => 'integer', 'group' => 'general', 'label' => 'سيرفر البث الافتراضي', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'jitsi_domain', 'value' => 'meet.jit.si', 'type' => 'string', 'group' => 'jitsi', 'label' => 'نطاق Jitsi Meet', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'jitsi_app_id', 'value' => null, 'type' => 'string', 'group' => 'jitsi', 'label' => 'Jitsi App ID', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'jitsi_jwt_secret', 'value' => null, 'type' => 'string', 'group' => 'jitsi', 'label' => 'JWT Secret', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'max_participants_default', 'value' => '100', 'type' => 'integer', 'group' => 'general', 'label' => 'الحد الأقصى للمشاركين', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'allow_recording', 'value' => '1', 'type' => 'boolean', 'group' => 'general', 'label' => 'السماح بالتسجيل', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_end_minutes', 'value' => '180', 'type' => 'integer', 'group' => 'general', 'label' => 'إنهاء تلقائي بعد (دقيقة)', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'require_enrollment', 'value' => '1', 'type' => 'boolean', 'group' => 'access', 'label' => 'يتطلب تسجيل في الكورس', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mute_students_on_join', 'value' => '1', 'type' => 'boolean', 'group' => 'room', 'label' => 'كتم الطلاب عند الدخول', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'video_off_students_on_join', 'value' => '1', 'type' => 'boolean', 'group' => 'room', 'label' => 'إيقاف فيديو الطلاب عند الدخول', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('live_recordings');
        Schema::dropIfExists('session_attendance');
        Schema::dropIfExists('live_sessions');
        Schema::dropIfExists('live_settings');
        Schema::dropIfExists('live_servers');
    }
};
