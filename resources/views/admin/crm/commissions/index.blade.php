@extends('layouts.admin')

@section('title', 'عمولات CRM')
@section('page_title', 'CRM — العمولات')

@section('content')
<div class="space-y-4">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-muted">متابعة واعتماد عمولات المبيعات والتسويق</p>
        <a href="{{ route('admin.crm.commissions.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
            <i class="fas fa-plus text-xs"></i>
            عمولة يدوية
        </a>
    </section>

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
                @forelse($commissions as $c)
                    <tr>
                        <td class="px-4 py-3">@if($c->sales_lead_id)#{{ $c->sales_lead_id }} {{ $c->lead?->name }}@else<span class="text-muted">—</span>@endif</td>
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
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-muted">لا توجد عمولات مسجّلة بعد. يمكنك إضافة عمولة يدوية من الزر أعلاه.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $commissions->links() }}</div>
    </div>
</div>
@endsection
