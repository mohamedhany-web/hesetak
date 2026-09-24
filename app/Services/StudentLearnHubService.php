<?php

namespace App\Services;

use App\Helpers\VideoHelper;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\InstructorProfile;
use App\Models\OneToOneSession;
use App\Models\ServicePackage;
use App\Models\StudentServiceEntitlement;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupBooking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class StudentLearnHubService
{
    /**
     * @param  array{q?:string,subject_id?:int|string,year_id?:int|string,type?:string,bookable?:string}  $filters
     * @return array<string, mixed>
     */
    public function hub(User $student, string $tab = 'private', array $filters = []): array
    {
        // حصتك: فردي + رصيدي فقط — لا مجموعات ولا مدرسة
        $tab = in_array($tab, ['private', 'mine'], true) ? $tab : 'private';
        $filters = $this->normalizeFilters($filters);

        $privateUnits = $this->unitsLeft($student->id, ServicePackage::SCOPE_PRIVATE_LESSONS);

        $packagesUrl = Route::has('public.pricing')
            ? route('public.pricing')
            : (Route::has('public.service-packages.index')
                ? route('public.service-packages.index')
                : route('dashboard'));

        return [
            'tab' => $tab,
            'filters' => $filters,
            'filter_subjects' => $this->filterSubjects(),
            'filter_years' => collect(),
            'packages_url' => $packagesUrl,
            'private_units' => $privateUnits,
            'collective_units' => 0,
            'individual_units' => 0,
            'global_units' => 0,
            'teachers' => $tab === 'private' ? $this->teachersCatalog($student, $filters) : collect(),
            'groups' => collect(),
            'entitlements' => $tab === 'mine' ? $this->activeEntitlements($student->id) : collect(),
            'upcoming_private' => $this->upcomingPrivateSessions($student->id),
            'upcoming_bookings' => collect(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{q:string,subject_id:int|null,year_id:int|null,type:string,bookable:bool}
     */
    private function normalizeFilters(array $filters): array
    {
        $type = (string) ($filters['type'] ?? '');
        if (! in_array($type, ['individual', 'collective'], true)) {
            $type = '';
        }

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'subject_id' => (($id = (int) ($filters['subject_id'] ?? 0)) > 0) ? $id : null,
            'year_id' => (($id = (int) ($filters['year_id'] ?? 0)) > 0) ? $id : null,
            'type' => $type,
            'bookable' => in_array((string) ($filters['bookable'] ?? ''), ['1', 'true', 'yes'], true),
        ];
    }

    /**
     * @return Collection<int, AcademicSubject>
     */
    private function filterSubjects(): Collection
    {
        return AcademicSubject::query()
            ->active()
            ->ordered()
            ->limit(120)
            ->get(['id', 'name', 'academic_year_id']);
    }

    /**
     * @return Collection<int, AcademicYear>
     */
    private function filterYears(): Collection
    {
        return AcademicYear::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name']);
    }

    /**
     * @return array{
     *   profile: InstructorProfile,
     *   instructor: User,
     *   can_book: bool,
     *   units_left: int,
     *   bookable_slots: Collection,
     *   weekly_calendar: array,
     *   group_courses: Collection,
     *   one_to_one_courses: Collection,
     *   packages_url: string,
     *   photo_url: string
     * }
     */
    public function teacherPage(User $student, User $instructor): array
    {
        if (! $instructor->isInstructor()) {
            abort(404);
        }

        $profile = InstructorProfile::query()
            ->approved()
            ->where('user_id', $instructor->id)
            ->with(['user:id,name,email,phone,profile_image,portfolio_intro_video_url,role,is_active'])
            ->firstOrFail();

        $entitlement = StudentEntitlementService::availableFor(
            (int) $student->id,
            ServicePackage::SCOPE_PRIVATE_LESSONS
        );
        $unitsLeft = $entitlement ? StudentEntitlementService::bookableUnitsLeft($entitlement) : 0;
        $canBook = $unitsLeft > 0;

        $bookableSlots = collect();
        if ($canBook) {
            $bookableSlots = OneToOneAvailabilityService::availableSlots(
                (int) $instructor->id,
                now()->addHour(),
                now()->addWeeks(3),
                OneToOneSession::defaultDurationMinutes()
            )->take(24);
        }

        $courses = AdvancedCourse::query()
            ->where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->withCount('lessons')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();

        // حصتك: كورسات فردية فقط في ملف المعلم الداخلي
        $oneToOneCourses = $courses->filter(fn ($c) => $c->isOneToOne())->values();

        $photo = $profile->photo_url
            ?: ($instructor->profile_image_url ?: asset('img/student-timeline/avatar.png'));

        $introVideoUrl = trim((string) ($instructor->portfolio_intro_video_url ?? ''));
        $introEmbedUrl = VideoHelper::getEmbedUrl($introVideoUrl);
        $introDirectVideo = VideoHelper::getDirectVideoUrl($introVideoUrl);
        $introThumb = VideoHelper::getThumbnail($introVideoUrl) ?: $photo;

        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $teachingChips = $this->teachingMetaLabels($instructor, $locale);
        if ($instructor->gender && isset(config('private_lessons.genders')[$instructor->gender])) {
            array_unshift(
                $teachingChips,
                (string) (config("private_lessons.genders.{$instructor->gender}.{$locale}")
                    ?? config("private_lessons.genders.{$instructor->gender}.en"))
            );
        }

        $packagesUrl = Route::has('public.pricing')
            ? route('public.pricing')
            : (Route::has('public.service-packages.index')
                ? route('public.service-packages.index')
                : route('dashboard'));

        return [
            'profile' => $profile,
            'instructor' => $instructor,
            'can_book' => $canBook,
            'units_left' => $unitsLeft,
            'bookable_slots' => $bookableSlots,
            'weekly_calendar' => $this->buildWeeklyCalendar($instructor->id),
            'group_courses' => collect(),
            'one_to_one_courses' => $oneToOneCourses,
            'packages_url' => $packagesUrl,
            'photo_url' => $photo,
            'intro_video_url' => $introVideoUrl !== '' ? $introVideoUrl : null,
            'intro_embed_url' => $introEmbedUrl,
            'intro_direct_video' => $introDirectVideo,
            'intro_video_thumb' => $introThumb,
            'has_intro_video' => filled($introEmbedUrl) || filled($introDirectVideo),
            'teaching_chips' => $teachingChips,
            'consultation_price' => $profile->consultation_price_egp !== null
                ? (float) $profile->consultation_price_egp
                : null,
            'consultation_duration' => $profile->consultation_duration_minutes
                ? (int) $profile->consultation_duration_minutes
                : null,
            'courses_count' => $oneToOneCourses->count(),
        ];
    }

    /**
     * @return list<string>
     */
    private function teachingMetaLabels(User $instructor, string $locale): array
    {
        $meta = $instructor->privateTeachingMeta();
        $labels = [];

        foreach (['subjects', 'age_groups', 'languages', 'specializations'] as $group) {
            $map = config("private_lessons.{$group}", []);
            foreach (($meta[$group] ?? []) as $key) {
                if (! is_string($key) || $key === '') {
                    continue;
                }
                $entry = $map[$key] ?? null;
                if (is_array($entry)) {
                    $labels[] = (string) ($entry[$locale] ?? $entry['en'] ?? $key);
                } else {
                    $labels[] = $key;
                }
            }
        }

        return array_values(array_unique($labels));
    }

    public function unitsLeft(int $userId, string $scope): int
    {
        $entitlement = StudentEntitlementService::availableFor($userId, $scope);

        return $entitlement ? StudentEntitlementService::bookableUnitsLeft($entitlement) : 0;
    }

    /**
     * @param  array{q:string,subject_id:int|null,year_id:int|null,type:string,bookable:bool}  $filters
     */
    private function teachersCatalog(User $student, array $filters): LengthAwarePaginator
    {
        $privateUnits = $this->unitsLeft($student->id, ServicePackage::SCOPE_PRIVATE_LESSONS);
        $q = $filters['q'];
        $subjectId = $filters['subject_id'];

        $query = InstructorProfile::query()
            ->approved()
            ->whereHas('user', function ($userQuery) {
                $userQuery->whereIn('role', ['instructor', 'teacher'])
                    ->where('is_active', true);
            })
            ->when($q !== '', function ($profileQuery) use ($q) {
                $like = '%'.$q.'%';
                $profileQuery->where(function ($inner) use ($like) {
                    $inner->where('headline', 'like', $like)
                        ->orWhere('bio', 'like', $like)
                        ->orWhere('skills', 'like', $like)
                        ->orWhere('experience', 'like', $like)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
                });
            })
            ->when($subjectId, function ($profileQuery) use ($subjectId) {
                $profileQuery->whereHas('user', function ($userQuery) use ($subjectId) {
                    $userQuery->whereExists(function ($sub) use ($subjectId) {
                        $sub->selectRaw('1')
                            ->from('advanced_courses')
                            ->whereColumn('advanced_courses.instructor_id', 'users.id')
                            ->where('advanced_courses.is_active', true)
                            ->where('advanced_courses.academic_subject_id', $subjectId);
                    });
                });
            })
            ->with(['user:id,name,role,is_active,profile_image'])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(24)->withQueryString();

        $courseCounts = AdvancedCourse::query()
            ->where('is_active', true)
            ->whereIn('instructor_id', $paginator->getCollection()->pluck('user_id')->filter()->unique()->values())
            ->selectRaw('instructor_id, COUNT(*) as aggregate')
            ->groupBy('instructor_id')
            ->pluck('aggregate', 'instructor_id');

        $paginator->setCollection(
            $paginator->getCollection()->map(function (InstructorProfile $profile) use ($courseCounts, $privateUnits) {
                $user = $profile->user;
                $photo = $profile->photo_url
                    ?: ($user?->profile_image_url ?: asset('img/student-timeline/avatar.png'));

                return [
                    'id' => (int) $profile->user_id,
                    'name' => $user?->name ?? '—',
                    'headline' => $profile->headline_clean ?: '',
                    'skills' => array_slice($profile->skills_list ?? [], 0, 4),
                    'photo' => $photo,
                    'courses_count' => (int) ($courseCounts[$profile->user_id] ?? 0),
                    'units_left' => $privateUnits,
                    'can_book' => $privateUnits > 0,
                    'url' => route('public.instructors.show', $user ?? $profile->user_id),
                ];
            })->values()
        );

        return $paginator;
    }

    /**
     * @param  array{q:string,subject_id:int|null,year_id:int|null,type:string,bookable:bool}  $filters
     */
    private function groupsCatalog(User $student, array $filters): LengthAwarePaginator
    {
        $q = $filters['q'];
        $subjectId = $filters['subject_id'];
        $yearId = $filters['year_id'];
        $type = $filters['type'];

        $query = TutoringGroup::query()
            ->active()
            ->with(['instructor:id,name,profile_image', 'academicYear:id,name', 'academicSubject:id,name'])
            ->when($type !== '', fn ($g) => $g->where('type', $type))
            ->when($subjectId, fn ($g) => $g->where('academic_subject_id', $subjectId))
            ->when($yearId, fn ($g) => $g->where('academic_year_id', $yearId))
            ->when($q !== '', function ($g) use ($q) {
                $like = '%'.$q.'%';
                $g->where(function ($inner) use ($like) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('instructor', fn ($u) => $u->where('name', 'like', $like))
                        ->orWhereHas('academicSubject', fn ($s) => $s->where('name', 'like', $like))
                        ->orWhereHas('academicYear', fn ($y) => $y->where('name', 'like', $like));
                });
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(24)->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(function (TutoringGroup $group) use ($student) {
                $scope = StudentEntitlementService::scopeForTutoringGroup($group);
                $entitlement = StudentEntitlementService::availableFor(
                    (int) $student->id,
                    $scope,
                    (int) $group->id
                );
                $unitsLeft = $entitlement ? StudentEntitlementService::bookableUnitsLeft($entitlement) : 0;
                $canBook = $unitsLeft > 0;

                $slots = collect();
                if ($canBook) {
                    $slots = TutoringGroupAvailabilityService::availableSlots(
                        $group,
                        now()->addHour(),
                        now()->addDays(14)
                    )->take(8);
                }

                $instructor = $group->instructor;

                return [
                    'id' => (int) $group->id,
                    'title' => $group->title,
                    'type' => $group->type,
                    'type_label' => $group->typeLabel(),
                    'image' => $group->imageUrl(),
                    'instructor_name' => $instructor?->name ?? '—',
                    'instructor_photo' => $instructor?->profile_image_url ?: asset('img/student-timeline/avatar.png'),
                    'year' => $group->academicYear?->name,
                    'subject' => $group->academicSubject?->name,
                    'duration' => (int) ($group->duration_minutes ?? 60),
                    'units_left' => $unitsLeft,
                    'can_book' => $canBook,
                    'slots' => $slots->values(),
                    'teacher_url' => $instructor ? route('public.instructors.show', $instructor) : null,
                ];
            })->values()
        );

        return $paginator;
    }

    /**
     * @return Collection<int, StudentServiceEntitlement>
     */
    private function activeEntitlements(int $userId): Collection
    {
        StudentEntitlementService::expireStaleForUser($userId);

        return StudentServiceEntitlement::query()
            ->forUser($userId)
            ->active()
            ->whereColumn('units_used', '<', 'units_total')
            ->whereHas('servicePackage', function ($q) {
                $q->whereIn('scope', [
                    ServicePackage::SCOPE_PRIVATE_LESSONS,
                    ServicePackage::SCOPE_GLOBAL,
                ]);
            })
            ->with(['servicePackage:id,name,scope'])
            ->orderBy('expires_at')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, OneToOneSession>
     */
    private function upcomingPrivateSessions(int $userId): Collection
    {
        return OneToOneSession::query()
            ->where('student_id', $userId)
            ->whereIn('status', [OneToOneSession::STATUS_SCHEDULED, OneToOneSession::STATUS_PENDING])
            ->with(['instructor:id,name,profile_image', 'course:id,title'])
            ->orderByRaw("CASE status WHEN 'scheduled' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, TutoringGroupBooking>
     */
    private function upcomingGroupBookings(int $userId): Collection
    {
        return TutoringGroupBooking::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                TutoringGroupBooking::STATUS_CONFIRMED,
                TutoringGroupBooking::STATUS_PENDING,
            ])
            ->where('starts_at', '>=', now()->subHour())
            ->with(['tutoringGroup:id,title,slug,type', 'instructor:id,name'])
            ->orderBy('starts_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return list<array{day:int,label:string,times:list<string>}>
     */
    private function buildWeeklyCalendar(int $instructorId): array
    {
        $rules = \App\Models\OneToOneWeeklyAvailability::query()
            ->where('instructor_id', $instructorId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

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
