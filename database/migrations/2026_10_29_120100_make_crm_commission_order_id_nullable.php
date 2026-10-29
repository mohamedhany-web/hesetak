<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_commissions') || ! Schema::hasColumn('crm_commissions', 'order_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('crm_commissions', function (Blueprint $table) {
            try {
                $table->dropForeign(['order_id']);
            } catch (\Throwable $e) {
                // قد يكون الاسم مختلفاً على الإنتاج
            }
        });

        try {
            DB::statement('ALTER TABLE crm_commissions DROP INDEX crm_comm_order_user_type_uq');
        } catch (\Throwable $e) {
            // الفهرس قد يكون غير موجود
        }

        DB::statement('ALTER TABLE crm_commissions MODIFY order_id BIGINT UNSIGNED NULL');

        Schema::table('crm_commissions', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->unique(['order_id', 'user_id', 'type'], 'crm_comm_order_user_type_uq');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_commissions') || ! Schema::hasColumn('crm_commissions', 'order_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('crm_commissions', function (Blueprint $table) {
            try {
                $table->dropForeign(['order_id']);
            } catch (\Throwable $e) {
            }
        });

        try {
            DB::statement('ALTER TABLE crm_commissions DROP INDEX crm_comm_order_user_type_uq');
        } catch (\Throwable $e) {
        }

        DB::statement('ALTER TABLE crm_commissions MODIFY order_id BIGINT UNSIGNED NOT NULL');

        Schema::table('crm_commissions', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unique(['order_id', 'user_id', 'type'], 'crm_comm_order_user_type_uq');
        });
    }
};
