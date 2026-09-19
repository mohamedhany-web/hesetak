<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('advanced_courses')) {
            return;
        }

        // MySQL-only: SQLite has no information_schema / MODIFY COLUMN
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // حذف Foreign Keys الموجودة إذا كانت موجودة باستخدام SQL مباشر
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'advanced_courses' 
            AND COLUMN_NAME IN ('academic_year_id', 'academic_subject_id')
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE advanced_courses DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Foreign key غير موجود أو تم حذفه بالفعل
            }
        }

        // تعديل نوع البيانات للأعمدة
        DB::statement('ALTER TABLE advanced_courses MODIFY COLUMN academic_year_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE advanced_courses MODIFY COLUMN academic_subject_id BIGINT UNSIGNED NULL');

        // إضافة Foreign Keys مرة أخرى
        try {
            DB::statement("
                ALTER TABLE advanced_courses 
                ADD CONSTRAINT advanced_courses_academic_year_id_foreign 
                FOREIGN KEY (academic_year_id) 
                REFERENCES academic_years(id) 
                ON DELETE SET NULL
            ");
        } catch (\Exception $e) {
            // Foreign key موجود بالفعل
        }
        
        try {
            DB::statement("
                ALTER TABLE advanced_courses 
                ADD CONSTRAINT advanced_courses_academic_subject_id_foreign 
                FOREIGN KEY (academic_subject_id) 
                REFERENCES academic_subjects(id) 
                ON DELETE SET NULL
            ");
        } catch (\Exception $e) {
            // Foreign key موجود بالفعل
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('advanced_courses')) {
            return;
        }

        Schema::table('advanced_courses', function (Blueprint $table) {
            try {
                $table->dropForeign(['academic_year_id']);
            } catch (\Exception $e) {
                // Foreign key غير موجود
            }
            
            try {
                $table->dropForeign(['academic_subject_id']);
            } catch (\Exception $e) {
                // Foreign key غير موجود
            }
        });
    }
};
