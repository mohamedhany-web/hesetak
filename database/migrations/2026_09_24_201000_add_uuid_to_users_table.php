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
            if (! Schema::hasColumn('users', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
        });

        DB::table('users')->whereNull('uuid')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('users')->where('id', $row->id)->update([
                    'uuid' => (string) Str::uuid(),
                ]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};
