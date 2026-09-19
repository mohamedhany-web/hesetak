<?php

namespace Tests\Feature;

use App\Models\ClassroomMeeting;
use App\Models\OneToOneSession;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class ClassroomRecordingR2UploadTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->ensureTables();
        Storage::fake('live_recordings_r2');
    }

    protected function ensureTables(): void
    {
        if (! Schema::hasTable('live_servers')) {
            Schema::create('live_servers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('domain');
                $table->string('provider', 32)->default('livekit');
                $table->string('status')->default('active');
                $table->string('ip_address')->nullable();
                $table->unsignedInteger('max_participants')->default(100);
                $table->unsignedInteger('current_load')->default(0);
                $table->json('config')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('classroom_meetings')) {
            Schema::create('classroom_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->unsignedBigInteger('one_to_one_session_id')->nullable();
                $table->string('code', 32)->unique();
                $table->string('room_name', 64)->nullable();
                $table->string('title')->nullable();
                $table->unsignedInteger('planned_duration_minutes')->nullable();
                $table->unsignedInteger('max_participants')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->string('recording_disk')->nullable();
                $table->string('recording_path')->nullable();
                $table->string('recording_mime_type')->nullable();
                $table->unsignedBigInteger('recording_size')->nullable();
                $table->string('recording_audio_path')->nullable();
                $table->string('recording_audio_mime_type')->nullable();
                $table->unsignedBigInteger('recording_audio_size')->nullable();
                $table->unsignedInteger('recording_duration_seconds')->nullable();
                $table->unsignedInteger('recording_audio_duration_seconds')->nullable();
                $table->timestamp('recording_uploaded_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('classroom_meetings', function (Blueprint $table) {
                foreach ([
                    'recording_disk', 'recording_path', 'recording_mime_type', 'recording_size',
                    'recording_audio_path', 'recording_audio_mime_type', 'recording_audio_size',
                    'recording_duration_seconds', 'recording_audio_duration_seconds', 'recording_uploaded_at',
                ] as $col) {
                    if (! Schema::hasColumn('classroom_meetings', $col)) {
                        if (str_contains($col, '_at')) {
                            $table->timestamp($col)->nullable();
                        } elseif (str_contains($col, '_size') || str_contains($col, '_seconds')) {
                            $table->unsignedBigInteger($col)->nullable();
                        } else {
                            $table->string($col)->nullable();
                        }
                    }
                }
            });
        }

        if (! Schema::hasTable('one_to_one_sessions')) {
            Schema::create('one_to_one_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instructor_id');
                $table->foreignId('student_id');
                $table->unsignedInteger('session_number')->default(1);
                $table->timestamp('scheduled_at')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->default(50);
                $table->string('status', 32)->default('scheduled');
                $table->unsignedBigInteger('classroom_meeting_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * @return array{0: User, 1: User, 2: ClassroomMeeting, 3: OneToOneSession}
     */
    protected function seedMeetingPair(bool $ended = false): array
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);

        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->subHour(),
            'duration_minutes' => 50,
            'status' => $ended ? OneToOneSession::STATUS_COMPLETED : OneToOneSession::STATUS_SCHEDULED,
        ]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'one_to_one_session_id' => $session->id,
            'code' => 'R2'.strtoupper(substr(uniqid(), -5)),
            'room_name' => 'حصتك-R2TEST',
            'title' => 'حصة 1:1',
            'started_at' => now()->subMinutes(30),
            'ended_at' => $ended ? now()->subMinutes(2) : null,
            'settings' => ['allow_guest_join' => false],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        return [$instructor, $student, $meeting, $session];
    }

    public function test_instructor_can_complete_direct_r2_upload_after_meeting_ended(): void
    {
        [$instructor, $student, $meeting] = $this->seedMeetingPair(ended: true);

        $path = 'classroom-recordings/'.now()->format('Y/m').'/meeting-'.$meeting->id.'-test.webm';
        Storage::disk('live_recordings_r2')->put($path, 'fake-webm-content');

        $token = Str::random(64);
        Cache::put('classroom_recording_presign:'.$token, [
            'path' => $path,
            'meeting_id' => $meeting->id,
            'user_id' => $instructor->id,
            'mime' => 'video/webm',
        ], now()->addHour());

        $this->actingAs($instructor)
            ->postJson(route('instructor.classroom.recording.complete', $meeting), [
                'upload_token' => $token,
                'duration_seconds' => 120,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'تم رفع وحفظ تسجيل المحاضرة بنجاح.');

        $meeting->refresh();
        $this->assertSame('live_recordings_r2', $meeting->recording_disk);
        $this->assertSame($path, $meeting->recording_path);
        $this->assertNotNull($meeting->recording_uploaded_at);
        $this->assertTrue($meeting->hasBrowserRecording());

        $this->actingAs($student)
            ->get(route('student.one-to-one-sessions.show', $meeting->one_to_one_session_id))
            ->assertOk()
            ->assertSee(route('student.classroom.recording', $meeting), false);
    }

    public function test_concurrent_meetings_get_unique_presign_paths(): void
    {
        [$instructorA, , $meetingA] = $this->seedMeetingPair();
        [$instructorB, , $meetingB] = $this->seedMeetingPair();

        $responseA = $this->actingAs($instructorA)
            ->postJson(route('instructor.classroom.recording.presign', $meetingA), [
                'content_type' => 'video/webm',
            ]);

        $responseB = $this->actingAs($instructorB)
            ->postJson(route('instructor.classroom.recording.presign', $meetingB), [
                'content_type' => 'video/webm',
            ]);

        if ($responseA->json('direct_upload') === false) {
            $this->markTestSkipped('Fake disk does not support temporary upload URLs in this environment.');
        }

        $responseA->assertOk();
        $responseB->assertOk();

        $tokenA = (string) $responseA->json('upload_token');
        $tokenB = (string) $responseB->json('upload_token');
        $this->assertNotSame($tokenA, $tokenB);

        $payloadA = Cache::get('classroom_recording_presign:'.$tokenA);
        $payloadB = Cache::get('classroom_recording_presign:'.$tokenB);
        $this->assertNotSame($payloadA['path'], $payloadB['path']);
        $this->assertSame($meetingA->id, $payloadA['meeting_id']);
        $this->assertSame($meetingB->id, $payloadB['meeting_id']);
    }

    public function test_admin_can_delete_classroom_recording_from_r2(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        [, , $meeting] = $this->seedMeetingPair(ended: true);

        $path = 'classroom-recordings/'.now()->format('Y/m').'/meeting-'.$meeting->id.'-delete.webm';
        $audioPath = 'classroom-recordings-audio/'.now()->format('Y/m').'/meeting-'.$meeting->id.'-delete.webm';
        Storage::disk('live_recordings_r2')->put($path, 'video-bytes');
        Storage::disk('live_recordings_r2')->put($audioPath, 'audio-bytes');

        $meeting->forceFill([
            'recording_disk' => 'live_recordings_r2',
            'recording_path' => $path,
            'recording_audio_path' => $audioPath,
            'recording_uploaded_at' => now(),
        ])->save();

        $this->assertTrue($meeting->fresh()->hasBrowserRecording());

        $destroyUrl = route('admin.classroom-recordings.destroy', $meeting);

        $this->actingAs($admin)
            ->withoutMiddleware([
                \App\Http\Middleware\EnsurePermission::class,
                \App\Http\Middleware\RestrictRbacEmployeeAdminRoutes::class,
            ])
            ->get(route('admin.classroom-recordings.index'))
            ->assertOk()
            ->assertSee('مسح', false)
            ->assertSee($destroyUrl, false);

        $this->actingAs($admin)
            ->withoutMiddleware([
                \App\Http\Middleware\EnsurePermission::class,
                \App\Http\Middleware\RestrictRbacEmployeeAdminRoutes::class,
            ])
            ->from(route('admin.classroom-recordings.index'))
            ->delete($destroyUrl)
            ->assertRedirect(route('admin.classroom-recordings.index'))
            ->assertSessionHas('success');

        $meeting->refresh();
        $this->assertNull($meeting->recording_path);
        $this->assertNull($meeting->recording_audio_path);
        $this->assertNull($meeting->recording_disk);
        $this->assertNull($meeting->recording_uploaded_at);
        $this->assertFalse($meeting->hasBrowserRecording());
        Storage::disk('live_recordings_r2')->assertMissing($path);
        Storage::disk('live_recordings_r2')->assertMissing($audioPath);

        $this->actingAs($admin)
            ->withoutMiddleware([
                \App\Http\Middleware\EnsurePermission::class,
                \App\Http\Middleware\RestrictRbacEmployeeAdminRoutes::class,
            ])
            ->get(route('admin.classroom-recordings.index'))
            ->assertOk()
            ->assertSee('تسجيلات Classroom', false)
            ->assertDontSee($destroyUrl, false);
    }

    public function test_admin_can_silently_observe_live_classroom_meeting(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        [, , $meeting] = $this->seedMeetingPair(ended: false);

        config([
            'livekit.livekit.api_key' => 'devkey',
            'livekit.livekit.api_secret' => 'secret',
            'livekit.livekit.url' => 'wss://live.example.test',
            'livekit.livekit.host' => 'live.example.test',
        ]);

        $observeUrl = route('admin.classroom-recordings.observe', $meeting);

        $this->actingAs($admin)
            ->withoutMiddleware([
                \App\Http\Middleware\EnsurePermission::class,
                \App\Http\Middleware\RestrictRbacEmployeeAdminRoutes::class,
            ])
            ->get(route('admin.classroom-recordings.index', ['status' => 'live']))
            ->assertOk()
            ->assertSee('دخول صامت', false)
            ->assertSee($observeUrl, false);

        $response = $this->actingAs($admin)
            ->withoutMiddleware([
                \App\Http\Middleware\EnsurePermission::class,
                \App\Http\Middleware\RestrictRbacEmployeeAdminRoutes::class,
            ])
            ->get($observeUrl);

        $response->assertOk()
            ->assertSee('مراقبة صامتة', false)
            ->assertDontSee('id="mx-end-meeting-form"', false);

        $html = $response->getContent();
        $this->assertStringContainsString('const token =', $html);
        $this->assertMatchesRegularExpression('/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', $html);
        preg_match('/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', $html, $m);
        $jwt = $m[0];

        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertIsArray($payload);
        $this->assertFalse((bool) ($payload['video']['canPublish'] ?? true));
        $this->assertTrue((bool) ($payload['video']['canSubscribe'] ?? false));
        $this->assertTrue((bool) ($payload['video']['hidden'] ?? false));
        $this->assertFalse((bool) ($payload['video']['canPublishData'] ?? true));
    }

    public function test_admin_cannot_observe_ended_classroom_meeting(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        [, , $meeting] = $this->seedMeetingPair(ended: true);

        $this->actingAs($admin)
            ->withoutMiddleware([
                \App\Http\Middleware\EnsurePermission::class,
                \App\Http\Middleware\RestrictRbacEmployeeAdminRoutes::class,
            ])
            ->get(route('admin.classroom-recordings.observe', $meeting))
            ->assertRedirect(route('admin.classroom-recordings.index', ['status' => 'live']))
            ->assertSessionHas('error');
    }
}
