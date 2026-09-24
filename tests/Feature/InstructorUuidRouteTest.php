<?php

namespace Tests\Feature;

use App\Models\InstructorProfile;
use App\Models\TutorApplication;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class InstructorUuidRouteTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
    }

    public function test_public_instructor_url_uses_uuid_not_numeric_id(): void
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        InstructorProfile::create([
            'user_id' => $user->id,
            'status' => InstructorProfile::STATUS_APPROVED,
            'headline' => 'Teacher',
        ]);

        $url = route('public.instructors.show', $user);
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        $this->assertStringContainsString((string) $user->uuid, $path);
        $this->assertDoesNotMatchRegularExpression('#/instructors/'.$user->id.'(/|$)#', $path);
    }

    public function test_tutor_application_admin_route_uses_uuid(): void
    {
        $app = TutorApplication::create([
            'full_name' => 'مرشح',
            'email' => 'c@example.com',
            'status' => TutorApplication::STATUS_PENDING,
        ]);

        $url = route('admin.tutor-applications.show', $app);
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        $this->assertStringContainsString((string) $app->uuid, $path);
        $this->assertDoesNotMatchRegularExpression('#/tutor-applications/'.$app->id.'(/|$)#', $path);
    }

    public function test_tutor_application_binding_rejects_numeric_id(): void
    {
        $app = TutorApplication::create([
            'full_name' => 'مرشح 2',
            'email' => 'c2@example.com',
            'status' => TutorApplication::STATUS_PENDING,
        ]);

        $this->assertNull((new TutorApplication)->resolveRouteBinding((string) $app->id));
        $this->assertNotNull((new TutorApplication)->resolveRouteBinding((string) $app->uuid));
    }
}
