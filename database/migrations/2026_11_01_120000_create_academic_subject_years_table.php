<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_subject_years')) {
            return;
        }

        Schema::create('academic_subject_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_subject_id')->constrained('academic_subjects')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->unique(['academic_subject_id', 'academic_year_id'], 'academic_subject_years_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_subject_years');
    }
};
