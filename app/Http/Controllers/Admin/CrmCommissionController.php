<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmCommission;
use App\Models\SalesLead;
use App\Models\User;
use App\Services\Crm\CrmCommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmCommissionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('manage.leads') || $request->user()->role === 'super_admin', 403);

        $query = CrmCommission::with(['user:id,name', 'lead:id,name', 'order:id,amount']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $commissions = $query->latest()->paginate(30)->withQueryString();

        $stats = [
            'pending' => CrmCommission::where('status', CrmCommission::STATUS_PENDING)->count(),
            'approved' => CrmCommission::where('status', CrmCommission::STATUS_APPROVED)->count(),
            'total_amount' => (float) CrmCommission::sum('commission_amount_egp'),
        ];

        return view('admin.crm.commissions.index', compact('commissions', 'stats'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermission('manage.leads') || $request->user()->role === 'super_admin', 403);

        $users = User::query()
            ->where('is_employee', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $leads = SalesLead::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'name']);

        return view('admin.crm.commissions.create', compact('users', 'leads'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('manage.leads') || $request->user()->role === 'super_admin', 403);

        $data = $request->validate([
            // عمود sales_lead_id على الإنتاج غير قابل للإفراغ.
            'sales_lead_id' => ['required', 'integer', 'exists:sales_leads,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(CrmCommission::typeLabels()))],
            'base_amount_egp' => ['nullable', 'numeric', 'min:0'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_amount_egp' => ['nullable', 'numeric', 'min:0'],
        ], [
            'sales_lead_id.required' => 'اختر Lead مرتبط بالعمولة.',
        ]);

        $amount = isset($data['commission_amount_egp']) ? (float) $data['commission_amount_egp'] : null;
        if ($amount === null && isset($data['base_amount_egp'], $data['commission_percent'])) {
            $amount = round((float) $data['base_amount_egp'] * (float) $data['commission_percent'] / 100, 2);
        }
        if ($amount === null || $amount <= 0) {
            return back()->withInput()->withErrors([
                'commission_amount_egp' => 'حدّد مبلغ العمولة أو الأساس مع النسبة.',
            ]);
        }

        CrmCommission::create([
            'sales_lead_id' => $data['sales_lead_id'],
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'base_amount_egp' => $data['base_amount_egp'] ?? 0,
            'commission_percent' => $data['commission_percent'] ?? 0,
            'commission_amount_egp' => $amount,
            'status' => CrmCommission::STATUS_PENDING,
        ]);

        return redirect()->route('admin.crm.commissions.index')
            ->with('success', 'تم إنشاء العمولة اليدوية بنجاح.');
    }

    public function approve(Request $request, CrmCommission $commission): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('manage.leads') || $request->user()->role === 'super_admin', 403);

        CrmCommissionService::approveCommission($commission, $request->user());

        return back()->with('success', 'تم اعتماد العمولة.');
    }
}
