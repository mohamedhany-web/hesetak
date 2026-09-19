<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolio_projects')) {
            return;
        }

        Schema::create('portfolio_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // الطالب
            // FKs deferred: academic_years / advanced_courses are created in later migrations
            $table->unsignedBigInteger('academic_year_id')->nullable()->index();
            $table->unsignedBigInteger('advanced_course_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('project_url')->nullable(); // رابط المشروع أو الريبو
            $table->string('image_path')->nullable(); // صورة معاينة
            $table->string('status')->default('pending_review'); // pending_review, approved, rejected, published
            $table->text('instructor_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); // المدرب
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('is_visible')->default(true); // الرقابة: الأدمن يمكنه إخفاء المشروع
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_projects');
    }
};
