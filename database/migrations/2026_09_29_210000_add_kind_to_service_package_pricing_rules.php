<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_package_pricing_rules')) {
            return;
        }

        Schema::table('service_package_pricing_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('service_package_pricing_rules', 'kind')) {
                $table->string('kind', 32)->default('standard')->after('scope');
                $table->index(['kind', 'is_active'], 'sppr_kind_active_idx');
            }
        });

        DB::table('service_package_pricing_rules')
            ->whereNull('kind')
            ->orWhere('kind', '')
            ->update(['kind' => 'standard']);

        $existsUrgent = DB::table('service_package_pricing_rules')
            ->where('kind', 'urgent')
            ->exists();

        if (! $existsUrgent) {
            $base = (float) (DB::table('service_package_pricing_rules')
                ->where('kind', 'standard')
                ->whereIn('scope', ['tutoring_individual', 'private_lessons'])
                ->orderBy('sort_order')
                ->value('price_per_session') ?: 30);

            DB::table('service_package_pricing_rules')->insert([
                'name' => 'حصة مستعجلة اليوم',
                'scope' => 'tutoring_individual',
                'kind' => 'urgent',
                'price_per_session' => round($base * 1.2, 2),
                'min_sessions' => 1,
                'max_sessions' => 1,
                'session_step' => 1,
                'session_minutes' => 60,
                'duration_days' => 1,
                'discount_tiers' => json_encode([]),
                'is_active' => true,
                'sort_order' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_package_pricing_rules')) {
            return;
        }

        DB::table('service_package_pricing_rules')->where('kind', 'urgent')->delete();

        Schema::table('service_package_pricing_rules', function (Blueprint $table) {
            if (Schema::hasColumn('service_package_pricing_rules', 'kind')) {
                $table->dropIndex('sppr_kind_active_idx');
                $table->dropColumn('kind');
            }
        });
    }
};
