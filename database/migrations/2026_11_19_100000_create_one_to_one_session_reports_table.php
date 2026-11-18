<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('one_to_one_session_reports')) {
            return;
        }

        Schema::create('one_to_one_session_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('one_to_one_session_id')->unique()->constrained('one_to_one_sessions')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('summary');
            $table->text('topics_covered')->nullable();
            $table->string('performance_level', 20)->nullable();
            $table->text('homework')->nullable();
            $table->text('next_steps')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('student_read_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'submitted_at']);
            $table->index(['instructor_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('one_to_one_session_reports');
    }
};
