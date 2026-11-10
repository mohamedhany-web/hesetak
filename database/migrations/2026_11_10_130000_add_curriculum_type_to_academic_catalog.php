<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_years') && ! Schema::hasColumn('academic_years', 'curriculum_type')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->string('curriculum_type', 64)->nullable()->after('slug');
            });
        }

        if (Schema::hasTable('academic_subjects') && ! Schema::hasColumn('academic_subjects', 'curriculum_type')) {
            Schema::table('academic_subjects', function (Blueprint $table) {
                $table->string('curriculum_type', 64)->nullable()->after('academic_year_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('academic_years') && Schema::hasColumn('academic_years', 'curriculum_type')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropColumn('curriculum_type');
            });
        }

        if (Schema::hasTable('academic_subjects') && Schema::hasColumn('academic_subjects', 'curriculum_type')) {
            Schema::table('academic_subjects', function (Blueprint $table) {
                $table->dropColumn('curriculum_type');
            });
        }
    }
};
