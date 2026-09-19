@extends('layouts.employee')

@section('title', 'CRM حصتك')
@section('header', 'CRM حصتك')

@section('content')
<div class="space-y-6">
  @include('partials.crm-employee-nav', ['role' => $role])

  @if(session('success'))<div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif

  <div class="rounded-2xl bg-gradient-to-l from-indigo-600 to-violet-700 text-white p-5">
    <p class="text-xs uppercase tracking-wider text-indigo-200">دورك: {{ $roleLabel ?? $role }}</p>
    <p class="font-black text-lg mt-1">ما لم يُسجَّل في CRM، لم يحدث</p>
    <p class="text-indigo-100 text-sm mt-1">أي تواصل أو بيع أو عمولة يجب أن يمر عبر النظام.</p>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">عملائي</p><p class="text-2xl font-black">{{ $stats['my_leads'] }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">مفتوحة</p><p class="text-2xl font-black text-sky-700">{{ $stats['open'] }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">ناجحة</p><p class="text-2xl font-black text-emerald-700">{{ $stats['closed_won'] }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">بانتظار الدفع</p><p class="text-2xl font-black text-amber-700">{{ $stats['payment_pending'] }}</p></div>
  </div>

  @if(isset($stats['subscriptions']))
    <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 flex flex-wrap items-center justify-between gap-3">
      <div class="text-sm text-teal-900"><span class="font-bold">اشتركوا من عملائي:</span> {{ number_format($stats['subscriptions']) }} — عمولة: <strong>{{ number_format($stats['my_commissions'] ?? 0, 2) }} $</strong></div>
      <a href="{{ route('employee.crm.marketing.desk') }}" class="text-sm font-bold text-teal-800 underline">لوحة المسوق بالعمولة ←</a>
    </div>
  @elseif(isset($stats['my_commissions']))
    <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">إجمالي عمولاتي:</span> <strong>{{ number_format($stats['my_commissions'], 2) }} $</strong></div>
  @endif
  @if(($role ?? null) === 'sales')
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex flex-wrap justify-between gap-3 items-center text-sm">
      <span class="text-emerald-900 font-semibold">استلم بيانات المسوقين بالعمولة وتابع تقدمها</span>
      <a href="{{ route('employee.crm.marketing-inbox.index') }}" class="font-bold text-emerald-800 underline">صندوق المسوقين ←</a>
    </div>
  @endif
  @if(isset($stats['team_members']))
    <div class="rounded-xl border bg-white p-4 flex flex-wrap justify-between items-center gap-3">
      <div class="text-sm"><span class="text-gray-500">أعضاء الفريق:</span> <strong>{{ $stats['team_members'] }}</strong></div>
      <a href="{{ route('employee.crm.team.index') }}" class="text-sky-700 font-bold text-sm">تفاصيل أداء كل عضو ←</a>
    </div>
  @endif
  @if(isset($stats['team_revenue']))
    <div class="grid grid-cols-2 gap-3">
      <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">إيراد الفريق:</span> <strong>{{ number_format($stats['team_revenue'], 2) }} $</strong></div>
      <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">عمولات الفريق:</span> <strong>{{ number_format($stats['team_commissions'], 2) }} $</strong></div>
    </div>
  @endif
  @if(isset($stats['revenue_month']))
    <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">إيراد الشهر (كل الطلبات):</span> <strong>{{ number_format($stats['revenue_month'], 2) }} $</strong></div>
  @endif
  @if(isset($stats['total_leads']))
    <div class="grid grid-cols-2 gap-3">
      <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">كل العملاء:</span> <strong>{{ $stats['total_leads'] }}</strong></div>
      <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">طلبات معلقة (المنصة):</span> <strong>{{ $stats['total_orders_pending'] }}</strong></div>
    </div>
  @endif
  @if(isset($stats['pending_commissions']))
    <div class="grid grid-cols-2 gap-3">
      <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">عمولات معلقة:</span> <strong>{{ $stats['pending_commissions'] }}</strong></div>
      <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">عملاء بانتظار الدفع:</span> <strong>{{ $stats['pending_payments'] }}</strong></div>
    </div>
  @endif

  <div class="rounded-2xl border bg-white overflow-hidden">
    <div class="px-5 py-3 border-b font-bold">آخر العملاء المحتملين</div>
    <ul class="divide-y">
      @forelse($recentLeads as $lead)
        <li><a href="{{ route('employee.crm.leads.show', $lead) }}" class="flex justify-between px-5 py-3 hover:bg-gray-50 text-sm">
          <span class="font-semibold">{{ $lead->name }}</span><span class="text-gray-500">{{ $lead->status_label }}</span>
        </a></li>
      @empty
        <li class="px-5 py-8 text-center text-gray-500 text-sm">لا يوجد عملاء بعد</li>
      @endforelse
    </ul>
  </div>
</div>
@endsection
