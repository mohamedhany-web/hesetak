<?php

namespace Tests\Feature;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\Order;
use App\Models\PackageGift;
use App\Models\ServicePackage;
use App\Models\ServiceSessionRate;
use App\Models\StudentServiceEntitlement;
use App\Models\User;
use App\Services\PackageCatalogFilterService;
use App\Services\PackageGiftService;
use App\Services\ServiceSessionRateService;
use App\Services\StudentEntitlementService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class PackageCatalogAndGiftTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        Mail::fake();
    }

    public function test_session_rate_quotes_stage_and_track_premium(): void
    {
        $year = AcademicYear::create([
            'name' => 'ثانوي',
            'slug' => 'secondary',
            'is_active' => true,
            'is_public' => true,
            'order' => 1,
        ]);

        ServiceSessionRate::create([
            'academic_year_id' => $year->id,
            'curriculum_type' => 'saudi',
            'price_per_session' => 50,
            'currency' => 'SAR',
            'is_active' => true,
        ]);
        ServiceSessionRate::create([
            'academic_year_id' => $year->id,
            'curriculum_type' => 'us_intl',
            'price_per_session' => 80,
            'currency' => 'SAR',
            'is_active' => true,
        ]);

        $pkg = ServicePackage::create([
            'name' => 'باقة 8',
            'slug' => 'pack-8',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'units_count' => 8,
            'price' => 999,
            'currency' => 'SAR',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $rates = app(ServiceSessionRateService::class);
        $saudi = $rates->quotePackage($pkg, $year->id, 'saudi');
        $intl = $rates->quotePackage($pkg, $year->id, 'us_intl');

        $this->assertSame(50.0, $saudi['unit']);
        $this->assertSame(400.0, $saudi['total']);
        $this->assertSame(80.0, $intl['unit']);
        $this->assertSame(640.0, $intl['total']);
        $this->assertSame(ServiceSessionRateService::SOURCE_YEAR_TRACK, $saudi['source']);
    }

    public function test_catalog_filters_subjects_by_selected_year(): void
    {
        $y1 = AcademicYear::create(['name' => 'ابتدائي', 'slug' => 'pri', 'is_active' => true, 'is_public' => true, 'order' => 1]);
        $y2 = AcademicYear::create(['name' => 'متوسط', 'slug' => 'mid', 'is_active' => true, 'is_public' => true, 'order' => 2]);

        AcademicSubject::create(['name' => 'رياضيات ابتدائي', 'academic_year_id' => $y1->id, 'is_active' => true, 'order' => 1]);
        AcademicSubject::create(['name' => 'علوم متوسط', 'academic_year_id' => $y2->id, 'is_active' => true, 'order' => 1]);
        AcademicSubject::create(['name' => 'إنجليزي عام', 'academic_year_id' => null, 'is_active' => true, 'order' => 2]);

        ServicePackage::create([
            'name' => 'باقة عامة',
            'slug' => 'gen',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => true,
        ]);

        $catalog = app(PackageCatalogFilterService::class)->catalog($y1->id, null, 'saudi');
        $names = $catalog['subjects']->pluck('name')->all();

        $this->assertContains('رياضيات ابتدائي', $names);
        $this->assertContains('إنجليزي عام', $names);
        $this->assertNotContains('علوم متوسط', $names);
        $this->assertTrue($catalog['packages']->isNotEmpty());
    }

    public function test_home_showcase_uses_only_featured_packages(): void
    {
        $featured = ServicePackage::create([
            'name' => 'باقة مميزة',
            'slug' => 'feat-home',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'units_count' => 8,
            'price' => 400,
            'currency' => 'SAR',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);
        ServicePackage::create([
            'name' => 'باقة عادية',
            'slug' => 'plain-home',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 2,
        ]);

        $home = app(PackageCatalogFilterService::class)->homeShowcase();
        $names = $home->pluck('name')->all();

        $this->assertContains('باقة مميزة', $names);
        $this->assertNotContains('باقة عادية', $names);
        $this->assertTrue($home->contains(fn ($row) => (int) $row['id'] === (int) $featured->id));
    }

    public function test_catalog_filters_follow_admin_year_subject_and_track(): void
    {
        $primary = AcademicYear::create(['name' => 'ابتدائية', 'slug' => 'pri', 'is_active' => true, 'is_public' => true, 'order' => 1]);
        $middle = AcademicYear::create(['name' => 'متوسط', 'slug' => 'mid', 'is_active' => true, 'is_public' => true, 'order' => 2]);
        $math = AcademicSubject::create(['name' => 'رياضيات', 'academic_year_id' => $primary->id, 'is_active' => true, 'order' => 1]);

        $general = ServicePackage::create([
            'name' => 'عامة',
            'slug' => 'filter-gen',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => true,
        ]);
        $primaryOnly = ServicePackage::create([
            'name' => 'ابتدائي فقط',
            'slug' => 'filter-pri',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'academic_year_id' => $primary->id,
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => true,
        ]);
        $primaryMath = ServicePackage::create([
            'name' => 'ابتدائي رياضيات',
            'slug' => 'filter-pri-math',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'academic_year_id' => $primary->id,
            'academic_subject_id' => $math->id,
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => true,
        ]);
        $middleOnly = ServicePackage::create([
            'name' => 'متوسط فقط',
            'slug' => 'filter-mid',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'academic_year_id' => $middle->id,
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => true,
        ]);
        $saudiOnly = ServicePackage::create([
            'name' => 'سعودي فقط',
            'slug' => 'filter-saudi',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'curriculum_type' => 'saudi',
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => true,
        ]);
        $inactive = ServicePackage::create([
            'name' => 'موقوفة',
            'slug' => 'filter-off',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => false,
            'is_featured' => true,
        ]);

        $service = app(PackageCatalogFilterService::class);

        $byPrimary = $service->catalog(
            yearId: $primary->id,
            applyViewerDefaults: false,
        );
        $this->assertEqualsCanonicalizing(
            ['عامة', 'ابتدائي فقط', 'ابتدائي رياضيات', 'سعودي فقط'],
            $byPrimary['packages']->pluck('name')->all()
        );

        $byPrimaryMath = $service->catalog(
            yearId: $primary->id,
            subjectId: $math->id,
            applyViewerDefaults: false,
        );
        $this->assertEqualsCanonicalizing(
            ['عامة', 'ابتدائي فقط', 'ابتدائي رياضيات', 'سعودي فقط'],
            $byPrimaryMath['packages']->pluck('name')->all()
        );

        $byMiddle = $service->catalog(
            yearId: $middle->id,
            applyViewerDefaults: false,
        );
        $this->assertEqualsCanonicalizing(
            ['عامة', 'متوسط فقط', 'سعودي فقط'],
            $byMiddle['packages']->pluck('name')->all()
        );

        $byEgyptian = $service->catalog(
            curriculumType: 'egyptian',
            applyViewerDefaults: false,
        );
        $this->assertContains('عامة', $byEgyptian['packages']->pluck('name')->all());
        $this->assertNotContains('سعودي فقط', $byEgyptian['packages']->pluck('name')->all());
        $this->assertNotContains('موقوفة', $byEgyptian['packages']->pluck('name')->all());

        $this->assertContains($general->name, $service->catalog(applyViewerDefaults: false)['packages']->pluck('name'));
        $this->assertNotContains($inactive->name, $service->homeShowcase()->pluck('name'));
        $this->assertNotContains($middleOnly->name, $byPrimary['packages']->pluck('name'));
        $this->assertContains($primaryMath->name, $byPrimary['packages']->pluck('name'));
        $this->assertContains($primaryOnly->id, $byPrimary['packages']->pluck('id'));
    }

    public function test_admin_can_save_package_filters_and_they_hit_the_storefront(): void
    {
        $year = AcademicYear::create(['name' => 'ابتدائية', 'slug' => 'pri-admin', 'is_active' => true, 'is_public' => true, 'order' => 1]);
        $subject = AcademicSubject::create(['name' => 'علوم', 'academic_year_id' => $year->id, 'is_active' => true, 'order' => 1]);
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.service-packages.store'), [
                'name' => 'باقة فلتر الإدارة',
                'slug' => 'admin-filter-pkg',
                'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
                'academic_year_id' => $year->id,
                'academic_subject_id' => $subject->id,
                'curriculum_type' => 'saudi',
                'units_count' => 6,
                'session_minutes' => 60,
                'price' => 300,
                'currency' => 'SAR',
                'is_active' => 1,
                'is_featured' => 1,
                'sort_order' => 1,
                'tagline' => 'من الإدارة',
                'badge' => 'الأكثر مبيعًا',
                'features_text' => "حصة فردية\nتقرير ولي أمر",
            ])
            ->assertRedirect(route('admin.service-packages.index'));

        $this->assertDatabaseHas('service_packages', [
            'slug' => 'admin-filter-pkg',
            'academic_year_id' => $year->id,
            'academic_subject_id' => $subject->id,
            'curriculum_type' => 'saudi',
            'is_active' => 1,
            'is_featured' => 1,
        ]);

        $this->get(route('public.pricing', ['year' => $year->id, 'subject' => $subject->id, 'curriculum_type' => 'saudi']))
            ->assertOk()
            ->assertSee('باقة فلتر الإدارة', false)
            ->assertSee('الأكثر مبيعًا', false);

        $otherYear = AcademicYear::create(['name' => 'ثانوي', 'slug' => 'sec-admin', 'is_active' => true, 'is_public' => true, 'order' => 2]);
        $this->get(route('public.pricing', ['year' => $otherYear->id, 'curriculum_type' => 'egyptian']))
            ->assertOk()
            ->assertDontSee('باقة فلتر الإدارة', false);

        $home = app(PackageCatalogFilterService::class)->homeShowcase();
        $this->assertTrue($home->contains(fn ($row) => $row['name'] === 'باقة فلتر الإدارة'));
    }

    public function test_gift_fulfillment_creates_recipient_and_moves_entitlement(): void
    {
        $buyer = User::factory()->create([
            'role' => 'student',
            'email' => 'buyer@example.com',
            'password' => Hash::make('password'),
        ]);

        $pkg = ServicePackage::create([
            'name' => 'هدية 4',
            'slug' => 'gift-4',
            'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'units_count' => 4,
            'price' => 200,
            'currency' => 'SAR',
            'is_active' => true,
            'duration_days' => 90,
        ]);

        ServiceSessionRate::create([
            'academic_year_id' => null,
            'curriculum_type' => 'saudi',
            'price_per_session' => 40,
            'currency' => 'SAR',
            'is_active' => true,
        ]);

        $created = app(PackageGiftService::class)->createPendingGiftOrder($buyer, $pkg, [
            'recipient_email' => 'friend@example.com',
            'recipient_name' => 'صديق',
            'curriculum_type' => 'saudi',
            'message' => 'بالتوفيق',
        ]);

        /** @var Order $order */
        $order = $created['order'];
        $this->assertSame(160.0, (float) $order->amount);
        $this->assertTrue((bool) ($order->custom_package_data['is_gift'] ?? false));

        $order->update(['status' => Order::STATUS_APPROVED]);
        // Simulate paid fulfillment path
        StudentEntitlementService::grantFromOrder($order->fresh());

        $gift = PackageGift::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($gift);
        $this->assertSame(PackageGift::STATUS_GRANTED, $gift->status);

        $recipient = User::query()->where('email', 'friend@example.com')->first();
        $this->assertNotNull($recipient);

        $entitlement = StudentServiceEntitlement::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($entitlement);
        $this->assertSame((int) $recipient->id, (int) $entitlement->user_id);
        $this->assertSame(4, (int) $entitlement->units_total);
    }
}
