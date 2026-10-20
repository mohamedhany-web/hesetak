<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years') || ! Schema::hasTable('academic_subjects')) {
            return;
        }

        $now = now();
        $hasCode = Schema::hasColumn('academic_subjects', 'code');

        $subjectsByStage = [
            'primary' => [
                ['تأسيس الأطفال', 'fa-child', 'PKG-SUB-FOUND'],
                ['القرآن الكريم', 'fa-book-quran', 'PKG-SUB-QURAN'],
                ['الرياضيات', 'fa-square-root-variable', 'PKG-SUB-MATH'],
                ['العلوم', 'fa-flask', 'PKG-SUB-SCI'],
                ['اللغة العربية', 'fa-book-open', 'PKG-SUB-AR'],
                ['اللغة الإنجليزية', 'fa-language', 'PKG-SUB-EN'],
                ['الاجتماعيات', 'fa-earth-americas', 'PKG-SUB-SOC'],
            ],
            'middle' => [
                ['القرآن الكريم', 'fa-book-quran', 'PKG-SUB-MQURAN'],
                ['الرياضيات', 'fa-square-root-variable', 'PKG-SUB-MMATH'],
                ['العلوم', 'fa-flask', 'PKG-SUB-MSCI'],
                ['اللغة العربية', 'fa-book-open', 'PKG-SUB-MAR'],
                ['اللغة الإنجليزية', 'fa-language', 'PKG-SUB-MEN'],
                ['الاجتماعيات', 'fa-earth-americas', 'PKG-SUB-MSOC'],
            ],
            'secondary' => [
                ['القرآن الكريم', 'fa-book-quran', 'PKG-SUB-SQURAN'],
                ['الرياضيات', 'fa-square-root-variable', 'PKG-SUB-SMATH'],
                ['الفيزياء', 'fa-atom', 'PKG-SUB-PHY'],
                ['الكيمياء', 'fa-flask', 'PKG-SUB-CHEM'],
                ['الأحياء', 'fa-dna', 'PKG-SUB-BIO'],
                ['اللغة العربية', 'fa-book-open', 'PKG-SUB-SAR'],
                ['اللغة الإنجليزية', 'fa-language', 'PKG-SUB-SEN'],
            ],
            'international' => [
                ['Mathematics', 'fa-square-root-variable', 'PKG-SUB-IMATH'],
                ['Science', 'fa-flask', 'PKG-SUB-ISCI'],
                ['English', 'fa-language', 'PKG-SUB-IEN'],
                ['Arabic', 'fa-book-open', 'PKG-SUB-IAR'],
            ],
            'university' => [
                ['رياضيات جامعية', 'fa-square-root-variable', 'PKG-SUB-UMATH'],
                ['فيزياء جامعية', 'fa-atom', 'PKG-SUB-UPHY'],
                ['لغة إنجليزية أكاديمية', 'fa-language', 'PKG-SUB-UEN'],
            ],
            'qiyas' => [
                ['قدرات كمي', 'fa-calculator', 'PKG-SUB-QQUANT'],
                ['قدرات لفظي', 'fa-comments', 'PKG-SUB-QVERB'],
                ['تحصيلي', 'fa-clipboard-check', 'PKG-SUB-QTACH'],
            ],
            'skills' => [
                ['تأسيس مهارات', 'fa-lightbulb', 'PKG-SUB-SKF'],
                ['لغة إنجليزية محادثة', 'fa-language', 'PKG-SUB-SKEN'],
                ['مهارات دراسة', 'fa-book-reader', 'PKG-SUB-SKST'],
            ],
        ];

        foreach ($subjectsByStage as $slug => $list) {
            $year = DB::table('academic_years')->where('slug', $slug)->orWhere('code', 'PKG-'.strtoupper($slug === 'international' ? 'INTL' : ($slug === 'primary' ? 'PRIMARY' : strtoupper($slug))))->first();
            if (! $year) {
                $year = DB::table('academic_years')->where('slug', $slug)->first();
            }
            if (! $year) {
                continue;
            }

            foreach ($list as $index => [$name, $icon, $code]) {
                $existing = DB::table('academic_subjects')
                    ->where('academic_year_id', $year->id)
                    ->where(function ($q) use ($name, $code, $hasCode) {
                        $q->where('name', $name);
                        if ($hasCode) {
                            $q->orWhere('code', $code);
                        }
                    })
                    ->first();

                $payload = [
                    'academic_year_id' => $year->id,
                    'name' => $name,
                    'slug' => Str::slug($name) ?: ('subject-'.$year->id.'-'.($index + 1)),
                    'icon' => $icon,
                    'order' => ($index + 1) * 10,
                    'is_active' => true,
                    'updated_at' => $now,
                ];
                if ($hasCode) {
                    $payload['code'] = $code;
                }

                if ($existing) {
                    DB::table('academic_subjects')->where('id', $existing->id)->update($payload);
                    continue;
                }

                $baseSlug = $payload['slug'];
                $i = 1;
                while (DB::table('academic_subjects')->where('slug', $payload['slug'])->exists()) {
                    $payload['slug'] = $baseSlug.'-'.$year->id.'-'.$i;
                    $i++;
                }
                $payload['created_at'] = $now;
                DB::table('academic_subjects')->insert($payload);
            }
        }

        // Mark failed previous migration as done if stages already exist
        if (DB::table('academic_years')->where('code', 'like', 'PKG-%')->count() >= 5) {
            // no-op; data complete via this migration
        }
    }

    public function down(): void
    {
        //
    }
};
