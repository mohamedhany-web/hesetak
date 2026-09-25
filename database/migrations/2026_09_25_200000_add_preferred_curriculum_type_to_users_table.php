<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'academic_year_id')) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->index();
            }
            if (! Schema::hasColumn('users', 'preferred_curriculum_type')) {
                $table->string('preferred_curriculum_type', 40)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'preferred_curriculum_type')) {
                $table->dropColumn('preferred_curriculum_type');
            }
            if (Schema::hasColumn('users', 'academic_year_id')) {
                $table->dropColumn('academic_year_id');
            }
        });
    }
};
