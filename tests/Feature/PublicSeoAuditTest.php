<?php

namespace Tests\Feature;

use App\Models\InstructorProfile;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\PublicInstructorDirectory;
use App\Support\PublicDemoContent;
use Illuminate\Support\Facades\Hash;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class PublicSeoAuditTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
    }

    public function test_robots_txt_is_plain_text_with_expected_rules(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Disallow: /login', false);
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Allow: /', false);
        $response->assertSee('Sitemap:', false);
        $this->assertStringNotContainsString('<html', $response->getContent());
    }

    public function test_sitemap_is_xml_and_excludes_private_paths(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));
        $body = $response->getContent();
        $this->assertStringContainsString('<urlset', $body);
        $this->assertStringNotContainsString('/login', $body);
        $this->assertStringNotContainsString('/admin', $body);
        $this->assertStringNotContainsString('/checkout', $body);
        $this->assertStringNotContainsString('/dashboard', $body);
    }

    public function test_partial_user_select_does_not_rotate_uuid(): void
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
        $original = (string) $user->uuid;

        $partial = User::query()->select(['id', 'name'])->find($user->id);
        $key = $partial->getRouteKey();

        $this->assertSame($original, $key);
        $this->assertSame($original, (string) $user->fresh()->uuid);
    }

    public function test_public_instructor_profile_returns_200_for_approved_active_uuid(): void
    {
        $user = $this->makePublicInstructor('معلم معتمد');

        $this->get(route('public.instructors.show', $user))
            ->assertOk()
            ->assertSee('معلم معتمد', false);
    }

    public function test_unpublished_instructor_returns_410(): void
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
        InstructorProfile::create([
            'user_id' => $user->id,
            'status' => InstructorProfile::STATUS_PENDING_REVIEW,
            'headline' => 'Pending',
        ]);

        $this->get('/instructors/'.$user->uuid)->assertStatus(410);
    }

    public function test_unknown_instructor_uuid_redirects_to_directory(): void
    {
        $this->get('/instructors/00000000-0000-0000-0000-000000000000')
            ->assertRedirect(route('public.instructors.index'));
    }

    public function test_numeric_instructor_id_redirects_to_uuid_when_public(): void
    {
        $user = $this->makePublicInstructor('معلم رقمي');

        $this->get('/instructors/'.$user->id)
            ->assertRedirect(route('public.instructors.show', $user));
    }

    public function test_demo_named_instructor_is_hidden_from_directory(): void
    {
        $this->makePublicInstructor('test');

        $visible = PublicInstructorDirectory::approvedActiveQuery()
            ->with(['user:'.implode(',', PublicInstructorDirectory::userSelect())])
            ->get()
            ->reject(fn (InstructorProfile $profile) => PublicInstructorDirectory::profileLooksLikeDemo($profile));

        $this->assertTrue($visible->isEmpty());
    }

    public function test_public_pages_have_unique_titles(): void
    {
        $login = $this->get('/login')->assertOk();
        $pricing = $this->get('/pricing')->assertOk();
        $students = $this->get('/for-students')->assertOk();
        $how = $this->get('/how-it-works')->assertOk();

        $titles = [
            $this->htmlTitle($login->getContent()),
            $this->htmlTitle($pricing->getContent()),
            $this->htmlTitle($students->getContent()),
            $this->htmlTitle($how->getContent()),
        ];

        $this->assertCount(4, array_unique($titles));
        $this->assertStringContainsString('noindex', $login->getContent());
    }

    public function test_guest_package_checkout_keeps_intended_url(): void
    {
        $package = ServicePackage::query()->create([
            'name' => 'باقة التأسيس',
            'slug' => 'taesis-pack',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'units_count' => 8,
            'session_minutes' => 60,
            'duration_days' => 60,
            'price' => 400,
            'currency' => 'SAR',
            'is_active' => true,
        ]);

        $this->get(route('public.service-packages.checkout', $package))
            ->assertRedirect();

        $this->assertNotEmpty(session('url.intended'));
        $this->assertStringContainsString('/service-packages/'.$package->id.'/checkout', (string) session('url.intended'));
    }

    public function test_demo_placeholder_detection(): void
    {
        $this->assertTrue(PublicDemoContent::isPlaceholder('test'));
        $this->assertTrue(PublicDemoContent::isPlaceholder('TEST'));
        $this->assertTrue(PublicDemoContent::isPlaceholder('تجريبي'));
        $this->assertTrue(PublicDemoContent::isPlaceholder('website'));
        $this->assertTrue(PublicDemoContent::isPlaceholder('test، test'));
        $this->assertFalse(PublicDemoContent::isPlaceholder('أحمد العتيبي'));
        $this->assertFalse(PublicDemoContent::isPlaceholder('رياضيات الصف الثالث'));
    }

    private function makePublicInstructor(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
        InstructorProfile::create([
            'user_id' => $user->id,
            'status' => InstructorProfile::STATUS_APPROVED,
            'headline' => 'رياضيات',
        ]);

        return $user->fresh();
    }

    private function htmlTitle(string $html): string
    {
        preg_match('/<title>(.*?)<\/title>/si', $html, $m);

        return html_entity_decode(trim($m[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
