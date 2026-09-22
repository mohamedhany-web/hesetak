<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('one_to_one_sessions')) {
            return;
        }

        if (! Schema::hasColumn('one_to_one_sessions', 'report_required_at')) {
            Schema::table('one_to_one_sessions', function (Blueprint $table) {
                $table->timestamp('report_required_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('one_to_one_sessions') || ! Schema::hasColumn('one_to_one_sessions', 'report_required_at')) {
            return;
        }

        Schema::table('one_to_one_sessions', function (Blueprint $table) {
            $table->dropColumn('report_required_at');
        });
    }
};
