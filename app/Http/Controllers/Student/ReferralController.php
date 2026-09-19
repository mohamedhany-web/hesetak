<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralProgram;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(protected ReferralService $referralService)
    {
    }

    public function index()
    {
        $user = auth()->user();
        $referralCode = $this->referralService->getUserReferralCode($user);

        $referrals = Referral::where('referrer_id', $user->id)
            ->with(['referred', 'referralProgram'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total_referrals' => $user->total_referrals ?? 0,
            'completed_referrals' => $user->completed_referrals ?? 0,
            'pending_referrals' => Referral::where('referrer_id', $user->id)
                ->where('status', Referral::STATUS_PENDING)
                ->count(),
            'total_credits' => (int) Referral::where('referrer_id', $user->id)->sum('referrer_units_granted'),
        ];

        $referralLink = url('/register?ref='.urlencode($referralCode));
        $activeProgram = ReferralProgram::currentForNewReferrals();
        $shareMessage = $activeProgram
            ? $this->referralService->buildShareMessage($activeProgram, $referralCode, $referralLink)
            : ('سجّل في حصتك من رابطي: '.$referralLink);
        $whatsappUrl = 'https://wa.me/?text='.rawurlencode($shareMessage);

        return view('student.referrals.index', compact(
            'referralCode',
            'referralLink',
            'referrals',
            'stats',
            'activeProgram',
            'shareMessage',
            'whatsappUrl'
        ));
    }

    public function copyLink(Request $request)
    {
        $user = auth()->user();
        $referralCode = $this->referralService->getUserReferralCode($user);
        $referralLink = url('/register?ref='.urlencode($referralCode));

        return response()->json([
            'success' => true,
            'link' => $referralLink,
            'code' => $referralCode,
        ]);
    }
}
