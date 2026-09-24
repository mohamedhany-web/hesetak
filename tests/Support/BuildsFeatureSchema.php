<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مخطط SQLite مصغّر للاختبارات — مشروع الهجرات يعتمد MySQL (information_schema).
 */
trait BuildsFeatureSchema
{
    protected function buildFeatureSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('progress_share_token', 64)->nullable()->unique();
            $table->string('referral_code')->nullable()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('student');
            $table->boolean('is_active')->default(true);
            $table->string('gender')->nullable();
            $table->string('address')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('portfolio_intro_video_url')->nullable();
            $table->json('private_teaching_meta')->nullable();
            $table->json('portfolio_marketing_published')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('sender_id')->nullable();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('type')->nullable();
            $table->string('priority')->nullable();
            $table->string('audience')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('hiring_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('hiring_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiring_form_id');
            $table->string('type', 40);
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();
            $table->string('system_key', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('tutor_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('hiring_form_id')->nullable();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('nationality')->nullable();
            $table->string('city')->nullable();
            $table->string('gender')->nullable();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->text('experience')->nullable();
            $table->text('education')->nullable();
            $table->unsignedInteger('years_experience')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('intro_video_path')->nullable();
            $table->string('intro_video_url')->nullable();
            $table->json('answers')->nullable();
            $table->json('teaching_subject_ids')->nullable();
            $table->json('academic_year_ids')->nullable();
            $table->json('curriculum_types')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->string('blocked_reason', 120)->nullable();
            $table->string('status', 32)->default('draft');
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('instructor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->text('experience')->nullable();
            $table->json('skills')->nullable();
            $table->json('teaching_subject_ids')->nullable();
            $table->json('curriculum_types')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });

        
        Schema::create('tutor_hiring_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('tutor_interview_slots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->unsignedTinyInteger('capacity')->default(1);
            $table->string('meeting_mode', 20)->default('livekit');
            $table->string('external_url', 500)->nullable();
            $table->boolean('is_open')->default(true);
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('tutor_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_application_id');
            $table->foreignId('tutor_interview_slot_id')->nullable();
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
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('instructor_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->nullable();
            $table->foreignId('tutor_application_id')->nullable();
            $table->foreignId('advanced_course_id')->nullable();
            $table->decimal('course_percentage', 5, 2)->nullable();
            $table->string('billing_type')->nullable();
            $table->string('type')->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->string('agreement_number')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('salary_per_session', 10, 2)->nullable();
            $table->unsignedInteger('sessions_count')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('monthly_amount', 10, 2)->nullable();
            $table->unsignedInteger('months_count')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('status')->default('draft');
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('candidate_ip', 45)->nullable();
            $table->string('candidate_user_agent', 500)->nullable();
            $table->string('offer_token', 64)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedInteger('level_number')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('message')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tutoring_groups', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('instructor_id');
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('hourly_rate')->nullable();
            $table->unsignedInteger('sessions_per_month')->nullable();
            $table->string('whatsapp_group_url')->nullable();
            $table->string('learning_path')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('academic_subject_id')->nullable();
            $table->string('currency', 8)->default('USD');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tutoring_group_cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoring_group_id');
            $table->string('title');
            $table->string('slug');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('study_days')->nullable();
            $table->time('study_time')->nullable();
            $table->unsignedSmallInteger('sessions_count')->nullable();
            $table->unsignedSmallInteger('session_duration_minutes')->nullable();
            $table->string('timezone')->nullable();
            $table->unsignedInteger('capacity')->default(8);
            $table->unsignedInteger('enrolled_count')->default(0);
            $table->unsignedInteger('min_enrollment')->default(1);
            $table->string('status', 32)->default('open');
            $table->timestamp('postponed_to')->nullable();
            $table->string('whatsapp_group_url')->nullable();
            $table->timestamp('enrollment_closes_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('classroom_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('consultation_request_id')->nullable();
            $table->unsignedBigInteger('one_to_one_session_id')->nullable();
            $table->unsignedBigInteger('tutoring_group_booking_id')->nullable();
            $table->string('code', 32)->unique();
            $table->string('room_name', 64);
            $table->string('title')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->unsignedInteger('planned_duration_minutes')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->unsignedInteger('participants_peak')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('tutoring_class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoring_group_cohort_id');
            $table->foreignId('tutoring_group_id');
            $table->unsignedInteger('session_number')->default(1);
            $table->string('title')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('scheduled');
            $table->foreignId('classroom_meeting_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tutoring_group_cohort_id', 'session_number'], 'cohort_session_number_unique');
        });

        Schema::create('tutoring_cohort_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoring_group_cohort_id');
            $table->foreignId('user_id');
            $table->string('status', 32)->default('active');
            $table->timestamp('enrolled_at')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('student_service_entitlement_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tutoring_group_cohort_id', 'user_id'], 'cohort_user_enrollment_unique');
        });

        Schema::create('tutoring_class_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoring_class_session_id');
            $table->foreignId('user_id');
            $table->string('status', 32)->default('present');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tutoring_class_session_id', 'user_id'], 'session_user_attendance_unique');
        });

        Schema::create('student_service_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('service_package_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('scope', 64)->default('global');
            $table->string('plan_type')->nullable();
            $table->unsignedInteger('term_months')->nullable();
            $table->unsignedInteger('weekly_group_sessions')->nullable();
            $table->unsignedInteger('weekly_private_sessions')->nullable();
            $table->boolean('includes_community')->default(false);
            $table->boolean('includes_libraries')->default(false);
            $table->unsignedBigInteger('tutoring_group_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('academic_subject_id')->nullable();
            $table->unsignedInteger('units_total')->default(0);
            $table->unsignedInteger('units_used')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tutoring_group_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tutoring_group_id')->nullable();
            $table->unsignedBigInteger('cohort_id')->nullable();
            $table->unsignedBigInteger('student_service_entitlement_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedBigInteger('classroom_meeting_id')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('student_tutoring_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('tutoring_group_id')->nullable();
            $table->unsignedBigInteger('tutoring_group_package_id')->nullable();
            $table->unsignedInteger('sessions_total')->default(0);
            $table->unsignedInteger('sessions_used')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('student_service_entitlement_id')->nullable();
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('verification_code')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->string('certificate_hash')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('tagline')->nullable();
            $table->string('badge')->nullable();
            $table->string('scope')->default('global');
            $table->string('plan_type')->nullable();
            $table->unsignedInteger('term_months')->nullable();
            $table->unsignedInteger('weekly_group_sessions')->nullable();
            $table->unsignedInteger('weekly_private_sessions')->nullable();
            $table->boolean('includes_community')->default(false);
            $table->boolean('includes_libraries')->default(false);
            $table->json('features')->nullable();
            $table->json('gifts')->nullable();
            $table->unsignedBigInteger('tutoring_group_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('academic_subject_id')->nullable();
            $table->string('curriculum_type', 64)->nullable();
            $table->unsignedInteger('units_count')->default(1);
            $table->unsignedInteger('session_minutes')->default(60);
            $table->unsignedInteger('duration_days')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('currency', 8)->default('SAR');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('service_package_id')->nullable();
            $table->unsignedBigInteger('tutoring_group_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->json('custom_package_data')->nullable();
            $table->string('order_type')->nullable();
            $table->decimal('original_amount', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 8)->default('SAR');
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('service_session_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->string('curriculum_type', 64);
            $table->decimal('price_per_session', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('package_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->unsignedBigInteger('service_package_id')->nullable();
            $table->foreignId('buyer_user_id');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 64)->nullable();
            $table->unsignedBigInteger('recipient_user_id')->nullable();
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
        });

        Schema::create('advanced_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('delivery_type', 32)->nullable();
            $table->string('product_track', 32)->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->unsignedBigInteger('course_category_id')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('student_progress_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('period_key', 16);
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

        Schema::create('adaptive_learning_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('title');
            $table->text('reason')->nullable();
            $table->string('priority', 16)->default('medium');
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

        Schema::create('family_progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('period_key', 16);
            $table->json('payload');
            $table->string('delivery_channel', 16)->default('email');
            $table->string('status', 16)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'period_key']);
        });
    }
}
