<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeJob;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsController extends Controller
{
    private function ensureCanViewSalesAnalytics(): void
    {
        $u = Auth::user();
        abort_unless(
            $u && ($u->isSuperAdmin() || $u->hasPermission('manage.orders') || $u->hasPermission('view.sales-analytics') || $u->hasPermission('manage.leads')),
            403,
            'غير مصرح لك بعرض تحليلات المبيعات.'
        );
    }

    public function index(Request $request)
    {
        $this->ensureCanViewSalesAnalytics();

        $from = $request->date('from') ?? now()->subDays(29)->startOfDay();
        $to = $request->date('to') ?? now()->endOfDay();

        $stats = [
            'orders_total' => Order::query()->whereBetween('created_at', [$from, $to])->count(),
            'pending' => Order::pending()->whereBetween('created_at', [$from, $to])->count(),
            'approved_period' => Order::approved()->whereBetween('approved_at', [$from, $to])->count(),
            'revenue_period' => (float) Order::approved()->whereBetween('approved_at', [$from, $to])->sum('amount'),
            'revenue_month' => (float) Order::approved()->where('approved_at', '>=', now()->startOfMonth())->sum('amount'),
            'conversion' => $this->conversionRate($from, $to),
        ];

        $daily = Order::query()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->pluck('c', 'd');

        $topCourses = Order::query()
            ->select('advanced_course_id', DB::raw('COUNT(*) as order_count'))
            ->selectRaw('SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as revenue', [Order::STATUS_APPROVED])
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('advanced_course_id')
            ->groupBy('advanced_course_id')
            ->orderByDesc('revenue')
            ->limit(8)
            ->with('course:id,title')
            ->get();

        $salesJobIds = EmployeeJob::query()->where('code', 'sales')->pluck('id');
        $repStats = collect();
        if ($salesJobIds->isNotEmpty()) {
            $repStats = User::query()
                ->where('is_employee', true)
                ->whereIn('employee_job_id', $salesJobIds)
                ->withCount([
                    'salesOwnedOrders as owned_pending' => fn ($q) => $q->where('status', Order::STATUS_PENDING),
                    'salesOwnedOrders as owned_won_period' => function ($q) use ($from, $to) {
                        $q->where('status', Order::STATUS_APPROVED)->whereBetween('approved_at', [$from, $to]);
                    },
                ])
                ->orderByDesc('owned_won_period')
                ->get();
        }

        $unassignedPending = Order::pending()->whereNull('sales_owner_id')->count();

        return view('admin.sales.index', compact(
            'stats',
            'daily',
            'topCourses',
            'repStats',
            'unassignedPending',
            'from',
            'to'
        ));
    }

    private function conversionRate($from, $to): ?float
    {
        $total = Order::query()->whereBetween('created_at', [$from, $to])->count();
        if ($total === 0) {
            return null;
        }
        $won = Order::approved()->whereBetween('approved_at', [$from, $to])->count();

        return round(100 * $won / $total, 1);
    }
}
