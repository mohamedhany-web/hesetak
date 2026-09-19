<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index()
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => auth()->id()],
            [
                'balance' => 0,
                'pending_balance' => 0,
                'currency' => platform_currency(),
                'is_active' => true,
            ]
        );

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('student.wallet.index', compact('wallet', 'transactions'));
    }

    public function show($id)
    {
        return $this->index();
    }
}
