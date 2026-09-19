<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('free_trial_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('free_trial_bookings', 'instructor_id')) {
                $table->foreignId('instructor_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('free_trial_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('free_trial_bookings', 'instructor_id')) {
                $table->dropConstrainedForeignId('instructor_id');
            }
        });
    }
};
