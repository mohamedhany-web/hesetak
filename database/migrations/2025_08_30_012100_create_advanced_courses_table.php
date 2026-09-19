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
        if (!Schema::hasTable('advanced_courses')) {
            Schema::create('advanced_courses', function (Blueprint $table) {
                $table->id();
                
                // العلاقات
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
                $table->foreignId('academic_subject_id')->nullable()->constrained('academic_subjects')->onDelete('set null');
                $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
                
                // المعلومات الأساسية
                $table->string('title');
                $table->text('description')->nullable();
                $table->text('objectives')->nullable();
                
                // المستوى والسعر والمدة
                $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
                $table->decimal('price', 10, 2)->default(0);
                $table->integer('duration_hours')->default(0);
                
                // الصور والمتطلبات
                $table->string('thumbnail')->nullable();
                $table->text('requirements')->nullable();
                $table->text('what_you_learn')->nullable();
                
                // التواريخ
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                
                // الحالة والظهور
                $table->boolean('is_active')->default(true);
                $table->boolean('is_featured')->default(false);
                
                $table->timestamps();
                
                // فهارس
                $table->index(['academic_year_id', 'academic_subject_id']);
                $table->index(['is_active', 'is_featured']);
                $table->index('level');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advanced_courses');
    }
};
