<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\ReferralProgram;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class ReferralProgramSafetyTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->buildReferralSchema();
    }

    private function buildReferralSchema(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'referred_by')) {
                $table->unsignedBigInteger('referred_by')->nullable();
            }
            if (! Schema::hasColumn('users', 'referred_at')) {
                $table->timestamp('referred_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'total_referrals')) {
                $table->unsignedInteger('total_referrals')->default(0);
            }
            if (! Schema::hasColumn('users', 'completed_referrals')) {
                $table->unsignedInteger('completed_referrals')->default(0);
            }
        });

        Schema::create('referral_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('reward_mode')->default('credits');
            $table->string('credit_scope')->nullable();
            $table->unsignedInteger('referred_credit_units')->default(1);
            $table->unsignedInteger('referrer_credit_units')->default(1);
            $table->unsignedInteger('credit_duration_days')->nullable();
            $table->string('grant_referred_on')->nullable();
            $table->string('grant_referrer_on')->nullable();
            $table->text('share_message_ar')->nullable();
            $table->text('share_message_en')->nullable();
            $table->string('discount_type')->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('maximum_discount', 10, 2)->nullable();
            $table->decimal('minimum_order_amount', 10, 2)->nullable();
            $table->string('referrer_reward_type')->nullable();
            $table->decimal('referrer_reward_value', 10, 2)->nullable();
            $table->unsignedInteger('discount_valid_days')->default(30);
            $table->unsignedInteger('referral_code_valid_days')->nullable();
            $table->unsignedInteger('max_referrals_per_user')->nullable();
            $table->unsignedInteger('max_discount_uses_per_referred')->default(1);
            $table->boolean('allow_self_referral')->default(false);
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_program_id')->nullable();
            $table->foreignId('referrer_id');
            $table->foreignId('referred_id');
            $table->string('code')->nullable();
            $table->string('referral_code')->nullable();
            $table->string('device_fingerprint', 64)->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('auto_coupon_id')->nullable();
            $table->unsignedBigInteger('referred_entitlement_id')->nullable();
            $table->unsignedBigInteger('referrer_entitlement_id')->nullable();
            $table->unsignedInteger('referred_units_granted')->default(0);
            $table->unsignedInteger('referrer_units_granted')->default(0);
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->unsignedInteger('discount_used_count')->default(0);
            $table->timestamp('discount_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('reward_amount', 10, 2)->nullable();
            $table->unsignedInteger('reward_points')->default(0);
            $table->timestamps();
        });
    }

    private function makeProgram(array $overrides = []): ReferralProgram
    {
        return ReferralProgram::create(array_merge([
            'name' => 'اختبار إحالات',
            'reward_mode' => ReferralProgram::REWARD_MODE_CREDITS,
            'credit_scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'referred_credit_units' => 1,
            'referrer_credit_units' => 1,
            'grant_referred_on' => ReferralProgram::GRANT_FIRST_PURCHASE,
            'grant_referrer_on' => ReferralProgram::GRANT_FIRST_PURCHASE,
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'max_referrals_per_user' => 10,
            'allow_self_referral' => true,
            'is_active' => true,
            'is_default' => true,
        ], $overrides));
    }

    public function test_admin_save_defaults_max_to_ten_and_blocks_self_flag(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.referral-programs.store'), [
                'name' => 'برنامج آمن',
                'reward_mode' => 'credits',
                'referred_credit_units' => 1,
                'referrer_credit_units' => 1,
                'grant_referred_on' => 'first_purchase',
                'grant_referrer_on' => 'first_purchase',
                'is_active' => '1',
                'confirm_no_expiry' => '1',
                'allow_self_referral' => '1',
            ])
            ->assertRedirect(route('admin.referral-programs.index'));

        $program = ReferralProgram::query()->where('name', 'برنامج آمن')->first();
        $this->assertNotNull($program);
        $this->assertSame(10, (int) $program->max_referrals_per_user);
        $this->assertFalse((bool) $program->allow_self_referral);
        $this->assertNotSame('', (string) $program->share_message_en);
    }

    public function test_admin_requires_confirm_when_active_without_expiry(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.referral-programs.store'), [
                'name' => 'بلا انتهاء',
                'reward_mode' => 'credits',
                'is_active' => '1',
                'max_referrals_per_user' => 5,
            ])
            ->assertSessionHasErrors(['expires_at']);
    }

    public function test_process_referral_blocks_self_same_phone_and_device(): void
    {
        $this->makeProgram();
        $service = app(ReferralService::class);

        $referrer = User::factory()->create([
            'role' => 'student',
            'phone' => '+966500000001',
            'referral_code' => 'REFTESTCODE01',
        ]);
        $samePhone = User::factory()->create([
            'role' => 'student',
            'phone' => '966500000001',
        ]);
        $this->assertNull($service->processReferral($referrer, $samePhone, 'REFTESTCODE01'));

        $other = User::factory()->create([
            'role' => 'student',
            'phone' => '+966500000099',
        ]);
        $ok = $service->processReferral($referrer, $other, 'REFTESTCODE01', 'device-abc-12345678');
        $this->assertInstanceOf(Referral::class, $ok);

        $third = User::factory()->create([
            'role' => 'student',
            'phone' => '+966500000088',
        ]);
        $this->assertNull($service->processReferral($referrer, $third, 'REFTESTCODE01', 'device-abc-12345678'));
    }

    public function test_process_referral_respects_max_cap(): void
    {
        $program = $this->makeProgram(['max_referrals_per_user' => 1]);
        $service = app(ReferralService::class);
        $referrer = User::factory()->create([
            'role' => 'student',
            'phone' => '+966511111111',
            'referral_code' => 'REFCAP001',
        ]);
        $a = User::factory()->create(['role' => 'student', 'phone' => '+966511111112']);
        $b = User::factory()->create(['role' => 'student', 'phone' => '+966511111113']);

        $this->assertInstanceOf(Referral::class, $service->processReferral($referrer, $a, 'REFCAP001', 'dev-1-xxxxxx'));
        $this->assertNull($service->processReferral($referrer, $b, 'REFCAP001', 'dev-2-xxxxxx'));
        $this->assertSame(1, $program->fresh()->referrals()->count());
    }

    public function test_rules_summary_mentions_limit(): void
    {
        $program = $this->makeProgram(['max_referrals_per_user' => 10]);
        $this->assertStringContainsString('حد 10', $program->rulesSummary());
        $this->assertStringContainsString('إحالة ذاتية ممنوعة', $program->rulesSummary());
    }
}
