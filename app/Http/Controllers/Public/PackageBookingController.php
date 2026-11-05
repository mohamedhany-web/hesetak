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

        $yearId = $request->filled('year')
            ? $request->integer('year')
            : ($servicePackage->academic_year_id ? (int) $servicePackage->academic_year_id : null);
        $subjectId = $request->filled('subject')
            ? $request->integer('subject')
            : ($servicePackage->academic_subject_id ? (int) $servicePackage->academic_subject_id : null);
        $curriculum = trim((string) $request->query('curriculum', $servicePackage->curriculum_type ?? ''));

        $years = Schema::hasTable('academic_years')
            ? AcademicYear::query()->active()->ordered()->get(['id', 'name', 'slug'])
            : collect();
        $subjects = Schema::hasTable('academic_subjects')
            ? AcademicSubject::query()->active()->when($yearId, fn ($q) => $q->forYear($yearId))->ordered()->get(['id', 'name', 'slug'])
            : collect();

        $profiles = $this->teacherProfilesForBooking($yearId, $subjectId, $curriculum);
        // المرحلة (ومنها مرحلة الباقة الافتراضية) قد تفرّغ القائمة. المادة تبقى شرطاً.
        if ($profiles->isEmpty() && $yearId) {
            $profiles = $this->teacherProfilesForBooking(null, $subjectId, $curriculum);
        }

        $weeklyOptions = CustomServicePackagePricingService::PRIVATE_WEEKLY_OPTIONS;
        $termOptions = CustomServicePackagePricingService::PRIVATE_TERM_MONTHS;
        $weekly = $request->integer('weekly') ?: 2;
        $weeks = $request->integer('weeks') ?: 4;
        if (! in_array($weekly, $weeklyOptions, true)) {
            $weekly = 2;
        }
        $weeks = max(1, min(16, $weeks));
        $sessions = $request->filled('sessions')
            ? max(1, min(200, $request->integer('sessions')))
            : ($weekly * $weeks);
        $unit = (float) $servicePackage->pricePerUnit();
        $total = round($unit * $sessions, 2);

        return view('public.package-booking-teachers', [
            'package' => $servicePackage,
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
            'pageTitle' => ($servicePackage->name).' — '.__('landing.nav.brand'),
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
        $sessions = $weekly * $weeks;
        $unit = (float) $servicePackage->pricePerUnit();
        $total = round($unit * $sessions, 2);

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

        try {
            PackageCart::add([
                'package_id' => (int) $servicePackage->id,
                'year_id' => $data['year_id'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
                'instructor_id' => $data['instructor_id'] ?? null,
                'weekly_sessions' => $weekly,
                'sessions' => $sessions,
                'schedule_notes' => $notes !== '' ? $notes : null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
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
