<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'neoleap_track_id')) {
                $table->string('neoleap_track_id', 64)->nullable()->after('stripe_checkout_session_id');
            }
            if (! Schema::hasColumn('orders', 'neoleap_payment_id')) {
                $table->string('neoleap_payment_id', 64)->nullable()->after('neoleap_track_id');
            }
            if (! Schema::hasColumn('orders', 'neoleap_tran_id')) {
                $table->string('neoleap_tran_id', 64)->nullable()->after('neoleap_payment_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach (['neoleap_tran_id', 'neoleap_payment_id', 'neoleap_track_id'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
