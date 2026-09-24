<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutor_applications')) {
            Schema::table('tutor_applications', function (Blueprint $table) {
                if (! Schema::hasColumn('tutor_applications', 'teaching_subject_ids')) {
                    $table->json('teaching_subject_ids')->nullable()->after('answers');
                }
                if (! Schema::hasColumn('tutor_applications', 'academic_year_ids')) {
                    $table->json('academic_year_ids')->nullable()->after('teaching_subject_ids');
                }
                if (! Schema::hasColumn('tutor_applications', 'curriculum_types')) {
                    $table->json('curriculum_types')->nullable()->after('academic_year_ids');
                }
                if (! Schema::hasColumn('tutor_applications', 'blocked_at')) {
                    $table->timestamp('blocked_at')->nullable()->after('activated_by');
                }
                if (! Schema::hasColumn('tutor_applications', 'blocked_reason')) {
                    $table->string('blocked_reason', 120)->nullable()->after('blocked_at');
                }
            });
        }

        if (! Schema::hasTable('tutor_hiring_settings')) {
            Schema::create('tutor_hiring_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 80)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tutor_interview_slots')) {
            Schema::create('tutor_interview_slots', function (Blueprint $table) {
                $table->id();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->unsignedTinyInteger('capacity')->default(1);
                $table->string('meeting_mode', 20)->default('livekit');
                $table->string('external_url', 500)->nullable();
                $table->boolean('is_open')->default(true);
                $table->string('title')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['starts_at', 'is_open']);
            });
        }

        if (! Schema::hasTable('tutor_interviews')) {
            Schema::create('tutor_interviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tutor_application_id')->constrained('tutor_applications')->cascadeOnDelete();
                $table->foreignId('tutor_interview_slot_id')->nullable()->constrained('tutor_interview_slots')->nullOnDelete();
                $table->timestamp('scheduled_at');
                $table->timestamp('ends_at')->nullable();
                $table->string('meeting_mode', 20)->default('livekit');
                $table->string('room_name', 80)->nullable();
                $table->string('join_url', 500)->nullable();
                $table->string('external_url', 500)->nullable();
                $table->string('status', 32)->default('scheduled');
                $table->string('result', 32)->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('no_show_marked_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'scheduled_at']);
                $table->index(['tutor_application_id', 'status']);
            });
        }

        if (Schema::hasTable('instructor_agreements')) {
            Schema::table('instructor_agreements', function (Blueprint $table) {
                if (! Schema::hasColumn('instructor_agreements', 'tutor_application_id')) {
                    $table->foreignId('tutor_application_id')->nullable()->after('instructor_id')
                        ->constrained('tutor_applications')->nullOnDelete();
                }
                if (! Schema::hasColumn('instructor_agreements', 'offered_at')) {
                    $table->timestamp('offered_at')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('instructor_agreements', 'signed_at')) {
                    $table->timestamp('signed_at')->nullable()->after('offered_at');
                }
                if (! Schema::hasColumn('instructor_agreements', 'signer_name')) {
                    $table->string('signer_name')->nullable()->after('signed_at');
                }
                if (! Schema::hasColumn('instructor_agreements', 'signature_path')) {
                    $table->string('signature_path')->nullable()->after('signer_name');
                }
                if (! Schema::hasColumn('instructor_agreements', 'pdf_path')) {
                    $table->string('pdf_path')->nullable()->after('signature_path');
                }
                if (! Schema::hasColumn('instructor_agreements', 'candidate_ip')) {
                    $table->string('candidate_ip', 45)->nullable()->after('pdf_path');
                }
                if (! Schema::hasColumn('instructor_agreements', 'candidate_user_agent')) {
                    $table->string('candidate_user_agent', 500)->nullable()->after('candidate_ip');
                }
                if (! Schema::hasColumn('instructor_agreements', 'offer_token')) {
                    $table->string('offer_token', 64)->nullable()->unique()->after('candidate_user_agent');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('instructor_agreements')) {
            Schema::table('instructor_agreements', function (Blueprint $table) {
                foreach ([
                    'offer_token', 'candidate_user_agent', 'candidate_ip', 'pdf_path',
                    'signature_path', 'signer_name', 'signed_at', 'offered_at',
                ] as $col) {
                    if (Schema::hasColumn('instructor_agreements', $col)) {
                        $table->dropColumn($col);
                    }
                }
                if (Schema::hasColumn('instructor_agreements', 'tutor_application_id')) {
                    $table->dropConstrainedForeignId('tutor_application_id');
                }
            });
        }

        Schema::dropIfExists('tutor_interviews');
        Schema::dropIfExists('tutor_interview_slots');
        Schema::dropIfExists('tutor_hiring_settings');

        if (Schema::hasTable('tutor_applications')) {
            Schema::table('tutor_applications', function (Blueprint $table) {
                foreach ([
                    'teaching_subject_ids', 'academic_year_ids', 'curriculum_types',
                    'blocked_at', 'blocked_reason',
                ] as $col) {
                    if (Schema::hasColumn('tutor_applications', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
