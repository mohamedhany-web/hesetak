<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\CustomServicePackagePricingService;
use App\Services\OneToOneAvailabilityService;
use App\Services\PackageCart;
use App\Services\PublicInstructorDirectory;
use App\Support\AcademicSubjectMatcher;
use App\Support\AppTimezone;
use App\Support\HesetakMatchCatalog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * مسار: الباقة → المعلمون → المواعيد → تفاصيل/سلة → الدفع
 */
class PackageBookingController extends Controller
{
    public function teachers(Request $request, ServicePackage $servicePackage): View
    {
        abort_unless($servicePackage->is_active && ! $servicePackage->tutoring_group_id, 404);

        $servicePackage->load(['academicSubject:id,name,slug,academic_year_id,curriculum_type']);
        if (Schema::hasTable('service_package_subjects')) {
            $servicePackage->load(['subjects' => function ($q) {
                $q->select(
                    'academic_subjects.id',
                    'academic_subjects.name',
                    'academic_subjects.slug',
                    'academic_subjects.academic_year_id',
                    'academic_subjects.curriculum_type'
                );
                if (method_exists(AcademicSubject::class, 'years')) {
                    $q->with(['years:id']);
                }
            }]);
        }

        $isMixed = $servicePackage->isMixedPackage();
        $maxSubjects = $servicePackage->maxSelectableSubjects();

        $yearId = $request->filled('year')
            ? $request->integer('year')
            : ($servicePackage->academic_year_id ? (int) $servicePackage->academic_year_id : null);
        $subjectId = $request->filled('subject')
            ? $request->integer('subject')
            : ($servicePackage->academic_subject_id ? (int) $servicePackage->academic_subject_id : null);
        $curriculum = trim((string) $request->query('curriculum', $servicePackage->curriculum_type ?? ''));

        $years = Schema::hasTable('academic_years')
            ? AcademicYear::query()
                ->publicCatalog()
                ->when($curriculum !== '', fn ($q) => $q->forCurriculumType($curriculum))
                ->ordered()
                ->get(['id', 'name', 'slug', 'curriculum_type'])
            : collect();
        $packageSubjects = $servicePackage->relationLoaded('subjects') && $servicePackage->subjects->isNotEmpty()
            ? $servicePackage->subjects
            : collect(array_filter([$servicePackage->academicSubject]));
        if ($packageSubjects->isNotEmpty()) {
            $subjects = $packageSubjects;
            if ($yearId) {
                $subjects = $subjects->filter(function ($subject) use ($yearId) {
                    if ((int) ($subject->academic_year_id ?? 0) === (int) $yearId) {
                        return true;
                    }
                    if ($subject->relationLoaded('years')) {
                        return $subject->years->contains('id', (int) $yearId);
                    }

                    return method_exists($subject, 'years')
                        && $subject->years()->where('academic_years.id', $yearId)->exists();
                })->values();
            }
            if ($curriculum !== '') {
                $subjects = $subjects->filter(function ($subject) use ($curriculum) {
                    $type = trim((string) ($subject->curriculum_type ?? ''));

                    return $type === '' || $type === $curriculum;
                })->values();
            }
        } else {
            $subjects = Schema::hasTable('academic_subjects')
                ? AcademicSubject::query()
                    ->active()
                    ->when($yearId, fn ($q) => $q->forYear($yearId))
                    ->when($curriculum !== '', fn ($q) => $q->forCurriculumType($curriculum))
                    ->ordered()
                    ->get(['id', 'name', 'slug', 'academic_year_id', 'curriculum_type'])
                : collect();
        }

        $teachersBySubject = $isMixed
            ? $this->buildTeachersBySubject($subjects, $yearId, $curriculum)
            : [];

        $profiles = collect();
        if (! $isMixed) {
            $profiles = $this->teacherProfilesForBooking($yearId, $subjectId, $curriculum);
            if ($profiles->isEmpty() && $yearId) {
                $profiles = $this->teacherProfilesForBooking(null, $subjectId, $curriculum);
            }
        }

        $weeklyOptions = CustomServicePackagePricingService::PRIVATE_WEEKLY_OPTIONS;
        $weekly = $request->integer('weekly') ?: 2;
        $weeks = $request->integer('weeks') ?: 4;
        if ($isMixed) {
            $sessions = max(1, (int) $servicePackage->units_count);
            $weekly = max(1, min(6, $weekly));
            $weeks = max(1, min(16, $weeks));
            $unit = (float) $servicePackage->pricePerUnit();
            $total = round((float) $servicePackage->price, 2);
        } else {
            if (! in_array($weekly, $weeklyOptions, true)) {
                $weekly = 2;
            }
            $weeks = max(1, min(16, $weeks));
            $sessions = $request->filled('sessions')
                ? max(1, min(200, $request->integer('sessions')))
                : ($weekly * $weeks);
            $unit = (float) $servicePackage->pricePerUnit();
            $total = round($unit * $sessions, 2);
        }

        $subjectName = $subjectId
            ? (string) (AcademicSubject::query()->whereKey($subjectId)->value('name') ?? '')
            : '';
        $displayName = $isMixed
            ? $servicePackage->name
            : ($subjectName !== ''
                ? ((app()->getLocale() === 'ar' ? 'باقة ' : '').$subjectName.(app()->getLocale() === 'ar' ? '' : ' package'))
                : $servicePackage->name);
        $scheduleCopy = $isMixed
            ? (app()->getLocale() === 'ar'
                ? sprintf('باقة متنوعة · %d حصة · سعر ثابت', $sessions)
                : sprintf('Mixed package · %d sessions · fixed price', $sessions))
            : (app()->getLocale() === 'ar'
                ? ($sessions > 8
                    ? sprintf('%d حصص أسبوعياً لمدة %d أسابيع (إجمالي %d حصة)', $weekly, $weeks, $sessions)
                    : sprintf('%d حصة · %d أسبوعياً × %d أسابيع', $sessions, $weekly, $weeks))
                : sprintf('%d sessions · %d / week × %d weeks', $sessions, $weekly, $weeks));

        $selectedSubjectIds = collect($request->input('subjects', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        if ($selectedSubjectIds === [] && $subjectId) {
            $selectedSubjectIds = [$subjectId];
        }

        return view('public.package-booking-teachers', [
            'package' => $servicePackage,
            'displayName' => $displayName,
            'scheduleCopy' => $scheduleCopy,
            'lockSubject' => ! $isMixed && (bool) $subjectId,
            'isMixed' => $isMixed,
            'maxSubjects' => $maxSubjects,
            'teachersBySubject' => $teachersBySubject,
            'selectedSubjectIds' => $selectedSubjectIds,
            'years' => $years,
            'subjects' => $subjects,
            'yearId' => $yearId,
            'subjectId' => $subjectId,
            'curriculum' => $curriculum,
            'curriculumTypes' => HesetakMatchCatalog::curriculumTypes(),
            'profiles' => $profiles,
            'weekly' => $weekly,
            'weeks' => $weeks,
            'sessions' => $sessions,
            'unitPrice' => $unit,
            'totalPrice' => $total,
            'weeklyOptions' => $weeklyOptions,
            'cartCount' => PackageCart::count(),
            'mcActive' => 'pricing',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => $displayName.' — '.__('landing.nav.brand'),
        ]);
    }

    public function slots(Request $request, ServicePackage $servicePackage, User $instructor): View|RedirectResponse
    {
        abort_unless($servicePackage->is_active, 404);
        abort_unless(in_array($instructor->role, ['instructor', 'teacher'], true), 404);

        $yearId = $request->integer('year') ?: null;
        $subjectId = $request->integer('subject') ?: null;
        $weekly = max(1, min(6, $request->integer('weekly') ?: 2));
        $weeks = max(1, min(16, $request->integer('weeks') ?: 4));
        if ($servicePackage->isMixedPackage()) {
            $sessions = max(1, (int) $servicePackage->units_count);
            $unit = (float) $servicePackage->pricePerUnit();
            $total = round((float) $servicePackage->price, 2);
        } else {
            $sessions = $request->filled('sessions')
                ? max(1, min(200, $request->integer('sessions')))
                : ($weekly * $weeks);
            $unit = (float) $servicePackage->pricePerUnit();
            $total = round($unit * $sessions, 2);
        }

        $viewerTz = AppTimezone::forUser(auth()->user());
        $slots = OneToOneAvailabilityService::availableSlots(
            (int) $instructor->id,
            now(),
            now()->addDays(21),
            (int) ($servicePackage->session_minutes ?: 60)
        )->map(function (array $slot) use ($viewerTz) {
            /** @var Carbon $starts */
            $starts = $slot['starts_at']->copy()->utc();
            $local = $starts->copy()->timezone($viewerTz);
            $hour = (int) $local->format('G');
            $period = $hour < 12 ? 'AM' : 'PM';
            $periodAr = $hour < 12 ? 'صباحاً (AM)' : 'مساءً (PM)';

            return [
                'starts_at' => $starts->toIso8601String(),
                'label' => $local->locale(app()->getLocale())->translatedFormat('D j M، g:i').' '.$periodAr,
                'period' => $period,
            ];
        })->values();

        $profile = $instructor->instructorProfile;

        return view('public.package-booking-slots', [
            'package' => $servicePackage,
            'instructor' => $instructor,
            'profile' => $profile,
            'slots' => $slots,
            'yearId' => $yearId,
            'subjectId' => $subjectId,
            'weekly' => $weekly,
            'weeks' => $weeks,
            'sessions' => $sessions,
            'unitPrice' => $unit,
            'totalPrice' => $total,
            'viewerTz' => $viewerTz,
            'cartCount' => PackageCart::count(),
            'mcActive' => 'pricing',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => $instructor->name.' — '.$servicePackage->name,
        ]);
    }

    public function addToCart(Request $request, ServicePackage $servicePackage): RedirectResponse
    {
        $request->merge([
            'year_id' => $request->filled('year_id') ? $request->input('year_id') : null,
            'subject_id' => $request->filled('subject_id') ? $request->input('subject_id') : null,
            'instructor_id' => $request->filled('instructor_id') ? $request->input('instructor_id') : null,
            'weekly_sessions' => $request->filled('weekly_sessions') ? $request->input('weekly_sessions') : null,
            'weeks' => $request->filled('weeks') ? $request->input('weeks') : null,
            'sessions' => $request->filled('sessions') ? $request->input('sessions') : null,
            'propose_date' => $request->filled('propose_date') ? $request->input('propose_date') : null,
            'propose_hour' => $request->filled('propose_hour') ? $request->input('propose_hour') : null,
        ]);

        $data = $request->validate([
            'year_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'instructor_id' => ['nullable', 'integer'],
            'weekly_sessions' => ['nullable', 'integer', 'min:1', 'max:6'],
            'weeks' => ['nullable', 'integer', 'min:1', 'max:16'],
            'sessions' => ['nullable', 'integer', 'min:1', 'max:200'],
            'schedule_notes' => ['nullable', 'string', 'max:1000'],
            'suggested_slots' => ['nullable', 'array'],
            'suggested_slots.*' => ['string', 'max:64'],
            'propose_date' => ['nullable', 'date'],
            'propose_hour' => ['nullable', 'integer', 'min:1', 'max:12'],
            'propose_minute' => ['nullable', 'in:00,15,30,45'],
            'propose_period' => ['nullable', 'in:AM,PM'],
        ]);

        $weekly = (int) ($data['weekly_sessions'] ?? 2);
        $weeks = (int) ($data['weeks'] ?? 4);
        $sessions = (int) ($data['sessions'] ?? ($weekly * $weeks));

        $notes = trim((string) ($data['schedule_notes'] ?? ''));
        $picked = collect($data['suggested_slots'] ?? [])->filter()->take(12)->values()->all();
        if ($picked !== []) {
            $notes = trim($notes."\nمواعيد مختارة: ".implode(', ', $picked));
        }
        if (! empty($data['propose_date']) && ! empty($data['propose_hour']) && ! empty($data['propose_period'])) {
            $notes = trim($notes.sprintf(
                "\nموعد مقترح: %s %d:%s %s",
                $data['propose_date'],
                (int) $data['propose_hour'],
                $data['propose_minute'] ?? '00',
                $data['propose_period']
            ));
        }

        $subjectId = isset($data['subject_id']) ? (int) $data['subject_id'] : null;
        $label = $servicePackage->name;
        if ($subjectId) {
            $subjectName = AcademicSubject::query()->whereKey($subjectId)->value('name');
            if ($subjectName) {
                $label = 'باقة '.$subjectName;
            }
        }

        try {
            PackageCart::add([
                'package_id' => (int) $servicePackage->id,
                'year_id' => isset($data['year_id']) ? (int) $data['year_id'] : null,
                'subject_id' => $subjectId,
                'instructor_id' => isset($data['instructor_id']) ? (int) $data['instructor_id'] : null,
                'weekly_sessions' => $weekly,
                'sessions' => $sessions,
                'schedule_notes' => $notes !== '' ? $notes : null,
                'label' => $label,
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('public.package-cart.index')
            ->with('success', 'أُضيفت الباقة إلى السلة.');
    }

    public function cart(): View
    {
        return view('public.package-cart', [
            'items' => PackageCart::items(),
            'packages' => PackageCart::packages(),
            'total' => PackageCart::total(),
            'mcActive' => 'pricing',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => (app()->getLocale() === 'ar' ? 'سلة الباقات' : 'Package cart').' — '.__('landing.nav.brand'),
        ]);
    }

    public function removeFromCart(Request $request): RedirectResponse
    {
        $key = (string) $request->input('key', '');
        if ($key !== '') {
            PackageCart::remove($key);
        }

        return redirect()->route('public.package-cart.index')->with('success', 'تم حذف العنصر من السلة.');
    }

    public function checkoutCart(): RedirectResponse
    {
        $items = PackageCart::items();
        if ($items === []) {
            return redirect()->route('public.pricing')->with('info', 'السلة فارغة.');
        }

        if (! auth()->check()) {
            return redirect()->guest(route('login'))->with('info', 'سجّل الدخول لإتمام دفع السلة.');
        }

        $first = $items[0];
        $package = ServicePackage::query()->find((int) $first['package_id']);
        if (! $package) {
            return redirect()->route('public.package-cart.index')->with('error', 'باقة غير صالحة في السلة.');
        }

        session(['package_checkout_prefs.'.$package->id => [
            'subjects' => array_values(array_filter(array_map(fn ($i) => (int) ($i['subject_id'] ?? 0), $items))),
            'teachers' => collect($items)
                ->filter(fn ($i) => ! empty($i['instructor_id']) && ! empty($i['subject_id']))
                ->mapWithKeys(fn ($i) => [(int) $i['subject_id'] => (int) $i['instructor_id']])
                ->all(),
            'schedule_notes' => collect($items)->pluck('schedule_notes')->filter()->implode("\n---\n"),
            'cart_items' => $items,
            'cart_total' => PackageCart::total(),
        ]]);

        return redirect()->route('public.service-packages.checkout', array_filter([
            'servicePackage' => $package->id,
            'year' => $first['year_id'] ?? null,
            'subject' => $first['subject_id'] ?? null,
            'sessions' => $first['sessions'] ?? null,
        ]));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AcademicSubject>  $subjects
     * @return array<int, \Illuminate\Support\Collection>
     */
    protected function buildTeachersBySubject($subjects, ?int $yearId, string $curriculum): array
    {
        $map = [];
        if ($subjects->isEmpty() || ! Schema::hasTable('instructor_profiles')) {
            return $map;
        }

        foreach ($subjects as $subject) {
            $sid = (int) $subject->id;
            $map[$sid] = $this->teacherProfilesForBooking($yearId, $sid, $curriculum);
            if ($map[$sid]->isEmpty() && $yearId) {
                $map[$sid] = $this->teacherProfilesForBooking(null, $sid, $curriculum);
            }
        }

        return $map;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\InstructorProfile>
     */
    protected function teacherProfilesForBooking(?int $yearId, ?int $subjectId, string $curriculum)
    {
        $query = PublicInstructorDirectory::approvedActiveQuery()
            ->with(['user:'.implode(',', PublicInstructorDirectory::userSelect())]);

        if ($subjectId && Schema::hasColumn('instructor_profiles', 'teaching_subject_ids')) {
            $subject = AcademicSubject::query()->find($subjectId);
            $related = AcademicSubjectMatcher::idsForSkill(
                (string) ($subject?->slug ?: $subject?->name ?: (string) $subjectId),
                $yearId
            );
            if ($related->isEmpty()) {
                $related = collect([(int) $subjectId]);
            }
            $query->where(function ($q) use ($related) {
                foreach ($related as $sid) {
                    $q->orWhereJsonContains('instructor_profiles.teaching_subject_ids', (int) $sid)
                        ->orWhereJsonContains('instructor_profiles.teaching_subject_ids', (string) $sid);
                }
            });
        }

        if ($yearId) {
            $query->whereHas('user', function ($u) use ($yearId) {
                $u->whereHas('teachingLearningPaths', fn ($y) => $y->where('academic_years.id', $yearId));
            });
        }

        return $query->limit(48)->get()
            ->filter(fn ($p) => PublicInstructorDirectory::isPublishableListing($p))
            ->when($curriculum !== '', function ($collection) use ($curriculum) {
                return $collection->filter(function ($p) use ($curriculum) {
                    $hay = implode(' ', [
                        (string) ($p->headline ?? ''),
                        (string) ($p->skills ?? ''),
                        (string) ($p->bio ?? ''),
                    ]);

                    return HesetakMatchCatalog::profileMatchesCurriculumType(
                        method_exists($p, 'curriculumTypeKeys') ? $p->curriculumTypeKeys() : null,
                        $curriculum,
                        $hay
                    );
                });
            })
            ->values();
    }
}
