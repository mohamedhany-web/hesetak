<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_package_pricing_rules')) {
            return;
        }

        // Private custom builder: up to 4 sessions/week × 4 weeks × 3 months = 48.
        DB::table('service_package_pricing_rules')
            ->where('scope', 'private_lessons')
            ->update([
                'name' => 'حصص خاصة 1:1 — حصص باقتك الخاصة',
                'min_sessions' => 4,
                'max_sessions' => 48,
                'session_step' => 1,
                'session_minutes' => 60,
                'duration_days' => 30,
                'discount_tiers' => json_encode([
                    ['min_sessions' => 8, 'discount_percent' => 5],
                    ['min_sessions' => 16, 'discount_percent' => 10],
                    ['min_sessions' => 24, 'discount_percent' => 15],
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'updated_at' => now(),
            ]);

        $exists = DB::table('service_package_pricing_rules')
            ->where('scope', 'private_lessons')
            ->exists();

        if (! $exists) {
            DB::table('service_package_pricing_rules')->insert([
                'name' => 'حصص خاصة 1:1 — حصص باقتك الخاصة',
                'scope' => 'private_lessons',
                'price_per_session' => 40,
                'min_sessions' => 4,
                'max_sessions' => 48,
                'session_step' => 1,
                'session_minutes' => 60,
                'duration_days' => 30,
                'discount_tiers' => json_encode([
                    ['min_sessions' => 8, 'discount_percent' => 5],
                    ['min_sessions' => 16, 'discount_percent' => 10],
                    ['min_sessions' => 24, 'discount_percent' => 15],
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep pricing rule; no destructive rollback needed.
    }
};
