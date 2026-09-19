@extends('layouts.admin')

@section('title', 'مراجعة طلبات المعلمين - حصتك')
@section('page_title', 'مراجعة طلبات التوظيف')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $statusCards = [
        ['label' => 'مسودات', 'value' => $stats['draft'] ?? 0, 'status' => 'draft', 'icon' => 'fa-file-alt', 'tone' => 'muted'],
        ['label' => 'قيد المراجعة', 'value' => $stats['pending'], 'status' => 'pending', 'icon' => 'fa-inbox', 'tone' => 'metal'],
        ['label' => 'بانتظار التفعيل', 'value' => $stats['approved'], 'status' => 'approved', 'icon' => 'fa-user-check', 'tone' => 'accent'],
        ['label' => 'مفعّل', 'value' => $stats['activated'], 'status' => 'activated', 'icon' => 'fa-chalkboard-teacher', 'tone' => 'accent'],
        ['label' => 'مرفوض', 'value' => $stats['rejected'], 'status' => 'rejected', 'icon' => 'fa-ban', 'tone' => 'danger'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
        'danger' => 'bg-danger/10 text-danger',
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التوظيف · مراجعة الطلبات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">مراجعة طلبات التوظيف</h2>
            <p class="mt-1 text-sm text-muted">عرض ومراجعة كل طلبات التقديم مع البيانات والمستندات.</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.tutor-applications.hub') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:text-accent">
                <i class="fas fa-briefcase text-xs"></i>
                لوحة التوظيف
            </a>
            <a href="{{ $applyUrl }}" target="_blank" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-link text-xs"></i>
                لينك التقديم
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($statusCards as $card)
            <a href="{{ route('admin.tutor-applications.index', ['status' => $card['status']]) }}"
               class="rounded-2xl border bg-surface p-4 shadow-soft transition {{ request('status') === $card['status'] ? 'border-accent ring-1 ring-accent/20' : 'border-line hover:border-accent/30' }}">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$card['tone']] }}">
                    <i class="fas {{ $card['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($card['value']) }}</p>
            </a>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">ابحث بالاسم أو البريد أو الجوال، أو صفِّ حسب الحالة</p>
        </div>
        <form method="GET" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-3 md:items-end">
            <div>
                <label class="{{ $labelClass }}" for="search">بحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="اسم / بريد / جوال" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    <option value="draft" @selected(request('status') === 'draft')>مسودة</option>
                    <option value="pending" @selected(request('status') === 'pending')>قيد المراجعة</option>
                    <option value="approved" @selected(request('status') === 'approved')>بانتظار التفعيل</option>
                    <option value="activated" @selected(request('status') === 'activated')>مفعّل</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>مرفوض</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تصفية
                </button>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.tutor-applications.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
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
                <h3 class="text-base font-semibold text-ink">سجل الطلبات</h3>
                <p class="mt-0.5 text-xs text-muted">{{ number_format($applications->total()) }} طلب</p>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="w-full min-w-[900px] text-right text-sm">
                <thead class="bg-[#f7f8fa] text-[11px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">المتقدم</th>
                        <th class="px-3 py-3 font-medium">التواصل</th>
                        <th class="px-3 py-3 font-medium">البيانات</th>
                        <th class="px-3 py-3 font-medium">الحالة</th>
                        <th class="px-3 py-3 font-medium">التاريخ</th>
                        <th class="px-5 py-3 font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($applications as $app)
                        @php
                            $badgeClass = match ($app->status) {
                                'activated' => 'bg-accent-soft text-accent',
                                'approved' => 'bg-metal/15 text-metal',
                                'rejected' => 'bg-danger/10 text-danger',
                                default => 'bg-canvas-muted text-muted',
                            };
                        @endphp
                        <tr class="transition hover:bg-[#f7f8fa]">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if($app->photo_path)
                                        <img src="{{ route('admin.tutor-applications.file', [$app, 'photo']) }}" alt="" class="size-10 rounded-xl border border-line object-cover">
                                    @else
                                        <span class="inline-flex size-10 items-center justify-center rounded-xl bg-canvas-muted font-bold text-muted">{{ mb_substr($app->full_name, 0, 1) }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-semibold text-ink">{{ $app->full_name }}</p>
                                        <p class="mt-0.5 line-clamp-1 text-xs text-muted">{{ $app->headline }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-xs text-muted">
                                <p dir="ltr">{{ $app->email }}</p>
                                <p class="mt-0.5" dir="ltr">{{ $app->phone }}</p>
                            </td>
                            <td class="px-3 py-3 text-xs text-muted">
                                <p>{{ $app->education ?: '—' }}</p>
                                <p class="mt-0.5">{{ $app->years_experience !== null ? $app->years_experience.' سنة خبرة' : '—' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">{{ $app->statusLabel() }}</span>
                                @if($app->user)
                                    <p class="mt-1 text-[11px] text-muted">حساب: {{ $app->user->name }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 tabular-nums text-muted">{{ $app->created_at?->format('Y-m-d') }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.tutor-applications.show', $app) }}"
                                   class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-accent-soft text-accent transition hover:bg-accent hover:text-white"
                                   title="{{ $app->canActivateAccount() ? 'مراجعة / تفعيل' : 'عرض البيانات' }}">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا توجد طلبات</p>
                                <p class="mt-1 text-xs text-muted">ستظهر هنا طلبات التوظيف القادمة من لينك التقديم.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $applications->withQueryString()->links() }}</div>
        @endif
    </article>
</div>
@endsection
