@extends('layouts.employee')



@section('title', 'طلبات بانتظار الدفع')

@section('header', 'CRM — الطلبات')



@section('content')

<div class="space-y-4">

  @include('partials.crm-employee-nav', ['role' => $role])



  <p class="text-sm text-gray-600">

    @if($viewAll ?? false)

      عرض كل الطلبات كما في لوحة الإدارة — راجعها ثم اعتمد بعد التأكد من استلام المبلغ.

    @else

      راجع الطلبات المرتبطة بعملاء CRM ثم اضغط اعتماد بعد التأكد من استلام المبلغ.

    @endif

  </p>



  @if($viewAll ?? false)

  <form method="GET" class="flex flex-wrap gap-2 items-center text-sm">

    <select name="status" class="rounded-lg border px-3 py-2">

      <option value="pending" @selected(request('status', 'pending')==='pending')>معلقة</option>

      <option value="approved" @selected(request('status')==='approved')>معتمدة</option>

      <option value="rejected" @selected(request('status')==='rejected')>مرفوضة</option>

    </select>

    <label class="flex items-center gap-2"><input type="checkbox" name="crm_only" value="1" @checked(request('crm_only'))> CRM فقط</label>

    <button class="px-3 py-2 rounded-lg bg-slate-700 text-white font-bold">تصفية</button>

  </form>

  @endif



  <div class="rounded-2xl border bg-white overflow-hidden">

    <table class="min-w-full text-sm">

      <thead class="bg-gray-50 text-xs uppercase">

        <tr>

          <th class="px-4 py-3 text-right">#</th>

          <th class="px-4 py-3 text-right">الطالب</th>

          <th class="px-4 py-3 text-right">الكورس</th>

          <th class="px-4 py-3 text-right">المبلغ</th>

          <th class="px-4 py-3 text-right">طريقة الدفع</th>

          @if($viewAll ?? false)<th class="px-4 py-3 text-right">مندوب</th>@endif

          <th class="px-4 py-3 text-right"></th>

        </tr>

      </thead>

      <tbody class="divide-y">

        @forelse($orders as $order)

          <tr>

            <td class="px-4 py-3">{{ $order->id }}</td>

            <td class="px-4 py-3 font-semibold">{{ $order->user?->name ?? '—' }}</td>

            <td class="px-4 py-3">{{ $order->course?->title ?? '—' }}</td>

            <td class="px-4 py-3 font-bold">{{ number_format((float) $order->amount, 2) }} {{ currency_symbol() }}</td>

            <td class="px-4 py-3">{{ $order->payment_method ?? '—' }}</td>

            @if($viewAll ?? false)<td class="px-4 py-3 text-gray-600">{{ $order->salesOwner?->name ?? '—' }}</td>@endif

            <td class="px-4 py-3">

              @if($order->status === \App\Models\Order::STATUS_PENDING)

              <form method="POST" action="{{ route('employee.crm.orders.approve', $order) }}" onsubmit="return confirm('تأكيد اعتماد الطلب واستلام الدفع؟')">

                @csrf

                <button type="submit" class="text-emerald-600 font-bold text-xs">اعتماد الطلب</button>

              </form>

              @else

                <span class="text-gray-500 text-xs">{{ $order->status }}</span>

              @endif

            </td>

          </tr>

        @empty

          <tr><td colspan="{{ ($viewAll ?? false) ? 7 : 6 }}" class="px-4 py-10 text-center text-gray-500">لا توجد طلبات</td></tr>

        @endforelse

      </tbody>

    </table>

    <div class="p-4">{{ $orders->links() }}</div>

  </div>

</div>

@endsection

