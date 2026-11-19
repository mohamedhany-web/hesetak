<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('popup_ads') || Schema::hasColumn('popup_ads', 'page_keys')) {
            return;
        }

        Schema::table('popup_ads', function (Blueprint $table) {
            $table->json('page_keys')->nullable()->after('page_key');
        });

        DB::table('popup_ads')->orderBy('id')->each(function ($ad) {
            $key = trim((string) ($ad->page_key ?? '')) ?: 'home';
            DB::table('popup_ads')->where('id', $ad->id)->update(['page_keys' => json_encode([$key])]);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('popup_ads') && Schema::hasColumn('popup_ads', 'page_keys')) {
            Schema::table('popup_ads', function (Blueprint $table) {
                $table->dropColumn('page_keys');
            });
        }
    }
};
