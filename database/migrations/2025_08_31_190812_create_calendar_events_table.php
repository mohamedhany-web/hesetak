<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('calendar_events')) {
            return;
        }

        $hasAcademicYears = Schema::hasTable('academic_years');
        $hasAcademicSubjects = Schema::hasTable('academic_subjects');
        $hasAdvancedCourses = Schema::hasTable('advanced_courses');

        Schema::create('calendar_events', function (Blueprint $table) use ($hasAcademicYears, $hasAcademicSubjects, $hasAdvancedCourses) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->enum('type', ['exam', 'lesson', 'assignment', 'meeting', 'holiday', 'deadline', 'review', 'personal', 'system'])->default('personal');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('color', 7)->default('#3B82F6'); // HEX color
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            
            // المنشئ والمستهدفين
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('visibility', ['public', 'private', 'course', 'year', 'subject'])->default('private');

            if ($hasAcademicYears) {
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
            } else {
                $table->unsignedBigInteger('academic_year_id')->nullable()->index();
            }

            if ($hasAcademicSubjects) {
                $table->foreignId('academic_subject_id')->nullable()->constrained('academic_subjects')->onDelete('cascade');
            } else {
                $table->unsignedBigInteger('academic_subject_id')->nullable()->index();
            }

            if ($hasAdvancedCourses) {
                $table->foreignId('advanced_course_id')->nullable()->constrained('advanced_courses')->onDelete('cascade');
            } else {
                $table->unsignedBigInteger('advanced_course_id')->nullable()->index();
            }
            
            // التذكيرات
            $table->boolean('has_reminder')->default(false);
            $table->integer('reminder_minutes')->nullable(); // عدد الدقائق قبل الحدث
            $table->boolean('email_reminder')->default(false);
            
            // الحالة والتكرار
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->integer('recurrence_interval')->default(1); // كل كم وحدة زمنية
            $table->date('recurrence_end_date')->nullable();
            
            // التقييمات والدرجات
            $table->boolean('has_grade')->default(false);
            $table->decimal('max_grade', 8, 2)->nullable();
            $table->text('grading_criteria')->nullable();
            
            $table->timestamps();
            
            // الفهارس
            $table->index(['start_date', 'end_date']);
            $table->index(['created_by', 'type']);
            $table->index(['academic_year_id', 'academic_subject_id']);
            $table->index(['visibility', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};