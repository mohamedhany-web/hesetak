<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hesetak_curriculum_types')) {
            Schema::create('hesetak_curriculum_types', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('label_ar');
                $table->string('label_en');
                $table->json('aliases')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('hesetak_curriculum_types') && DB::table('hesetak_curriculum_types')->count() === 0) {
            $now = now();
            $rows = [];
            $order = 0;
            foreach (config('hesetak_match.curriculum_types', []) as $key => $row) {
                $rows[] = [
                    'key' => (string) $key,
                    'label_ar' => (string) ($row['label_ar'] ?? $key),
                    'label_en' => (string) ($row['label_en'] ?? $key),
                    'aliases' => json_encode(array_values(array_filter(array_map('strval', $row['aliases'] ?? []))), JSON_UNESCAPED_UNICODE),
                    'sort_order' => $order++,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows !== []) {
                DB::table('hesetak_curriculum_types')->insert($rows);
            }
        }

        if (Schema::hasTable('instructor_profiles') && ! Schema::hasColumn('instructor_profiles', 'teaching_subject_ids')) {
            Schema::table('instructor_profiles', function (Blueprint $table) {
                $table->json('teaching_subject_ids')->nullable()->after('curriculum_types');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('instructor_profiles') && Schema::hasColumn('instructor_profiles', 'teaching_subject_ids')) {
            Schema::table('instructor_profiles', function (Blueprint $table) {
                $table->dropColumn('teaching_subject_ids');
            });
        }

        Schema::dropIfExists('hesetak_curriculum_types');
    }
};
