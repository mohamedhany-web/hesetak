<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\AcademicSubject;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('academic_years')) {
            $this->command->warn('⚠️  جدول academic_years غير موجود. يرجى تشغيل migrations أولاً.');

            return;
        }

        $years = [
            [
                'name' => 'مسار التمهيد للتدريس أونلاين',
                'code' => 'TCH-L1',
                'description' => 'المستوى الأول: أساسيات الحصة الرقمية والتفاعل مع المتعلمين',
                'icon' => 'fa-chalkboard-teacher',
                'color' => '#3B82F6',
                'order' => 1,
                'is_active' => true,
                'subjects' => [
                    ['name' => 'الحصة الرقمية والأدوات الأساسية', 'code' => 'TCH101', 'icon' => 'fa-laptop-house', 'color' => '#10B981', 'order' => 1],
                    ['name' => 'التخطيط والأهداف التعليمية', 'code' => 'TCH102', 'icon' => 'fa-bullseye', 'color' => '#F59E0B', 'order' => 2],
                    ['name' => 'التقييم والتغذية الراجعة', 'code' => 'TCH103', 'icon' => 'fa-clipboard-check', 'color' => '#8B5CF6', 'order' => 3],
                ],
            ],
            [
                'name' => 'مسار المهارات الرقمية للمعلم',
                'code' => 'TCH-L2',
                'description' => 'المستوى الثاني: دمج أدوات رقمية آمنة وفعّالة في التعليم',
                'icon' => 'fa-laptop-code',
                'color' => '#10B981',
                'order' => 2,
                'is_active' => true,
                'subjects' => [
                    ['name' => 'أدوات التواصل والتعاون الصفي', 'code' => 'TCH201', 'icon' => 'fa-users', 'color' => '#EF4444', 'order' => 1],
                    ['name' => 'الخصوصية والسلوك الرقمي في المدرسة', 'code' => 'TCH202', 'icon' => 'fa-shield-halved', 'color' => '#06B6D4', 'order' => 2],
                ],
            ],
            [
                'name' => 'مسار التطوير المهني للمدرّب',
                'code' => 'TCH-L3',
                'description' => 'المستوى الثالث: بناء حضور مهني وتقديم برامج تدريبية أونلاين',
                'icon' => 'fa-user-tie',
                'color' => '#8B5CF6',
                'order' => 3,
                'is_active' => true,
                'subjects' => [
                    ['name' => 'تصميم البرنامج التدريبي', 'code' => 'TCH301', 'icon' => 'fa-diagram-project', 'color' => '#EC4899', 'order' => 1],
                    ['name' => 'التواصل مع المتعلمين وخدمة ما بعد التدريب', 'code' => 'TCH302', 'icon' => 'fa-comments', 'color' => '#F59E0B', 'order' => 2],
                ],
            ],
        ];

        foreach ($years as $yearData) {
            $subjects = $yearData['subjects'] ?? [];
            unset($yearData['subjects']);

            $year = AcademicYear::firstOrCreate(
                ['code' => $yearData['code']],
                $yearData
            );

            foreach ($subjects as $subjectData) {
                AcademicSubject::firstOrCreate(
                    [
                        'code' => $subjectData['code'],
                        'academic_year_id' => $year->id,
                    ],
                    array_merge($subjectData, [
                        'academic_year_id' => $year->id,
                        'is_active' => true,
                    ])
                );
            }
        }

        $this->command->info('تم إنشاء '.count($years).' مسارات تعليمية (حصتك) بنجاح');
    }
}
