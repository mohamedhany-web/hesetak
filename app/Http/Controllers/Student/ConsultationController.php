<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\ConsultationSetting;
use App\Models\InstructorProfile;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:student']);
        $this->middleware(function ($request, $next) {
            if (! student_ui('show_consultations', false)) {
                return redirect()
                    ->route('dashboard')
                    ->with('info', app()->getLocale() === 'ar'
                        ? 'خدمة الاستشارات غير متاحة حالياً.'
                        : 'Consultations are not available right now.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $requests = ConsultationRequest::query()
            ->where('student_id', Auth::id())
            ->with(['instructor', 'classroomMeeting'])
            ->latest()
            ->paginate(15);

        return view('student.consultations.index', compact('requests'));
    }

    public function create(User $instructor)
    {
        if (! $instructor->isInstructor()) {
            abort(404);
        }

        $profile = InstructorProfile::where('user_id', $instructor->id)->approved()->firstOrFail();

        $settings = ConsultationSetting::current();
        if (! $settings->is_active) {
            abort(404, 'خدمة الاستشارات غير مفعّلة حالياً.');
        }

        if ($this->hasOpenPipeline(Auth::id(), $instructor->id)) {
            return redirect()
                ->route('consultations.index')
                ->with('error', 'لديك طلب استشارة قيد المعالجة مع هذا المدرب. تابع حالته من قائمة طلباتك.');
        }

        $priceEgp = $profile->effectiveConsultationPriceEgp();
        $availableWallets = $this->platformWalletsQuery()->get();

        return view('student.consultations.create', [
            'instructor' => $instructor,
            'instructorProfile' => $profile,
            'settings' => $settings,
            'priceEgp' => $priceEgp,
            'durationMinutes' => $profile->effectiveConsultationDurationMinutes(),
            'availableWallets' => $availableWallets,
        ]);
    }

    public function store(Request $request, User $instructor)
    {
        if (! $instructor->isInstructor()) {
            abort(404);
        }

        $profile = InstructorProfile::where('user_id', $instructor->id)->approved()->firstOrFail();

        $settings = ConsultationSetting::current();
        if (! $settings->is_active) {
            abort(404);
        }

        if ($this->hasOpenPipeline(Auth::id(), $instructor->id)) {
            return redirect()->route('consultations.index')->with('error', 'لديك طلب قيد المعالجة مع هذا المدرب.');
        }

        $availableWallets = $this->platformWalletsQuery()->get();

        $walletRules = ['nullable', 'integer', 'exists:wallets,id'];
        if ($availableWallets->isNotEmpty()
            && in_array($request->input('payment_method'), ['bank_transfer', 'other'], true)) {
            $walletRules = ['required', 'integer', 'exists:wallets,id'];
        }

        $data = $request->validate([
            'student_message' => ['nullable', 'string', 'max:5000'],
            'payment_method' => ['required', 'in:bank_transfer,cash,other'],
            'wallet_id' => $walletRules,
            'payment_proof' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:'.config('upload_limits.max_upload_kb')],
            'payment_reference' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'wallet_id.required' => 'يرجى اختيار حساب التحويل (محفظة المنصة)',
            'payment_proof.required' => 'صورة الإيصال مطلوبة',
            'payment_proof.image' => 'يجب أن يكون الملف صورة',
            'payment_proof.mimes' => 'يجب أن تكون الصورة بصيغة jpeg, png أو jpg',
            'payment_proof.max' => 'حجم الصورة يجب ألا يتجاوز ' . round(config('upload_limits.max_upload_kb') / 1024) . ' ميجابايت',
        ]);

        if (! empty($data['wallet_id'])) {
            if (! $this->platformWalletsQuery()->whereKey((int) $data['wallet_id'])->exists()) {
                return back()
                    ->withErrors(['wallet_id' => 'حساب التحويل المختار غير صالح أو غير متاح.'])
                    ->withInput();
            }
        }

        $priceEgp = $profile->effectiveConsultationPriceEgp();
        $durationMinutes = $profile->effectiveConsultationDurationMinutes();

        $paymentProofPath = PublicMediaStorage::storeFile($request->file('payment_proof'), 'payment-proofs');

        $consultation = ConsultationRequest::create([
            'instructor_id' => $instructor->id,
            'student_id' => Auth::id(),
            'price_amount' => $priceEgp,
            'duration_minutes' => $durationMinutes,
            'student_message' => $data['student_message'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'status' => ConsultationRequest::STATUS_PAYMENT_REPORTED,
            'payment_reported_at' => now(),
            'platform_wallet_id' => in_array($data['payment_method'], ['bank_transfer', 'other'], true)
                ? ($data['wallet_id'] ?? null)
                : null,
            'payment_method' => $data['payment_method'],
            'payment_proof' => $paymentProofPath,
            'wallet_transaction_id' => null,
        ]);

        return redirect()
            ->route('consultations.show', $consultation)
            ->with('success', 'تم إرسال طلب الاستشارة مع إيصال الدفع. ستُراجع الإدارة الطلب ثم تُحدَّد الجلسة بعد تأكيد استلام المبلغ.');
    }

    public function show(ConsultationRequest $consultation)
    {
        $this->authorizeStudent($consultation);
        $consultation->load(['instructor', 'classroomMeeting', 'paidConfirmedBy', 'platformWallet']);
        $settings = ConsultationSetting::current();

        return view('student.consultations.show', compact('consultation', 'settings'));
    }

    public function reportPayment(Request $request, ConsultationRequest $consultation)
    {
        $this->authorizeStudent($consultation);

        if ($consultation->status !== ConsultationRequest::STATUS_PENDING) {
            return back()->with('error', 'لا يمكن تسجيل التحويل في هذه الحالة.');
        }

        $data = $request->validate([
            'payment_reference' => ['nullable', 'string', 'max:500'],
        ]);

        $consultation->update([
            'status' => ConsultationRequest::STATUS_PAYMENT_REPORTED,
            'payment_reported_at' => now(),
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);

        return back()->with('success', 'تم تسجيل إبلاغك عن التحويل. سيتم المراجعة من الإدارة.');
    }

    private function authorizeStudent(ConsultationRequest $consultation): void
    {
        if ((int) $consultation->student_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    private function hasOpenPipeline(int $studentId, int $instructorId): bool
    {
        return ConsultationRequest::query()
            ->where('student_id', $studentId)
            ->where('instructor_id', $instructorId)
            ->whereIn('status', [
                ConsultationRequest::STATUS_PENDING,
                ConsultationRequest::STATUS_PAYMENT_REPORTED,
                ConsultationRequest::STATUS_AWAITING_VERIFICATION,
                ConsultationRequest::STATUS_PAID,
            ])
            ->exists();
    }

    /**
     * محافظ المنصة (حسابات التحويل) — نفس منطق صفحة الكورسات.
     */
    private function platformWalletsQuery()
    {
        return Wallet::where('is_active', true)
            ->whereNotNull('type')
            ->whereIn('type', ['vodafone_cash', 'instapay', 'bank_transfer'])
            ->where(function ($query) {
                $query->whereNotNull('account_number')
                    ->orWhereNotNull('name');
            })
            ->orderBy('type')
            ->orderBy('name');
    }
}
