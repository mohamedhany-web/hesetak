@extends('layouts.admin')

@section('title', 'العملاء المحتملون - حصتك')
@section('page_title', 'العملاء المحتملون')

@section('content')
@php
    $stats = $stats ?? ['total' => 0, 'open' => 0, 'filtered' => $leads->total(), 'closed_won' => 0];
    $kpis = [
        ['label' => 'الإجمالي', 'value' => $stats['total'], 'icon' => 'fa-users', 'tone' => 'accent', 'note' => 'كل العملاء المحتملين'],
        ['label' => 'مفتوحة', 'value' => $stats['open'], 'icon' => 'fa-folder-open', 'tone' => 'accent', 'note' => 'قيد المتابعة'],
        ['label' => 'نتيجة التصفية', 'value' => $stats['filtered'], 'icon' => 'fa-filter', 'tone' => 'metal', 'note' => 'بعد البحث والحالة'],
        ['label' => 'مغلقة ناجحة', 'value' => $stats['closed_won'], 'icon' => 'fa-check-circle', 'tone' => 'muted', 'note' => 'صفقات مكتملة'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المبيعات · CRM · العملاء المحتملون</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">العملاء المحتملون</h2>
            <p class="mt-1 text-sm text-muted">ابحث وصِفِّ حسب الحالة ثم افتح تفاصيل كل عميل</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.crm.leads.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                إضافة عميل
            </a>
            <a href="{{ route('admin.crm.pipeline') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-columns text-xs"></i>
                Pipeline
            </a>
            <a href="{{ route('admin.crm.dashboard') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft">
                <i class="fas fa-chart-pie text-xs"></i>
                لوحة CRM
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">بالاسم أو البريد أو الهاتف، أو حسب حالة المسار</p>
        </div>
        <form method="GET" action="{{ route('admin.crm.leads.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-3 md:items-end">
            <div>
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}"
                       placeholder="الاسم، البريد، أو الهاتف..." class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">كل الحالات</option>
                    @foreach($statusLabels as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تصفية
                </button>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.crm.leads.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح
                    </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">قائمة العملاء</h3>
                <p class="mt-0.5 text-xs text-muted">{{ number_format($leads->total()) }} نتيجة</p>
            </div>
            <a href="{{ route('admin.crm.leads.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent">
                <i class="fas fa-plus"></i>
                جديد
            </a>
        </div>
        <div class="admin-table-wrap overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">#</th>
                        <th class="px-4 py-3 text-start font-medium">الاسم</th>
                        <th class="px-4 py-3 text-start font-medium">مالك التسويق</th>
                        <th class="px-4 py-3 text-start font-medium">المبيعات</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3 tabular-nums text-muted">{{ $lead->id }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $lead->name }}</p>
                                @if($lead->phone || $lead->email)
                                    <p class="mt-0.5 text-[11px] text-muted">{{ $lead->phone ?: $lead->email }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-soft">{{ $lead->marketingOwner?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $lead->assignedTo?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ $lead->status_label }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <a href="{{ route('admin.crm.leads.show', $lead) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent" title="تفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.crm.leads.edit', $lead) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent" title="تعديل">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.crm.leads.destroy', $lead) }}" class="inline" onsubmit="return confirm('حذف هذا العميل المحتمل؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-rose-600 hover:border-rose-300 hover:bg-rose-50" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا يوجد عملاء محتملون</p>
                                <p class="mt-1 text-xs text-muted">ابدأ بإضافة أول عميل محتمل.</p>
                                <a href="{{ route('admin.crm.leads.create') }}" class="btn-press mt-4 inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                                    <i class="fas fa-plus text-xs"></i>
                                    إضافة عميل
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $leads->links() }}</div>
        @endif
    </article>
</div>
@endsection
