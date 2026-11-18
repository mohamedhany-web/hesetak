<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_subject_curricula')) {
            Schema::create('academic_subject_curricula', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_subject_id')->constrained('academic_subjects')->cascadeOnDelete();
                $table->string('curriculum_type', 64);
                $table->unique(['academic_subject_id', 'curriculum_type'], 'academic_subject_curricula_pair_unique');
            });
        }

        if (! Schema::hasColumn('academic_subjects', 'curriculum_type')) {
            return;
        }

        // نقل المنهج الوحيد الحالي إلى جدول الربط المتعدد (بدون تغيير السلوك الحالي).
        DB::table('academic_subjects')
            ->whereNotNull('curriculum_type')
            ->where('curriculum_type', '!=', '')
            ->orderBy('id')
            ->get(['id', 'curriculum_type'])
            ->each(function ($row) {
                DB::table('academic_subject_curricula')->insertOrIgnore([
                    'academic_subject_id' => $row->id,
                    'curriculum_type' => $row->curriculum_type,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_subject_curricula');
    }
};
