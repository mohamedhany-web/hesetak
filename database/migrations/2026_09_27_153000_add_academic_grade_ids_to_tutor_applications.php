<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutor_applications') && ! Schema::hasColumn('tutor_applications', 'academic_grade_ids')) {
            Schema::table('tutor_applications', function (Blueprint $table) {
                $table->json('academic_grade_ids')->nullable()->after('academic_year_ids');
            });
        }

        if (Schema::hasTable('instructor_profiles') && ! Schema::hasColumn('instructor_profiles', 'academic_year_ids')) {
            Schema::table('instructor_profiles', function (Blueprint $table) {
                $table->json('academic_year_ids')->nullable()->after('teaching_subject_ids');
            });
        }

        if (Schema::hasTable('instructor_profiles') && ! Schema::hasColumn('instructor_profiles', 'academic_grade_ids')) {
            Schema::table('instructor_profiles', function (Blueprint $table) {
                $table->json('academic_grade_ids')->nullable()->after('academic_year_ids');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tutor_applications') && Schema::hasColumn('tutor_applications', 'academic_grade_ids')) {
            Schema::table('tutor_applications', function (Blueprint $table) {
                $table->dropColumn('academic_grade_ids');
            });
        }

        if (Schema::hasTable('instructor_profiles')) {
            Schema::table('instructor_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('instructor_profiles', 'academic_grade_ids')) {
                    $table->dropColumn('academic_grade_ids');
                }
                if (Schema::hasColumn('instructor_profiles', 'academic_year_ids')) {
                    $table->dropColumn('academic_year_ids');
                }
            });
        }
    }
};
