<?php

namespace Tests\Feature;

use App\Models\InstructorAgreement;
use App\Models\InstructorProfile;
use App\Models\TutorApplication;
use App\Models\TutorHiringSetting;
use App\Models\TutorInterview;
use App\Models\TutorInterviewSlot;
use App\Models\User;
use App\Services\TeacherSpecialtyMatcher;
use App\Services\TutorContractService;
use App\Services\TutorInterviewBookingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class TutorHiringPipelineTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        Storage::fake('public');
        config(['filesystems.public_media_disk' => 'public']);
        config(['services.whatsapp.type' => 'disabled']);
        TutorHiringSetting::putMany([
            'require_specialty' => true,
            'require_interview' => true,
            'require_contract' => true,
            'allow_reschedule' => true,
            'no_show_grace_minutes' => 0,
            'interview_duration_minutes' => 30,
        ]);
    }

    public function test_specialty_incomplete_fails_assert(): void
    {
        $app = TutorApplication::create([
            'full_name' => 'معلم',
            'email' => 't@example.com',
            'status' => TutorApplication::STATUS_PENDING,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        TeacherSpecialtyMatcher::assertComplete($app);
    }

    public function test_booking_slot_sends_mail_and_schedules(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'role' => 'instructor',
            'email' => 'cand@example.com',
            'phone' => '+966500000001',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $app = TutorApplication::create([
            'user_id' => $user->id,
            'full_name' => 'مرشح',
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => TutorApplication::STATUS_INTERVIEW_PENDING,
            'teaching_subject_ids' => [1],
            'academic_year_ids' => [1],
            'curriculum_types' => ['saudi'],
        ]);

        $slot = TutorInterviewSlot::create([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'capacity' => 1,
            'meeting_mode' => 'external',
            'external_url' => 'https://meet.example.com/room',
            'is_open' => true,
        ]);

        $interview = app(TutorInterviewBookingService::class)->bookSlot($app, $slot, $user);

        $this->assertSame(TutorInterview::STATUS_SCHEDULED, $interview->status);
        $this->assertSame(TutorApplication::STATUS_INTERVIEW_SCHEDULED, $app->fresh()->status);
        Mail::assertSent(\App\Mail\TutorInterviewScheduledMail::class);
    }

    public function test_no_show_command_blocks_application(): void
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'email' => 'noshow@example.com',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $app = TutorApplication::create([
            'user_id' => $user->id,
            'full_name' => 'متغيب',
            'email' => $user->email,
            'status' => TutorApplication::STATUS_INTERVIEW_SCHEDULED,
            'teaching_subject_ids' => [1],
            'curriculum_types' => ['saudi'],
        ]);

        TutorInterview::create([
            'tutor_application_id' => $app->id,
            'scheduled_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
            'meeting_mode' => 'external',
            'external_url' => 'https://meet.example.com/x',
            'join_url' => 'https://meet.example.com/x',
            'status' => TutorInterview::STATUS_SCHEDULED,
            'result' => TutorInterview::RESULT_PENDING,
        ]);

        Artisan::call('tutor:interviews-mark-no-shows');

        $this->assertSame(TutorApplication::STATUS_BLOCKED_NO_SHOW, $app->fresh()->status);
        $this->assertFalse((bool) $user->fresh()->is_active);
    }

    public function test_contract_gate_and_sign_flow(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $user = User::factory()->create([
            'role' => 'instructor',
            'email' => 'sign@example.com',
            'phone' => '+966500000099',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        InstructorProfile::create([
            'user_id' => $user->id,
            'status' => InstructorProfile::STATUS_PENDING_REVIEW,
            'teaching_subject_ids' => [1],
            'curriculum_types' => ['saudi'],
        ]);

        $app = TutorApplication::create([
            'user_id' => $user->id,
            'full_name' => 'موقع',
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => TutorApplication::STATUS_INTERVIEW_PASSED,
            'teaching_subject_ids' => [1],
            'academic_year_ids' => [1],
            'curriculum_types' => ['saudi'],
        ]);

        $contracts = app(TutorContractService::class);

        try {
            $contracts->assertReadyForActivation($app);
            $this->fail('Expected validation for missing contract');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('contract', $e->errors());
        }

        $agreement = $contracts->offer($app, $admin, [
            'billing_type' => InstructorAgreement::BILLING_PER_SESSION,
            'salary_per_session' => 50,
            'terms' => 'شروط تجريبية',
        ]);

        $png = base64_encode(hex2bin('89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c4890000000a49444154789c63000100000500010d0a2db40000000049454e44ae426082'));
        $contracts->sign($agreement, 'موقع العقد', 'data:image/png;base64,'.$png, '127.0.0.1', 'phpunit');

        $this->assertSame(TutorApplication::STATUS_CONTRACT_SIGNED, $app->fresh()->status);
        $this->assertTrue($agreement->fresh()->isSigned());

        $contracts->assertReadyForActivation($app->fresh());
    }

    public function test_matcher_rejects_outside_subject(): void
    {
        $profile = new InstructorProfile([
            'teaching_subject_ids' => [10],
            'curriculum_types' => ['saudi'],
        ]);

        $this->assertFalse(TeacherSpecialtyMatcher::matches($profile, 99, null, 'saudi'));
        $this->assertTrue(TeacherSpecialtyMatcher::matches($profile, 10, null, 'saudi'));
    }
}
