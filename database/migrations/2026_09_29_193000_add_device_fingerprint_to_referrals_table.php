<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referrals')) {
            return;
        }

        Schema::table('referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('referrals', 'device_fingerprint')) {
                $table->string('device_fingerprint', 64)->nullable()->after('referral_code');
                $table->index('device_fingerprint');
            }
            if (! Schema::hasColumn('referrals', 'metadata')) {
                $table->json('metadata')->nullable()->after('device_fingerprint');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('referrals')) {
            return;
        }

        Schema::table('referrals', function (Blueprint $table) {
            if (Schema::hasColumn('referrals', 'device_fingerprint')) {
                $table->dropIndex(['device_fingerprint']);
                $table->dropColumn('device_fingerprint');
            }
            if (Schema::hasColumn('referrals', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
