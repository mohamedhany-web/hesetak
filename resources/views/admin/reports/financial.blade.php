@extends('layouts.admin')

@section('title', 'التقارير المالية')
@section('header', 'التقارير المالية')

@section('content')
<div class="space-y-6">
    <!-- الهيدر -->
    <section class="rounded-2xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 bg-slate-50 border-b border-slate-200 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md">
                    <i class="fas fa-money-bill-wave text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-900">التقارير المالية</h2>
                    <p class="text-sm text-slate-600 mt-1">تقارير شاملة عن الفواتير، المدفوعات، المعاملات، والمصروفات</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <i class="fas fa-arrow-right"></i>
                    العودة
                </a>
                <a href="{{ route('admin.reports.export.financial', array_merge(request()->all())) }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-file-excel"></i>
                    تصدير إلى Excel
                </a>
            </div>
        </div>
    </section>

    <!-- الفلاتر -->
    <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
            <h3 class="text-lg font-black text-slate-900 mb-2 flex items-center gap-2">
                <i class="fas fa-filter text-blue-600"></i>
                الفلاتر
            </h3>
        </div>
        <div class="p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">الفترة</label>
                    <select name="period" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="today" {{ $period == 'today' ? 'selected' : '' }}>اليوم</option>
                        <option value="week" {{ $period == 'week' ? 'selected' : '' }}>هذا الأسبوع</option>
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>هذا الشهر</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>هذا العام</option>
                        <option value="all" {{ $period == 'all' ? 'selected' : '' }}>الكل</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">من تاريخ</label>
                    <input type="date" name="start_date" value="{{ $startDate ? $startDate->format('Y-m-d') : '' }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">إلى تاريخ</label>
                    <input type="date" name="end_date" value="{{ $endDate ? $endDate->format('Y-m-d') : '' }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">النوع</label>
                    <select name="type" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="all" {{ $type == 'all' ? 'selected' : '' }}>الكل</option>
                        <option value="summary" {{ $type == 'summary' ? 'selected' : '' }}>الملخص</option>
                        <option value="invoices" {{ $type == 'invoices' ? 'selected' : '' }}>الفواتير فقط</option>
                        <option value="payments" {{ $type == 'payments' ? 'selected' : '' }}>المدفوعات فقط</option>
                        <option value="transactions" {{ $type == 'transactions' ? 'selected' : '' }}>المعاملات فقط</option>
                        <option value="expenses" {{ $type == 'expenses' ? 'selected' : '' }}>المصروفات فقط</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex items-end gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-filter"></i>
                        تطبيق الفلاتر
                    </button>
                    <a href="{{ route('admin.reports.financial') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </section>

    <!-- الإحصائيات المالية -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl bg-white border border-slate-200 shadow-md p-5">
            <div class="flex items-center justify-between mb-2">
                <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <i class="fas fa-arrow-up text-base"></i>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-slate-600">إجمالي الإيرادات</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total_revenue'] ?? 0, 2) }} {{ currency_symbol() }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-md p-5">
            <div class="flex items-center justify-between mb-2">
                <div class="w-12 h-12 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600">
                    <i class="fas fa-arrow-down text-base"></i>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-slate-600">إجمالي المصروفات</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total_expenses'] ?? 0, 2) }} {{ currency_symbol() }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-md p-5">
            <div class="flex items-center justify-between mb-2">
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                    <i class="fas fa-chart-line text-base"></i>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-slate-600">الربح الصافي</p>
                    <p class="text-2xl font-black {{ ($stats['net_profit'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($stats['net_profit'] ?? 0, 2) }} {{ currency_symbol() }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-md p-5">
            <div class="flex items-center justify-between mb-2">
                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600">
                    <i class="fas fa-file-invoice text-base"></i>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-slate-600">عدد الفواتير</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total_invoices'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- الفواتير -->
    @if(in_array($type, ['all', 'invoices']))
    <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-blue-600"></i>
                الفواتير
            </h3>
            <span class="text-xs font-semibold text-slate-600">{{ $invoices->total() }} فاتورة</span>
        </div>
        <div class="p-6">
            @if($invoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">رقم الفاتورة</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">المستخدم</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">المبلغ</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الحالة</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">تاريخ الإنشاء</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @foreach($invoices as $invoice)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">{{ htmlspecialchars($invoice->invoice_number ?? 'N/A', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($invoice->user->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">{{ number_format($invoice->total_amount ?? 0, 2) }} {{ currency_symbol() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-semibold border 
                                        {{ $invoice->status == 'paid' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 
                                           ($invoice->status == 'pending' ? 'bg-amber-100 text-amber-700 border-amber-200' : 
                                           'bg-rose-100 text-rose-700 border-rose-200') }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ htmlspecialchars($invoice->status, ENT_QUOTES, 'UTF-8') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($invoices->hasPages())
                    <div class="mt-5 border-t border-slate-200 pt-5">
                        {{ $invoices->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-blue-600">
                        <i class="fas fa-file-invoice text-2xl"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-900 mb-1">لا توجد بيانات</p>
                    <p class="text-xs text-slate-600">لا توجد فواتير مطابقة للبحث الحالي.</p>
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- المدفوعات -->
    @if(in_array($type, ['all', 'payments']))
    <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <i class="fas fa-credit-card text-blue-600"></i>
                المدفوعات
            </h3>
            <span class="text-xs font-semibold text-slate-600">{{ $payments->total() }} دفع</span>
        </div>
        <div class="p-6">
            @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">المستخدم</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">المبلغ</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">طريقة الدفع</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الحالة</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">تاريخ الدفع</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @foreach($payments as $payment)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">{{ $payment->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($payment->user->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-emerald-600">{{ number_format($payment->amount, 2) }} {{ currency_symbol() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($payment->payment_method ?? '-', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-semibold border {{ $payment->status == 'completed' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-amber-100 text-amber-700 border-amber-200' }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ htmlspecialchars($payment->status, ENT_QUOTES, 'UTF-8') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($payments->hasPages())
                    <div class="mt-5 border-t border-slate-200 pt-5">
                        {{ $payments->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-blue-600">
                        <i class="fas fa-credit-card text-2xl"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-900 mb-1">لا توجد بيانات</p>
                    <p class="text-xs text-slate-600">لا توجد مدفوعات مطابقة للبحث الحالي.</p>
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- المعاملات -->
    @if(in_array($type, ['all', 'transactions']))
    <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <i class="fas fa-right-left text-blue-600"></i>
                المعاملات
            </h3>
            <span class="text-xs font-semibold text-slate-600">{{ $transactions->total() }} معاملة</span>
        </div>
        <div class="p-6">
            @if($transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">المستخدم</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">النوع</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">المبلغ</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الحالة</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @foreach($transactions as $transaction)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">{{ $transaction->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($transaction->user->name ?? 'غير محدد', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($transaction->type ?? '-', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">{{ number_format((float)($transaction->amount ?? 0), 2) }} {{ currency_symbol() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($transaction->status ?? '-', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $transaction->created_at ? $transaction->created_at->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($transactions->hasPages())
                    <div class="mt-5 border-t border-slate-200 pt-5">
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-blue-600">
                        <i class="fas fa-right-left text-2xl"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-900 mb-1">لا توجد بيانات</p>
                    <p class="text-xs text-slate-600">لا توجد معاملات مطابقة للبحث الحالي.</p>
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- المصروفات -->
    @if(in_array($type, ['all', 'expenses']))
    <section class="rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <i class="fas fa-receipt text-blue-600"></i>
                المصروفات
            </h3>
            <span class="text-xs font-semibold text-slate-600">{{ $expenses->total() }} مصروف</span>
        </div>
        <div class="p-6">
            @if($expenses->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">رقم المصروف</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">العنوان</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الفئة</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">المبلغ</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الحالة</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">تاريخ المصروف</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @foreach($expenses as $expense)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">{{ htmlspecialchars($expense->expense_number ?? 'N/A', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($expense->title ?? '-', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($expense->category ?? '-', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-rose-600">{{ number_format((float)($expense->amount ?? 0), 2) }} {{ currency_symbol() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ htmlspecialchars($expense->status ?? '-', ENT_QUOTES, 'UTF-8') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">{{ $expense->expense_date ? $expense->expense_date->format('d/m/Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($expenses->hasPages())
                    <div class="mt-5 border-t border-slate-200 pt-5">
                        {{ $expenses->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-blue-600">
                        <i class="fas fa-receipt text-2xl"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-900 mb-1">لا توجد بيانات</p>
                    <p class="text-xs text-slate-600">لا توجد مصروفات مطابقة للبحث الحالي.</p>
                </div>
            @endif
        </div>
    </section>
    @endif
</div>
@endsection
