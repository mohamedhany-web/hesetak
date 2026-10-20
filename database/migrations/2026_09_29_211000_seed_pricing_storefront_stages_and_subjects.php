<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * مراحل ومواد واجهة الباقات العامة (بنية كتالوج شبيهة بالمرجع، بهوية حصتك).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years') || ! Schema::hasTable('academic_subjects')) {
            return;
        }

        $now = now();

        $stages = [
            ['code' => 'PKG-PRIMARY', 'slug' => 'primary', 'name' => 'الابتدائي', 'order' => 10, 'level_number' => 2],
            ['code' => 'PKG-MIDDLE', 'slug' => 'middle', 'name' => 'المتوسط', 'order' => 20, 'level_number' => 3],
            ['code' => 'PKG-SECONDARY', 'slug' => 'secondary', 'name' => 'الثانوي', 'order' => 30, 'level_number' => 4],
            ['code' => 'PKG-INTL', 'slug' => 'international', 'name' => 'دولي', 'order' => 40, 'level_number' => 5],
            ['code' => 'PKG-UNIVERSITY', 'slug' => 'university', 'name' => 'الجامعة', 'order' => 50, 'level_number' => 6],
            ['code' => 'PKG-QIYAS', 'slug' => 'qiyas', 'name' => 'قياس', 'order' => 60, 'level_number' => 7],
            ['code' => 'PKG-SKILLS', 'slug' => 'skills', 'name' => 'تطوير مهارات', 'order' => 70, 'level_number' => 8],
        ];

        $stageIds = [];
        foreach ($stages as $stage) {
            $existing = DB::table('academic_years')
                ->where(function ($q) use ($stage) {
                    $q->where('code', $stage['code'])
                        ->orWhere('slug', $stage['slug'])
                        ->orWhere('name', $stage['name']);
                })
                ->first();

            if (! $existing && $stage['slug'] === 'primary') {
                $existing = DB::table('academic_years')
                    ->where(function ($q) {
                        $q->where('slug', 'primary')->orWhere('code', 'YR')->orWhere('name', 'like', '%ابتد%');
                    })
                    ->first();
            }
            if (! $existing && $stage['slug'] === 'middle') {
                $existing = DB::table('academic_years')
                    ->where(function ($q) {
                        $q->whereIn('slug', ['middle', 'almrhl-almtost'])->orWhere('code', 'MID')->orWhere('name', 'like', '%متوسط%');
                    })
                    ->first();
            }

            if ($existing) {
                DB::table('academic_years')->where('id', $existing->id)->update([
                    'name' => $stage['name'],
                    'slug' => $stage['slug'],
                    'code' => $stage['code'],
                    'order' => $stage['order'],
                    'level_number' => $stage['level_number'],
                    'is_active' => true,
                    'is_public' => true,
                    'updated_at' => $now,
                ]);
                $stageIds[$stage['slug']] = (int) $existing->id;
            } else {
                $id = DB::table('academic_years')->insertGetId([
                    'name' => $stage['name'],
                    'slug' => $stage['slug'],
                    'code' => $stage['code'],
                    'description' => 'مرحلة باقات الحصص الفردية على حصتك',
                    'order' => $stage['order'],
                    'level_number' => $stage['level_number'],
                    'is_active' => true,
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $stageIds[$stage['slug']] = (int) $id;
            }
        }

        // إخفاء رياض الأطفال من كتالوج الباقات العام (ليست ضمن تبويبات المرجع)
        DB::table('academic_years')
            ->where(function ($q) {
                $q->where('slug', 'kg')->orWhere('code', 'SCH-L1')->orWhere('name', 'like', '%رياض الأطفال%');
            })
            ->update(['is_public' => false, 'updated_at' => $now]);

        $subjectsByStage = [
            'primary' => [
                ['تأسيس الأطفال', 'fa-child'],
                ['القرآن الكريم', 'fa-book-quran'],
                ['الرياضيات', 'fa-square-root-variable'],
                ['العلوم', 'fa-flask'],
                ['اللغة العربية', 'fa-book-open'],
                ['اللغة الإنجليزية', 'fa-language'],
                ['الاجتماعيات', 'fa-earth-americas'],
            ],
            'middle' => [
                ['القرآن الكريم', 'fa-book-quran'],
                ['الرياضيات', 'fa-square-root-variable'],
                ['العلوم', 'fa-flask'],
                ['اللغة العربية', 'fa-book-open'],
                ['اللغة الإنجليزية', 'fa-language'],
                ['الاجتماعيات', 'fa-earth-americas'],
            ],
            'secondary' => [
                ['القرآن الكريم', 'fa-book-quran'],
                ['الرياضيات', 'fa-square-root-variable'],
                ['الفيزياء', 'fa-atom'],
                ['الكيمياء', 'fa-flask'],
                ['الأحياء', 'fa-dna'],
                ['اللغة العربية', 'fa-book-open'],
                ['اللغة الإنجليزية', 'fa-language'],
            ],
            'international' => [
                ['Mathematics', 'fa-square-root-variable'],
                ['Science', 'fa-flask'],
                ['English', 'fa-language'],
                ['Arabic', 'fa-book-open'],
            ],
            'university' => [
                ['رياضيات جامعية', 'fa-square-root-variable'],
                ['فيزياء جامعية', 'fa-atom'],
                ['لغة إنجليزية أكاديمية', 'fa-language'],
            ],
            'qiyas' => [
                ['قدرات كمي', 'fa-calculator'],
                ['قدرات لفظي', 'fa-comments'],
                ['تحصيلي', 'fa-clipboard-check'],
            ],
            'skills' => [
                ['تأسيس مهارات', 'fa-lightbulb'],
                ['لغة إنجليزية محادثة', 'fa-language'],
                ['مهارات دراسة', 'fa-book-reader'],
            ],
        ];

        foreach ($subjectsByStage as $slug => $list) {
            $yearId = $stageIds[$slug] ?? null;
            if (! $yearId) {
                continue;
            }

            foreach ($list as $index => [$name, $icon]) {
                $existing = DB::table('academic_subjects')
                    ->where('academic_year_id', $yearId)
                    ->where('name', $name)
                    ->first();

                $payload = [
                    'academic_year_id' => $yearId,
                    'name' => $name,
                    'slug' => Str::slug($name) ?: ('subject-'.$yearId.'-'.($index + 1)),
                    'icon' => $icon,
                    'order' => ($index + 1) * 10,
                    'is_active' => true,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('academic_subjects', 'code')) {
                    $payload['code'] = 'PKG-S-'.$yearId.'-'.($index + 1);
                }

                if ($existing) {
                    DB::table('academic_subjects')->where('id', $existing->id)->update($payload);
                } else {
                    $payload['created_at'] = $now;
                    // unique slug per table if needed
                    $baseSlug = $payload['slug'];
                    $i = 1;
                    while (DB::table('academic_subjects')->where('slug', $payload['slug'])->exists()) {
                        $payload['slug'] = $baseSlug.'-'.$i;
                        $i++;
                    }
                    DB::table('academic_subjects')->insert($payload);
                }
            }
        }
    }

    public function down(): void
    {
        // لا نحذف بيانات المراحل/المواد تلقائياً
    }
};
