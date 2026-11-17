<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('popup_ads')) {
            return;
        }

        Schema::table('popup_ads', function (Blueprint $table) {
            if (! Schema::hasColumn('popup_ads', 'page_key')) {
                $table->string('page_key', 64)->nullable()->after('cta_text')->index();
            }
            if (! Schema::hasColumn('popup_ads', 'delay_seconds')) {
                $table->unsignedSmallInteger('delay_seconds')->default(30)->after('max_views_per_visitor');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('popup_ads')) {
            return;
        }

        Schema::table('popup_ads', function (Blueprint $table) {
            if (Schema::hasColumn('popup_ads', 'page_key')) {
                $table->dropColumn('page_key');
            }
            if (Schema::hasColumn('popup_ads', 'delay_seconds')) {
                $table->dropColumn('delay_seconds');
            }
        });
    }
};
