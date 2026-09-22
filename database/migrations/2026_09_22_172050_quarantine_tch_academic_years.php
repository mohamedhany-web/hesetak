<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years')) {
            return;
        }

        $query = DB::table('academic_years')->where('code', 'like', 'TCH-%');

        $payload = [];
        if (Schema::hasColumn('academic_years', 'is_active')) {
            $payload['is_active'] = false;
        }
        if (Schema::hasColumn('academic_years', 'is_public')) {
            $payload['is_public'] = false;
        }
        if ($payload === []) {
            return;
        }

        $query->update($payload);
    }

    public function down(): void
    {
        // Intentional no-op: TCH tracks stay Quarantined for Hesetak public catalog.
    }
};
