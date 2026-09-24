<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('advanced_courses') && ! Schema::hasColumn('advanced_courses', 'product_track')) {
            Schema::table('advanced_courses', function (Blueprint $table) {
                $table->string('product_track', 32)->nullable()->after('delivery_type')->index();
            });
        }

        if (! Schema::hasTable('student_progress_snapshots')) {
            Schema::create('student_progress_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('period_key', 16); // e.g. 2026-09
                $table->date('period_start');
                $table->date('period_end');
                $table->unsignedTinyInteger('exam_average')->nullable();
                $table->unsignedTinyInteger('attendance_percent')->nullable();
                $table->unsignedTinyInteger('course_progress_percent')->nullable();
                $table->unsignedInteger('sessions_completed')->default(0);
                $table->unsignedInteger('xp_total')->default(0);
                $table->json('strengths')->nullable();
                $table->json('improvements')->nullable();
                $table->json('metrics')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'period_key']);
            });
        }

        if (! Schema::hasTable('adaptive_learning_suggestions')) {
            Schema::create('adaptive_learning_suggestions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('kind', 32); // review_lesson|book_session|recorded_course|book_material
                $table->string('title');
                $table->text('reason')->nullable();
                $table->string('priority', 16)->default('medium'); // high|medium|low
                $table->string('source', 32)->default('exam_errors');
                $table->string('suggestable_type')->nullable();
                $table->unsignedBigInteger('suggestable_id')->nullable();
                $table->string('action_url')->nullable();
                $table->json('meta')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_active'], 'als_user_active_idx');
                $table->index(['suggestable_type', 'suggestable_id'], 'als_suggestable_idx');
            });
        } elseif (! $this->hasIndex('adaptive_learning_suggestions', 'als_suggestable_idx')) {
            Schema::table('adaptive_learning_suggestions', function (Blueprint $table) {
                $table->index(['suggestable_type', 'suggestable_id'], 'als_suggestable_idx');
            });
        }

        if (! Schema::hasTable('family_progress_reports')) {
            Schema::create('family_progress_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('period_key', 16);
                $table->json('payload');
                $table->string('delivery_channel', 16)->default('email'); // email|whatsapp|both
                $table->string('status', 16)->default('pending'); // pending|sent|failed
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'period_key'], 'fpr_student_period_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('family_progress_reports');
        Schema::dropIfExists('adaptive_learning_suggestions');
        Schema::dropIfExists('student_progress_snapshots');

        if (Schema::hasTable('advanced_courses') && Schema::hasColumn('advanced_courses', 'product_track')) {
            Schema::table('advanced_courses', function (Blueprint $table) {
                $table->dropColumn('product_track');
            });
        }
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $rows = Schema::getConnection()->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $rows !== [];
    }
};
