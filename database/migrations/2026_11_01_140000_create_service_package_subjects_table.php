<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_package_subjects')) {
            Schema::create('service_package_subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_package_id')->constrained('service_packages')->cascadeOnDelete();
                $table->foreignId('academic_subject_id')->constrained('academic_subjects')->cascadeOnDelete();
                $table->unique(['service_package_id', 'academic_subject_id'], 'service_package_subjects_pair_unique');
            });
        }

        if (Schema::hasTable('service_packages') && Schema::hasColumn('service_packages', 'academic_subject_id')) {
            $rows = DB::table('service_packages')
                ->whereNotNull('academic_subject_id')
                ->get(['id', 'academic_subject_id']);
            foreach ($rows as $row) {
                DB::table('service_package_subjects')->insertOrIgnore([
                    'service_package_id' => $row->id,
                    'academic_subject_id' => $row->academic_subject_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_subjects');
    }
};
