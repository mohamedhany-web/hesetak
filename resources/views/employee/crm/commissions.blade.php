@extends('layouts.employee')

@section('title', 'عمولاتي')
@section('header', 'CRM — عمولاتي')

@section('content')
<div class="space-y-4">
  @include('partials.crm-employee-nav', ['role' => $role])

  <div class="rounded-2xl border bg-white overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-xs uppercase"><tr>
        <th class="px-4 py-3 text-right">العميل</th><th class="px-4 py-3 text-right">النوع</th><th class="px-4 py-3 text-right">المبلغ</th><th class="px-4 py-3 text-right">الحالة</th><th></th>
      </tr></thead>
      <tbody class="divide-y">
        @forelse($commissions as $c)
          <tr>
            <td class="px-4 py-3">#{{ $c->sales_lead_id }} {{ $c->lead?->name }}</td>
            <td class="px-4 py-3">{{ $c->typeLabel() }}</td>
            <td class="px-4 py-3 font-bold">{{ number_format($c->commission_amount_egp, 2) }} {{ currency_symbol() }}</td>
            <td class="px-4 py-3">{{ $c->statusLabel() }}</td>
            <td class="px-4 py-3">
              @if($role === 'finance' && $c->status === \App\Models\CrmCommission::STATUS_PENDING)
                <form method="POST" action="{{ route('employee.crm.commissions.approve', $c) }}">@csrf<button class="text-emerald-600 font-bold text-xs">اعتماد للصرف</button></form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">لا توجد عمولات بعد</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="p-4">{{ $commissions->links() }}</div>
  </div>
</div>
@endsection
