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

        Schema::table('service_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('service_packages', 'cover_image_path')) {
                $table->string('cover_image_path')->nullable()->after('badge');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_packages')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            if (Schema::hasColumn('service_packages', 'cover_image_path')) {
                $table->dropColumn('cover_image_path');
            }
        });
    }
};
