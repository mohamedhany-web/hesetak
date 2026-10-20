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
            if (! Schema::hasColumn('orders', 'stripe_checkout_session_id')) {
                $table->string('stripe_checkout_session_id', 191)->nullable()->after('paypal_order_id');
                $table->index('stripe_checkout_session_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'stripe_checkout_session_id')) {
            return;
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['stripe_checkout_session_id']);
            $table->dropColumn('stripe_checkout_session_id');
        });
    }
};
