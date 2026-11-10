@extends('layouts.admin')

@section('title', 'العملاء المحتملون - حصتك')
@section('page_title', 'العملاء المحتملون')

@section('content')
@php
    $audience = $audience ?? 'all';
    $stats = $stats ?? ['total' => 0, 'open' => 0, 'filtered' => 0, 'closed_won' => 0, 'registered_total' => 0];
    $leads = $leads ?? null;
    $registeredUsers = $registeredUsers ?? null;
    $recentUnlinkedUsers = $recentUnlinkedUsers ?? collect();
    $leadLinksByUserId = $leadLinksByUserId ?? collect();
    $recentRegistrationCutoff = $recentRegistrationCutoff ?? now()->subDays(14);
    $kpis = [
        ['label' => 'عملاء محتملون', 'value' => $stats['total'], 'icon' => 'fa-users', 'tone' => 'accent', 'note' => 'سجلات CRM'],
        ['label' => 'مفتوحة', 'value' => $stats['open'], 'icon' => 'fa-folder-open', 'tone' => 'accent', 'note' => 'قيد المتابعة'],
        ['label' => 'مسجّلون', 'value' => $stats['registered_total'] ?? 0, 'icon' => 'fa-user-check', 'tone' => 'metal', 'note' => 'طلاب وأولياء أمور'],
        ['label' => 'مغلقة ناجحة', 'value' => $stats['closed_won'], 'icon' => 'fa-check-circle', 'tone' => 'muted', 'note' => 'صفقات مكتملة'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $tabClass = fn (string $key) => 'btn-press inline-flex h-9 items-center gap-2 rounded-xl border px-4 text-sm font-medium transition '
        .($audience === $key ? 'border-accent bg-accent-soft text-accent' : 'border-line bg-surface text-ink-soft hover:border-accent/30');
@endphp

<div class="space-y-5">

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المبيعات · CRM · العملاء المحتملون</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">العملاء المحتملون والمسجّلون</h2>
            <p class="mt-1 text-sm text-muted">تابع العملاء المحتملين واطّلع على المستخدمين المسجّلين حديثاً</p>
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
            <p class="mt-0.5 text-xs text-muted">بالاسم أو البريد أو الهاتف</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('admin.crm.leads.index', array_merge(request()->except('page', 'audience'), ['audience' => 'all'])) }}" class="{{ $tabClass('all') }}">الكل</a>
                <a href="{{ route('admin.crm.leads.index', array_merge(request()->except('page', 'audience'), ['audience' => 'leads'])) }}" class="{{ $tabClass('leads') }}">عملاء محتملون</a>
                <a href="{{ route('admin.crm.leads.index', array_merge(request()->except('page', 'audience'), ['audience' => 'registered'])) }}" class="{{ $tabClass('registered') }}">مسجّلون بالمنصة</a>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.crm.leads.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-3 md:items-end">
            <input type="hidden" name="audience" value="{{ $audience }}">
            <div>
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}"
                       placeholder="الاسم، البريد، أو الهاتف..." class="{{ $fieldClass }}">
            </div>
            @if($audience !== 'registered')
                <div>
                    <label class="{{ $labelClass }}" for="status">الحالة (عملاء محتملون)</label>
                    <select id="status" name="status" class="{{ $fieldClass }}">
                        <option value="">كل الحالات</option>
                        @foreach($statusLabels as $val => $label)
                            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div></div>
            @endif
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تصفية
                </button>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.crm.leads.index', ['audience' => $audience]) }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح
                    </a>
                @endif
            </div>
        </form>
    </article>

    @if($audience !== 'registered' && $leads)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-ink">عملاء محتملون</h3>
                    <p class="mt-0.5 text-xs text-muted">{{ number_format($leads->total()) }} نتيجة</p>
                </div>
            </div>
            <div class="admin-table-wrap overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs text-muted">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">#</th>
                            <th class="px-4 py-3 text-start font-medium">النوع</th>
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
                                    <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">عميل محتمل</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-ink">{{ $lead->name }}</p>
                                    @if($lead->phone || $lead->email)
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $lead->phone ?: $lead->email }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-soft">{{ $lead->marketingOwner?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $lead->assignedTo?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-lg bg-canvas px-2.5 py-1 text-xs font-medium text-ink">{{ $lead->status_label }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        <a href="{{ route('admin.crm.leads.show', $lead) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent" title="تفاصيل"><i class="fas fa-eye"></i></a>
                                        <a href="{{ route('admin.crm.leads.edit', $lead) }}" class="btn-press inline-flex h-8 items-center rounded-lg border border-line px-3 text-xs font-medium text-ink hover:border-accent/30 hover:text-accent" title="تعديل"><i class="fas fa-pen"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-muted">لا يوجد عملاء محتملون مطابقون للتصفية.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($leads->hasPages())
                <div class="border-t border-line px-4 py-4 sm:px-5">{{ $leads->links() }}</div>
            @endif
        </article>
    @endif

    @if($audience === 'registered' && $registeredUsers)
        @include('admin.crm.leads.partials.registered-users-table', [
            'registeredUsers' => $registeredUsers,
            'leadLinksByUserId' => $leadLinksByUserId,
            'recentRegistrationCutoff' => $recentRegistrationCutoff,
            'title' => 'مسجّلون بالمنصة',
        ])
    @endif

    @if($audience === 'all' && $recentUnlinkedUsers->isNotEmpty())
        @include('admin.crm.leads.partials.registered-users-table', [
            'registeredUsers' => $recentUnlinkedUsers,
            'leadLinksByUserId' => $leadLinksByUserId,
            'recentRegistrationCutoff' => $recentRegistrationCutoff,
            'title' => 'مسجّلون غير مربوطين بعميل محتمل',
            'paginated' => false,
        ])
    @endif
</div>
@endsection
