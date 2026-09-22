<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('academic_years')) {
            $this->command->warn('⚠️  جدول academic_years غير موجود. يرجى تشغيل migrations أولاً.');

            return;
        }

        // Hesetak public catalog uses Gulf/Egypt/US school years from HesetakPublicDemoSeeder
        // (and admin CRUD). Legacy TCH-L1/L2/L3 teacher-PD tracks are intentionally NOT seeded.

        /*
        // Quarantined — do not re-enable without product decision:
        $years = [
            ['code' => 'TCH-L1', ...],
            ['code' => 'TCH-L2', ...],
            ['code' => 'TCH-L3', ...],
        ];
        */

        $this->command->info('AcademicYearSeeder: skipped TCH-* tracks (quarantined for Hesetak).');
    }
};
