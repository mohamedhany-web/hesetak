<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_grades')) {
            Schema::create('academic_grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedSmallInteger('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['academic_year_id', 'name']);
                $table->index(['academic_year_id', 'is_active', 'order']);
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'academic_grade_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('academic_grade_id')
                    ->nullable()
                    ->after('academic_year_id')
                    ->constrained('academic_grades')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'academic_grade_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('academic_grade_id');
            });
        }

        Schema::dropIfExists('academic_grades');
    }
};
