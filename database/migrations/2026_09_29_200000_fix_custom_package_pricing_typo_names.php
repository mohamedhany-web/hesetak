<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تصحيح خطأ إملائي: «خصص» → «حصص» في أسماء قواعد تسعير الباقات المخصصة.
 * لا يمس عبارة «خصص باقتك» العامة على الموقع (بمعنى customize).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_package_pricing_rules')) {
            return;
        }

        $correct = 'حصص خاصة 1:1 — حصص باقتك الخاصة';

        $typos = [
            'حصص خاصة 1:1 — خصص باقتك',
            'حصص خاصة 1:1 — خصّص باقتك',
            'حصص خاصة 1:1 — خصّص باقتك الخاصة',
            'خصص باقتك الخاصة',
        ];

        foreach ($typos as $old) {
            DB::table('service_package_pricing_rules')
                ->where('name', $old)
                ->update(['name' => $correct, 'updated_at' => now()]);
        }

        // أي صف ما زال يحتوي «خصص باقتك» في الاسم (خطأ حصص) دون المساس بعبارات أخرى
        DB::table('service_package_pricing_rules')
            ->where('name', 'like', '%خصص باقتك%')
            ->where('name', 'not like', '%حصص باقتك%')
            ->update([
                'name' => $correct,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // لا نعيد الخطأ الإملائي عمداً
    }
};
