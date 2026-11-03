<?php

namespace App\Support;

use App\Models\AcademicSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * يوسّع بحث/فلتر المادة ليشمل مواد قريبة (مثلاً «رياضيات» ↔ «جبر»).
 */
final class AcademicSubjectMatcher
{
    /**
     * مجموعات مرادفات عربية/إنجليزية للمواد الدراسية الشائعة.
     *
     * @var list<list<string>>
     */
    private const SYNONYM_GROUPS = [
        ['رياض', 'جبر', 'تفاضل', 'تكامل', 'إحصاء', 'احتمال', 'حساب', 'math', 'algebra', 'calculus', 'statistics'],
        ['عربي', 'نحو', 'بلاغ', 'أدب', 'قراء', 'تعبير', 'arabic'],
        ['إنجل', 'انجليز', 'english', 'esl'],
        ['علم', 'فيزياء', 'كيمياء', 'أحياء', 'أحيا', 'science', 'physics', 'chemistry', 'biology'],
        ['دين', 'فق', 'تفسير', 'حديث', 'سيرة', 'قرآن', 'تجويد', 'fiqh', 'seerah', 'quran'],
        ['تأسيس', 'kg', 'روضة', 'أطفال'],
        ['حاسب', 'برمج', 'digit', 'tech', 'حاسوب'],
    ];

    /**
     * @return Collection<int, int>
     */
    public static function idsMatchingSearch(string $query, ?int $yearId = null): Collection
    {
        $query = trim($query);
        if ($query === '' || ! Schema::hasTable('academic_subjects')) {
            return collect();
        }

        $base = AcademicSubject::query()->active();
        if ($yearId) {
            $base->forYear($yearId);
        }

        $like = '%'.$query.'%';
        $direct = (clone $base)
            ->where(function ($s) use ($like, $query) {
                $s->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('name', $query)
                    ->orWhere('code', $query);
            })
            ->pluck('id');

        $tokens = self::significantTokens($query);
        $expanded = collect();
        if ($tokens->isNotEmpty()) {
            $all = (clone $base)->get(['id', 'name', 'slug']);
            foreach ($all as $subject) {
                $hay = mb_strtolower(trim(($subject->name ?? '').' '.($subject->slug ?? '')));
                foreach ($tokens as $token) {
                    if ($token !== '' && str_contains($hay, $token)) {
                        $expanded->push((int) $subject->id);
                        break;
                    }
                }
                if (self::sharesSynonymGroup($query, (string) $subject->name.' '.(string) $subject->slug)) {
                    $expanded->push((int) $subject->id);
                }
            }
        }

        return $direct->map(fn ($id) => (int) $id)
            ->merge($expanded)
            ->unique()
            ->values();
    }

    /**
     * من slug/اسم مادة مختارة في الفلتر — يشمل المواد ذات المجموعة نفسها.
     *
     * @return Collection<int, int>
     */
    public static function idsForSkill(string $skill, ?int $yearId = null): Collection
    {
        $skill = trim($skill);
        if ($skill === '' || ! Schema::hasTable('academic_subjects')) {
            return collect();
        }

        $base = AcademicSubject::query()->active();
        if ($yearId) {
            $base->forYear($yearId);
        } else {
            $base->where(function ($q) {
                $q->whereNull('academic_year_id')
                    ->orWhereHas('academicYear', fn ($y) => $y->publicCatalog());
            });
        }

        $direct = (clone $base)
            ->where(function ($s) use ($skill) {
                $s->where('slug', $skill)
                    ->orWhere('id', ctype_digit($skill) ? (int) $skill : 0)
                    ->orWhere('name', $skill);
            })
            ->get(['id', 'name', 'slug']);

        if ($direct->isEmpty()) {
            return self::idsMatchingSearch($skill, $yearId);
        }

        $ids = $direct->pluck('id')->map(fn ($id) => (int) $id);
        $seedNames = $direct->map(fn ($s) => trim(($s->name ?? '').' '.($s->slug ?? '')))->implode(' ');

        $related = (clone $base)->get(['id', 'name', 'slug']);
        foreach ($related as $subject) {
            $label = (string) $subject->name.' '.(string) $subject->slug;
            if (self::sharesSynonymGroup($seedNames, $label)) {
                $ids->push((int) $subject->id);
            }
        }

        return $ids->unique()->values();
    }

    /**
     * @return Collection<int, string>
     */
    private static function significantTokens(string $query): Collection
    {
        $normalized = mb_strtolower($query);
        $parts = preg_split('/[\s\-_,.\/\\\\]+/u', $normalized) ?: [];

        return collect($parts)
            ->map(fn ($p) => trim((string) $p))
            ->filter(function (string $p) {
                if ($p === '') {
                    return false;
                }
                // تجاهل كلمات قصيرة جداً غير العربية
                if (! preg_match('/\p{Arabic}/u', $p) && mb_strlen($p) < 3) {
                    return false;
                }

                return mb_strlen($p) >= 2;
            })
            ->values();
    }

    private static function sharesSynonymGroup(string $a, string $b): bool
    {
        $a = mb_strtolower($a);
        $b = mb_strtolower($b);
        foreach (self::SYNONYM_GROUPS as $group) {
            $hitA = false;
            $hitB = false;
            foreach ($group as $token) {
                $t = mb_strtolower($token);
                if ($t !== '' && str_contains($a, $t)) {
                    $hitA = true;
                }
                if ($t !== '' && str_contains($b, $t)) {
                    $hitB = true;
                }
            }
            if ($hitA && $hitB) {
                return true;
            }
        }

        return false;
    }
}
