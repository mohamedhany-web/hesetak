<?php

namespace Tests\Feature;

use App\Models\ClassroomMeeting;
use App\Models\LiveServer;
use App\Models\OneToOneSession;
use App\Models\User;
use App\Services\LiveMeetingProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

/**
 * اختبارات دخان شاملة لحصص 1:1 — سبورة + تسجيل + توكن LiveKit.
 */
class ClassroomPrivateLessonSmokeTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->ensureTables();

        config([
            'livekit.provider' => 'livekit',
            'livekit.livekit.api_key' => 'APItestkey',
            'livekit.livekit.api_secret' => 'test-secret-value-1234567890',
            'livekit.livekit.url' => 'wss://live.glottical.com',
            'livekit.livekit.host' => 'live.glottical.com',
            'livekit.livekit.token_ttl' => 3600,
        ]);
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
                $table->json('settings')->nullable();
                $table->timestamps();
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
     * @return array{0: User, 1: User, 2: ClassroomMeeting}
     */
    protected function seedMeeting(): array
    {
        LiveServer::query()->create([
            'name' => 'Hissatak Meeting',
            'domain' => 'live.glottical.com',
            'provider' => 'livekit',
            'status' => 'active',
        ]);

        $instructor = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('x'),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('x'),
        ]);
        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->subMinutes(5),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
        ]);
        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'one_to_one_session_id' => $session->id,
            'code' => 'SMK'.strtoupper(substr(uniqid(), -4)),
            'room_name' => 'حصتك-Smoke',
            'title' => 'حصة اختبار دخان',
            'started_at' => now()->subMinutes(2),
            'settings' => [
                'allow_guest_join' => false,
                'allow_participant_whiteboard' => false,
                'private_lesson' => true,
            ],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        return [$instructor, $student, $meeting];
    }

    private function decodeJwtVideoClaims(string $token): array
    {
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['video'] ?? null);

        return $payload['video'];
    }

    public function test_classroom_tokens_allow_data_publish_for_whiteboard_sync(): void
    {
        [$instructor, $student, $meeting] = $this->seedMeeting();
        $provider = app(LiveMeetingProvider::class);

        $hostPayload = $provider->classroomPayload($meeting->liveRoomName(), $instructor, true);
        $studentPayload = $provider->classroomPayload($meeting->liveRoomName(), $student, false);

        $this->assertNotEmpty($hostPayload['livekitToken']);
        $this->assertNotEmpty($studentPayload['livekitToken']);

        $hostVideo = $this->decodeJwtVideoClaims($hostPayload['livekitToken']);
        $studentVideo = $this->decodeJwtVideoClaims($studentPayload['livekitToken']);

        $this->assertTrue($hostVideo['canPublishData'] ?? false);
        $this->assertTrue($studentVideo['canPublishData'] ?? false);
        $this->assertTrue($hostVideo['roomAdmin'] ?? false);
        // JWT يحذف القيم false غالباً — المهم ألا يكون roomAdmin مفعّلاً للطالب
        $this->assertTrue(empty($studentVideo['roomAdmin']));
    }

    public function test_observer_grants_can_still_disable_data_publish(): void
    {
        [$instructor, , $meeting] = $this->seedMeeting();
        $payload = app(LiveMeetingProvider::class)->classroomPayload(
            $meeting->liveRoomName(),
            $instructor,
            false,
            ['canPublishData' => false, 'canPublish' => false, 'hidden' => true]
        );

        $video = $this->decodeJwtVideoClaims($payload['livekitToken']);
        $this->assertFalse($video['canPublishData'] ?? true);
    }

    public function test_whiteboard_routes_exist_for_student_and_instructor(): void
    {
        $this->assertTrue(Route::has('student.classroom.whiteboard.state'));
        $this->assertTrue(Route::has('student.classroom.whiteboard.push'));
        $this->assertTrue(Route::has('instructor.classroom.whiteboard.state'));
        $this->assertTrue(Route::has('instructor.classroom.whiteboard.push'));
    }

    public function test_instructor_room_includes_recording_and_whiteboard_sync_hooks(): void
    {
        [$instructor, , $meeting] = $this->seedMeeting();

        $response = $this->actingAs($instructor)
            ->get(route('instructor.classroom.room', $meeting));

        $response->assertOk();
        $html = $response->getContent();

        // تسجيل صامت من فتح الغرفة بدون انتظار شير
        $this->assertStringContainsString('mxSilentAutoRecording', $html);
        $this->assertStringContainsString('startLectureRecording', $html);
        $this->assertStringContainsString('mxTryStartSilentRecording', $html);
        $this->assertStringContainsString('__mxLkIsConnected', $html);
        $this->assertStringContainsString('mxCommitEndMeeting', $html);
        $this->assertStringContainsString('drawLectureCameraGrid', $html);
        $this->assertStringContainsString('cameraVideos', $html);
        $this->assertStringContainsString('التسجيل يعمل — جاري التقاط الغرفة', $html);
        $this->assertStringContainsString('الكاميرات ومشاركة الشاشة والسبورة تُضاف تلقائياً', $html);
        $this->assertStringContainsString('__mxLkGetRecordCapture', $html);
        $this->assertStringContainsString('__mxLkPublishData', $html);
        $this->assertStringContainsString('RoomEvent.DataReceived', $html);

        // سبورة متزامنة
        $this->assertStringContainsString('btn-wb-popup-open', $html);
        $this->assertStringContainsString('classroom-whiteboard-sync', $html);
        $this->assertStringContainsString('__mxClassroomWbSync', $html);
        $this->assertStringContainsString('MxClassroomWhiteboardSync', $html);
        $this->assertTrue(
            str_contains($html, '/whiteboard/state') || str_contains($html, '\/whiteboard\/state') || str_contains($html, 'whiteboard.state'),
            'whiteboard state URL missing from instructor room'
        );
        $this->assertStringContainsString('z-index: 100120', $html);
        $this->assertStringContainsString('hstk-wb-open', $html);
    }

    public function test_student_room_includes_synced_whiteboard_viewer(): void
    {
        [, $student, $meeting] = $this->seedMeeting();

        $response = $this->actingAs($student)
            ->get(route('student.classroom.room', $meeting));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('btn-wb-popup-open', $html);
        $this->assertStringContainsString('__mxWbSyncOptions', $html);
        $this->assertStringContainsString('classroom-whiteboard-sync', $html);
        $this->assertStringContainsString('MxClassroomWhiteboardSync', $html);
        $this->assertStringContainsString('canReceive: true', $html);
        $this->assertTrue(
            str_contains($html, '/whiteboard/state') || str_contains($html, '\/whiteboard\/state') || str_contains($html, 'stateUrl'),
            'whiteboard state URL missing from student room'
        );
        $this->assertStringContainsString('z-index: 100120', $html);
        $this->assertStringContainsString('mx-excalidraw-root', $html);
        $this->assertStringNotContainsString('mxSilentAutoRecording', $html);
        $this->assertStringNotContainsString('id="mx-end-meeting-form"', $html);
    }

    public function test_whiteboard_asset_route_serves_excalidraw_bundle(): void
    {
        $path = public_path('vendor/excalidraw/dist/excalidraw.production.min.js');
        $this->assertFileExists($path);
        $this->assertGreaterThan(100000, filesize($path));

        $response = $this->get('/mx-vendor/excalidraw/dist/excalidraw.production.min.js');
        $response->assertOk();
        $ctype = strtolower((string) $response->headers->get('Content-Type'));
        $this->assertTrue(
            str_contains($ctype, 'javascript') || str_contains($ctype, 'octet-stream'),
            'unexpected content-type: '.$ctype
        );
    }

    public function test_whiteboard_sync_script_is_publicly_available(): void
    {
        $path = public_path('js/classroom-whiteboard-sync.js');
        $this->assertFileExists($path);
        $body = file_get_contents($path);
        $this->assertIsString($body);
        $this->assertStringContainsString('MxClassroomWhiteboardSync', $body);
        $this->assertStringContainsString('wb_chunk', $body);
        $this->assertStringContainsString('attach', $body);
        $this->assertStringContainsString('__mxLkPublishData', $body);
    }
}
