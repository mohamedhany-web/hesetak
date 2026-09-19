<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_years') && ! Schema::hasColumn('academic_years', 'is_public')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->boolean('is_public')->default(false)->after('is_active');
            });

            // Student-facing Gulf catalog; keep teacher-PD tracks internal.
            DB::table('academic_years')
                ->where('code', 'HESETAK-GULF')
                ->orWhere('slug', 'gulf-curricula')
                ->update(['is_public' => true]);

            DB::table('academic_years')
                ->where('code', 'like', 'TCH-%')
                ->update(['is_public' => false]);
        }

        if (Schema::hasTable('instructor_profiles') && ! Schema::hasColumn('instructor_profiles', 'curriculum_types')) {
            Schema::table('instructor_profiles', function (Blueprint $table) {
                $table->json('curriculum_types')->nullable()->after('skills');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('academic_years') && Schema::hasColumn('academic_years', 'is_public')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }

        if (Schema::hasTable('instructor_profiles') && Schema::hasColumn('instructor_profiles', 'curriculum_types')) {
            Schema::table('instructor_profiles', function (Blueprint $table) {
                $table->dropColumn('curriculum_types');
            });
        }
    }
};
