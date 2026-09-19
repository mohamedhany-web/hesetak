<?php

namespace Database\Seeders;

use App\Models\ReferralProgram;
use App\Models\ServicePackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ReferralProgramSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('referral_programs')) {
            $this->command->warn('جدول referral_programs غير موجود. يرجى تشغيل migrations أولاً.');

            return;
        }

        $defaults = [
            'name' => 'دعوة الأصدقاء — رصيد حصص',
            'description' => 'ادعُ من تحب — عند اشتراكه تحصلان معاً على رصيد حصص خاصة 1:1 (بدون خصم مالي).',
            'reward_mode' => ReferralProgram::REWARD_MODE_CREDITS,
            'credit_scope' => ServicePackage::SCOPE_PRIVATE_LESSONS,
            'referred_credit_units' => 1,
            'referrer_credit_units' => 1,
            'credit_duration_days' => 90,
            'grant_referred_on' => ReferralProgram::GRANT_FIRST_PURCHASE,
            'grant_referrer_on' => ReferralProgram::GRANT_FIRST_PURCHASE,
            'share_message_ar' => 'سجّل في حصتك من رابطي واحصل على رصيد حصص: {link}',
            'share_message_en' => 'Join حصتك with my link and get lesson credits: {link}',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'maximum_discount' => null,
            'minimum_order_amount' => null,
            'discount_valid_days' => 30,
            'referrer_reward_type' => 'fixed',
            'referrer_reward_value' => 0,
            'max_referrals_per_user' => null,
            'max_discount_uses_per_referred' => 1,
            'allow_self_referral' => false,
            'is_active' => true,
            'is_default' => true,
        ];

        $program = ReferralProgram::query()->where('is_default', true)->first()
            ?? ReferralProgram::query()->where('name', 'برنامج الإحالات الافتراضي')->first()
            ?? ReferralProgram::query()->where('name', 'LIKE', '%أخوات%')->first()
            ?? ReferralProgram::query()->where('name', 'LIKE', '%أصدقاء%')->first();

        if ($program) {
            $program->fill($defaults)->save();
            $this->command->info('تم تحديث برنامج الإحالات الافتراضي.');
        } else {
            ReferralProgram::create($defaults);
            $this->command->info('تم إنشاء برنامج دعوة الأصدقاء بنجاح!');
        }
    }
}
