@extends('layouts.employee')

@section('title', 'لوحة المبيعات')
@section('header', 'لوحة المبيعات')

@section('content')
@php
    $leadsRoute = $leadsIndexRoute ?? 'employee.sales.leads.index';
@endphp
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-gray-600 max-w-2xl">مؤشرات الطلبات، العملاء المحتملون، إيرادات معتمدة، ومتابعة طلباتك كمندوب مبيعات.</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route($leadsRoute) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold shadow-sm transition-colors">
                <i class="fas fa-user-plus"></i> العملاء المحتملون
            </a>
            <a href="{{ route('employee.sales.orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-sm transition-colors">
                <i class="fas fa-list"></i> كل الطلبات
            </a>
            @if(!empty($useCrm))
                <a href="{{ route('employee.crm.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm transition-colors">
                    <i class="fas fa-funnel-dollar"></i> لوحة CRM
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
        <div class="rounded-2xl border-2 border-amber-200/60 bg-gradient-to-br from-white to-amber-50/80 p-4 sm:p-5 shadow-sm">
            <p class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">قيد المراجعة</p>
            <p class="text-2xl sm:text-3xl font-black text-amber-800 tabular-nums">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="rounded-2xl border-2 border-slate-200/60 bg-gradient-to-br from-white to-slate-50/80 p-4 sm:p-5 shadow-sm">
            <p class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">بلا مندوب (معلّق)</p>
            <p class="text-2xl sm:text-3xl font-black text-slate-800 tabular-nums">{{ number_format($stats['unassigned_pending']) }}</p>
        </div>
        <div class="rounded-2xl border-2 border-emerald-200/60 bg-gradient-to-br from-white to-emerald-50/80 p-4 sm:p-5 shadow-sm">
            <p class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">طلباتي المعلّقة</p>
            <p class="text-2xl sm:text-3xl font-black text-emerald-800 tabular-nums">{{ number_format($stats['mine_pending']) }}</p>
        </div>
        <div class="rounded-2xl border-2 border-blue-200/60 bg-gradient-to-br from-white to-blue-50/80 p-4 sm:p-5 shadow-sm">
            <p class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">إيراد معتمد (الشهر)</p>
            <p class="text-xl sm:text-2xl font-black text-blue-900 tabular-nums">{{ number_format($stats['revenue_month'], 2) }}</p>
        </div>
        <div class="rounded-2xl border-2 border-violet-200/60 bg-gradient-to-br from-white to-violet-50/80 p-4 sm:p-5 shadow-sm">
            <p class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">معتمدة هذا الشهر (لي)</p>
            <p class="text-2xl sm:text-3xl font-black text-violet-800 tabular-nums">{{ number_format($stats['mine_won_month'] ?? 0) }}</p>
        </div>
        <a href="{{ route($leadsRoute, array_filter(['open' => !empty($useCrm) ? 1 : null])) }}" class="rounded-2xl border-2 border-teal-200/60 bg-gradient-to-br from-white to-teal-50/80 p-4 sm:p-5 shadow-sm block hover:border-teal-300 transition-colors">
            <p class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">عملاء محتملون مفتوحون</p>
            <p class="text-2xl sm:text-3xl font-black text-teal-800 tabular-nums">{{ number_format($stats['leads_open']) }}</p>
        </a>
        <a href="{{ route($leadsRoute, array_filter(['mine' => 1, 'open' => !empty($useCrm) ? 1 : null])) }}" class="rounded-2xl border-2 border-cyan-200/60 bg-gradient-to-br from-white to-cyan-50/80 p-4 sm:p-5 shadow-sm block hover:border-cyan-300 transition-colors">
            <p class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">عملاء مفتوحون لي</p>
            <p class="text-2xl sm:text-3xl font-black text-cyan-900 tabular-nums">{{ number_format($stats['leads_mine_open']) }}</p>
        </a>
        <div class="rounded-2xl border-2 border-rose-200/60 bg-gradient-to-br from-white to-rose-50/80 p-4 sm:p-5 shadow-sm">
            <p class="text-xs sm:text-sm font-semibold text-gray-600 mb-1">مرفوضة</p>
            <p class="text-2xl sm:text-3xl font-black text-rose-800 tabular-nums">{{ number_format($stats['rejected'] ?? 0) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-900">طلباتي كمندوب</h2>
                <a href="{{ route('employee.sales.orders.index', ['mine' => 1]) }}" class="text-xs font-bold text-emerald-700 hover:underline">عرض الكل</a>
            </div>
            <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                @forelse($myOrders as $o)
                    <li>
                        <a href="{{ route('employee.sales.orders.show', $o) }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 px-5 py-3 hover:bg-emerald-50/50 transition-colors">
                            <div>
                                <span class="font-semibold text-gray-900">#{{ $o->id }}</span>
                                <span class="text-gray-700 mr-2">{{ $o->course?->title ?? '—' }}</span>
                                <div class="text-xs text-gray-500">{{ $o->user?->name }}</div>
                            </div>
                            <span class="text-sm font-bold tabular-nums text-emerald-800">{{ number_format((float) $o->amount, 2) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-gray-500 text-sm">لا توجد طلبات مسندة إليك بعد. استعرض «كل الطلبات» واضغط «استلام الطلب».</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900">آخر النشاط على المنصة</h2>
                <p class="text-xs text-gray-500 mt-1">أحدث {{ $recentOrders->count() }} طلباً</p>
            </div>
            <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                @forelse($recentOrders as $o)
                    <li>
                        <a href="{{ route('employee.sales.orders.show', $o) }}" class="block px-5 py-3 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between gap-2">
                                <span class="font-medium text-gray-900">#{{ $o->id }} — {{ \Illuminate\Support\Str::limit($o->course?->title ?? '—', 32) }}</span>
                                @if($o->status === \App\Models\Order::STATUS_PENDING)
                                    <span class="text-xs font-bold text-amber-700 shrink-0">معلّق</span>
                                @elseif($o->status === \App\Models\Order::STATUS_APPROVED)
                                    <span class="text-xs font-bold text-emerald-700 shrink-0">معتمد</span>
                                @else
                                    <span class="text-xs font-bold text-rose-700 shrink-0">مرفوض</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-3">
                                <span>{{ $o->user?->name }}</span>
                                @if($o->salesOwner)
                                    <span>المندوب: {{ $o->salesOwner->name }}</span>
                                @else
                                    <span class="text-amber-600">بدون مندوب</span>
                                @endif
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-gray-500 text-sm">لا توجد طلبات بعد.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900">
        <strong>تنبيه:</strong> الموافقة النهائية على الطلبات والصرف تتم من لوحة الإدارة. دورك متابعة العميل، تسجيل الملاحظات، واستلام الطلب في خط الأنابيب.
        @if(!empty($useCrm))
            لمتابعة العملاء المحتملين وحالات البيع استخدم <a href="{{ route('employee.crm.dashboard') }}" class="font-bold underline">CRM حصتك</a>.
        @endif
    </div>
</div>
@endsection
