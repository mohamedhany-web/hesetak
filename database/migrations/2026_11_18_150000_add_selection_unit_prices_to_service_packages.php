<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * أسعار حصة مستقلة لخيارات صفحة /pricing لكل باقة: 2 حصص / 8 حصص / حصة مستعجلة.
     */
    public function up(): void
    {
        if (! Schema::hasTable('service_packages')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('service_packages', 'unit_price_small')) {
                $table->decimal('unit_price_small', 10, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('service_packages', 'unit_price_bulk')) {
                $table->decimal('unit_price_bulk', 10, 2)->nullable()->after('unit_price_small');
            }
            if (! Schema::hasColumn('service_packages', 'unit_price_urgent')) {
                $table->decimal('unit_price_urgent', 10, 2)->nullable()->after('unit_price_bulk');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_packages')) {
            return;
        }

        Schema::table('service_packages', function (Blueprint $table) {
            foreach (['unit_price_small', 'unit_price_bulk', 'unit_price_urgent'] as $column) {
                if (Schema::hasColumn('service_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
