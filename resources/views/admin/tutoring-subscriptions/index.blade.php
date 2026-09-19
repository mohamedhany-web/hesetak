@extends('layouts.admin')

@section('title', 'اشتراكات التدريس - حصتك')
@section('page_title', 'اشتراكات التدريس')

@section('content')
@php $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink'; @endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">التجارة · التحكم بالطالب</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">اشتراكات الباقات الفردية</h2>
            <p class="mt-1 text-sm text-muted">نشط: {{ $stats['active'] }} · حصص متبقية: {{ $stats['sessions_left'] }}</p>
        </div>
        <a href="{{ route('admin.student-entitlements.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink">أرصدة الحصص</a>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <form method="GET" class="grid gap-3 rounded-2xl border border-line bg-surface p-4 sm:grid-cols-4">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="بحث طالب…" class="{{ $fieldClass }}">
        <input type="number" name="user_id" value="{{ request('user_id') }}" placeholder="user_id" class="{{ $fieldClass }}">
        <select name="status" class="{{ $fieldClass }}">
            <option value="">كل الحالات</option>
            @foreach(['active'=>'نشط','expired'=>'منتهٍ','cancelled'=>'ملغى'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn-press h-11 rounded-xl bg-accent text-sm font-medium text-white">تصفية</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-line bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-canvas-muted text-xs text-muted">
                <tr>
                    <th class="px-4 py-3 text-start">الطالب</th>
                    <th class="px-4 py-3 text-start">المجموعة / الباقة</th>
                    <th class="px-4 py-3 text-start">الحصص</th>
                    <th class="px-4 py-3 text-start">الرصيد المرتبط</th>
                    <th class="px-4 py-3 text-start">الصلاحية</th>
                    <th class="px-4 py-3 text-start">الحالة</th>
                    <th class="px-4 py-3 text-start"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                    @php
                        $ent = $sub->entitlement;
                        $desynced = $ent && (
                            (int) $ent->units_total !== (int) $sub->sessions_total
                            || (int) $ent->units_used !== (int) $sub->sessions_used
                        );
                    @endphp
                    <tr class="border-t border-line {{ $desynced ? 'bg-amber-50/60' : '' }}">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-ink">{{ $sub->user?->name }}</div>
                            <div class="text-xs text-muted">{{ $sub->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $sub->tutoringGroup?->title }}</div>
                            <div class="text-xs text-muted">{{ $sub->package?->name }}</div>
                            @if($sub->order)
                                <a href="{{ route('admin.orders.show', $sub->order) }}" class="text-[11px] font-medium text-accent">طلب #{{ $sub->order_id }}</a>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ max(0, (int)$sub->sessions_total - (int)$sub->sessions_used) }} / {{ $sub->sessions_total }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($ent)
                                {{ $ent->unitsLeft() }} / {{ $ent->units_total }}
                                @if($desynced)<div class="font-semibold text-amber-700">غير متزامن</div>@endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-muted">{{ $sub->expires_at?->format('Y-m-d') ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $sub->statusLabel() }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.tutoring-subscriptions.show', $sub) }}" class="text-accent text-xs font-semibold">تفاصيل</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted">لا توجد اشتراكات.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $subscriptions->links() }}
</div>
@endsection
