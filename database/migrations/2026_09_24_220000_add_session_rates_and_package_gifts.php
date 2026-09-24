<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_session_rates')) {
            Schema::create('service_session_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('curriculum_type', 64);
                $table->decimal('price_per_session', 10, 2);
                $table->string('currency', 3)->default('SAR');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(
                    ['academic_year_id', 'curriculum_type', 'currency'],
                    'ssr_year_curriculum_currency_unique'
                );
                $table->index(['curriculum_type', 'is_active']);
            });
        }

        if (Schema::hasTable('service_packages') && ! Schema::hasColumn('service_packages', 'curriculum_type')) {
            Schema::table('service_packages', function (Blueprint $table) {
                $table->string('curriculum_type', 64)->nullable()->after('academic_subject_id');
            });
        }

        if (! Schema::hasTable('package_gifts')) {
            Schema::create('package_gifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
                $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('recipient_email');
                $table->string('recipient_name')->nullable();
                $table->string('recipient_phone', 64)->nullable();
                $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('message')->nullable();
                $table->string('status', 32)->default('pending_payment');
                $table->string('claim_token', 64)->unique();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->unsignedBigInteger('academic_subject_id')->nullable();
                $table->string('curriculum_type', 64)->nullable();
                $table->decimal('quoted_unit_price', 10, 2)->nullable();
                $table->decimal('quoted_total', 10, 2)->nullable();
                $table->string('currency', 3)->nullable();
                $table->timestamp('granted_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'recipient_email']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('package_gifts');

        if (Schema::hasTable('service_packages') && Schema::hasColumn('service_packages', 'curriculum_type')) {
            Schema::table('service_packages', function (Blueprint $table) {
                $table->dropColumn('curriculum_type');
            });
        }

        Schema::dropIfExists('service_session_rates');
    }
};
