<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_packages')) {
            return;
        }

        if (Schema::hasColumn('service_packages', 'max_subjects')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            $table->unsignedInteger('max_subjects')->nullable()->after('academic_subject_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_packages') || ! Schema::hasColumn('service_packages', 'max_subjects')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('max_subjects');
        });
    }
};
