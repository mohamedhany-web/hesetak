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
        if (Schema::hasColumn('service_packages', 'show_in_storefront')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            $table->boolean('show_in_storefront')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_packages')) {
            return;
        }
        if (! Schema::hasColumn('service_packages', 'show_in_storefront')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('show_in_storefront');
        });
    }
};
