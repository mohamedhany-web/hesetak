@extends('layouts.employee')

@section('title', 'التقارير المالية للمبيعات')
@section('header', 'CRM — التقارير المالية للمبيعات')

@section('content')
<div class="space-y-6">
  @include('partials.crm-employee-nav', ['role' => $role])

  <div class="rounded-2xl bg-gradient-to-l from-emerald-600 to-teal-700 text-white p-5">
    <p class="font-black text-lg">تقارير مبيعات CRM فقط</p>
    <p class="text-emerald-100 text-sm mt-1">طلبات، إيرادات، عمولات، وتقارير الفريق — بدون تقارير المنصة العامة (مهام/إجازات/محاسبة كاملة).</p>
  </div>

  <form method="GET" class="flex flex-wrap gap-2 items-center">
    <label class="text-sm font-bold text-gray-700">الفترة:</label>
    @foreach(['week'=>'أسبوع','month'=>'شهر','quarter'=>'ربع سنة','year'=>'سنة'] as $k=>$l)
      <a href="{{ route('employee.crm.sales-financial', ['period'=>$k]) }}"
         class="px-3 py-1.5 rounded-lg text-sm font-bold {{ $period===$k ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700' }}">{{ $l }}</a>
    @endforeach
    <span class="text-xs text-gray-500 mr-2">{{ $report['start']->format('Y-m-d') }} — {{ $report['end']->format('Y-m-d') }}</span>
  </form>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">إيراد معتمد</p><p class="text-2xl font-black text-emerald-700">{{ number_format($report['orders']['revenue_approved'], 2) }} {{ currency_symbol() }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">طلبات معلقة</p><p class="text-2xl font-black text-amber-700">{{ $report['orders']['pending'] }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">عمولات معلقة</p><p class="text-2xl font-black text-violet-700">{{ number_format($report['commissions']['pending'], 2) }} {{ currency_symbol() }}</p></div>
    <div class="rounded-xl border bg-white p-4"><p class="text-xs text-gray-500">عملاء بانتظار الدفع</p><p class="text-2xl font-black text-rose-700">{{ $report['leads']['payment_pending'] }}</p></div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="rounded-2xl border bg-white p-5 space-y-3">
      <h3 class="font-bold">ملخص الطلبات (مبيعات)</h3>
      <dl class="grid grid-cols-2 gap-2 text-sm">
        <div><dt class="text-gray-500">إجمالي الطلبات</dt><dd class="font-bold">{{ $report['orders']['total'] }}</dd></div>
        <div><dt class="text-gray-500">معتمدة</dt><dd class="font-bold text-emerald-700">{{ $report['orders']['approved'] }}</dd></div>
        <div><dt class="text-gray-500">مرفوضة</dt><dd class="font-bold">{{ $report['orders']['rejected'] }}</dd></div>
        <div><dt class="text-gray-500">مرتبطة بـ CRM</dt><dd class="font-bold">{{ $report['orders']['crm_linked'] }}</dd></div>
        <div class="col-span-2"><dt class="text-gray-500">إيراد CRM المعتمد</dt><dd class="font-bold">{{ number_format($report['orders']['crm_revenue'], 2) }} {{ currency_symbol() }}</dd></div>
      </dl>
    </div>
    <div class="rounded-2xl border bg-white p-5 space-y-3">
      <h3 class="font-bold">ملخص العمولات</h3>
      <dl class="grid grid-cols-2 gap-2 text-sm">
        <div><dt class="text-gray-500">معلقة</dt><dd class="font-bold text-amber-700">{{ number_format($report['commissions']['pending'], 2) }} {{ currency_symbol() }}</dd></div>
        <div><dt class="text-gray-500">معتمدة</dt><dd class="font-bold text-emerald-700">{{ number_format($report['commissions']['approved'], 2) }} {{ currency_symbol() }}</dd></div>
        <div><dt class="text-gray-500">مصروفة</dt><dd class="font-bold">{{ number_format($report['commissions']['paid'], 2) }} {{ currency_symbol() }}</dd></div>
        @foreach($report['commissions']['by_type'] as $type => $total)
          <div><dt class="text-gray-500">عمولة {{ $type }}</dt><dd class="font-bold">{{ number_format((float)$total, 2) }} {{ currency_symbol() }}</dd></div>
        @endforeach
      </dl>
    </div>
  </div>

  @if($report['revenue_by_sales']->isNotEmpty())
  <div class="rounded-2xl border bg-white overflow-hidden">
    <div class="px-5 py-3 border-b font-bold">إيراد المبيعات حسب المندوب</div>
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-xs uppercase"><tr>
        <th class="px-4 py-3 text-right">المندوب</th>
        <th class="px-4 py-3 text-right">طلبات</th>
        <th class="px-4 py-3 text-right">إيراد</th>
      </tr></thead>
      <tbody class="divide-y">
        @foreach($report['revenue_by_sales'] as $row)
          <tr>
            <td class="px-4 py-3 font-semibold">{{ $row->sales_name }}</td>
            <td class="px-4 py-3">{{ $row->orders_count }}</td>
            <td class="px-4 py-3 font-bold">{{ number_format((float)$row->revenue, 2) }} {{ currency_symbol() }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif

  @if($teamPerformance->isNotEmpty())
  <div class="rounded-2xl border bg-white overflow-hidden">
    <div class="px-5 py-3 border-b font-bold">أداء فرق المبيعات والتسويق</div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase"><tr>
          <th class="px-4 py-3 text-right">المجموعة</th>
          <th class="px-4 py-3 text-right">العضو</th>
          <th class="px-4 py-3 text-right">الدور</th>
          <th class="px-4 py-3 text-right">عملاء</th>
          <th class="px-4 py-3 text-right">ناجحة</th>
          <th class="px-4 py-3 text-right">إيراد</th>
          <th class="px-4 py-3 text-right">عمولات</th>
        </tr></thead>
        <tbody class="divide-y">
          @foreach($teamPerformance as $row)
            <tr>
              <td class="px-4 py-3">{{ $row['group_name'] }}</td>
              <td class="px-4 py-3 font-semibold">{{ $row['user_name'] }}</td>
              <td class="px-4 py-3">{{ $row['role_label'] }}</td>
              <td class="px-4 py-3">{{ $row['total_leads'] }}</td>
              <td class="px-4 py-3 text-emerald-700 font-bold">{{ $row['closed_won'] }}</td>
              <td class="px-4 py-3">{{ number_format($row['revenue'], 2) }} {{ currency_symbol() }}</td>
              <td class="px-4 py-3">{{ number_format($row['commissions'], 2) }} {{ currency_symbol() }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  <div class="rounded-2xl border bg-white overflow-hidden">
    <div class="px-5 py-3 border-b font-bold">تقارير الفريق المرفوعة (تسويق/مبيعات/قادة)</div>
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-xs uppercase"><tr>
        <th class="px-4 py-3 text-right">الموظف</th>
        <th class="px-4 py-3 text-right">العنوان</th>
        <th class="px-4 py-3 text-right">النوع</th>
        <th class="px-4 py-3 text-right">التاريخ</th>
        <th class="px-4 py-3 text-right">الحالة</th>
      </tr></thead>
      <tbody class="divide-y">
        @forelse($report['submitted_reports'] as $r)
          <tr>
            <td class="px-4 py-3">{{ $r->user?->name }}</td>
            <td class="px-4 py-3 font-semibold">{{ $r->title }}</td>
            <td class="px-4 py-3">{{ $r->type_label }}</td>
            <td class="px-4 py-3 text-gray-600">{{ $r->created_at?->format('Y-m-d') }}</td>
            <td class="px-4 py-3">{{ $r->status_label }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا توجد تقارير مرفوعة في هذه الفترة</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
