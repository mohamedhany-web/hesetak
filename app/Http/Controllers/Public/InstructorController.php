<?php

namespace App\Http\Controllers\Public;

use App\Helpers\VideoHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\ConsultationSetting;
use App\Models\InstructorProfile;
use App\Models\OneToOneWeeklyAvailability;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\OneToOneAvailabilityService;
use App\Services\StudentEntitlementService;
use App\Support\HesetakMatchCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class InstructorController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $skill = trim((string) $request->query('skill', ''));
        $stage = trim((string) $request->query('stage', ''));
        $curriculum = trim((string) $request->query('curriculum', ''));
        $sort = (string) $request->query('sort', 'newest');
        if (! in_array($sort, ['newest', 'name', 'courses'], true)) {
            $sort = 'newest';
        }

        $allowedCurriculum = HesetakMatchCatalog::allowedCurriculumTypeKeys();
        if ($curriculum !== '' && ! in_array($curriculum, $allowedCurriculum, true)) {
            $curriculum = '';
        }

        // طالب مسجّل بمرحلة/منهج → تطبيق افتراضي إن لم يُحدَّد فلتر في الرابط.
        $viewer = \App\Support\StudentLearningProfile::fromAuth();
        if ($stage === '' && $viewer->hasStage()) {
            $stage = (string) $viewer->yearId;
        }
        if ($curriculum === '' && $viewer->hasCurriculum()) {
            $curriculum = (string) $viewer->curriculumType;
        }

        $stages = collect();
        if (Schema::hasTable('academic_years')) {
            $stages = AcademicYear::query()
                ->publicCatalog()
                ->ordered()
                ->get(['id', 'name', 'slug', 'tagline']);
        }

        $stageYear = null;
        if ($stage !== '' && $stages->isNotEmpty()) {
            $stageYear = $stages->first(fn (AcademicYear $y) => $y->slug === $stage || (string) $y->id === $stage);
            if (! $stageYear) {
                $stage = '';
            } else {
                $stage = (string) $stageYear->slug;
            }
        }

        $subjectOptions = collect();
        if (Schema::hasTable('academic_subjects')) {
            $subjectQuery = AcademicSubject::query()->active()->ordered();
            if ($stageYear) {
                $subjectQuery->where('academic_year_id', $stageYear->id);
            } else {
                $subjectQuery->whereHas('academicYear', fn ($y) => $y->publicCatalog());
            }
            $subjectOptions = $subjectQuery
                ->get(['id', 'name', 'slug', 'academic_year_id'])
                ->unique(fn ($s) => mb_strtolower($s->name))
                ->values();
        }

        $baseQuery = InstructorProfile::query()
            ->approved()
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['instructor', 'teacher'])
                    ->where('is_active', true);
            })
            ->with(['user:id,name,role,is_active']);

        $allForFacets = (clone $baseQuery)->get(['id', 'user_id', 'skills', 'headline', 'curriculum_types']);
        $skillFacets = $this->buildSkillFacets($allForFacets);
        $totalTeachers = $allForFacets->count();

        $query = clone $baseQuery;

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('instructor_profiles.headline', 'like', $like)
                    ->orWhere('instructor_profiles.bio', 'like', $like)
                    ->orWhere('instructor_profiles.skills', 'like', $like)
                    ->orWhere('instructor_profiles.experience', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
            });
        }

        if ($skill !== '') {
            $matchedSubjectIds = collect();
            if (Schema::hasTable('academic_subjects')) {
                $subjectMatch = AcademicSubject::query()->active();
                if ($stageYear) {
                    $subjectMatch->where('academic_year_id', $stageYear->id);
                } else {
                    $subjectMatch->whereHas('academicYear', fn ($y) => $y->publicCatalog());
                }
                $matchedSubjectIds = $subjectMatch
                    ->where(function ($s) use ($skill) {
                        $s->where('slug', $skill)
                            ->orWhere('id', ctype_digit($skill) ? (int) $skill : 0)
                            ->orWhere('name', $skill);
                    })
                    ->pluck('id');
            }

            $query->where(function ($inner) use ($matchedSubjectIds, $skill) {
                if ($matchedSubjectIds->isNotEmpty() && Schema::hasColumn('instructor_profiles', 'teaching_subject_ids')) {
                    $inner->where(function ($structured) use ($matchedSubjectIds) {
                        foreach ($matchedSubjectIds as $sid) {
                            $structured->orWhereJsonContains('instructor_profiles.teaching_subject_ids', (int) $sid)
                                ->orWhereJsonContains('instructor_profiles.teaching_subject_ids', (string) $sid);
                        }

                        $courseInstructorIds = AdvancedCourse::query()
                            ->where('is_active', true)
                            ->whereIn('academic_subject_id', $matchedSubjectIds)
                            ->pluck('instructor_id')
                            ->filter()
                            ->unique()
                            ->values();
                        if ($courseInstructorIds->isNotEmpty()) {
                            $structured->orWhereIn('instructor_profiles.user_id', $courseInstructorIds);
                        }
                    });
                } else {
                    // Strict: unknown subject slug / no structured column → empty result unless exact skill token in skills JSON list.
                    $skillLike = '%'.$skill.'%';
                    $inner->where('instructor_profiles.skills', 'like', $skillLike);
                }
            });
        }

        if ($stageYear) {
            $yearId = (int) $stageYear->id;
            $query->whereHas('user', function ($u) use ($yearId) {
                $u->whereHas('teachingLearningPaths', fn ($y) => $y->where('academic_years.id', $yearId));
            });
        }

        $profiles = $query
            ->orderByDesc('instructor_profiles.reviewed_at')
            ->orderByDesc('instructor_profiles.id')
            ->get();

        if ($curriculum !== '') {
            $profiles = $profiles->filter(function (InstructorProfile $profile) use ($curriculum) {
                $keys = $profile->curriculumTypeKeys();

                return $keys !== [] && (
                    in_array($curriculum, $keys, true)
                    || HesetakMatchCatalog::profileMatchesCurriculumType($keys, $curriculum, '')
                );
            })->values();
        }

        $courseCounts = AdvancedCourse::query()
            ->where('is_active', true)
            ->whereIn('instructor_id', $profiles->pluck('user_id')->filter()->unique()->values())
            ->selectRaw('instructor_id, COUNT(*) as aggregate')
            ->groupBy('instructor_id')
            ->pluck('aggregate', 'instructor_id');

        $profiles->each(function (InstructorProfile $profile) use ($courseCounts) {
            $profile->setAttribute('courses_count', (int) ($courseCounts[$profile->user_id] ?? 0));
        });

        if ($sort === 'name') {
            $profiles = $profiles->sortBy(fn (InstructorProfile $p) => mb_strtolower((string) ($p->user->name ?? '')), SORT_NATURAL)->values();
        } elseif ($sort === 'courses') {
            $profiles = $profiles->sortByDesc(fn (InstructorProfile $p) => (int) ($p->courses_count ?? 0))->values();
        }

        $consultationSetting = ConsultationSetting::current();

        $instructorIds = $profiles->pluck('user_id')->filter()->unique()->values();
        $featuredCourses = $instructorIds->isEmpty()
            ? collect()
            : AdvancedCourse::query()
                ->where('is_active', true)
                ->whereIn('instructor_id', $instructorIds)
                ->with(['instructor:id,name', 'courseCategory:id,name'])
                ->withCount('lessons')
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();

        $filters = [
            'q' => $q,
            'skill' => $skill,
            'stage' => $stage,
            'curriculum' => $curriculum,
            'sort' => $sort,
        ];

        $curriculumTypes = HesetakMatchCatalog::curriculumTypes();

        return view('instructors.index', compact(
            'profiles',
            'consultationSetting',
            'featuredCourses',
            'filters',
            'skillFacets',
            'totalTeachers',
            'stages',
            'subjectOptions',
            'curriculumTypes'
        ));
    }

    /**
     * @param  Collection<int, InstructorProfile>  $profiles
     * @return list<array{label:string,count:int}>
     */
    private function buildSkillFacets(Collection $profiles): array
    {
        $counts = [];
        foreach ($profiles as $profile) {
            foreach ($profile->skills_list as $item) {
                $label = trim((string) $item);
                if ($label === '' || mb_strlen($label) > 28) {
                    continue;
                }
                $key = mb_strtolower($label);
                if (! isset($counts[$key])) {
                    $counts[$key] = ['label' => $label, 'count' => 0];
                }
                $counts[$key]['count']++;
            }
        }

        uasort($counts, fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcmp($a['label'], $b['label']));

        return array_values(array_slice($counts, 0, 12));
    }

    public function show(User $instructor)
    {
        if (! $instructor->isInstructor()) {
            abort(404);
        }
        $profile = InstructorProfile::where('user_id', $instructor->id)->approved()->with('user')->firstOrFail();
        $courses = AdvancedCourse::where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->withCount('lessons')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();

        $groupCourses = $courses->filter(fn ($c) => ! $c->isOneToOne())->values();
        $oneToOneCourses = $courses->filter(fn ($c) => $c->isOneToOne())->values();

        // Collective/group tutoring surface removed — keep empty for view compatibility
        $privateGroups = collect();

        $consultationSetting = ConsultationSetting::current();

        $weeklyRules = OneToOneWeeklyAvailability::query()
            ->where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $weeklyCalendar = $this->buildWeeklyCalendar($weeklyRules);

        $introVideoUrl = trim((string) ($instructor->portfolio_intro_video_url ?? ''));
        $introEmbedUrl = VideoHelper::getEmbedUrl($introVideoUrl);
        $introDirectVideo = VideoHelper::getDirectVideoUrl($introVideoUrl);

        $canBook = false;
        $unitsLeft = 0;
        $hasActivePackage = false;
        if (auth()->check() && auth()->user()->isStudent()) {
            $hasActivePackage = StudentEntitlementService::hasActivePrivatePackage((int) auth()->id());
            $entitlement = StudentEntitlementService::availableFor(
                (int) auth()->id(),
                ServicePackage::SCOPE_PRIVATE_LESSONS
            );
            if ($entitlement) {
                $unitsLeft = StudentEntitlementService::bookableUnitsLeft($entitlement);
                $canBook = $unitsLeft > 0;
            }
        }

        $bookableSlots = collect();
        if ($canBook || $hasActivePackage) {
            $bookableSlots = OneToOneAvailabilityService::availableSlots(
                (int) $instructor->id,
                now()->addHour(),
                now()->addWeeks(3),
                \App\Models\OneToOneSession::defaultDurationMinutes()
            )->take(24);
        }

        $packagesUrl = route('public.pricing');

        $teachingYears = collect();
        if (Schema::hasTable('academic_year_instructors')) {
            $teachingYears = AcademicYear::query()
                ->publicCatalog()
                ->whereHas('instructors', fn ($q) => $q->where('users.id', $instructor->id))
                ->ordered()
                ->get(['id', 'name', 'slug', 'tagline']);
        }

        $curriculumTypeKeys = $profile->curriculumTypeKeys();
        $curriculumTypeLabels = collect($curriculumTypeKeys)
            ->map(fn ($key) => HesetakMatchCatalog::curriculumTypeLabel($key))
            ->filter()
            ->values()
            ->all();

        if ($curriculumTypeLabels === []) {
            $hay = implode(' ', [(string) $profile->headline, (string) $profile->skills, (string) $profile->bio]);
            foreach (HesetakMatchCatalog::curriculumTypes() as $meta) {
                if (HesetakMatchCatalog::profileMatchesCurriculumType(null, $meta['key'], $hay)) {
                    $curriculumTypeLabels[] = $meta['label'];
                }
            }
        }

        return view('instructors.show', compact(
            'profile',
            'courses',
            'groupCourses',
            'oneToOneCourses',
            'privateGroups',
            'consultationSetting',
            'weeklyCalendar',
            'introVideoUrl',
            'introEmbedUrl',
            'introDirectVideo',
            'canBook',
            'hasActivePackage',
            'unitsLeft',
            'bookableSlots',
            'packagesUrl',
            'teachingYears',
            'curriculumTypeLabels'
        ));
    }

    /**
     * @param  Collection<int, OneToOneWeeklyAvailability>  $rules
     * @return array<int, array{day:int,label:string,times:list<string>}>
     */
    private function buildWeeklyCalendar(Collection $rules): array
    {
        $duration = (int) config('private_lessons.lesson_duration_minutes', 50);
        $isRtl = app()->getLocale() === 'ar';
        $labels = $isRtl
            ? [1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت', 7 => 'الأحد']
            : [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        $days = [];
        foreach ([1, 2, 3, 4, 5, 6, 7] as $day) {
            $dayRules = $rules->where('day_of_week', $day);
            $times = [];
            foreach ($dayRules as $rule) {
                $start = substr((string) $rule->start_time, 0, 5);
                $end = substr((string) $rule->end_time, 0, 5);
                $slotMins = (int) ($rule->slot_duration_minutes ?: $duration);
                if ($slotMins < 30) {
                    $slotMins = $duration;
                }
                $cursor = strtotime('1970-01-01 '.$start.':00');
                $limit = strtotime('1970-01-01 '.$end.':00');
                while ($cursor !== false && $limit !== false && ($cursor + ($slotMins * 60)) <= $limit) {
                    $times[] = date('g:i A', $cursor);
                    $cursor += max($slotMins, 60) * 60;
                    if (count($times) >= 6) {
                        break;
                    }
                }
                if (count($times) >= 6) {
                    break;
                }
            }
            if ($times !== []) {
                $days[] = [
                    'day' => $day,
                    'label' => $labels[$day] ?? (string) $day,
                    'times' => array_values(array_unique($times)),
                ];
            }
        }

        return $days;
    }
}
