<?php

namespace Database\Seeders;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\CourseCategory;
use App\Models\InstructorProfile;
use App\Models\ServicePackage;
use App\Models\SiteTestimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * بيانات عرض عامة لحصتك: معلمون، مواد، كورسات، آراء، وتفعيل باقات فردية.
 * تشغيل: php artisan db:seed --class=HesetakPublicDemoSeeder
 */
class HesetakPublicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GlotticalAcademyUserSeeder::class,
            AcademicYearSeeder::class,
            SubjectsSeeder::class,
        ]);

        $this->seedPublicSchoolStages();
        $this->seedInstructorProfiles();
        $this->activatePrivatePackages();
        $this->seedTestimonials();
        $this->seedCourses();

        $this->command?->info('✅ HesetakPublicDemoSeeder اكتمل — الصفحة العامة جاهزة للعرض.');
    }

    private function seedInstructorProfiles(): void
    {
        if (! Schema::hasTable('instructor_profiles')) {
            return;
        }

        $profiles = [
            'instructor1@hesetak.com' => [
                'headline' => 'رياضيات، منهج سعودي',
                'bio' => 'معلم رياضيات معتمد لحصص فردية أونلاين، مع تركيز على تأسيس المرحلة المتوسطة والثانوية.',
                'experience' => "12 سنة تدريس خصوصي أونلاين\nاعتماد أكاديمي في الرياضيات\nتقارير أسبوعية لأولياء الأمور",
                'skills' => 'رياضيات، جبر، حساب، تأسيس',
                'curriculum_types' => ['saudi', 'emirati'],
                'years' => ['HSK-MID', 'HSK-SEC'],
            ],
            'instructor2@hesetak.com' => [
                'headline' => 'لغة عربية، نحو وصرف وقراءة',
                'bio' => 'معلمة لغة عربية للمنهج المصري والسعودي، مع تركيز على التعبير والقراءة.',
                'experience' => "10 سنوات في تدريس العربية\nخبرة مناهج مصرية وسعودية\nتحضير اختبارات مدرسية",
                'skills' => 'لغة عربية، نحو، إملاء، تعبير',
                'curriculum_types' => ['saudi', 'egyptian'],
                'years' => ['HSK-PRI', 'HSK-MID'],
            ],
            'instructor3@hesetak.com' => [
                'headline' => 'علوم، فيزياء وكيمياء',
                'bio' => 'معلم علوم للمرحلة الثانوية بمنهج سعودي ومسار دولي عند الحاجة.',
                'experience' => "8 سنوات تدريس علوم\nشرح عملي بتجارب مبسطة\nمتابعة واجبات وتقارير",
                'skills' => 'علوم، فيزياء، كيمياء',
                'curriculum_types' => ['saudi', 'us_intl'],
                'years' => ['HSK-SEC'],
            ],
            'instructor4@hesetak.com' => [
                'headline' => 'إنجليزي، محادثة ومنهج مدرسي',
                'bio' => 'معلمة إنجليزي لحصص فردية، تدعم المنهج المدرسي والمحادثة.',
                'experience' => "9 سنوات تدريس إنجليزي\nتركيز على المحادثة والقراءة\nمرونة في المواعيد",
                'skills' => 'إنجليزي، محادثة، قراءة',
                'curriculum_types' => ['us_intl', 'saudi'],
                'years' => ['HSK-PRI', 'HSK-MID', 'HSK-UNI'],
            ],
        ];

        foreach ($profiles as $email => $data) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                continue;
            }

            $yearCodes = $data['years'] ?? [];
            $types = $data['curriculum_types'] ?? [];
            unset($data['years'], $data['curriculum_types']);

            InstructorProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge($data, [
                    'status' => InstructorProfile::STATUS_APPROVED,
                    'submitted_at' => now(),
                    'reviewed_at' => now(),
                    'curriculum_types' => $types,
                ])
            );

            if (Schema::hasTable('academic_year_instructors') && $yearCodes !== []) {
                $yearIds = AcademicYear::query()->whereIn('code', $yearCodes)->pluck('id')->all();
                $user->teachingLearningPaths()->syncWithoutDetaching($yearIds);
            }
        }
    }

    private function seedPublicSchoolStages(): void
    {
        if (! Schema::hasTable('academic_years') || ! Schema::hasTable('academic_subjects')) {
            return;
        }

        AcademicYear::query()->where('code', 'like', 'TCH-%')->update(['is_public' => false]);
        AcademicYear::query()->where('code', 'HESETAK-GULF')->update(['is_public' => false]);

        $coreSubjects = [
            ['name' => 'رياضيات', 'code_suffix' => 'MATH', 'order' => 1],
            ['name' => 'لغة عربية', 'code_suffix' => 'AR', 'order' => 2],
            ['name' => 'لغة إنجليزية', 'code_suffix' => 'EN', 'order' => 3],
            ['name' => 'علوم', 'code_suffix' => 'SCI', 'order' => 4],
            ['name' => 'دراسات إسلامية', 'code_suffix' => 'ISL', 'order' => 5],
            ['name' => 'قرآن وتجويد', 'code_suffix' => 'QUR', 'order' => 6],
        ];

        $stages = [
            [
                'code' => 'HSK-PRI',
                'name' => 'المرحلة الابتدائية',
                'slug' => 'primary',
                'tagline' => 'تأسيس القراءة والحساب والمواد الأساسية',
                'description' => 'متابعة منهج المرحلة الابتدائية عبر حصص فردية أونلاين: مرحلة + مادة + معلم مطابق.',
                'level_number' => 1,
                'order' => 10,
                'subjects' => $coreSubjects,
            ],
            [
                'code' => 'HSK-MID',
                'name' => 'المرحلة المتوسطة',
                'slug' => 'middle',
                'tagline' => 'تعميق المواد وتثبيت الأساسيات',
                'description' => 'حصص فردية لمواد المتوسط مع مطابقة نوع المنهج عند التوفر.',
                'level_number' => 2,
                'order' => 20,
                'subjects' => $coreSubjects,
            ],
            [
                'code' => 'HSK-SEC',
                'name' => 'المرحلة الثانوية',
                'slug' => 'secondary',
                'tagline' => 'ثانوي علمي وأدبي ومتابعة الاختبارات',
                'description' => 'رياضيات، فيزياء، كيمياء، أحياء، عربي، وإنجليزي للثانوية.',
                'level_number' => 3,
                'order' => 30,
                'subjects' => array_merge($coreSubjects, [
                    ['name' => 'فيزياء', 'code_suffix' => 'PHY', 'order' => 7],
                    ['name' => 'كيمياء', 'code_suffix' => 'CHE', 'order' => 8],
                    ['name' => 'أحياء', 'code_suffix' => 'BIO', 'order' => 9],
                ]),
            ],
            [
                'code' => 'HSK-UNI',
                'name' => 'المرحلة الجامعية',
                'slug' => 'university',
                'tagline' => 'مقررات جامعية وتأسيس',
                'description' => 'دعم مقررات جامعية وتأسيس حسب المادة المطلوبة.',
                'level_number' => 4,
                'order' => 40,
                'subjects' => [
                    ['name' => 'رياضيات', 'code_suffix' => 'MATH', 'order' => 1],
                    ['name' => 'لغة إنجليزية', 'code_suffix' => 'EN', 'order' => 2],
                    ['name' => 'حاسب آلي', 'code_suffix' => 'CS', 'order' => 3],
                    ['name' => 'مواد جامعية عامة', 'code_suffix' => 'GEN', 'order' => 4],
                ],
            ],
        ];

        foreach ($stages as $stage) {
            $subjects = $stage['subjects'];
            unset($stage['subjects']);

            $year = AcademicYear::query()->updateOrCreate(
                ['code' => $stage['code']],
                array_merge($stage, [
                    'is_active' => true,
                    'is_public' => true,
                    'icon' => 'fas fa-book-open',
                    'color' => '#1E4E8C',
                ])
            );

            foreach ($subjects as $row) {
                $code = $stage['code'].'-'.$row['code_suffix'];
                AcademicSubject::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'academic_year_id' => $year->id,
                        'name' => $row['name'],
                        'slug' => Str::slug($row['name']).'-'.Str::lower($row['code_suffix']).'-'.$year->id,
                        'is_active' => true,
                        'order' => $row['order'],
                        'icon' => 'fas fa-book',
                        'color' => '#1E4E8C',
                        'description' => 'مادة ضمن '.$year->name.' كما تُدار من لوحة الأدمن.',
                    ]
                );
            }
        }
    }

    private function activatePrivatePackages(): void
    {
        if (! Schema::hasTable('service_packages')) {
            return;
        }

        ServicePackage::query()
            ->where('plan_type', ServicePackage::PLAN_PRIVATE)
            ->update(['is_active' => true]);

        ServicePackage::query()
            ->where('plan_type', ServicePackage::PLAN_PRIVATE)
            ->where('units_count', 24)
            ->update([
                'is_featured' => true,
                'badge' => 'الأكثر اختياراً',
                'tagline' => 'حصص فردية مرنة مع معلم معتمد',
            ]);

        ServicePackage::query()
            ->whereIn('name', ['باقة تجريبية', 'باقة أساسية', 'باقة مكثفة'])
            ->update(['is_active' => true, 'scope' => ServicePackage::SCOPE_PRIVATE_LESSONS]);

        ServicePackage::query()
            ->where('name', 'باقة أساسية')
            ->update(['is_featured' => true, 'tagline' => 'توازن بين السعر وعدد الحصص']);
    }

    private function seedTestimonials(): void
    {
        if (! Schema::hasTable('site_testimonials')) {
            return;
        }

        $items = [
            [
                'body' => 'بعد أسبوعين من الحصص الفردية تحسّن مستوى ابني في الرياضيات بشكل واضح، والمعلمة ترسل تقريرًا بعد كل حصة.',
                'author_name' => 'أمل الحربي',
                'role_label' => 'ولية أمر، الرياض',
                'sort_order' => 1,
                'is_featured' => true,
            ],
            [
                'body' => 'حجز الحصة سهل والتوقيت حسب جدولنا. المعلم ملتزم ويشرح المنهج الإماراتي بنفس أسلوب المدرسة.',
                'author_name' => 'خالد المنصوري',
                'role_label' => 'ولي أمر، دبي',
                'sort_order' => 2,
                'is_featured' => true,
            ],
            [
                'body' => 'جرّبنا باقة الحصص الفردية للإنجليزي؛ ابنتنا صارت تتكلم بثقة أكبر في الصف.',
                'author_name' => 'سارة العتيبي',
                'role_label' => 'ولية أمر، جدة',
                'sort_order' => 3,
                'is_featured' => false,
            ],
            [
                'body' => 'منصة واضحة، معلمون معتمدون، والدفع بالمحفظة وفّر علينا متابعة الفواتير.',
                'author_name' => 'فهد الشمري',
                'role_label' => 'ولي أمر، الكويت',
                'sort_order' => 4,
                'is_featured' => false,
            ],
        ];

        foreach ($items as $item) {
            SiteTestimonial::query()->updateOrCreate(
                [
                    'author_name' => $item['author_name'],
                    'body' => $item['body'],
                ],
                [
                    'content_type' => SiteTestimonial::CONTENT_TEXT,
                    'role_label' => $item['role_label'],
                    'sort_order' => $item['sort_order'],
                    'is_featured' => $item['is_featured'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedCourses(): void
    {
        if (! Schema::hasTable('advanced_courses')) {
            return;
        }

        $instructor = User::query()
            ->where('role', 'instructor')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $instructor) {
            $this->command?->warn('لا يوجد معلم لربط الكورسات.');

            return;
        }

        $ensureCat = function (string $name, int $order) {
            return CourseCategory::query()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $order, 'is_active' => true]
            );
        };

        $cats = [
            'math' => $ensureCat('رياضيات', 1),
            'ar' => $ensureCat('لغة عربية', 2),
            'en' => $ensureCat('لغة إنجليزية', 3),
            'sci' => $ensureCat('علوم', 4),
            'quran' => $ensureCat('قرآن ومهارات', 5),
        ];

        $courses = [
            [
                'title' => 'تأسيس رياضيات للمتوسط',
                'description' => 'مسار فردي لتقوية الجبر والهندسة وفق المناهج الخليجية.',
                'category_id' => $cats['math']->id,
                'price' => 249,
                'is_featured' => true,
                'level' => 'intermediate',
            ],
            [
                'title' => 'لغة عربية نحو وصرف',
                'description' => 'حصص فردية لتحسين النحو والإملاء والتعبير للمرحلة المتوسطة.',
                'category_id' => $cats['ar']->id,
                'price' => 199,
                'is_featured' => true,
                'level' => 'intermediate',
            ],
            [
                'title' => 'إنجليزي محادثة للمدرسة',
                'description' => 'تدريب نطق واستماع ومحادثة مرتبطة بالمنهج المدرسي.',
                'category_id' => $cats['en']->id,
                'price' => 229,
                'is_featured' => true,
                'level' => 'beginner',
            ],
            [
                'title' => 'علوم المرحلة الثانوية',
                'description' => 'شرح مفاهيم الفيزياء والكيمياء بأمثلة وتمارين محلولة.',
                'category_id' => $cats['sci']->id,
                'price' => 279,
                'is_featured' => false,
                'level' => 'advanced',
            ],
            [
                'title' => 'تحفيظ قرآن للأطفال',
                'description' => 'جلسات فردية قصيرة مع متابعة أسبوعية لولي الأمر.',
                'category_id' => $cats['quran']->id,
                'price' => 159,
                'is_featured' => false,
                'level' => 'beginner',
            ],
            [
                'title' => 'مراجعة اختبارات دولية (IELTS Prep)',
                'description' => 'تحضير مهارات القراءة والكتابة والاستماع للاختبارات الدولية.',
                'category_id' => $cats['en']->id,
                'price' => 349,
                'is_featured' => true,
                'level' => 'advanced',
            ],
        ];

        foreach ($courses as $row) {
            AdvancedCourse::query()->updateOrCreate(
                ['title' => $row['title']],
                [
                    'instructor_id' => $instructor->id,
                    'description' => $row['description'],
                    'objectives' => 'أهداف قابلة للقياس ضمن مسار الكورس.',
                    'level' => $row['level'],
                    'duration_hours' => 20,
                    'duration_minutes' => 1200,
                    'price' => $row['price'],
                    'is_free' => false,
                    'is_featured' => $row['is_featured'],
                    'is_active' => true,
                    'course_category_id' => $row['category_id'],
                    'category' => 'مستقل',
                    'language' => 'ar',
                    'requirements' => 'اتصال إنترنت وجهاز مناسب للحصة المباشرة.',
                    'what_you_learn' => 'مهارات عملية ومتابعة تقدم واضحة.',
                ]
            );
        }
    }
}
