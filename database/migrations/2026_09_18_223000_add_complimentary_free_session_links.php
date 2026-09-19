<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('one_to_one_sessions') && ! Schema::hasColumn('one_to_one_sessions', 'is_complimentary')) {
            Schema::table('one_to_one_sessions', function (Blueprint $table) {
                $table->boolean('is_complimentary')->default(false)->after('system_channel');
            });
        }

        if (Schema::hasTable('free_trial_bookings') && ! Schema::hasColumn('free_trial_bookings', 'one_to_one_session_id')) {
            Schema::table('free_trial_bookings', function (Blueprint $table) {
                $table->foreignId('one_to_one_session_id')
                    ->nullable()
                    ->after('instructor_id')
                    ->constrained('one_to_one_sessions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('free_trial_bookings') && Schema::hasColumn('free_trial_bookings', 'one_to_one_session_id')) {
            Schema::table('free_trial_bookings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('one_to_one_session_id');
            });
        }

        if (Schema::hasTable('one_to_one_sessions') && Schema::hasColumn('one_to_one_sessions', 'is_complimentary')) {
            Schema::table('one_to_one_sessions', function (Blueprint $table) {
                $table->dropColumn('is_complimentary');
            });
        }
    }
};
