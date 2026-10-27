<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الواجبات/الامتحانات مربوطة بـ advanced_courses، لكن FK القديم لـ courses/lessons القديمة
 * كانت تمنع الإنشاء من لوحة الإدارة.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assignments')) {
            $this->dropFkIfExists('assignments', 'assignments_course_id_foreign');
            $this->dropFkIfExists('assignments', 'assignments_lesson_id_foreign');

            // lesson_id يشير الآن إلى course_lessons (منهج الحصتك)
            if (Schema::hasColumn('assignments', 'lesson_id') && Schema::hasTable('course_lessons')) {
                try {
                    Schema::table('assignments', function (Blueprint $table) {
                        $table->foreign('lesson_id')
                            ->references('id')
                            ->on('course_lessons')
                            ->nullOnDelete();
                    });
                } catch (\Throwable) {
                    // قد تكون القيم القديمة غير متوافقة — الإنشاء يعمل بـ lesson_id=null
                }
            }
        }

        if (Schema::hasTable('exams')) {
            $this->dropFkIfExists('exams', 'exams_course_id_foreign');
        }
    }

    public function down(): void
    {
        // لا نعيد FK القديمة إلى courses/lessons — كانت مكسورة مع advanced_courses
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        );

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($constraint) {
                $table->dropForeign($constraint);
            });
        }
    }
};
