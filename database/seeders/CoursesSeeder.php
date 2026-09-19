<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\AdvancedCourse;
use App\Models\AcademicYear;
use App\Models\AcademicSubject;

ini_set('memory_limit', '1024M');

/**
 * كورسات تجريبية متسقة مع منصة حصتك (تأهيل المعلمين والتدريس أونلاين).
 */
class CoursesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('advanced_courses')) {
            $this->command->warn('⚠️  جدول advanced_courses غير موجود. يرجى تشغيل migrations أولاً.');

            return;
        }

        echo "\n📚 إضافة كورسات تجريبية (حصتك)...\n";
        echo '='.str_repeat('=', 60)."\n";

        $instructor = User::where('role', 'instructor')->where('is_active', true)->first()
            ?? User::where('role', 'teacher')->where('is_active', true)->first()
            ?? User::where('role', 'admin')->where('is_active', true)->first()
            ?? User::first();

        $instructorId = $instructor->id ?? null;

        $academicYear = AcademicYear::where('is_active', true)->first();
        $academicSubject = AcademicSubject::where('is_active', true)->first();

        $courses = [
            [
                'title' => 'التدريس الفعّال في الصف الرقمي',
                'description' => 'مبادئ تخطيط الحصة أونلاين، إدارة الوقت، والتفاعل مع الطلاب عبر أدوات المنصة مع الحفاظ على جودة التعلّم.',
                'objectives' => 'فهم خصائص التعلّم الرقمي، تطبيق استراتيجيات تفاعلية، ضبط إيقاع الحصة',
                'level' => 'beginner',
                'duration_hours' => 24,
                'price' => 0,
                'is_free' => true,
                'is_featured' => true,
                'category' => 'تأهيل معلمين',
                'requirements' => 'لا توجد متطلبات مسبقة',
                'what_you_learn' => 'تهيئة بيئة الحصة الرقمية، صياغة أهداف واضحة، أنماط تفاعل مناسبة للمرحلة',
            ],
            [
                'title' => 'تخطيط الحصص وأهداف التعلّم',
                'description' => 'كيفية كتابة أهداف قابلة للقياس، اختيار الأنشطة، وربط التقييم بالهدف في بيئة تعليمية عربية.',
                'objectives' => 'صياغة أهداف SMART، تصميم مسار الحصة، ربط الأنشطة بالنتائج المتوقعة',
                'level' => 'beginner',
                'duration_hours' => 18,
                'price' => 249,
                'is_free' => false,
                'is_featured' => true,
                'category' => 'تأهيل معلمين',
                'requirements' => 'خبرة أساسية في التدريس أو الرغبة في العمل كمدرّب',
                'what_you_learn' => 'نماذج تخطيط، خرائط حصة، أدوات تقييم تكويني',
            ],
            [
                'title' => 'التقييم الصفي والتغذية الراجعة',
                'description' => 'أساليب التقييم أثناء الحصة، التغذية الراجعة البناءة، ومتابعة تقدّم المتعلّم دون إرهاقه.',
                'objectives' => 'تنويع أدوات التقييم، صياغة تغذية راجعة فعّالة، استخدام rubrics بسيطة',
                'level' => 'intermediate',
                'duration_hours' => 20,
                'price' => 299,
                'is_free' => false,
                'is_featured' => false,
                'category' => 'تأهيل معلمين',
                'requirements' => 'إتمام مقدمة في التدريس الرقمي أو خبرة صفية',
                'what_you_learn' => 'تقييم تكويني وختامي، أسئلة فورية، تقارير بسيطة للطالب',
            ],
            [
                'title' => 'إدارة الصف الافتراضي والتفاعل',
                'description' => 'قواعد الانضباط الرقمي، إشراك الطلاب الصامتين، إدارة النقاشات والعمل الجماعي عن بُعد.',
                'objectives' => 'بناء بروتوكول صف افتراضي، تقنيات مشاركة فعّالة، التعامل مع الإزعاج الرقمي',
                'level' => 'intermediate',
                'duration_hours' => 22,
                'price' => 329,
                'is_free' => false,
                'is_featured' => true,
                'category' => 'تأهيل معلمين',
                'requirements' => 'تجربة عملية أو قريبة من التدريس أونلاين',
                'what_you_learn' => 'جلسات تفاعلية، توزيع الأدوار، دعم الطلاب ذوي الاحتياجات المختلفة',
            ],
            [
                'title' => 'بناء حضورك المهني كمدرّب على الإنترنت',
                'description' => 'عرض الخبرة، التواصل مع المتعلمين، والمحافظة على ثقة الجمهور في البرامج التدريبية الرقمية.',
                'objectives' => 'صياغة سيرة مهنية تدريبية، أسلوب تقديم واضح، استدامة العلاقة مع المتعلمين',
                'level' => 'intermediate',
                'duration_hours' => 16,
                'price' => 279,
                'is_free' => false,
                'is_featured' => false,
                'category' => 'تطوير مهني للمدرّبين',
                'requirements' => 'حساب مدرّب فعّال أو رغبة في التأهيل كمدرّب',
                'what_you_learn' => 'هوية مهنية، التواصل الكتابي والمرئي، خدمة ما بعد التدريب',
            ],
            [
                'title' => 'أدوات رقمية آمنة في خدمة التعليم',
                'description' => 'اختيار أدوات مناسبة للمرحلة، خصوصية الطلاب، وترشيد استخدام الشاشات داخل وخارج الحصة.',
                'objectives' => 'معايير اختيار الأداة الرقمية، أساسيات الخصوصية، دمج الأدوات دون تشتيت',
                'level' => 'beginner',
                'duration_hours' => 14,
                'price' => 199,
                'is_free' => false,
                'is_featured' => false,
                'category' => 'مهارات رقمية للمعلم',
                'requirements' => 'لا توجد متطلبات مسبقة',
                'what_you_learn' => 'سياسات الاستخدام الآمن، مصادر موثوقة، أنشطة منخفضة التكلفة',
            ],
        ];

        $created = 0;
        $sectionsCreated = 0;
        $lessonsCreated = 0;

        foreach ($courses as $courseData) {
            $course = AdvancedCourse::where('title', $courseData['title'])->first();

            if ($course) {
                echo "ℹ️  الكورس موجود مسبقاً: {$courseData['title']}\n";

                continue;
            }

            $courseId = DB::table('advanced_courses')->insertGetId([
                'title' => $courseData['title'],
                'description' => $courseData['description'] ?? null,
                'objectives' => $courseData['objectives'] ?? null,
                'level' => $courseData['level'] ?? 'beginner',
                'duration_hours' => $courseData['duration_hours'] ?? 0,
                'duration_minutes' => ($courseData['duration_hours'] ?? 0) * 60,
                'price' => $courseData['price'] ?? 0,
                'is_free' => $courseData['is_free'] ?? false,
                'is_featured' => $courseData['is_featured'] ?? false,
                'is_active' => true,
                'programming_language' => null,
                'framework' => null,
                'category' => $courseData['category'] ?? null,
                'requirements' => $courseData['requirements'] ?? null,
                'what_you_learn' => $courseData['what_you_learn'] ?? null,
                'skills' => isset($courseData['skills']) ? json_encode($courseData['skills']) : null,
                'instructor_id' => $instructorId,
                'academic_year_id' => $academicYear->id ?? null,
                'academic_subject_id' => $academicSubject->id ?? null,
                'rating' => rand(40, 50) / 10,
                'reviews_count' => rand(10, 100),
                'students_count' => rand(50, 500),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
            echo '✅ تم إنشاء كورس: '.$courseData['title'].' - السعر: '.($courseData['price'] ?? 0)." $\n";

            $sections = $this->getCourseSections($courseData['title']);
            $sectionOrder = 1;

            foreach ($sections as $sectionData) {
                DB::table('course_sections')->insert([
                    'advanced_course_id' => $courseId,
                    'title' => $sectionData['title'],
                    'description' => $sectionData['description'] ?? null,
                    'order' => $sectionOrder++,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sectionsCreated++;

                $lessonOrder = 1;
                $lessonsToInsert = [];
                foreach ($sectionData['lessons'] ?? [] as $lessonData) {
                    $lessonsToInsert[] = [
                        'advanced_course_id' => $courseId,
                        'title' => $lessonData['title'],
                        'description' => $lessonData['description'] ?? null,
                        'type' => $lessonData['type'] ?? 'video',
                        'content' => $lessonData['content'] ?? null,
                        'duration_minutes' => $lessonData['duration_minutes'] ?? rand(15, 45),
                        'order' => $lessonOrder++,
                        'is_free' => $lessonData['is_free'] ?? false,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (! empty($lessonsToInsert)) {
                    DB::table('course_lessons')->insert($lessonsToInsert);
                    $lessonsCreated += count($lessonsToInsert);
                }
            }

            unset($sections);
        }

        echo "\n🎉 تم إنشاء {$created} كورس، {$sectionsCreated} قسم، و {$lessonsCreated} درس بنجاح!\n";
        echo '='.str_repeat('=', 60)."\n";
    }

    private function getCourseSections(string $courseTitle): array
    {
        $sectionsMap = [
            'التدريس الفعّال في الصف الرقمي' => [
                [
                    'title' => 'أساسيات الحصة الرقمية',
                    'description' => 'تهيئة البيئة والتركيز على التعلّم',
                    'lessons' => [
                        ['title' => 'ما الذي يميّز الصف الرقمي عن الحضوري؟', 'type' => 'video', 'duration_minutes' => 22, 'is_free' => true],
                        ['title' => 'قبل الحصة: التحقق التقني والترحيب', 'type' => 'video', 'duration_minutes' => 18],
                        ['title' => 'اختبار سريع: جاهزية الحصة', 'type' => 'quiz', 'duration_minutes' => 10],
                    ],
                ],
                [
                    'title' => 'التفاعل والمشاركة',
                    'description' => 'إشراك المتعلمين باستمرار',
                    'lessons' => [
                        ['title' => 'أسئلة فتحية وأسئلة مغلقة في الزوم/المنصة', 'type' => 'video', 'duration_minutes' => 28],
                        ['title' => 'تمرين: تصميم نشاط مشاركة لـ 15 دقيقة', 'type' => 'assignment', 'duration_minutes' => 45],
                    ],
                ],
            ],
            'تخطيط الحصص وأهداف التعلّم' => [
                [
                    'title' => 'من الهدف إلى الأنشطة',
                    'description' => 'صياغة أهداف واضحة',
                    'lessons' => [
                        ['title' => 'أهداف قابلة للقياس في الحصة الواحدة', 'type' => 'video', 'duration_minutes' => 25, 'is_free' => true],
                        ['title' => 'ربط الأنشطة بالهدف والتقييم', 'type' => 'video', 'duration_minutes' => 30],
                    ],
                ],
                [
                    'title' => 'خريطة الحصة',
                    'description' => 'تنظيم الوقت والانتقالات',
                    'lessons' => [
                        ['title' => 'نموذج خطة حصة 45–60 دقيقة', 'type' => 'video', 'duration_minutes' => 35],
                        ['title' => 'واجب: خطّة حصة كاملة لموضوع تختاره', 'type' => 'assignment', 'duration_minutes' => 50],
                    ],
                ],
            ],
            'التقييم الصفي والتغذية الراجعة' => [
                [
                    'title' => 'أدوات التقييم السريع',
                    'description' => 'معرفة ما إذا كان الطلاب يتابعون',
                    'lessons' => [
                        ['title' => 'أسئلة فورية وتكوين خفيف', 'type' => 'video', 'duration_minutes' => 24, 'is_free' => true],
                        ['title' => 'مؤشرات بسيطة لنجاح الحصة', 'type' => 'video', 'duration_minutes' => 22],
                    ],
                ],
                [
                    'title' => 'التغذية الراجعة البناءة',
                    'description' => 'صياغة رسائل محفّزة',
                    'lessons' => [
                        ['title' => 'نماذج تغذية راجعة لفظية وكتابية', 'type' => 'video', 'duration_minutes' => 30],
                        ['title' => 'تمرين: إعادة صياغة 3 تعليقات سلبية', 'type' => 'assignment', 'duration_minutes' => 40],
                    ],
                ],
            ],
            'إدارة الصف الافتراضي والتفاعل' => [
                [
                    'title' => 'انضباط وآداب الصف الرقمي',
                    'description' => 'قواعد واضحة للجميع',
                    'lessons' => [
                        ['title' => 'بروتوكول بداية الحصة ونهايتها', 'type' => 'video', 'duration_minutes' => 26, 'is_free' => true],
                        ['title' => 'التعامل مع الانقطاع والتأخير', 'type' => 'video', 'duration_minutes' => 22],
                    ],
                ],
                [
                    'title' => 'العمل الجماعي عن بُعد',
                    'description' => 'غرف فرعية ومهام مشتركة',
                    'lessons' => [
                        ['title' => 'تقسيم المجموعات وتوزيع الأدوار', 'type' => 'video', 'duration_minutes' => 32],
                        ['title' => 'مشروع جماعي قصير: خطة تنفيذ', 'type' => 'assignment', 'duration_minutes' => 55],
                    ],
                ],
            ],
            'بناء حضورك المهني كمدرّب على الإنترنت' => [
                [
                    'title' => 'هويتك كمدرّب',
                    'description' => 'الثقة والمصداقية',
                    'lessons' => [
                        ['title' => 'عرض الخبرة دون مبالغة', 'type' => 'video', 'duration_minutes' => 20, 'is_free' => true],
                        ['title' => 'أسلوب التحدث أمام الكاميرا', 'type' => 'video', 'duration_minutes' => 28],
                    ],
                ],
                [
                    'title' => 'ما بعد الجلسة',
                    'description' => 'استدامة التعلّم',
                    'lessons' => [
                        ['title' => 'متابعة خفيفة عبر المنصة أو البريد', 'type' => 'video', 'duration_minutes' => 18],
                        ['title' => 'قائمة تحقق: تجربة متعلّم إيجابية', 'type' => 'quiz', 'duration_minutes' => 12],
                    ],
                ],
            ],
            'أدوات رقمية آمنة في خدمة التعليم' => [
                [
                    'title' => 'اختيار الأداة المناسبة',
                    'description' => 'معايير عملية',
                    'lessons' => [
                        ['title' => 'هل نحتاج أداة جديدة أم نبسّط الموجود؟', 'type' => 'video', 'duration_minutes' => 22, 'is_free' => true],
                        ['title' => 'خصوصية الطلاب وأسرّة التخزين', 'type' => 'video', 'duration_minutes' => 26],
                    ],
                ],
                [
                    'title' => 'دمج ذكي في الحصة',
                    'description' => 'أقل تشتيتاً وأثراً أكبر',
                    'lessons' => [
                        ['title' => 'نشاط بدون شاشة + نشاط مع شاشة', 'type' => 'video', 'duration_minutes' => 24],
                        ['title' => 'تخطيط أسبوعي لاستخدام الأدوات', 'type' => 'assignment', 'duration_minutes' => 35],
                    ],
                ],
            ],
        ];

        if (! isset($sectionsMap[$courseTitle])) {
            return [
                [
                    'title' => 'المقدمة',
                    'description' => 'مقدمة الكورس',
                    'lessons' => [
                        ['title' => 'نظرة عامة على الكورس', 'type' => 'video', 'duration_minutes' => 20, 'is_free' => true],
                        ['title' => 'كيف تستفيد من المنصة', 'type' => 'video', 'duration_minutes' => 15],
                    ],
                ],
                [
                    'title' => 'المحتوى الأساسي',
                    'description' => 'وحدات التعلّم',
                    'lessons' => [
                        ['title' => 'الدرس الأول', 'type' => 'video', 'duration_minutes' => 30],
                        ['title' => 'الدرس الثاني', 'type' => 'video', 'duration_minutes' => 35],
                        ['title' => 'تمرين عملي', 'type' => 'assignment', 'duration_minutes' => 50],
                    ],
                ],
            ];
        }

        return $sectionsMap[$courseTitle];
    }
}

