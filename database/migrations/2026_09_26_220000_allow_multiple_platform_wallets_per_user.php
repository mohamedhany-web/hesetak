<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallets')) {
            return;
        }

        // فك القيد الفريد على user_id وحده للسماح بعدة محافظ منصة (بنكي + فودافون…).
        Schema::table('wallets', function (Blueprint $table) {
            try {
                $table->dropUnique('wallets_user_id_unique');
            } catch (\Throwable $e) {
                // قد يكون الاسم مختلفاً بين البيئات
            }
        });

        // إن بقي قيد فريد على user_id بأسماء أخرى
        try {
            $indexes = DB::select('SHOW INDEX FROM wallets WHERE Non_unique = 0 AND Column_name = ?', ['user_id']);
            foreach ($indexes as $idx) {
                $name = $idx->Key_name ?? null;
                if ($name && $name !== 'PRIMARY') {
                    try {
                        DB::statement('ALTER TABLE wallets DROP INDEX `'.$name.'`');
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore on non-mysql
        }

        // ربط محافظ المنصة القديمة المربوطة بـ admin بـ null
        if (Schema::hasColumn('wallets', 'type')) {
            DB::table('wallets')
                ->whereNotNull('user_id')
                ->whereIn('type', ['vodafone_cash', 'instapay', 'bank_transfer', 'cash', 'other'])
                ->update(['user_id' => null]);
        }

        Schema::table('wallets', function (Blueprint $table) {
            // فهرس غير فريد كافٍ للبحث؛ تعدد NULL مسموح
            if (! $this->hasIndexNamed('wallets', 'wallets_user_id_type_index')) {
                $table->index(['user_id', 'type'], 'wallets_user_id_type_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallets')) {
            return;
        }

        Schema::table('wallets', function (Blueprint $table) {
            try {
                $table->dropIndex('wallets_user_id_type_index');
            } catch (\Throwable $e) {
            }
        });
    }

    private function hasIndexNamed(string $table, string $index): bool
    {
        try {
            $rows = DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [$index]);

            return count($rows) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
