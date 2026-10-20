@extends('layouts.employee')

@section('title', 'مسوق بالعمولة')
@section('header', 'لوحة المسوق بالعمولة')

@section('content')
<div class="space-y-6">
  @include('partials.crm-employee-nav', ['role' => $role])

  @if(session('success'))<div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif

  <div class="rounded-2xl bg-gradient-to-l from-teal-600 to-cyan-700 text-white p-5 sm:p-6">
    <p class="text-xs uppercase tracking-wider text-teal-100">عمولة التسويق: {{ number_format($summary['commission_percent'], 1) }}٪ لكل مشترك</p>
    <h2 class="font-black text-xl mt-1">العملاء الذين تجلبهم أنت</h2>
    <p class="text-teal-50 text-sm mt-1 max-w-2xl">أضف الليدز، أرسلها للمبيعات، وتابع من اشترك وفي أي كورس — عمولتك تُحسب تلقائياً عند اعتماد الاشتراك.</p>
    <div class="mt-4 flex flex-wrap gap-2">
      <a href="{{ route('employee.crm.leads.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white text-teal-800 text-sm font-bold">+ إضافة عميل جديد</a>
      <a href="{{ route('employee.crm.leads.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-500/30 text-white text-sm font-bold border border-white/30">كل عملائي</a>
    </div>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">جلبتُ (إجمالي)</p><p class="text-2xl font-black">{{ number_format($summary['total_leads']) }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">مفتوحة</p><p class="text-2xl font-black text-sky-700">{{ number_format($summary['open_leads']) }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">اشتركوا</p><p class="text-2xl font-black text-emerald-700">{{ number_format($summary['subscribed_count']) }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">نسبة التحويل</p><p class="text-2xl font-black text-violet-700">{{ number_format($summary['conversion_rate'], 1) }}٪</p></div>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">أُرسل للمبيعات</span><p class="font-black text-lg">{{ number_format($summary['submitted_to_sales']) }}</p></div>
    <div class="rounded-xl border bg-amber-50 border-amber-200 p-4 text-sm"><span class="text-amber-800">بانتظار استلام سيلز</span><p class="font-black text-lg text-amber-900">{{ number_format($summary['awaiting_sales']) }}</p></div>
    <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">عند المبيعات الآن</span><p class="font-black text-lg">{{ number_format($summary['in_sales_pipeline']) }}</p></div>
    <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">عمولة إجمالي</span><p class="font-black text-lg text-violet-800">{{ number_format($summary['commissions_total'], 2) }} {{ currency_symbol() }}</p></div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">معلّقة</span><p class="font-bold">{{ number_format($summary['commissions_pending'], 2) }} {{ currency_symbol() }}</p></div>
    <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">معتمدة</span><p class="font-bold text-emerald-700">{{ number_format($summary['commissions_approved'], 2) }} {{ currency_symbol() }}</p></div>
    <div class="rounded-xl border bg-white p-4 text-sm"><span class="text-gray-500">مدفوعة</span><p class="font-bold text-sky-700">{{ number_format($summary['commissions_paid'], 2) }} {{ currency_symbol() }}</p></div>
  </div>

  <div class="rounded-2xl border bg-white overflow-hidden">
    <div class="px-5 py-3 border-b font-bold">اشتراكات حسب الكورس</div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-xs text-gray-600"><tr>
          <th class="px-4 py-3 text-right">الكورس</th>
          <th class="px-4 py-3 text-right">مشتركون</th>
          <th class="px-4 py-3 text-right">إيراد مرتبط</th>
          <th class="px-4 py-3 text-right">عمولة متوقعة ({{ number_format($summary['commission_percent'], 1) }}٪)</th>
        </tr></thead>
        <tbody class="divide-y">
          @forelse($summary['by_course'] as $row)
            <tr>
              <td class="px-4 py-3 font-semibold">{{ $row['course_title'] }}</td>
              <td class="px-4 py-3 tabular-nums">{{ $row['subscribers'] }}</td>
              <td class="px-4 py-3 tabular-nums">{{ format_money($row['revenue']) }}</td>
              <td class="px-4 py-3 font-bold text-violet-800 tabular-nums">{{ number_format($row['revenue'] * $summary['commission_percent'] / 100, 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">لا اشتراكات بعد — عندما يشترك عميل جلبته سيظهر هنا مع الكورس.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="rounded-2xl border bg-white overflow-hidden">
      <div class="px-5 py-3 border-b font-bold">من اشتركوا (عميلك)</div>
      <ul class="divide-y max-h-96 overflow-y-auto">
        @forelse($subscribers as $lead)
          <li>
            <a href="{{ route('employee.crm.leads.show', $lead) }}" class="block px-5 py-3 hover:bg-emerald-50/50 text-sm">
              <div class="flex justify-between gap-2">
                <span class="font-bold text-gray-900">{{ $lead->name }}</span>
                <span class="text-xs font-bold text-emerald-700">{{ $lead->status_label }}</span>
              </div>
              <div class="text-xs text-gray-500 mt-1">
                كورس: {{ $lead->order?->course?->title ?? $lead->interestedCourse?->title ?? '—' }}
                @if($lead->assignedTo) — سيلز: {{ $lead->assignedTo->name }}@endif
              </div>
            </a>
          </li>
        @empty
          <li class="px-5 py-8 text-center text-gray-500 text-sm">لا مشتركون بعد</li>
        @endforelse
      </ul>
    </div>

    <div class="rounded-2xl border bg-white overflow-hidden">
      <div class="px-5 py-3 border-b font-bold">آخر عملائي ومتابعة التقدم</div>
      <ul class="divide-y max-h-96 overflow-y-auto">
        @forelse($leads as $lead)
          <li>
            <a href="{{ route('employee.crm.leads.show', $lead) }}" class="block px-5 py-3 hover:bg-gray-50 text-sm">
              <div class="flex justify-between gap-2">
                <span class="font-bold">{{ $lead->name }}</span>
                <span class="text-xs text-gray-600">{{ $lead->status_label }}</span>
              </div>
              <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-3">
                <span>كورس اهتمام: {{ $lead->interestedCourse?->title ?? '—' }}</span>
                @if($lead->submitted_to_sales_at && ! $lead->assigned_to)
                  <span class="text-amber-700 font-semibold">بانتظار استلام المبيعات</span>
                @elseif($lead->assignedTo)
                  <span>عند: {{ $lead->assignedTo->name }}</span>
                @else
                  <span class="text-teal-700">جاهز للإرسال للمبيعات</span>
                @endif
              </div>
            </a>
          </li>
        @empty
          <li class="px-5 py-8 text-center text-gray-500 text-sm">أضف أول عميل من زر الإضافة أعلاه</li>
        @endforelse
      </ul>
    </div>
  </div>

  <div class="rounded-2xl border bg-white overflow-hidden">
    <div class="px-5 py-3 border-b font-bold flex justify-between"><span>آخر العمولات</span><a href="{{ route('employee.crm.commissions') }}" class="text-xs text-violet-700 font-bold">الكل</a></div>
    <ul class="divide-y">
      @forelse($recentCommissions as $c)
        <li class="px-5 py-3 text-sm flex justify-between gap-2">
          <span>{{ $c->lead?->name ?? ('#'.$c->sales_lead_id) }} — {{ $c->statusLabel() }}</span>
          <span class="font-bold tabular-nums">{{ number_format($c->commission_amount_egp, 2) }} {{ currency_symbol() }} ({{ number_format((float)$c->commission_percent, 1) }}٪)</span>
        </li>
      @empty
        <li class="px-5 py-8 text-center text-gray-500 text-sm">تظهر العمولة بعد اشتراك العميل واعتماد الطلب</li>
      @endforelse
    </ul>
  </div>
</div>
@endsection
