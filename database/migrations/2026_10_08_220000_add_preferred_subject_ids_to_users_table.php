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
            if (! Schema::hasColumn('users', 'preferred_subject_ids')) {
                $table->json('preferred_subject_ids')->nullable()->after('preferred_curriculum_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'preferred_subject_ids')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_subject_ids');
        });
    }
};
