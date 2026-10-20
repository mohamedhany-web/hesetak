<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referral_programs')) {
            return;
        }

        // برامج بلا سقف كانت تسمح بإساءة الاستخدام — طبّق الحد الآمن الافتراضي.
        DB::table('referral_programs')
            ->whereNull('max_referrals_per_user')
            ->update(['max_referrals_per_user' => 10]);

        DB::table('referral_programs')
            ->where('allow_self_referral', true)
            ->update(['allow_self_referral' => false]);
    }

    public function down(): void
    {
        // لا نعيد null تلقائياً — قرار تشغيلي واعٍ.
    }
};
