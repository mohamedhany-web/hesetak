<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'progress_share_token')) {
                $table->string('progress_share_token', 64)->nullable()->unique()->after('uuid');
            }
        });

        DB::table('users')
            ->where('role', 'student')
            ->whereNull('progress_share_token')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('users')->where('id', $row->id)->update([
                        'progress_share_token' => Str::random(48),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'progress_share_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['progress_share_token']);
                $table->dropColumn('progress_share_token');
            });
        }
    }
};
