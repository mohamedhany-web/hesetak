<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use App\Models\StudentServiceEntitlement;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformWalletHubController extends Controller
{
    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'students');
        if (! in_array($tab, ['students', 'instructors', 'grant'], true)) {
            $tab = 'students';
        }

        $studentCredits = StudentServiceEntitlement::query()
            ->active()
            ->where('scope', ServicePackage::SCOPE_PRIVATE_LESSONS)
            ->with(['user:id,name,email,role', 'servicePackage:id,name'])
            ->orderByDesc('updated_at')
            ->paginate(20, ['*'], 'student_page')
            ->withQueryString();

        $instructorWallets = Wallet::query()
            ->with(['user:id,name,email,role'])
            ->whereHas('user', function ($q) {
                $q->whereIn('role', ['instructor', 'teacher']);
            })
            ->orderByDesc('balance')
            ->paginate(20, ['*'], 'wallet_page')
            ->withQueryString();

        $studentWallets = Wallet::query()
            ->with(['user:id,name,email,role'])
            ->whereHas('user', function ($q) {
                $q->where('role', 'student');
            })
            ->orderByDesc('balance')
            ->paginate(20, ['*'], 'student_wallet_page')
            ->withQueryString();

        $kpis = [
            'active_entitlements' => StudentServiceEntitlement::query()
                ->active()
                ->where('scope', ServicePackage::SCOPE_PRIVATE_LESSONS)
                ->count(),
            'credits_remaining' => (int) StudentServiceEntitlement::query()
                ->active()
                ->where('scope', ServicePackage::SCOPE_PRIVATE_LESSONS)
                ->selectRaw('COALESCE(SUM(GREATEST(units_total - units_used, 0)), 0) as left_units')
                ->value('left_units'),
            'instructor_wallets' => Wallet::query()
                ->whereHas('user', fn ($q) => $q->whereIn('role', ['instructor', 'teacher']))
                ->count(),
            'instructor_balance' => (float) Wallet::query()
                ->whereHas('user', fn ($q) => $q->whereIn('role', ['instructor', 'teacher']))
                ->sum('balance'),
        ];

        return view('admin.platform-wallets.index', compact(
            'tab',
            'studentCredits',
            'instructorWallets',
            'studentWallets',
            'kpis'
        ));
    }
}
