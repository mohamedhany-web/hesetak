<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutoring_groups') && Schema::hasColumn('tutoring_groups', 'is_active')) {
            DB::table('tutoring_groups')->update(['is_active' => false]);
        }

        if (Schema::hasTable('service_packages')) {
            if (Schema::hasColumn('service_packages', 'tutoring_group_id')) {
                DB::table('service_packages')->update(['tutoring_group_id' => null]);
            }

            if (
                Schema::hasColumn('service_packages', 'scope')
                && Schema::hasColumn('service_packages', 'is_active')
            ) {
                DB::table('service_packages')
                    ->where('scope', 'tutoring_collective')
                    ->update(['is_active' => false]);
            }
        }
    }

    public function down(): void
    {
        // Intentional no-op: collective tutoring surface was retired; do not reactivate.
    }
};
