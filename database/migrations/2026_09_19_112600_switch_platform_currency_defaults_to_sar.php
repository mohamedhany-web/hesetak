<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Align stored currencies to SAR (display currency). No FX conversion.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wallets') && Schema::hasColumn('wallets', 'currency')) {
            DB::table('wallets')->whereIn('currency', ['EGP', 'USD'])->update(['currency' => 'SAR']);
        }

        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'key') && Schema::hasColumn('settings', 'value')) {
            DB::table('settings')
                ->whereIn('key', ['kashier_currency', 'fawaterak_currency', 'paypal_currency', 'app_currency'])
                ->whereIn('value', ['EGP', 'USD'])
                ->update(['value' => 'SAR']);
        }

        foreach (['service_packages', 'packages', 'tutoring_groups', 'orders', 'payments', 'invoices', 'tutoring_group_packages'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'currency')) {
                DB::table($table)->whereIn('currency', ['EGP', 'USD'])->update(['currency' => 'SAR']);
            }
        }
    }

    public function down(): void
    {
        // Irreversible without FX.
    }
};
