<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * مستخدمون تجريبيون للتطوير (بدون أسماء تجارية قديمة).
 */
class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('users')) {
            $this->command->warn('⚠️  جدول users غير موجود. يرجى تشغيل migrations أولاً.');

            return;
        }

        User::firstOrCreate(
            ['phone' => '0500000000'],
            [
                'name' => 'المدير العام',
                'email' => 'admin@hesetak.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['phone' => '0500000001'],
            [
                'name' => 'أحمد المدرب',
                'email' => 'instructor@hesetak.com',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'is_active' => true,
                'bio' => 'مدرّب معتمد في التعليم أونلاين وتطوير المعلمين، مع خبرة في تصميم البرامج التدريبية',
            ]
        );

        User::firstOrCreate(
            ['phone' => '0500000002'],
            [
                'name' => 'فاطمة الطالبة',
                'email' => 'student@hesetak.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ تم إنشاء المستخدمين التجريبيين.');
        $this->command->info('   admin@hesetak.com / instructor@hesetak.com / student@hesetak.com — كلمة المرور: password123');
    }
}
