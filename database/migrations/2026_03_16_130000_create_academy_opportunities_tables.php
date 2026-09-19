<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academy_opportunities')) {
            Schema::create('academy_opportunities', function (Blueprint $table) {
                $table->id();
                $table->string('organization_name', 150);
                $table->string('title', 180);
                $table->string('specialization', 120)->nullable();
                $table->string('city', 120)->nullable();
                $table->enum('work_mode', ['remote', 'onsite', 'hybrid'])->default('remote');
                $table->enum('status', ['active', 'paused', 'closed'])->default('active');
                $table->boolean('is_featured')->default(false);
                $table->text('requirements')->nullable();
                $table->date('apply_until')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'is_featured'], 'ao_status_featured_idx');
                $table->index(['apply_until', 'status'], 'ao_apply_until_status_idx');
            });
        }

        if (!Schema::hasTable('academy_opportunity_applications')) {
            Schema::create('academy_opportunity_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academy_opportunity_id')->constrained('academy_opportunities')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('status', ['submitted', 'reviewing', 'accepted', 'rejected'])->default('submitted');
                $table->text('message')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->unique(['academy_opportunity_id', 'user_id'], 'aoa_unique_user_opportunity');
                $table->index(['user_id', 'status'], 'aoa_user_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_opportunity_applications');
        Schema::dropIfExists('academy_opportunities');
    }
};

