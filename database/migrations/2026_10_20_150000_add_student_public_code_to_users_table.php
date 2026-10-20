<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'student_public_code')) {
                $table->string('student_public_code', 16)->nullable()->unique()->after('progress_share_token');
            }
        });

        DB::table('users')
            ->where('role', 'student')
            ->whereNull('student_public_code')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('users')->where('id', $row->id)->update([
                        'student_public_code' => User::makeStudentPublicCode(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'student_public_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['student_public_code']);
                $table->dropColumn('student_public_code');
            });
        }
    }
};
