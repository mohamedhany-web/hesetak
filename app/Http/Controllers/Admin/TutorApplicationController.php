<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StorageFileController;
use App\Models\InstructorAgreement;
use App\Models\TutorApplication;
use App\Models\TutorHiringSetting;
use App\Models\TutorInterview;
use App\Models\User;
use App\Services\TeacherSpecialtyMatcher;
use App\Services\TutorApplicationActivationService;
use App\Services\TutorApplicationStorage;
use App\Services\TutorContractService;
use App\Services\TutorInterviewBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class TutorApplicationController extends Controller
{
    public function hub(): View
    {
        $stats = [
            'draft' => TutorApplication::draft()->count(),
            'pending' => TutorApplication::pending()->count(),
            'interview' => TutorApplication::query()->whereIn('status', [
                TutorApplication::STATUS_INTERVIEW_PENDING,
                TutorApplication::STATUS_INTERVIEW_SCHEDULED,
            ])->count(),
            'contract' => TutorApplication::query()->whereIn('status', [
                TutorApplication::STATUS_CONTRACT_PENDING,
                TutorApplication::STATUS_CONTRACT_SIGNED,
            ])->count(),
            'approved' => TutorApplication::awaitingActivation()->count(),
            'activated' => TutorApplication::activated()->count(),
            'rejected' => TutorApplication::where('status', TutorApplication::STATUS_REJECTED)->count(),
            'blocked' => TutorApplication::where('status', TutorApplication::STATUS_BLOCKED_NO_SHOW)->count(),
            'total' => TutorApplication::count(),
            'instructors' => User::query()->whereIn('role', ['instructor', 'teacher'])->where('is_active', true)->count(),
        ];

        $recentPending = TutorApplication::pending()->orderByDesc('id')->limit(6)->get();
        $awaitingActivation = TutorApplication::awaitingActivation()->orderByDesc('id')->limit(6)->get();
        $applyUrl = route('public.tutor.apply');

        return view('admin.tutor-applications.hub', compact(
            'stats',
            'recentPending',
            'awaitingActivation',
            'applyUrl'
        ));
    }

    public function index(Request $request): View
    {
        $query = TutorApplication::query()->with(['user:id,name,email', 'reviewedByUser:id,name'])->orderByDesc('id');

        $allowedStatuses = array_keys(TutorApplication::statusLabels());
        if ($request->filled('status') && in_array($request->status, $allowedStatuses, true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', '%'.$s.'%')
                    ->orWhere('email', 'like', '%'.$s.'%')
                    ->orWhere('phone', 'like', '%'.$s.'%');
            });
        }

        $applications = $query->paginate(20)->withQueryString();
        $stats = [
            'draft' => TutorApplication::draft()->count(),
            'pending' => TutorApplication::pending()->count(),
            'approved' => TutorApplication::awaitingActivation()->count(),
            'activated' => TutorApplication::activated()->count(),
            'rejected' => TutorApplication::where('status', TutorApplication::STATUS_REJECTED)->count(),
            'total' => TutorApplication::count(),
        ];
        $applyUrl = route('public.tutor.apply');

        return view('admin.tutor-applications.index', compact('applications', 'stats', 'applyUrl'));
    }

    public function activated(Request $request): View
    {
        $query = TutorApplication::query()
            ->activated()
            ->with(['user:id,name,email,phone,is_active,role,created_at', 'activatedByUser:id,name'])
            ->orderByDesc('activated_at')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', '%'.$s.'%')
                    ->orWhere('email', 'like', '%'.$s.'%')
                    ->orWhereHas('user', function ($uq) use ($s) {
                        $uq->where('name', 'like', '%'.$s.'%')
                            ->orWhere('email', 'like', '%'.$s.'%');
                    });
            });
        }

        $applications = $query->paginate(20)->withQueryString();

        return view('admin.tutor-applications.activated', compact('applications'));
    }

    public function hireManually(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'full_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد غير صحيحة.',
            'full_name.required' => 'الاسم الكامل مطلوب.',
        ]);

        try {
            $result = TutorApplicationActivationService::hireManuallyByEmail(
                $request->user(),
                $data['email'],
                $data['full_name'],
                $data['phone'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = $result['created']
            ? 'تم توظيف المعلم يدوياً وإرسال بيانات الدخول إلى '.$result['user']->email
            : 'تم تفعيل حساب المعلم الموجود وإرسال إشعار إلى '.$result['user']->email;

        if (! $result['mail_sent']) {
            $message .= ' — تعذر إرسال البريد، انسخ كلمة المرور من الشاشة إن وُجدت.';
        }

        return redirect()
            ->route('admin.tutor-applications.activated')
            ->with('success', $message)
            ->with('hired_email', $result['user']->email)
            ->with('hired_password', $result['password']);
    }

    public function show(TutorApplication $tutorApplication): View
    {
        $tutorApplication->load([
            'reviewedByUser:id,name',
            'activatedByUser:id,name',
            'user:id,name,email,phone,is_active,role,created_at',
            'latestInterview',
            'latestAgreement',
        ]);

        $applyUrl = route('public.tutor.apply');
        $photoInline = TutorApplicationStorage::inlineDataUri($tutorApplication->photo_path);
        $idInline = $tutorApplication->idDocumentIsPdf()
            ? null
            : TutorApplicationStorage::inlineDataUri($tutorApplication->id_document_path);
        $certificateInline = $tutorApplication->certificateIsPdf()
            ? null
            : TutorApplicationStorage::inlineDataUri($tutorApplication->certificate_path);

        $specialty = TeacherSpecialtyMatcher::summarize($tutorApplication);
        $settings = TutorHiringSetting::allMapped();
        $billingLabels = InstructorAgreement::billingTypeLabels();

        return view('admin.tutor-applications.show', [
            'application' => $tutorApplication,
            'applyUrl' => $applyUrl,
            'photoInline' => $photoInline,
            'idInline' => $idInline,
            'certificateInline' => $certificateInline,
            'specialty' => $specialty,
            'settings' => $settings,
            'billingLabels' => $billingLabels,
        ]);
    }

    public function file(TutorApplication $tutorApplication, string $kind): Response
    {
        $path = match ($kind) {
            'photo' => $tutorApplication->photo_path,
            'id' => $tutorApplication->id_document_path,
            'certificate' => $tutorApplication->certificate_path,
            'video' => $tutorApplication->intro_video_path,
            default => abort(404),
        };

        $relative = TutorApplicationStorage::storedRelativePath($path);
        if ($relative === null) {
            abort(404);
        }

        return app(StorageFileController::class)->show(request(), $relative);
    }

    public function inviteInterview(TutorApplication $tutorApplication, TutorInterviewBookingService $booking): RedirectResponse
    {
        try {
            $booking->inviteToInterview($tutorApplication, auth()->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'تمت دعوة المرشح لاختيار موعد مقابلة. رابط الاختيار: '.route('tutor.interview.pick'));
    }

    public function markInterviewPassed(Request $request, TutorApplication $tutorApplication, TutorInterviewBookingService $booking): RedirectResponse
    {
        $interview = TutorInterview::query()
            ->where('tutor_application_id', $tutorApplication->id)
            ->whereIn('status', [TutorInterview::STATUS_SCHEDULED, TutorInterview::STATUS_COMPLETED])
            ->latest('id')
            ->first();

        if (! $interview) {
            $tutorApplication->update([
                'status' => TutorApplication::STATUS_INTERVIEW_PASSED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            return back()->with('success', 'تم تسجيل اجتياز المقابلة.');
        }

        $booking->markPassed($interview, auth()->user(), $request->input('notes'));

        return back()->with('success', 'تم تسجيل نجاح المقابلة.');
    }

    public function unblock(TutorApplication $tutorApplication, TutorInterviewBookingService $booking): RedirectResponse
    {
        $booking->unblock($tutorApplication, auth()->user());

        return back()->with('success', 'تم فك الحجب وإعادة فتح اختيار الموعد.');
    }

    public function offerContract(Request $request, TutorApplication $tutorApplication, TutorContractService $contracts): RedirectResponse
    {
        $data = $request->validate([
            'billing_type' => ['required', 'string', 'max:40'],
            'salary_per_session' => ['nullable', 'numeric', 'min:0'],
            'monthly_amount' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'title' => ['nullable', 'string', 'max:190'],
            'terms' => ['required', 'string', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        try {
            $agreement = $contracts->offer($tutorApplication, auth()->user(), $data);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'تم إرسال عرض العقد للتوقيع. الرابط: '.route('tutor.contract.sign', ['token' => $agreement->offer_token]));
    }

    public function approve(TutorApplication $tutorApplication): RedirectResponse
    {
        if ($tutorApplication->isActivated()) {
            return back()->with('error', 'الحساب مفعّل مسبقاً.');
        }

        if ($tutorApplication->status === TutorApplication::STATUS_REJECTED) {
            return back()->with('error', 'الطلب مرفوض — أعده للمراجعة أولاً إن لزم.');
        }

        try {
            TeacherSpecialtyMatcher::assertComplete($tutorApplication);
            if (TutorHiringSetting::bool('require_interview', true)
                && ! in_array($tutorApplication->status, [
                    TutorApplication::STATUS_INTERVIEW_PASSED,
                    TutorApplication::STATUS_CONTRACT_PENDING,
                    TutorApplication::STATUS_CONTRACT_SIGNED,
                    TutorApplication::STATUS_APPROVED,
                ], true)) {
                return back()->with('error', 'أكمل المقابلة التقنية أولاً (أو عطّل إلزامها من إعدادات التوظيف).');
            }
            if (TutorHiringSetting::bool('require_contract', true)
                && $tutorApplication->status !== TutorApplication::STATUS_CONTRACT_SIGNED
                && $tutorApplication->status !== TutorApplication::STATUS_APPROVED) {
                return back()->with('error', 'توقيع العقد مطلوب قبل القبول النهائي.');
            }
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $tutorApplication->update([
            'status' => TutorApplication::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم قبول الطلب. يمكنك الآن تفعيل حساب المعلم.');
    }

    public function activate(Request $request, TutorApplication $tutorApplication): RedirectResponse
    {
        try {
            app(TutorContractService::class)->assertReadyForActivation($tutorApplication);
            $result = TutorApplicationActivationService::activate(
                $tutorApplication,
                $request->user()
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.tutor-applications.show', $tutorApplication)
            ->with('success', 'تم تفعيل الملف العام للمعلم. الحساب كان مُنشأ عند التسجيل: '.$result['user']->email)
            ->with('activated_email', $result['user']->email)
            ->with('activated_user_uuid', $result['user']->uuid);
    }

    public function reject(Request $request, TutorApplication $tutorApplication): RedirectResponse
    {
        if ($tutorApplication->isActivated()) {
            return back()->with('error', 'لا يمكن رفض طلب بعد تفعيل الحساب.');
        }

        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tutorApplication->update([
            'status' => TutorApplication::STATUS_REJECTED,
            'admin_notes' => $data['admin_notes'] ?? $tutorApplication->admin_notes,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم رفض الطلب.');
    }

    public function destroy(TutorApplication $tutorApplication): RedirectResponse
    {
        if ($tutorApplication->isActivated()) {
            return back()->with('error', 'لا تحذف طلباً مرتبطاً بحساب مفعّل. عطّل الحساب من إدارة المستخدمين إن لزم.');
        }

        TutorApplicationStorage::delete($tutorApplication->photo_path);
        TutorApplicationStorage::delete($tutorApplication->id_document_path);
        TutorApplicationStorage::delete($tutorApplication->certificate_path);
        TutorApplicationStorage::delete($tutorApplication->intro_video_path);

        $tutorApplication->delete();

        return redirect()
            ->route('admin.tutor-applications.index')
            ->with('success', 'تم حذف الطلب.');
    }
}
