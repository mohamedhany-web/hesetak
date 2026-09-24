<?php

namespace App\Services;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\InstructorProfile;
use App\Models\OneToOneWeeklyAvailability;
use App\Models\TutoringGroup;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * تصفح عام بنفس فكرة صفحة تعلّم الطالب: معلمون معتمدون + مجموعات نشطة.
 */
class PublicLearnCatalogService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function catalog(string $tab, array $filters): array
    {
        $tab = in_array($tab, ['private', 'groups'], true) ? $tab : 'private';
        $filters = $this->normalizeFilters($filters);

        return [
            'tab' => $tab,
            'filters' => $filters,
            'filter_subjects' => $this->filterSubjects(),
            'filter_years' => $this->filterYears(),
            'teachers' => $tab === 'private' ? $this->teachers($filters) : collect(),
            'groups' => $tab === 'groups' ? $this->groups($filters) : collect(),
            'lesson_duration' => (int) config('private_lessons.lesson_duration_minutes', 50),
            'filter_catalog' => config('private_lessons'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   q:string,subject_id:?int,year_id:?int,type:string,subject:string,age:string,
     *   gender:string,language:string,specialty:string,availability:string
     * }
     */
    public function normalizeFilters(array $filters): array
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
            'subject' => trim((string) ($filters['subject'] ?? '')),
            'age' => trim((string) ($filters['age'] ?? '')),
            'gender' => trim((string) ($filters['gender'] ?? '')),
            'language' => trim((string) ($filters['language'] ?? '')),
            'specialty' => trim((string) ($filters['specialty'] ?? '')),
            'availability' => trim((string) ($filters['availability'] ?? '')),
        ];
    }

    /**
     * @return Collection<int, AcademicSubject>
     */
    public function filterSubjects(): Collection
    {
        if (! Schema::hasTable('academic_subjects')) {
            return collect();
        }

        return AcademicSubject::query()
            ->active()
            ->ordered()
            ->limit(120)
            ->get(['id', 'name', 'academic_year_id']);
    }

    /**
     * @return Collection<int, AcademicYear>
     */
    public function filterYears(): Collection
    {
        if (! Schema::hasTable('academic_years')) {
            return collect();
        }

        return AcademicYear::query()
            ->when(Schema::hasColumn('academic_years', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->when(
                Schema::hasColumn('academic_years', 'order'),
                fn ($q) => $q->orderBy('order')->orderBy('name'),
                fn ($q) => $q->orderBy('name')
            )
            ->limit(40)
            ->get(['id', 'name']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function teachers(array $filters): LengthAwarePaginator
    {
        $filters = $this->normalizeFilters($filters);
        $q = $filters['q'];
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';

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
            ->when($filters['subject_id'] && Schema::hasTable('advanced_courses'), function ($profileQuery) use ($filters) {
                $subjectId = $filters['subject_id'];
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
            ->when($filters['year_id'] && Schema::hasTable('advanced_courses'), function ($profileQuery) use ($filters) {
                $yearId = $filters['year_id'];
                $profileQuery->whereHas('user', function ($userQuery) use ($yearId) {
                    $userQuery->whereExists(function ($sub) use ($yearId) {
                        $sub->selectRaw('1')
                            ->from('advanced_courses')
                            ->whereColumn('advanced_courses.instructor_id', 'users.id')
                            ->where('advanced_courses.is_active', true)
                            ->where('advanced_courses.academic_year_id', $yearId);
                    });
                });
            })
            ->when($filters['gender'] !== '' && in_array($filters['gender'], ['male', 'female'], true), function ($profileQuery) use ($filters) {
                $profileQuery->whereHas('user', fn ($u) => $u->where('gender', $filters['gender']));
            });

        foreach (['subject' => 'subjects', 'age' => 'age_groups', 'language' => 'languages', 'specialty' => 'specializations'] as $param => $metaKey) {
            if ($filters[$param] === '' || ! Schema::hasColumn('users', 'private_teaching_meta')) {
                continue;
            }
            $value = $filters[$param];
            $query->whereHas('user', function ($u) use ($metaKey, $value) {
                $u->whereJsonContains("private_teaching_meta->{$metaKey}", $value);
            });
        }

        $query->with(['user'])->orderByDesc('reviewed_at')->orderByDesc('id');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(24)->withQueryString();

        $ids = $paginator->getCollection()->pluck('user_id')->filter()->unique()->values();
        $courseCounts = collect();
        $subjectNames = collect();
        if (Schema::hasTable('advanced_courses') && $ids->isNotEmpty()) {
            $courseCounts = AdvancedCourse::query()
                ->where('is_active', true)
                ->whereIn('instructor_id', $ids)
                ->selectRaw('instructor_id, COUNT(*) as aggregate')
                ->groupBy('instructor_id')
                ->pluck('aggregate', 'instructor_id');

            $subjectNames = AdvancedCourse::query()
                ->where('is_active', true)
                ->whereIn('instructor_id', $ids)
                ->with('academicSubject:id,name')
                ->get(['id', 'instructor_id', 'academic_subject_id', 'title'])
                ->groupBy('instructor_id')
                ->map(function (Collection $rows) {
                    return $rows->map(fn ($c) => $c->academicSubject?->name ?: $c->title)
                        ->filter()
                        ->unique()
                        ->take(3)
                        ->values()
                        ->all();
                });
        }

        $weeklyByInstructor = collect();
        if (Schema::hasTable('one_to_one_weekly_availability') && $ids->isNotEmpty()) {
            $weeklyByInstructor = OneToOneWeeklyAvailability::query()
                ->whereIn('instructor_id', $ids)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy('instructor_id');
        }

        if ($filters['availability'] !== '') {
            $paginator->setCollection(
                $paginator->getCollection()->filter(function (InstructorProfile $profile) use ($weeklyByInstructor, $filters) {
                    $rules = $weeklyByInstructor[$profile->user_id] ?? collect();
                    if ($rules->isEmpty()) {
                        return true;
                    }

                    return $this->matchesAvailabilityFilter($rules, $filters['availability']);
                })->values()
            );
        }

        $paginator->setCollection(
            $paginator->getCollection()->map(function (InstructorProfile $profile) use ($courseCounts, $subjectNames, $weeklyByInstructor, $locale) {
                $user = $profile->user;
                $photo = $profile->photo_url
                    ?: ($user?->profile_image_url ?: asset('img/student-timeline/avatar.png'));
                $calendar = $this->buildWeeklyCalendar($weeklyByInstructor[$profile->user_id] ?? collect());
                $chips = $this->teachingMetaLabels($user, $locale);
                $skills = array_slice($profile->skills_list ?? [], 0, 4);

                return [
                    'id' => (int) $profile->user_id,
                    'name' => $user?->name ?? '—',
                    'headline' => $profile->headline_clean ?: '',
                    'bio' => Str::limit($profile->bio_clean ?: '', 90),
                    'skills' => $skills,
                    'chips' => $chips,
                    'subjects' => $subjectNames[$profile->user_id] ?? [],
                    'photo' => $photo,
                    'has_video' => filled($user?->portfolio_intro_video_url),
                    'courses_count' => (int) ($courseCounts[$profile->user_id] ?? 0),
                    'calendar' => $calendar,
                    'has_children' => in_array('children', $user?->privateTeachingMeta()['specializations'] ?? [], true),
                    'url' => route('public.instructors.show', $user ?? $profile->user_id),
                ];
            })->values()
        );

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function groups(array $filters): LengthAwarePaginator
    {
        $filters = $this->normalizeFilters($filters);
        if (! Schema::hasTable('tutoring_groups')) {
            return new Paginator([], 0, 24, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        $q = $filters['q'];

        $query = TutoringGroup::query()
            ->active()
            ->with(['instructor:id,name,profile_image', 'academicYear:id,name', 'academicSubject:id,name'])
            ->when($filters['type'] !== '', fn ($g) => $g->where('type', $filters['type']))
            ->when($filters['subject_id'], fn ($g) => $g->where('academic_subject_id', $filters['subject_id']))
            ->when($filters['year_id'], fn ($g) => $g->where('academic_year_id', $filters['year_id']))
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
            $paginator->getCollection()->map(function (TutoringGroup $group) {
                $instructor = $group->instructor;
                $photo = $group->imageUrl()
                    ?: ($instructor?->profile_image_url ?: asset('img/student-timeline/avatar.png'));

                return [
                    'id' => (int) $group->id,
                    'title' => $group->title,
                    'type' => $group->type,
                    'type_label' => $group->typeLabel(),
                    'photo' => $photo,
                    'instructor_name' => $instructor?->name ?? '—',
                    'year' => $group->academicYear?->name,
                    'subject' => $group->academicSubject?->name,
                    'duration' => (int) ($group->duration_minutes ?? 60),
                    'price' => $group->formattedPrice(),
                    'url' => route('public.groups.show', $group->slug),
                    'teacher_url' => $instructor ? route('public.instructors.show', $instructor) : null,
                ];
            })->values()
        );

        return $paginator;
    }

    /**
     * @return list<string>
     */
    private function teachingMetaLabels(?User $instructor, string $locale): array
    {
        if (! $instructor) {
            return [];
        }

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
                }
            }
        }

        return array_values(array_unique($labels));
    }

    /**
     * @param  Collection<int, OneToOneWeeklyAvailability>  $rules
     * @return list<array{day:int,label:string,times:list<string>}>
     */
    public function buildWeeklyCalendar(Collection $rules): array
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
                    if (count($times) >= 4) {
                        break;
                    }
                }
                if (count($times) >= 4) {
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

        return array_slice($days, 0, 4);
    }

    /**
     * @param  Collection<int, OneToOneWeeklyAvailability>  $rules
     */
    private function matchesAvailabilityFilter(Collection $rules, string $availability): bool
    {
        if ($availability === 'weekend') {
            return $rules->contains(fn ($r) => in_array((int) $r->day_of_week, [6, 7], true));
        }

        foreach ($rules as $rule) {
            $hour = (int) substr((string) $rule->start_time, 0, 2);
            if ($availability === 'morning' && $hour < 12) {
                return true;
            }
            if ($availability === 'afternoon' && $hour >= 12 && $hour < 17) {
                return true;
            }
            if ($availability === 'evening' && $hour >= 17) {
                return true;
            }
        }

        return false;
    }
}
