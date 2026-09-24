<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class ParentProgressShareTokenTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
    }

    public function test_parent_progress_rejects_numeric_student_id(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('password'),
            'progress_share_token' => 'tok_'.str_repeat('a', 44),
        ]);

        $this->get('/parent-progress?student_id='.$student->id)
            ->assertNotFound();
    }

    public function test_parent_progress_opens_with_valid_share_token(): void
    {
        $token = 'tok_'.str_repeat('b', 44);

        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'name' => 'طالب تجريبي',
            'password' => Hash::make('password'),
            'progress_share_token' => $token,
        ]);

        $this->get('/parent-progress?token='.$token)
            ->assertOk()
            ->assertSee('طالب تجريبي', false)
            ->assertDontSee('ID '.$student->id, false);
    }

    public function test_parent_progress_rejects_unknown_token(): void
    {
        $this->get('/parent-progress?token='.str_repeat('x', 48))
            ->assertOk()
            ->assertSee('رمز', false);
    }

    public function test_admin_user_routes_use_uuid_not_numeric_id(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $target = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $url = route('admin.users.show', $target);
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        $this->assertStringContainsString((string) $target->uuid, $path);
        $this->assertDoesNotMatchRegularExpression('#/users/'.$target->id.'(/|$)#', $path);

        $this->actingAs($admin)
            ->get('/admin/users/'.$target->id)
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/admin/users/'.$target->uuid)
            ->assertOk();
    }

    public function test_user_route_binding_rejects_numeric_id(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->assertNull((new User)->resolveRouteBinding((string) $instructor->id));
        $this->assertNotNull((new User)->resolveRouteBinding((string) $instructor->uuid));
    }
}
