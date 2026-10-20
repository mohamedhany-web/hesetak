<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralProgram;
use App\Models\ServicePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReferralProgramController extends Controller
{
    public function index()
    {
        $programs = ReferralProgram::withCount('referrals')
            ->orderByDesc('is_default')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => ReferralProgram::count(),
            'active' => ReferralProgram::where('is_active', true)->count(),
            'inactive' => ReferralProgram::where('is_active', false)->count(),
            'valid_now' => ReferralProgram::active()->count(),
        ];

        return view('admin.referral-programs.index', compact('programs', 'stats'));
    }

    public function create()
    {
        $scopes = ServicePackage::scopes();

        return view('admin.referral-programs.create', compact('scopes'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProgram($request);

        $data = array_merge($validated, [
            'is_active' => $request->boolean('is_active', true),
            'is_default' => false,
        ]);

        $program = ReferralProgram::create($data);

        if ($request->boolean('is_default')) {
            DB::transaction(function () use ($program) {
                ReferralProgram::whereKeyNot($program->id)->update(['is_default' => false]);
                $program->forceFill(['is_default' => true])->save();
            });
        }

        return redirect()->route('admin.referral-programs.index')
            ->with('success', 'تم إنشاء برنامج الإحالات بنجاح');
    }

    public function show(ReferralProgram $referralProgram)
    {
        $referralProgram->load(['referrals.referrer', 'referrals.referred']);

        $stats = [
            'total_referrals' => $referralProgram->referrals()->count(),
            'completed_referrals' => $referralProgram->referrals()->where('status', 'completed')->count(),
            'pending_referrals' => $referralProgram->referrals()->where('status', 'pending')->count(),
            'total_discount_given' => $referralProgram->referrals()->sum('discount_amount'),
            'total_rewards_given' => $referralProgram->referrals()->where('status', 'completed')->sum('reward_amount'),
            'total_credits_referred' => $referralProgram->referrals()->sum('referred_units_granted'),
            'total_credits_referrer' => $referralProgram->referrals()->sum('referrer_units_granted'),
        ];

        return view('admin.referral-programs.show', compact('referralProgram', 'stats'));
    }

    public function edit(ReferralProgram $referralProgram)
    {
        $scopes = ServicePackage::scopes();

        return view('admin.referral-programs.edit', compact('referralProgram', 'scopes'));
    }

    public function update(Request $request, ReferralProgram $referralProgram)
    {
        $validated = $this->validateProgram($request);

        $data = array_merge($validated, [
            'is_active' => $request->boolean('is_active', true),
        ]);

        $referralProgram->update($data);

        if ($request->boolean('is_default')) {
            DB::transaction(function () use ($referralProgram) {
                ReferralProgram::whereKeyNot($referralProgram->id)->update(['is_default' => false]);
                $referralProgram->forceFill(['is_default' => true])->save();
            });
        }

        return redirect()->route('admin.referral-programs.index')
            ->with('success', 'تم تحديث برنامج الإحالات بنجاح');
    }

    public function setDefault(ReferralProgram $referralProgram)
    {
        if (! $referralProgram->is_active || ! $referralProgram->isValid()) {
            return back()->with('error', 'فعّل البرنامج وتأكد من تواريخ البدء والانتهاء قبل تعيينه افتراضياً.');
        }

        DB::transaction(function () use ($referralProgram) {
            ReferralProgram::whereKeyNot($referralProgram->id)->update(['is_default' => false]);
            $referralProgram->forceFill(['is_default' => true])->save();
        });

        return back()->with('success', 'تم تعيين البرنامج الافتراضي لإحالات التسجيل الجديدة.');
    }

    public function destroy(ReferralProgram $referralProgram)
    {
        if ($referralProgram->referrals()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف برنامج الإحالات لأنه يحتوي على إحالات مرتبطة');
        }

        $referralProgram->delete();

        return redirect()->route('admin.referral-programs.index')
            ->with('success', 'تم حذف برنامج الإحالات بنجاح');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProgram(Request $request): array
    {
        $mode = $request->input('reward_mode', ReferralProgram::REWARD_MODE_CREDITS);
        $isActive = $request->boolean('is_active', true);
        $maxRaw = trim((string) $request->input('max_referrals_per_user', ''));
        $allowUnlimited = $request->boolean('allow_unlimited_referrals');
        $confirmNoExpiry = $request->boolean('confirm_no_expiry');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reward_mode' => ['required', Rule::in([ReferralProgram::REWARD_MODE_CREDITS, ReferralProgram::REWARD_MODE_DISCOUNT])],
            'credit_scope' => ['nullable', Rule::in(array_keys(ServicePackage::scopes()))],
            'referred_credit_units' => 'nullable|integer|min:0|max:1000',
            'referrer_credit_units' => 'nullable|integer|min:0|max:1000',
            'credit_duration_days' => 'nullable|integer|min:1|max:3650',
            'grant_referred_on' => ['nullable', Rule::in([
                ReferralProgram::GRANT_SIGNUP,
                ReferralProgram::GRANT_FIRST_PURCHASE,
                ReferralProgram::GRANT_BOTH,
                ReferralProgram::GRANT_NONE,
            ])],
            'grant_referrer_on' => ['nullable', Rule::in([
                ReferralProgram::GRANT_FIRST_PURCHASE,
                ReferralProgram::GRANT_NONE,
            ])],
            'share_message_ar' => 'nullable|string|max:1000',
            'share_message_en' => 'nullable|string|max:1000',
            'discount_type' => [
                Rule::requiredIf($mode === ReferralProgram::REWARD_MODE_DISCOUNT),
                'nullable',
                Rule::in(['percentage', 'fixed']),
            ],
            'discount_value' => [
                Rule::requiredIf($mode === ReferralProgram::REWARD_MODE_DISCOUNT),
                'nullable',
                'numeric',
                'min:0',
            ],
            'maximum_discount' => 'nullable|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'referrer_reward_type' => [
                Rule::requiredIf($mode === ReferralProgram::REWARD_MODE_DISCOUNT),
                'nullable',
                Rule::in(['percentage', 'fixed', 'points']),
            ],
            'referrer_reward_value' => 'nullable|numeric|min:0',
            'discount_valid_days' => 'nullable|integer|min:1',
            'referral_code_valid_days' => 'nullable|integer|min:1|max:3650',
            'max_referrals_per_user' => 'nullable|integer|min:1|max:10000',
            'max_discount_uses_per_referred' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'allow_unlimited_referrals' => 'nullable|boolean',
            'confirm_no_expiry' => 'nullable|boolean',
        ]);

        if ($maxRaw === '') {
            if ($allowUnlimited) {
                $validated['max_referrals_per_user'] = null;
            } else {
                $validated['max_referrals_per_user'] = ReferralProgram::DEFAULT_MAX_REFERRALS_PER_USER;
            }
        }

        if ($isActive && empty($validated['expires_at']) && ! $confirmNoExpiry) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'expires_at' => 'حدّدوا تاريخ انتهاء، أو أكّدوا صراحةً أن البرنامج بلا انتهاء (خطر إساءة استخدام).',
            ]);
        }

        $shareAr = trim((string) ($validated['share_message_ar'] ?? ''));
        $shareEn = trim((string) ($validated['share_message_en'] ?? ''));
        if ($shareEn === '' && $shareAr !== '') {
            $shareEn = $shareAr;
        }
        if ($shareEn === '') {
            $shareEn = 'Join حصتك with my link and get free lesson credits: {link}';
        }
        if ($shareAr === '') {
            $shareAr = 'سجّل في حصتك من رابطي واحصل على رصيد حصص مجاني: {link}';
        }

        $payload = array_merge($validated, [
            'credit_scope' => $validated['credit_scope'] ?? ServicePackage::SCOPE_PRIVATE_LESSONS,
            'referred_credit_units' => (int) ($validated['referred_credit_units'] ?? 1),
            'referrer_credit_units' => (int) ($validated['referrer_credit_units'] ?? 1),
            'grant_referred_on' => $validated['grant_referred_on'] ?? ReferralProgram::GRANT_FIRST_PURCHASE,
            'grant_referrer_on' => $validated['grant_referrer_on'] ?? ReferralProgram::GRANT_FIRST_PURCHASE,
            'discount_type' => $validated['discount_type'] ?? 'percentage',
            'discount_value' => $validated['discount_value'] ?? 0,
            'referrer_reward_type' => $validated['referrer_reward_type'] ?? 'fixed',
            'discount_valid_days' => (int) ($validated['discount_valid_days'] ?? 30),
            'max_discount_uses_per_referred' => (int) ($validated['max_discount_uses_per_referred'] ?? 1),
            'credit_duration_days' => $validated['credit_duration_days'] ?? null,
            'share_message_ar' => $shareAr,
            'share_message_en' => $shareEn,
            'allow_self_referral' => false,
        ]);

        unset($payload['allow_unlimited_referrals'], $payload['confirm_no_expiry']);

        return $payload;
    }
}
