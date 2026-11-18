<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('package_slot_proposals')) {
            return;
        }

        Schema::create('package_slot_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('service_package_id')->nullable();
            $table->unsignedBigInteger('academic_subject_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->json('proposed_slots');
            $table->json('accepted_slots')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->text('student_notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['instructor_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_slot_proposals');
    }
};
