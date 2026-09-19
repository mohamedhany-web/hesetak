<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingReportsController extends Controller
{
    public function index(Request $request)
    {
        // تحديد الفترة الزمنية
        $period = $request->get('period', 'month'); // day, week, month, year, all
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // حساب التواريخ حسب الفترة
        $dates = $this->calculateDateRange($period, $startDate, $endDate);
        $startDate = $dates['start'];
        $endDate = $dates['end'];

        // إحصائيات عامة
        $stats = $this->getGeneralStats($startDate, $endDate);

        // تقارير الإيرادات
        $revenueReports = $this->getRevenueReports($startDate, $endDate);

        // تقارير المصروفات
        $expenseReports = $this->getExpenseReports($startDate, $endDate);

        // تقارير الفواتير
        $invoiceReports = $this->getInvoiceReports($startDate, $endDate);

        // تقارير المدفوعات
        $paymentReports = $this->getPaymentReports($startDate, $endDate);

        // تقارير المعاملات
        $transactionReports = $this->getTransactionReports($startDate, $endDate);

        // البيانات الشهرية (لرسم بياني)
        $monthlyData = $this->getMonthlyData($startDate, $endDate);

        // البيانات اليومية (آخر 30 يوم)
        $dailyData = $this->getDailyData();

        return view('admin.accounting.reports', compact(
            'stats',
            'revenueReports',
            'expenseReports',
            'invoiceReports',
            'paymentReports',
            'transactionReports',
            'monthlyData',
            'dailyData',
            'period',
            'startDate',
            'endDate'
        ));
    }

    public function invoices(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->calculateDateRange($period, $request->get('start_date'), $request->get('end_date'));
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $stats = $this->getGeneralStats($startDate, $endDate);
        $items = Invoice::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();
        return view('admin.accounting.reports-invoices', compact('stats', 'items', 'startDate', 'endDate', 'period'));
    }

    public function payments(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->calculateDateRange($period, $request->get('start_date'), $request->get('end_date'));
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $stats = $this->getGeneralStats($startDate, $endDate);
        $items = Payment::with(['user', 'invoice'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();
        return view('admin.accounting.reports-payments', compact('stats', 'items', 'startDate', 'endDate', 'period'));
    }

    public function transactions(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->calculateDateRange($period, $request->get('start_date'), $request->get('end_date'));
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $stats = $this->getGeneralStats($startDate, $endDate);
        $items = Transaction::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();
        return view('admin.accounting.reports-transactions', compact('stats', 'items', 'startDate', 'endDate', 'period'));
    }

    public function expenses(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->calculateDateRange($period, $request->get('start_date'), $request->get('end_date'));
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $stats = $this->getGeneralStats($startDate, $endDate);
        $items = Expense::query()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->orderBy('expense_date', 'desc')
            ->paginate(25)
            ->withQueryString();
        return view('admin.accounting.reports-expenses', compact('stats', 'items', 'startDate', 'endDate', 'period'));
    }

    public function wallets(Request $request)
    {
        $period = $request->get('period', 'all');
        $dates = $this->calculateDateRange($period, $request->get('start_date'), $request->get('end_date'));
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $stats = $this->getGeneralStats($startDate, $endDate);
        $items = Wallet::withCount('transactions')
            ->orderBy('balance', 'desc')
            ->paginate(25)
            ->withQueryString();
        return view('admin.accounting.reports-wallets', compact('stats', 'items', 'startDate', 'endDate', 'period'));
    }

    public function orders(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->calculateDateRange($period, $request->get('start_date'), $request->get('end_date'));
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $stats = $this->getGeneralStats($startDate, $endDate);
        $items = Order::with(['user', 'course', 'learningPath'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();
        return view('admin.accounting.reports-orders', compact('stats', 'items', 'startDate', 'endDate', 'period'));
    }

    /** Online payment gateway report (gross, fees, net, by gateway). */
    public function paymentGateway(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->calculateDateRange($period, $request->get('start_date'), $request->get('end_date'));
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $stats = $this->getGeneralStats($startDate, $endDate);

        $base = Payment::query()
            ->where('payment_method', 'online')
            ->whereNotNull('payment_gateway')
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate]);

        $summaryRow = (clone $base)
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('COALESCE(SUM(amount), 0) as gross_sum')
            ->selectRaw('COALESCE(SUM(COALESCE(gateway_fee_amount, 0)), 0) as fee_sum')
            ->selectRaw('COALESCE(SUM(COALESCE(net_after_gateway_fee, amount - COALESCE(gateway_fee_amount, 0))), 0) as net_sum')
            ->first();

        $byGateway = (clone $base)
            ->select(
                'payment_gateway',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('COALESCE(SUM(amount), 0) as gross'),
                DB::raw('COALESCE(SUM(COALESCE(gateway_fee_amount, 0)), 0) as fees'),
                DB::raw('COALESCE(SUM(COALESCE(net_after_gateway_fee, amount - COALESCE(gateway_fee_amount, 0))), 0) as net'),
            )
            ->groupBy('payment_gateway')
            ->orderByDesc('gross')
            ->get();

        $items = Payment::with(['user', 'invoice'])
            ->where('payment_method', 'online')
            ->whereNotNull('payment_gateway')
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->orderByDesc('paid_at')
            ->paginate(25)
            ->withQueryString();

        $gatewaySummary = [
            'count' => (int) ($summaryRow->payment_count ?? 0),
            'gross' => (float) ($summaryRow->gross_sum ?? 0),
            'fees' => (float) ($summaryRow->fee_sum ?? 0),
            'net' => (float) ($summaryRow->net_sum ?? 0),
        ];

        return view('admin.accounting.reports-payment-gateway', compact(
            'stats',
            'items',
            'startDate',
            'endDate',
            'period',
            'byGateway',
            'gatewaySummary'
        ));
    }

    private function calculateDateRange($period, $startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay()
            ];
        }

        switch ($period) {
            case 'day':
                return [
                    'start' => Carbon::today()->startOfDay(),
                    'end' => Carbon::today()->endOfDay()
                ];
            case 'week':
                return [
                    'start' => Carbon::now()->startOfWeek()->startOfDay(),
                    'end' => Carbon::now()->endOfWeek()->endOfDay()
                ];
            case 'month':
                return [
                    'start' => Carbon::now()->startOfMonth()->startOfDay(),
                    'end' => Carbon::now()->endOfMonth()->endOfDay()
                ];
            case 'year':
                return [
                    'start' => Carbon::now()->startOfYear()->startOfDay(),
                    'end' => Carbon::now()->endOfYear()->endOfDay()
                ];
            case 'all':
            default:
                return [
                    'start' => Carbon::parse('2020-01-01')->startOfDay(),
                    'end' => Carbon::now()->endOfDay()
                ];
        }
    }

    private function getGeneralStats($startDate, $endDate)
    {
        // إجمالي الإيرادات (من المدفوعات المكتملة)
        $totalRevenue = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        // إجمالي الإيرادات من المعاملات (نوع credit = دائن = إيراد)
        $totalRevenueFromTransactions = Transaction::where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalRevenue = $totalRevenue + $totalRevenueFromTransactions;

        // إجمالي المصروفات
        $totalExpenses = Expense::where('status', 'approved')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        // إجمالي المصروفات من المعاملات (نوع debit = مدين = مصروف)
        $totalExpensesFromTransactions = Transaction::where('type', 'debit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalExpenses = $totalExpenses + $totalExpensesFromTransactions;

        // الربح الصافي
        $netProfit = $totalRevenue - $totalExpenses;

        // عدد الفواتير
        $totalInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])->count();
        $paidInvoices = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->count();
        $pendingInvoices = Invoice::where('status', 'pending')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // عدد المدفوعات
        $totalPayments = Payment::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedPayments = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->count();

        // عدد المعاملات
        $totalTransactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->count();

        // محافظ المنصة (إجماليات دون ربط بفترة — حالة حاليّة)
        $walletStats = [
            'total_wallets' => Wallet::count(),
            'active_wallets' => Wallet::where('is_active', true)->count(),
            'total_balance' => (float) Wallet::sum('balance'),
            'pending_balance' => (float) Wallet::sum('pending_balance'),
        ];

        // الطلبات (كورسات ومسارات)
        $ordersTotal = Order::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        $ordersApproved = Order::where('status', Order::STATUS_APPROVED)
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->sum('amount');
        $orderStats = [
            'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
            'approved_orders' => Order::where('status', Order::STATUS_APPROVED)->whereBetween('approved_at', [$startDate, $endDate])->count(),
            'pending_orders' => Order::where('status', Order::STATUS_PENDING)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'rejected_orders' => Order::where('status', Order::STATUS_REJECTED)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_amount' => $ordersTotal,
            'approved_amount' => $ordersApproved,
        ];

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'pending_invoices' => $pendingInvoices,
            'total_payments' => $totalPayments,
            'completed_payments' => $completedPayments,
            'total_transactions' => $totalTransactions,
            'wallet_stats' => $walletStats,
            'order_stats' => $orderStats,
        ];
    }

    private function getRevenueReports($startDate, $endDate)
    {
        // الإيرادات من المدفوعات
        $revenueFromPayments = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
                'payment_method'
            )
            ->groupBy('payment_method')
            ->get();

        // الإيرادات من المعاملات
        $revenueFromTransactions = Transaction::where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
                'category'
            )
            ->groupBy('category')
            ->get();

        return [
            'from_payments' => $revenueFromPayments,
            'from_transactions' => $revenueFromTransactions,
        ];
    }

    private function getExpenseReports($startDate, $endDate)
    {
        // المصروفات من جدول المصروفات
        $expenses = Expense::where('status', 'approved')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
                'category'
            )
            ->groupBy('category')
            ->get();

        // المصروفات من المعاملات
        $expensesFromTransactions = Transaction::where('type', 'debit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
                'category'
            )
            ->groupBy('category')
            ->get();

        return [
            'from_expenses' => $expenses,
            'from_transactions' => $expensesFromTransactions,
        ];
    }

    private function getInvoiceReports($startDate, $endDate)
    {
        return Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('COUNT(*) as count'),
                'status',
                'type'
            )
            ->groupBy('status', 'type')
            ->get();
    }

    private function getPaymentReports($startDate, $endDate)
    {
        return Payment::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
                'status',
                'payment_method'
            )
            ->groupBy('status', 'payment_method')
            ->get();
    }

    private function getTransactionReports($startDate, $endDate)
    {
        return Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
                'type',
                'status',
                'category'
            )
            ->groupBy('type', 'status', 'category')
            ->get();
    }

    private function getMonthlyData($startDate, $endDate)
    {
        $months = [];
        $revenues = [];
        $expenses = [];

        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $monthRevenue = Payment::where('status', 'completed')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthRevenue += Transaction::where('type', 'credit')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthExpense = Expense::where('status', 'approved')
                ->whereBetween('expense_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthExpense += Transaction::where('type', 'debit')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $months[] = $current->format('Y-m');
            $revenues[] = $monthRevenue;
            $expenses[] = $monthExpense;

            $current->addMonth();
        }

        return [
            'months' => $months,
            'revenues' => $revenues,
            'expenses' => $expenses,
        ];
    }

    private function getDailyData()
    {
        $days = [];
        $revenues = [];
        $expenses = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $dayRevenue = Payment::where('status', 'completed')
                ->whereBetween('paid_at', [$dayStart, $dayEnd])
                ->sum('amount');

            $dayRevenue += Transaction::where('type', 'credit')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->sum('amount');

            $dayExpense = Expense::where('status', 'approved')
                ->whereBetween('expense_date', [$dayStart, $dayEnd])
                ->sum('amount');

            $dayExpense += Transaction::where('type', 'debit')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->sum('amount');

            $days[] = $date->format('Y-m-d');
            $revenues[] = $dayRevenue;
            $expenses[] = $dayExpense;
        }

        return [
            'days' => $days,
            'revenues' => $revenues,
            'expenses' => $expenses,
        ];
    }

    public function export(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $type = $request->get('type', 'all');

        $dates = $this->calculateDateRange($period, $startDate, $endDate);
        $startDate = $dates['start'];
        $endDate = $dates['end'];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('حصتك')
            ->setTitle('التقارير المالية - حصتك')
            ->setSubject('تقارير محاسبية شاملة');

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ];
        $headerFont = [];
        $border = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]];

        $sheetIndex = 0;

        // ورقة الملخص
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('الملخص المالي');
        $sheet->setRightToLeft(true);
        $stats = $this->getGeneralStats($startDate, $endDate);
        $this->writeSummarySheet($sheet, $stats, $startDate, $endDate, $headerStyle, $headerFont, $border);

        if ($type === 'all') {
            $sheetIndex++;
            $this->addInvoicesSheet($spreadsheet, $sheetIndex, $startDate, $endDate, $headerStyle, $headerFont, $border);
            $sheetIndex++;
            $this->addPaymentsSheet($spreadsheet, $sheetIndex, $startDate, $endDate, $headerStyle, $headerFont, $border);
            $sheetIndex++;
            $this->addTransactionsSheet($spreadsheet, $sheetIndex, $startDate, $endDate, $headerStyle, $headerFont, $border);
            $sheetIndex++;
            $this->addExpensesSheet($spreadsheet, $sheetIndex, $startDate, $endDate, $headerStyle, $headerFont, $border);
            $sheetIndex++;
            $this->addWalletsSheet($spreadsheet, $sheetIndex, $headerStyle, $headerFont, $border);
            $sheetIndex++;
            $this->addOrdersSheet($spreadsheet, $sheetIndex, $startDate, $endDate, $headerStyle, $headerFont, $border);
            $sheetIndex++;
            $this->addPaymentGatewaySheet($spreadsheet, $sheetIndex, $startDate, $endDate, $headerStyle, $headerFont, $border);
        } elseif (in_array($type, ['invoices', 'payments', 'transactions', 'expenses', 'wallets', 'orders', 'payment_gateway'])) {
            if ($type === 'invoices') $this->addInvoicesSheet($spreadsheet, 1, $startDate, $endDate, $headerStyle, $headerFont, $border);
            if ($type === 'payments') $this->addPaymentsSheet($spreadsheet, 1, $startDate, $endDate, $headerStyle, $headerFont, $border);
            if ($type === 'transactions') $this->addTransactionsSheet($spreadsheet, 1, $startDate, $endDate, $headerStyle, $headerFont, $border);
            if ($type === 'expenses') $this->addExpensesSheet($spreadsheet, 1, $startDate, $endDate, $headerStyle, $headerFont, $border);
            if ($type === 'wallets') $this->addWalletsSheet($spreadsheet, 1, $headerStyle, $headerFont, $border);
            if ($type === 'orders') $this->addOrdersSheet($spreadsheet, 1, $startDate, $endDate, $headerStyle, $headerFont, $border);
            if ($type === 'payment_gateway') $this->addPaymentGatewaySheet($spreadsheet, 1, $startDate, $endDate, $headerStyle, $headerFont, $border);
        }

        $filename = 'التقارير_المالية_حصتك_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx';
        $asciiFilename = 'accounting_reports_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx';
        $disposition = "attachment; filename=\"{$asciiFilename}\"; filename*=UTF-8''" . rawurlencode($filename);

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function writeSummarySheet($sheet, $stats, $startDate, $endDate, $headerStyle, $headerFont, $border)
    {
        $sheet->setCellValue('A1', 'تقارير مالية شاملة - حصتك');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'الفترة: من ' . $startDate->format('Y-m-d') . ' إلى ' . $endDate->format('Y-m-d'));
        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A3', 'تاريخ التصدير: ' . now()->format('Y-m-d H:i'));
        $sheet->mergeCells('A3:D3');
        $row = 5;
        $sheet->setCellValue('A' . $row, 'البند');
        $sheet->setCellValue('B' . $row, 'القيمة');
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($headerStyle);
        $row++;
        $items = [
            ['إجمالي الإيرادات', number_format($stats['total_revenue'], 2) . ' ' . currency_symbol()],
            ['إجمالي المصروفات', number_format($stats['total_expenses'], 2) . ' ' . currency_symbol()],
            ['الربح الصافي', number_format($stats['net_profit'], 2) . ' ' . currency_symbol()],
            ['عدد الفواتير', $stats['total_invoices']],
            ['فواتير مدفوعة', $stats['paid_invoices']],
            ['فواتير معلقة', $stats['pending_invoices']],
            ['عدد المدفوعات', $stats['total_payments']],
            ['مدفوعات مكتملة', $stats['completed_payments']],
            ['عدد المعاملات', $stats['total_transactions']],
            ['عدد محافظ المنصة', $stats['wallet_stats']['total_wallets']],
            ['محافظ نشطة', $stats['wallet_stats']['active_wallets']],
            ['إجمالي أرصدة المحافظ', number_format($stats['wallet_stats']['total_balance'], 2) . ' ' . currency_symbol()],
            ['الرصيد المعلق للمحافظ', number_format($stats['wallet_stats']['pending_balance'], 2) . ' ' . currency_symbol()],
            ['عدد الطلبات (الفترة)', $stats['order_stats']['total_orders']],
            ['طلبات معتمدة', $stats['order_stats']['approved_orders']],
            ['طلبات معلقة', $stats['order_stats']['pending_orders']],
            ['إجمالي مبالغ الطلبات', number_format($stats['order_stats']['total_amount'], 2) . ' ' . currency_symbol()],
            ['مبالغ الطلبات المعتمدة', number_format($stats['order_stats']['approved_amount'], 2) . ' ' . currency_symbol()],
        ];
        foreach ($items as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($border);
            $row++;
        }
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(22);
    }

    private function addInvoicesSheet($spreadsheet, $index, $startDate, $endDate, $headerStyle, $headerFont, $border)
    {
        $sheet = $spreadsheet->createSheet($index);
        $sheet->setTitle('الفواتير');
        $sheet->setRightToLeft(true);
        $headers = ['رقم الفاتورة', 'العميل', 'النوع', 'المبلغ الفرعي', 'الضريبة', 'الخصم', 'المبلغ الإجمالي', 'الحالة', 'تاريخ الاستحقاق', 'تاريخ الإنشاء'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $col++;
        }
        $invoices = Invoice::with('user')->whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->get();
        $row = 2;
        foreach ($invoices as $inv) {
            $sheet->setCellValue('A' . $row, $inv->invoice_number);
            $sheet->setCellValue('B' . $row, $inv->user->name ?? 'غير معروف');
            $sheet->setCellValue('C' . $row, $inv->type ?? '-');
            $sheet->setCellValue('D' . $row, (float) $inv->subtotal);
            $sheet->setCellValue('E' . $row, (float) ($inv->tax_amount ?? 0));
            $sheet->setCellValue('F' . $row, (float) ($inv->discount_amount ?? 0));
            $sheet->setCellValue('G' . $row, (float) $inv->total_amount);
            $sheet->setCellValue('H' . $row, $inv->status);
            $sheet->setCellValue('I' . $row, $inv->due_date ? $inv->due_date->format('Y-m-d') : '-');
            $sheet->setCellValue('J' . $row, $inv->created_at->format('Y-m-d H:i'));
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($border);
            $row++;
        }
        for ($c = 0; $c < 10; $c++) $sheet->getColumnDimensionByColumn($c + 1)->setAutoSize(true);
    }

    private function addPaymentsSheet($spreadsheet, $index, $startDate, $endDate, $headerStyle, $headerFont, $border)
    {
        $sheet = $spreadsheet->createSheet($index);
        $sheet->setTitle('المدفوعات');
        $sheet->setRightToLeft(true);
        $headers = ['رقم الدفعة', 'العميل', 'رقم الفاتورة', 'المبلغ', 'طريقة الدفع', 'الحالة', 'تاريخ الدفع', 'مرجع', 'تاريخ الإنشاء'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $col++;
        }
        $payments = Payment::with(['user', 'invoice'])->whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->get();
        $row = 2;
        foreach ($payments as $p) {
            $sheet->setCellValue('A' . $row, $p->payment_number);
            $sheet->setCellValue('B' . $row, $p->user->name ?? 'غير معروف');
            $sheet->setCellValue('C' . $row, $p->invoice->invoice_number ?? '-');
            $sheet->setCellValue('D' . $row, (float) $p->amount);
            $sheet->setCellValue('E' . $row, $p->payment_method ?? '-');
            $sheet->setCellValue('F' . $row, $p->status);
            $sheet->setCellValue('G' . $row, $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : '-');
            $sheet->setCellValue('H' . $row, $p->reference_number ?? '-');
            $sheet->setCellValue('I' . $row, $p->created_at->format('Y-m-d H:i'));
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($border);
            $row++;
        }
        for ($c = 0; $c < 9; $c++) $sheet->getColumnDimensionByColumn($c + 1)->setAutoSize(true);
    }

    private function addTransactionsSheet($spreadsheet, $index, $startDate, $endDate, $headerStyle, $headerFont, $border)
    {
        $sheet = $spreadsheet->createSheet($index);
        $sheet->setTitle('المعاملات');
        $sheet->setRightToLeft(true);
        $headers = ['رقم المعاملة', 'العميل', 'النوع', 'الفئة', 'المبلغ', 'الحالة', 'الوصف', 'التاريخ'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $col++;
        }
        $transactions = Transaction::with('user')->whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->get();
        $row = 2;
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $row, $t->transaction_number ?? 'N/A');
            $sheet->setCellValue('B' . $row, $t->user->name ?? 'غير معروف');
            $sheet->setCellValue('C' . $row, $t->type === 'credit' ? 'إيراد' : 'مصروف');
            $sheet->setCellValue('D' . $row, $t->category ?? '-');
            $sheet->setCellValue('E' . $row, (float) $t->amount);
            $sheet->setCellValue('F' . $row, $t->status);
            $sheet->setCellValue('G' . $row, $t->description ?? '-');
            $sheet->setCellValue('H' . $row, $t->created_at->format('Y-m-d H:i'));
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($border);
            $row++;
        }
        for ($c = 0; $c < 8; $c++) $sheet->getColumnDimensionByColumn($c + 1)->setAutoSize(true);
    }

    private function addExpensesSheet($spreadsheet, $index, $startDate, $endDate, $headerStyle, $headerFont, $border)
    {
        $sheet = $spreadsheet->createSheet($index);
        $sheet->setTitle('المصروفات');
        $sheet->setRightToLeft(true);
        $headers = ['رقم المصروف', 'العنوان', 'الفئة', 'المبلغ', 'طريقة الدفع', 'الحالة', 'تاريخ المصروف', 'التاريخ'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $col++;
        }
        $expenses = Expense::whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->get();
        $row = 2;
        foreach ($expenses as $e) {
            $sheet->setCellValue('A' . $row, $e->expense_number ?? 'N/A');
            $sheet->setCellValue('B' . $row, $e->title ?? '-');
            $sheet->setCellValue('C' . $row, \App\Models\Expense::categoryLabel($e->category));
            $sheet->setCellValue('D' . $row, (float) $e->amount);
            $sheet->setCellValue('E' . $row, $e->payment_method ?? '-');
            $sheet->setCellValue('F' . $row, $e->status);
            $sheet->setCellValue('G' . $row, $e->expense_date ? $e->expense_date->format('Y-m-d') : '-');
            $sheet->setCellValue('H' . $row, $e->created_at->format('Y-m-d H:i'));
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($border);
            $row++;
        }
        for ($c = 0; $c < 8; $c++) $sheet->getColumnDimensionByColumn($c + 1)->setAutoSize(true);
    }

    private function addWalletsSheet($spreadsheet, $index, $headerStyle, $headerFont, $border)
    {
        $sheet = $spreadsheet->createSheet($index);
        $sheet->setTitle('المحافظ');
        $sheet->setRightToLeft(true);
        $headers = ['اسم المحفظة', 'النوع', 'رقم الحساب', 'البنك', 'صاحب الحساب', 'الرصيد', 'الرصيد المعلق', 'نشطة', 'عدد المعاملات', 'تاريخ التحديث'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $col++;
        }
        $wallets = Wallet::withCount('transactions')->orderBy('balance', 'desc')->get();
        $row = 2;
        foreach ($wallets as $w) {
            $sheet->setCellValue('A' . $row, $w->name ?? '-');
            $sheet->setCellValue('B' . $row, Wallet::typeLabel($w->type ?? ''));
            $sheet->setCellValue('C' . $row, $w->account_number ?? '-');
            $sheet->setCellValue('D' . $row, $w->bank_name ?? '-');
            $sheet->setCellValue('E' . $row, $w->account_holder ?? '-');
            $sheet->setCellValue('F' . $row, (float) $w->balance);
            $sheet->setCellValue('G' . $row, (float) ($w->pending_balance ?? 0));
            $sheet->setCellValue('H' . $row, $w->is_active ? 'نعم' : 'لا');
            $sheet->setCellValue('I' . $row, $w->transactions_count ?? 0);
            $sheet->setCellValue('J' . $row, $w->updated_at->format('Y-m-d H:i'));
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($border);
            $row++;
        }
        for ($c = 0; $c < 10; $c++) $sheet->getColumnDimensionByColumn($c + 1)->setAutoSize(true);
    }

    private function addOrdersSheet($spreadsheet, $index, $startDate, $endDate, $headerStyle, $headerFont, $border)
    {
        $sheet = $spreadsheet->createSheet($index);
        $sheet->setTitle('الطلبات');
        $sheet->setRightToLeft(true);
        $headers = ['رقم الطلب', 'العميل', 'نوع الطلب', 'المنتج', 'المبلغ', 'طريقة الدفع', 'الحالة', 'تاريخ الطلب', 'تاريخ الموافقة'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $col++;
        }
        $orders = Order::with(['user', 'course', 'learningPath'])->whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->get();
        $row = 2;
        foreach ($orders as $o) {
            $product = $o->course ? $o->course->title : ($o->learningPath ? $o->learningPath->name : '—');
            $sheet->setCellValue('A' . $row, $o->id);
            $sheet->setCellValue('B' . $row, $o->user->name ?? 'غير معروف');
            $sheet->setCellValue('C' . $row, $o->advanced_course_id ? 'كورس' : 'مسار');
            $sheet->setCellValue('D' . $row, $product);
            $sheet->setCellValue('E' . $row, (float) $o->amount);
            $sheet->setCellValue('F' . $row, $o->payment_method ?? '-');
            $sheet->setCellValue('G' . $row, $o->status === Order::STATUS_APPROVED ? 'معتمد' : ($o->status === Order::STATUS_PENDING ? 'معلق' : 'مرفوض'));
            $sheet->setCellValue('H' . $row, $o->created_at->format('Y-m-d H:i'));
            $sheet->setCellValue('I' . $row, $o->approved_at ? $o->approved_at->format('Y-m-d H:i') : '-');
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($border);
            $row++;
        }
        for ($c = 0; $c < 9; $c++) $sheet->getColumnDimensionByColumn($c + 1)->setAutoSize(true);
    }

    private function addPaymentGatewaySheet($spreadsheet, $index, $startDate, $endDate, $headerStyle, $headerFont, $border)
    {
        $sheet = $spreadsheet->createSheet($index);
        $sheet->setTitle('بوابة الدفع');
        $sheet->setRightToLeft(true);
        $headers = ['رقم الدفعة', 'العميل', 'البوابة', 'المحصّل', 'العمولة', 'الصافي', 'الفاتورة', 'مرجع', 'تاريخ الدفع'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $col++;
        }
        $payments = Payment::with(['user', 'invoice'])
            ->where('payment_method', 'online')
            ->whereNotNull('payment_gateway')
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->orderBy('paid_at', 'desc')
            ->get();
        $row = 2;
        foreach ($payments as $p) {
            $fee = (float) ($p->gateway_fee_amount ?? 0);
            $net = $p->net_after_gateway_fee !== null
                ? (float) $p->net_after_gateway_fee
                : round((float) $p->amount - $fee, 2);
            $sheet->setCellValue('A' . $row, $p->payment_number);
            $sheet->setCellValue('B' . $row, $p->user->name ?? 'غير معروف');
            $sheet->setCellValue('C' . $row, (string) $p->payment_gateway);
            $sheet->setCellValue('D' . $row, (float) $p->amount);
            $sheet->setCellValue('E' . $row, $fee);
            $sheet->setCellValue('F' . $row, $net);
            $sheet->setCellValue('G' . $row, $p->invoice->invoice_number ?? '-');
            $sheet->setCellValue('H' . $row, $p->transaction_id ?? $p->reference_number ?? '-');
            $sheet->setCellValue('I' . $row, $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : '-');
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($border);
            $row++;
        }
        for ($c = 0; $c < 9; $c++) {
            $sheet->getColumnDimensionByColumn($c + 1)->setAutoSize(true);
        }
    }

}

