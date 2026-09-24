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
        if (! Schema::hasTable('tutor_applications')) {
            return;
        }

        Schema::table('tutor_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('tutor_applications', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
        });

        DB::table('tutor_applications')->whereNull('uuid')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('tutor_applications')->where('id', $row->id)->update([
                    'uuid' => (string) Str::uuid(),
                ]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('tutor_applications') && Schema::hasColumn('tutor_applications', 'uuid')) {
            Schema::table('tutor_applications', function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};
