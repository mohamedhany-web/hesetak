<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\InstructorProfile;
use App\Models\TutorApplication;
use App\Models\User;
use App\Services\HiringFormService;
use App\Services\TutorApplicationStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TutorApplyController extends Controller
{
    /**
     * الخطوة ١: إنشاء حساب معلم (إيميل + كلمة مرور).
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isInstructor()) {
            return redirect()->route('public.tutor.apply.profile');
        }

        $phoneCountries = config('phone_countries.countries', []);
        $defaultCountry = collect($phoneCountries)->firstWhere('code', config('phone_countries.default_country', 'SA'))
            ?: collect($phoneCountries)->first();

        return view('tutor.apply-register', compact('phoneCountries', 'defaultCountry'));
    }

    public function register(Request $request): RedirectResponse
    {
        if ($request->user()?->isInstructor()) {
            return redirect()->route('public.tutor.apply.profile');
        }

        $phoneCountries = config('phone_countries.countries', []);
        $defaultCountry = collect($phoneCountries)->firstWhere('code', config('phone_countries.default_country', 'SA'))
            ?: collect($phoneCountries)->first();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'country_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', self::tutorPasswordRule()],
        ], [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'هذا البريد مسجّل مسبقاً. سجّل الدخول ثم أكمل بياناتك.',
            'country_code.required' => 'كود الدولة مطلوب.',
            'phone.required' => 'رقم الجوال مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 10 أحرف.',
            'password.letters' => 'كلمة المرور يجب أن تحتوي على حرف واحد على الأقل.',
            'password.numbers' => 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل.',
        ]);

        $country = collect($phoneCountries)->firstWhere('dial_code', $data['country_code']);
        if (! $country || empty($country['validation']['regex'])) {
            return back()->withErrors(['country_code' => 'كود الدولة غير مدعوم.'])->withInput()
                ->with(compact('phoneCountries', 'defaultCountry'));
        }

        $nationalNumber = preg_replace('/\D/', '', $data['phone']) ?? '';
        $nationalNumber = ltrim($nationalNumber, '0');
        // مصر: الإبقاء على 1xxxxxxxxx بعد إزالة صفر المقدمة إن وُجد 01…
        if (($country['code'] ?? '') === 'EG' && str_starts_with($nationalNumber, '1') === false) {
            $raw = preg_replace('/\D/', '', $data['phone']) ?? '';
            if (str_starts_with($raw, '01')) {
                $nationalNumber = substr($raw, 1);
            }
        }

        $rawDigits = preg_replace('/\D/', '', $data['phone']) ?? '';
        if (strlen($nationalNumber) < 9) {
            return back()->withErrors(['phone' => 'رقم الجوال قصير جداً.'])->withInput()
                ->with(compact('phoneCountries', 'defaultCountry'));
        }
        if (($country['code'] ?? '') === 'EG') {
            if (strlen($rawDigits) < 10) {
                return back()->withErrors(['phone' => 'رقم الجوال المصري يجب أن يكون 11 رقماً (مثل 01012345678).'])->withInput()
                    ->with(compact('phoneCountries', 'defaultCountry'));
            }
            if (strlen($nationalNumber) !== 10) {
                return back()->withErrors(['phone' => 'رقم الجوال المصري غير صحيح. يجب 10 أرقام بعد كود الدولة (مثل 1012345678).'])->withInput()
                    ->with(compact('phoneCountries', 'defaultCountry'));
            }
        }

        if (! preg_match($country['validation']['regex'], $nationalNumber)) {
            $example = $country['example'] ?? $country['placeholder'] ?? '';

            return back()->withErrors(['phone' => 'رقم الجوال غير صحيح لهذه الدولة. مثال: '.$example])->withInput()
                ->with(compact('phoneCountries', 'defaultCountry'));
        }

        $phone = ($country['dial_code'] ?? '').$nationalNumber;
        if (User::where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => 'رقم الجوال مسجّل مسبقاً.'])->withInput()
                ->with(compact('phoneCountries', 'defaultCountry'));
        }

        $email = strtolower(trim($data['email']));

        $user = DB::transaction(function () use ($data, $email, $phone) {
            $user = User::create([
                'name' => trim($data['full_name']),
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($data['password']),
                'role' => 'instructor',
                'is_active' => true,
            ]);

            TutorApplication::create([
                'user_id' => $user->id,
                'full_name' => trim($data['full_name']),
                'email' => $email,
                'phone' => $phone,
                'status' => TutorApplication::STATUS_DRAFT,
            ]);

            InstructorProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['status' => InstructorProfile::STATUS_DRAFT]
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('public.tutor.apply.profile')
            ->with('success', app()->getLocale() === 'ar'
                ? 'تم إنشاء حسابك بنجاح. أكمل بياناتك الشخصية والمستندات الآن.'
                : 'Your account was created. Please complete your personal details and documents.');
    }

    /**
     * الخطوة ٢: إكمال البيانات الشخصية والمستندات (بعد تسجيل الدخول).
     */
    public function profile(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isInstructor()) {
            return redirect()->route('public.tutor.apply');
        }

        $application = TutorApplication::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if (! $application) {
            $application = TutorApplication::create([
                'user_id' => $user->id,
                'full_name' => $user->name,
                'email' => $user->email,
                'status' => TutorApplication::STATUS_DRAFT,
            ]);
        }

        if (in_array($application->status, [
            TutorApplication::STATUS_PENDING,
            TutorApplication::STATUS_APPROVED,
            TutorApplication::STATUS_INTERVIEW_PASSED,
            TutorApplication::STATUS_CONTRACT_PENDING,
            TutorApplication::STATUS_CONTRACT_SIGNED,
        ], true)) {
            return view('tutor.apply-submitted', [
                'application' => $application,
                'waitStatus' => $application->status,
            ]);
        }

        if ($application->status === TutorApplication::STATUS_INTERVIEW_PENDING
            || $application->status === TutorApplication::STATUS_INTERVIEW_SCHEDULED) {
            return redirect()->route('tutor.interview.pick');
        }

        if ($application->status === TutorApplication::STATUS_BLOCKED_NO_SHOW) {
            return view('tutor.interview-blocked', compact('application'));
        }

        if ($application->status === TutorApplication::STATUS_ACTIVATED) {
            return redirect()
                ->route('dashboard')
                ->with('success', app()->getLocale() === 'ar'
                    ? 'تم تفعيل حسابك. يمكنك استخدام لوحة المعلم الآن.'
                    : 'Your account is activated. You can use the instructor dashboard now.');
        }

        $form = HiringFormService::publishedForm();
        $yearOptions = \App\Services\TeacherSpecialtyMatcher::yearOptions();

        return view('tutor.apply-profile', [
            'application' => $application,
            'user' => $user,
            'form' => $form,
            'fields' => $form->activeFields,
            'subjectOptions' => \App\Services\TeacherSpecialtyMatcher::subjectOptions(),
            'yearOptions' => $yearOptions,
            'gradesByYear' => \App\Services\TeacherSpecialtyMatcher::gradesByYearMap($yearOptions),
            'curriculumOptions' => \App\Support\HesetakMatchCatalog::curriculumTypes(),
        ]);
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isInstructor(), 403);

        $application = TutorApplication::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->firstOrFail();

        if (! in_array($application->status, [
            TutorApplication::STATUS_DRAFT,
            TutorApplication::STATUS_REJECTED,
        ], true)) {
            return redirect()
                ->route('public.tutor.apply.profile')
                ->with('error', 'تم إرسال طلبك مسبقاً وهو قيد المراجعة أو مفعّل.');
        }

        $form = HiringFormService::publishedForm();

        try {
            $processed = HiringFormService::processSubmission($form, $request, $application, $user);
            HiringFormService::applyMappedToApplication(
                $application,
                $processed['mapped'],
                $processed['answers'],
                $form
            );
            $application->refresh();

            $normalized = \App\Services\TeacherSpecialtyMatcher::normalizePayload(
                (array) $request->input('teaching_subject_ids', []),
                (array) $request->input('academic_year_ids', []),
                (array) $request->input('curriculum_types', []),
                (array) $request->input('academic_grade_ids', [])
            );
            \App\Services\TeacherSpecialtyMatcher::applyToApplication($application, $normalized);
            \App\Services\TeacherSpecialtyMatcher::assertComplete($application->fresh());

            $application->forceFill(['email' => $user->email, 'user_id' => $user->id])->save();
            HiringFormService::syncUserAndProfile($user, $application->fresh(), $processed['mapped']);

            $profile = \App\Models\InstructorProfile::query()->where('user_id', $user->id)->first();
            if ($profile) {
                \App\Services\TeacherSpecialtyMatcher::syncProfile($profile, $normalized);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('tutor_application_profile_upload_failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'disk' => TutorApplicationStorage::resolvedDisk(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'form' => app()->getLocale() === 'ar'
                        ? 'تعذّر حفظ الطلب أو رفع الملفات. حاول مرة أخرى.'
                        : 'Could not save the application or upload files. Please try again.',
                ]);
        }

        return redirect()
            ->route('public.tutor.apply.profile')
            ->with('success', app()->getLocale() === 'ar'
                ? 'تم إرسال بياناتك للمراجعة. لن تُفتح لوحة المعلم إلا بعد تفعيل الإدارة.'
                : 'Your details were submitted for review. The instructor dashboard opens only after admin activation.');
    }

    private static function tutorPasswordRule(): Password
    {
        return Password::min(10)->max(72)->letters()->numbers();
    }
}
