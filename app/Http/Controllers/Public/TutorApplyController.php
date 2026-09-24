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

        return view('tutor.apply-register');
    }

    public function register(Request $request): RedirectResponse
    {
        if ($request->user()?->isInstructor()) {
            return redirect()->route('public.tutor.apply.profile');
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:40', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'هذا البريد مسجّل مسبقاً. سجّل الدخول ثم أكمل بياناتك.',
            'phone.required' => 'رقم الجوال مطلوب.',
            'phone.unique' => 'رقم الجوال مسجّل مسبقاً.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
        ]);

        $email = strtolower(trim($data['email']));
        $phone = preg_replace('/\s+/', '', trim($data['phone'])) ?: trim($data['phone']);

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

        return view('tutor.apply-profile', [
            'application' => $application,
            'user' => $user,
            'form' => $form,
            'fields' => $form->activeFields,
            'subjectOptions' => \App\Services\TeacherSpecialtyMatcher::subjectOptions(),
            'yearOptions' => \App\Services\TeacherSpecialtyMatcher::yearOptions(),
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
                (array) $request->input('curriculum_types', [])
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
}
