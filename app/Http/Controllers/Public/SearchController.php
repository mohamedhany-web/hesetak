<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\InstructorProfile;
use App\Models\ServicePackage;
use App\Services\PublicInstructorDirectory;
use App\Support\HesetakMatchCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $locale = app()->getLocale();
        $isRtl = $locale === 'ar';
        $tokens = $this->tokens($q);
        $parsed = $this->parseCatalogIntent($tokens);

        $instructors = collect();
        $packages = collect();
        $courses = collect();
        $subjects = collect();
        $years = collect();

        if (count($tokens) >= 1 && mb_strlen($q) >= 2) {
            if (Schema::hasTable('instructor_profiles')) {
                $instructors = InstructorProfile::query()
                    ->approved()
                    ->where(function (Builder $query) use ($tokens) {
                        $this->requireAllTokens($query, $tokens, ['headline', 'skills']);
                        $query->orWhereHas('user', function (Builder $userQuery) use ($tokens) {
                            $userQuery->whereIn('role', ['instructor', 'teacher'])
                                ->where('is_active', true);
                            $this->requireAllTokens($userQuery, $tokens, ['name']);
                        });
                    })
                    ->with(['user:'.implode(',', PublicInstructorDirectory::userSelect())])
                    ->limit(8)
                    ->get();
            }

            if (Schema::hasTable('service_packages')) {
                $packages = ServicePackage::query()
                    ->storefront()
                    ->when($parsed['curriculum_type'], fn ($q) => $q->where(function ($inner) use ($parsed) {
                        $inner->whereNull('curriculum_type')->orWhere('curriculum_type', $parsed['curriculum_type']);
                    }))
                    ->when($parsed['year_id'], fn ($q) => $q->where('academic_year_id', $parsed['year_id']))
                    ->where(function (Builder $query) use ($parsed) {
                        $content = $parsed['content_tokens'];
                        if ($content === []) {
                            $query->whereRaw('1=1');

                            return;
                        }
                        $this->requireAnyToken($query, $content, ['name', 'tagline']);
                    })
                    ->orderByDesc('units_count')
                    ->limit(8)
                    ->get(['id', 'name', 'tagline', 'units_count', 'price', 'currency', 'academic_year_id', 'academic_subject_id']);
            }

            if (Schema::hasTable('advanced_courses')) {
                $courses = AdvancedCourse::query()
                    ->where('is_active', true)
                    ->where(function (Builder $query) use ($tokens) {
                        $this->requireAllTokens($query, $tokens, ['title', 'category']);
                    })
                    ->limit(8)
                    ->get(['id', 'title', 'thumbnail', 'level', 'duration_hours', 'category', 'language']);
            }

            if (Schema::hasTable('academic_subjects')) {
                $subjects = AcademicSubject::query()
                    ->active()
                    ->when($parsed['curriculum_type'], fn ($q) => $q->forCurriculumType($parsed['curriculum_type']))
                    ->when($parsed['year_id'], fn ($q) => $q->forYear($parsed['year_id']))
                    ->where(function (Builder $query) use ($parsed) {
                        $content = $parsed['content_tokens'];
                        if ($content === []) {
                            $query->whereRaw('1=1');

                            return;
                        }
                        $this->requireAnyToken($query, $content, ['name']);
                    })
                    ->with('academicYear:id,name')
                    ->limit(12)
                    ->get(['id', 'name', 'academic_year_id', 'curriculum_type']);
            }

            if (Schema::hasTable('academic_years')) {
                $years = AcademicYear::query()
                    ->publicCatalog()
                    ->when($parsed['curriculum_type'], fn ($q) => $q->forCurriculumType($parsed['curriculum_type']))
                    ->where(function (Builder $query) use ($parsed, $tokens) {
                        $content = $parsed['content_tokens'] !== [] ? $parsed['content_tokens'] : $tokens;
                        $this->requireAnyToken($query, $content, ['name', 'tagline']);
                    })
                    ->ordered()
                    ->limit(8)
                    ->get(['id', 'name', 'slug', 'tagline', 'curriculum_type']);
            }
        }

        return view('public.marketing.search', [
            'mcActive' => 'search',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => ($isRtl ? 'بحث' : 'Search').' | '.__('landing.nav.brand'),
            'pageDescription' => $isRtl
                ? 'ابحث عن معلم أو مادة أو باقة أو كورس على حصتك.'
                : 'Search teachers, subjects, packages, or courses on Hesetak.',
            'q' => $q,
            'instructors' => $instructors,
            'packages' => $packages,
            'courses' => $courses,
            'subjects' => $subjects,
            'years' => $years,
            'hasQuery' => mb_strlen($q) >= 2,
            'resolvedCurriculum' => $parsed['curriculum_type'],
            'resolvedYearId' => $parsed['year_id'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function tokens(string $q): array
    {
        $parts = preg_split('/\s+/u', mb_strtolower(trim($q))) ?: [];

        return array_values(array_filter($parts, fn ($t) => mb_strlen($t) >= 2));
    }

    /**
     * @param  list<string>  $tokens
     * @return array{curriculum_type:?string,year_id:?int,content_tokens:list<string>}
     */
    private function parseCatalogIntent(array $tokens): array
    {
        $stop = [
            'منهج', 'مادة', 'مرحلة', 'صف', 'دراسي', 'دراسية', 'في', 'على', 'مع',
            'curriculum', 'subject', 'stage', 'grade', 'for', 'the', 'and',
        ];
        $curriculumType = null;
        $yearId = null;
        $remaining = [];

        $types = HesetakMatchCatalog::curriculumTypes();
        $haystack = implode(' ', $tokens);

        foreach ($types as $key => $meta) {
            $label = mb_strtolower((string) (is_array($meta) ? ($meta['label'] ?? $key) : $meta));
            $extraAliases = is_array($meta) ? array_map('strval', $meta['aliases'] ?? []) : [];
            $aliases = array_filter(array_merge(
                [$key, $label, str_replace('منهج ', '', $label), str_replace('منهج', '', $label)],
                $extraAliases
            ));
            foreach ($aliases as $alias) {
                $alias = trim(mb_strtolower((string) $alias));
                if ($alias === '') {
                    continue;
                }
                if (str_contains($haystack, $alias) || in_array($alias, $tokens, true)) {
                    $curriculumType = (string) $key;
                    break 2;
                }
                // تطابق جزئي للكلمات الطويلة مثل "سعودي" داخل "منهج سعودي"
                foreach ($tokens as $token) {
                    if (mb_strlen($token) >= 3 && (str_contains($alias, $token) || str_contains($token, $alias))) {
                        $curriculumType = (string) $key;
                        break 3;
                    }
                }
            }
        }

        if (Schema::hasTable('academic_years')) {
            $years = AcademicYear::query()->publicCatalog()->ordered()->get(['id', 'name', 'slug']);
            foreach ($years as $year) {
                $name = mb_strtolower((string) $year->name);
                foreach ($tokens as $token) {
                    if (mb_strlen($token) >= 3 && str_contains($name, $token)) {
                        $yearId = (int) $year->id;
                        break 2;
                    }
                }
            }
        }

        foreach ($tokens as $token) {
            if (in_array($token, $stop, true)) {
                continue;
            }
            $isCurriculumAlias = false;
            if ($curriculumType) {
                foreach ($types as $key => $meta) {
                    $label = mb_strtolower((string) (is_array($meta) ? ($meta['label'] ?? $key) : $meta));
                    if ($token === mb_strtolower((string) $key) || str_contains($label, $token)) {
                        $isCurriculumAlias = true;
                        break;
                    }
                }
            }
            if ($isCurriculumAlias) {
                continue;
            }
            $remaining[] = $token;
        }

        return [
            'curriculum_type' => $curriculumType,
            'year_id' => $yearId,
            'content_tokens' => array_values(array_unique($remaining)),
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<string>  $columns
     */
    private function requireAllTokens(Builder $query, array $tokens, array $columns): void
    {
        foreach ($tokens as $token) {
            $like = '%'.$token.'%';
            $query->where(function (Builder $inner) use ($like, $columns) {
                foreach ($columns as $i => $column) {
                    if ($i === 0) {
                        $inner->where($column, 'like', $like);
                    } else {
                        $inner->orWhere($column, 'like', $like);
                    }
                }
            });
        }
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<string>  $columns
     */
    private function requireAnyToken(Builder $query, array $tokens, array $columns): void
    {
        $query->where(function (Builder $outer) use ($tokens, $columns) {
            foreach ($tokens as $ti => $token) {
                $like = '%'.$token.'%';
                $outer->orWhere(function (Builder $inner) use ($like, $columns) {
                    foreach ($columns as $i => $column) {
                        if ($i === 0) {
                            $inner->where($column, 'like', $like);
                        } else {
                            $inner->orWhere($column, 'like', $like);
                        }
                    }
                });
            }
        });
    }
}
