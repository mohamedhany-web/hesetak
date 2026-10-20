@extends('layouts.admin')

@section('title', 'عمولات CRM')
@section('header', 'CRM — العمولات')

@section('content')
<div class="space-y-4">
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl bg-white border p-4"><p class="text-xs text-slate-500">معلقة</p><p class="text-2xl font-bold">{{ $stats['pending'] }}</p></div>
        <div class="rounded-xl bg-white border p-4"><p class="text-xs text-slate-500">معتمدة</p><p class="text-2xl font-bold text-emerald-700">{{ $stats['approved'] }}</p></div>
        <div class="rounded-xl bg-white border p-4"><p class="text-xs text-slate-500">الإجمالي</p><p class="text-xl font-bold">{{ format_money($stats['total_amount']) }}</p></div>
    </div>
    <div class="rounded-2xl bg-white border overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase"><tr>
                <th class="px-4 py-3 text-right">Lead</th><th class="px-4 py-3 text-right">المستفيد</th><th class="px-4 py-3 text-right">النوع</th><th class="px-4 py-3 text-right">المبلغ</th><th class="px-4 py-3 text-right">الحالة</th><th></th>
            </tr></thead>
            <tbody class="divide-y">
                @foreach($commissions as $c)
                    <tr>
                        <td class="px-4 py-3">#{{ $c->sales_lead_id }} {{ $c->lead?->name }}</td>
                        <td class="px-4 py-3">{{ $c->user?->name }}</td>
                        <td class="px-4 py-3">{{ $c->typeLabel() }}</td>
                        <td class="px-4 py-3 font-bold">{{ format_money($c->commission_amount_egp) }}</td>
                        <td class="px-4 py-3">{{ $c->statusLabel() }}</td>
                        <td class="px-4 py-3">
                            @if($c->status === \App\Models\CrmCommission::STATUS_PENDING)
                                <form method="POST" action="{{ route('admin.crm.commissions.approve', $c) }}">@csrf
                                    <button class="text-emerald-600 font-bold text-xs">اعتماد</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $commissions->links() }}</div>
    </div>
</div>
@endsection
