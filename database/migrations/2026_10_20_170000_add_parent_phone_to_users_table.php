<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'parent_phone')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('parent_phone', 30)->nullable()->index()->after('phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'parent_phone')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('parent_phone');
        });
    }
};
