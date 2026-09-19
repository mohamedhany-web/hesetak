<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentWalletCreditController extends Controller
{
    public function create(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $students = collect();
        if ($search !== '') {
            $students = User::query()
                ->where('role', 'student')
                ->where('is_active', true)
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                })
                ->orderBy('name')
                ->limit(80)
                ->get(['id', 'name', 'phone', 'email']);
        }

        $selectedStudent = null;
        $oldUserId = old('user_id');
        if ($oldUserId) {
            $selectedStudent = User::query()
                ->whereKey($oldUserId)
                ->where('role', 'student')
                ->first(['id', 'name', 'phone', 'email']);
        }

        return view('admin.marketing.student-wallet-credit', compact('students', 'search', 'selectedStudent'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'user_id.required' => 'اختر الطالب.',
            'amount.required' => 'أدخل المبلغ.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر.',
        ]);

        $user = User::whereKey($validated['user_id'])->first();
        if (! $user || $user->role !== 'student') {
            return back()->withErrors(['user_id' => 'يجب اختيار حساب طالب.'])->withInput();
        }

        $notes = trim((string) ($validated['notes'] ?? ''));
        $desc = 'رصيد تسويق من الإدارة'.($notes !== '' ? ' — '.$notes : '');

        DB::transaction(function () use ($user, $validated, $desc) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => 0,
                    'pending_balance' => 0,
                    'currency' => platform_currency(),
                    'is_active' => true,
                ]
            );
            $wallet->deposit(
                (float) $validated['amount'],
                null,
                null,
                $desc
            );
        });

        return redirect()
            ->route('admin.marketing.student-wallet-credit.create')
            ->with('success', 'تم إضافة '.number_format((float) $validated['amount'], 2).' $ لمحفظة الطالب بنجاح.');
    }
}
