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
