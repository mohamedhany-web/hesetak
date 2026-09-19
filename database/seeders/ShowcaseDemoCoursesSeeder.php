<?php

namespace Database\Seeders;

use App\Models\AdvancedCourse;
use App\Models\CourseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * دورات تجريبية بصور عالية الجودة (محلية في storage) للواجهة — مواضيع تعليم ولغات فقط.
 *
 * تشغيل: php artisan db:seed --class=ShowcaseDemoCoursesSeeder
 * يتطلب: php artisan storage:link (إن لم يكن الرابط موجوداً)
 *
 * يحدّث الدورات الموجودة إن تطابقت مع مسار الصورة (showcase-courses/{slug}.jpg) أو العنوان القديم/الجديد.
 */
class ShowcaseDemoCoursesSeeder extends Seeder
{
    /** صور Unsplash: تعليم، صفوف، مكتبات، اجتماعات — w=2400&q=90 */
    private function u(string $photoPath): string
    {
        return 'https://images.unsplash.com/'.$photoPath.'?auto=format&fit=crop&w=2400&q=90&fm=jpg';
    }

    public function run(): void
    {
        if (! Schema::hasTable('advanced_courses')) {
            $this->command->warn('جدول advanced_courses غير موجود.');

            return;
        }

        $instructor = User::query()
            ->where('role', 'instructor')
            ->where('is_active', true)
            ->first()
            ?? User::query()->where('role', 'teacher')->where('is_active', true)->first()
            ?? User::query()->where('role', 'admin')->where('is_active', true)->first()
            ?? User::query()->first();

        if (! $instructor) {
            $this->command->error('لا يوجد مستخدم لربطه كمدرّب. شغّل GlotticalAcademyUserSeeder أولاً.');

            return;
        }

        $disk = Storage::disk('public');
        $dir = 'showcase-courses';
        if (! $disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $cat = [
            'en' => $this->ensureCategory('اللغة الإنجليزية', 1),
            'ar' => $this->ensureCategory('اللغة العربية', 2),
            'fr' => $this->ensureCategory('اللغة الفرنسية', 3),
            'kids' => $this->ensureCategory('برامج الأطفال', 4),
            'exams' => $this->ensureCategory('الاختبارات الدولية', 5),
            'es' => $this->ensureCategory('اللغة الإسبانية', 6),
            'de' => $this->ensureCategory('اللغة الألمانية', 7),
            'skills' => $this->ensureCategory('مهارات تعليمية', 8),
        ];

        $items = [
            [
                'slug' => 'english-conversation-a1',
                'title' => 'محادثة إنجليزية — المستوى الأول',
                'description' => 'مفردات يومية، نطق بسيط، وتمارين استماع قصيرة لبناء الثقة بالتحدث بالإنجليزية.',
                'level' => 'beginner',
                'duration_hours' => 28,
                'price' => 0,
                'is_free' => true,
                'is_featured' => true,
                'category_id' => $cat['en']->id,
                'image' => $this->u('photo-1523240795612-9a054b0db644'),
            ],
            [
                'slug' => 'english-grammar-b1',
                'title' => 'قواعد الإنجليزية — مستوى متوسط',
                'description' => 'زمن المضارع والماضي، الجمل الشرطية، والأخطاء الشائعة مع تطبيقات من الواقع.',
                'level' => 'intermediate',
                'duration_hours' => 36,
                'price' => 349,
                'is_free' => false,
                'is_featured' => true,
                'category_id' => $cat['en']->id,
                'image' => $this->u('photo-1456513080510-7bf3a84b82f8'),
            ],
            [
                'slug' => 'ielts-strategies',
                'title' => 'تحضير IELTS — استراتيجيات النجاح',
                'description' => 'Reading وListening وWriting وSpeaking: نماذج امتحانات، وقت الإجابة، وتصحيح أخطاء شائعة.',
                'level' => 'intermediate',
                'duration_hours' => 42,
                'price' => 499,
                'is_free' => false,
                'is_featured' => true,
                'category_id' => $cat['exams']->id,
                'image' => $this->u('photo-1434030216411-0b793f4b4173'),
            ],
            [
                'slug' => 'arabic-for-non-native',
                'title' => 'العربية للناطقين بغيرها — أساسيات',
                'description' => 'الحروف، التشكيل، الجمل البسيطة، ومفردات الحياة اليومية بأسلوب تدريجي.',
                'level' => 'beginner',
                'duration_hours' => 32,
                'price' => 279,
                'is_free' => false,
                'is_featured' => false,
                'category_id' => $cat['ar']->id,
                'image' => $this->u('photo-1497633762265-9d179a990aa6'),
            ],
            [
                'slug' => 'french-a1',
                'title' => 'فرنسية للمبتدئين — A1',
                'description' => 'تحيات، أرقام، أماكن، ومحادثات قصيرة مع تمارين نطق واستماع.',
                'level' => 'beginner',
                'duration_hours' => 26,
                'price' => 299,
                'is_free' => false,
                'is_featured' => false,
                'category_id' => $cat['fr']->id,
                'image' => $this->u('photo-1516321497487-e288fb19713f'),
            ],
            [
                'slug' => 'kids-english-fun',
                'title' => 'إنجليزي ممتع للأطفال',
                'description' => 'أنشطة صفية، أغاني تعليمية، حروف وأصوات، وقصص قصيرة داخل سياق المدرسة لبناء أول مفردات إنجليزية بثقة.',
                'level' => 'beginner',
                'duration_hours' => 18,
                'price' => 199,
                'is_free' => false,
                'is_featured' => true,
                'category_id' => $cat['kids']->id,
                'image' => $this->u('photo-1509062522246-3755977927d7'),
            ],
            [
                'slug' => 'business-english-meetings',
                'title' => 'إنجليزية الأعمال — الاجتماعات والبريد',
                'description' => 'صياغة رسائل مهنية، عروض شفهية قصيرة، ومفردات الاجتماعات عبر الإنترنت.',
                'level' => 'advanced',
                'duration_hours' => 22,
                'price' => 429,
                'is_free' => false,
                'is_featured' => false,
                'category_id' => $cat['en']->id,
                'image' => $this->u('photo-1552664730-d307ca884978'),
            ],
            [
                'slug' => 'academic-writing-en',
                'title' => 'الكتابة الأكاديمية بالإنجليزية',
                'description' => 'فقرة المقدمة، الأطروحة، الربط بين الفقرات، وأساليب الاستشهاد الأساسية.',
                'level' => 'intermediate',
                'duration_hours' => 30,
                'price' => 379,
                'is_free' => false,
                'is_featured' => false,
                'category_id' => $cat['en']->id,
                'image' => $this->u('photo-1524178232363-1fb2b075b655'),
            ],
            [
                'slug' => 'spanish-a1',
                'title' => 'إسبانية للمبتدئين — A1',
                'description' => 'مفردات أساسية، تعريف بالنفس، أرقام ومواعيد، وتمارين استماع بسيطة في سياقات يومية.',
                'level' => 'beginner',
                'duration_hours' => 24,
                'price' => 289,
                'is_free' => false,
                'is_featured' => false,
                'category_id' => $cat['es']->id,
                'image' => $this->u('photo-1521737604893-d14cc237f11d'),
            ],
            [
                'slug' => 'german-a1',
                'title' => 'ألمانية للمبتدئين — A1',
                'description' => 'التحيات، الأرقام، الجمل القصيرة، ومقدمة في النطق مع تمارين صفية موجهة.',
                'level' => 'beginner',
                'duration_hours' => 25,
                'price' => 309,
                'is_free' => false,
                'is_featured' => false,
                'category_id' => $cat['de']->id,
                'image' => $this->u('photo-1481627834876-b7833e8f5570'),
            ],
            [
                'slug' => 'online-teaching-skills',
                'title' => 'تدريس فعّال عبر الإنترنت',
                'description' => 'تخطيط الحصة، التفاعل مع الكاميرا، أدوات الصف الرقمي، وتقييم تعلم المتعلم عن بُعد.',
                'level' => 'intermediate',
                'duration_hours' => 16,
                'price' => 249,
                'is_free' => false,
                'is_featured' => true,
                'category_id' => $cat['skills']->id,
                'image' => $this->u('photo-1588072432836-e10032774398'),
            ],
            [
                'slug' => 'presentation-skills-classroom',
                'title' => 'مهارات العرض للمعلمين والطلاب',
                'description' => 'هيكل العرض، لغة الجسد، الشرائح التعليمية، وإدارة الأسئلة في الصف أو الاجتماع.',
                'level' => 'intermediate',
                'duration_hours' => 14,
                'price' => 189,
                'is_free' => false,
                'is_featured' => false,
                'category_id' => $cat['skills']->id,
                'image' => $this->u('photo-1542744173-8e7e53415bb0'),
            ],
            [
                'slug' => 'phonics-reading-kids',
                'title' => 'القراءة بالتحليل الصوتي للأطفال',
                'description' => 'ربط الحروف بالأصوات، مقاطع بسيطة، كلمات شائعة، وتمارين قراءة قصيرة مع متابعة أهلية.',
                'level' => 'beginner',
                'duration_hours' => 20,
                'price' => 219,
                'is_free' => false,
                'is_featured' => false,
                'category_id' => $cat['kids']->id,
                'image' => $this->u('photo-1503676260728-1c00da094a0b'),
            ],
        ];

        $this->command->info('جاري تنزيل الصور وإنشاء/تحديث دورات الواجهة التجريبية...');

        foreach ($items as $row) {
            $thumbRel = $dir.'/'.$row['slug'].'.jpg';
            $relativePath = $this->downloadImage($row['image'], $thumbRel);
            if ($relativePath === null) {
                $this->command->error("فشل تنزيل صورة: {$row['slug']}");

                continue;
            }

            $legacyTitle = '[عرض] '.$row['title'];

            $existing = AdvancedCourse::query()
                ->where('thumbnail', 'like', '%/'.$row['slug'].'.jpg')
                ->first();

            if (! $existing) {
                $existing = AdvancedCourse::query()->where('title', $row['title'])->first();
            }
            if (! $existing) {
                $existing = AdvancedCourse::query()->where('title', $legacyTitle)->first();
            }

            $payload = [
                'instructor_id' => $instructor->id,
                'title' => $row['title'],
                'description' => $row['description'],
                'objectives' => 'أهداف واضحة قابلة للقياس ضمن مسار الدورة.',
                'level' => $row['level'],
                'duration_hours' => $row['duration_hours'],
                'duration_minutes' => (int) $row['duration_hours'] * 60,
                'price' => $row['price'],
                'price_after_discount' => null,
                'is_free' => $row['is_free'],
                'is_featured' => $row['is_featured'],
                'is_active' => true,
                'thumbnail' => $relativePath,
                'course_category_id' => $row['category_id'],
                'category' => 'تجريبي',
                'language' => 'ar',
                'requirements' => 'لا متطلبات مسبقة لمعظم المستويات المبتدئة.',
                'what_you_learn' => 'مهارات لغوية عملية، تمارين موجهة، وجاهزية للمستوى التالي.',
                'rating' => round(4.2 + (crc32($row['slug']) % 8) / 10, 1),
                'reviews_count' => 20 + (crc32($row['slug']) % 80),
                'students_count' => 100 + (crc32($row['slug']) % 400),
            ];

            if ($existing) {
                $existing->update($payload);
                $this->command->info("✓ حُدّثت: {$row['title']}");
            } else {
                AdvancedCourse::query()->create($payload);
                $this->command->info("✓ أُنشئت: {$row['title']}");
            }
        }

        // عناوين قديمة تحتوي البادئة [عرض] (بدون الاعتماد على LIKE مع أقواس لتوافق محركات قواعد البيانات)
        AdvancedCourse::query()
            ->orderBy('id')
            ->chunkById(100, function ($courses) {
                foreach ($courses as $c) {
                    if (! is_string($c->title) || ! str_starts_with($c->title, '[عرض] ')) {
                        continue;
                    }
                    $clean = preg_replace('/^\s*\[عرض\]\s+/u', '', $c->title);
                    if ($clean !== '' && $clean !== $c->title) {
                        $c->update(['title' => $clean, 'category' => 'تجريبي']);
                    }
                }
            });

        $this->command->info('تم. تأكد من تشغيل: php artisan storage:link إن لزم.');
    }

    private function ensureCategory(string $name, int $sortOrder): CourseCategory
    {
        return CourseCategory::query()->firstOrCreate(
            ['name' => $name],
            ['sort_order' => $sortOrder, 'is_active' => true]
        );
    }

    private function downloadImage(string $url, string $relativePath): ?string
    {
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Accept' => 'image/jpeg,image/webp,*/*',
                    'User-Agent' => 'حصتكShowcaseSeeder/1.1',
                ])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();
            if ($body === '' || strlen($body) < 5000) {
                return null;
            }

            Storage::disk('public')->put($relativePath, $body);

            return $relativePath;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
