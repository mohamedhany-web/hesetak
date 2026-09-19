@extends('layouts.admin')

@section('title', 'وارد الإشعارات - حصتك')
@section('page_title', 'وارد الإشعارات')

@section('content')
@php
    $status = request('status');
    $readCount = max(0, (int) ($stats['total'] ?? 0) - (int) ($stats['unread'] ?? 0));
    $filterTabs = [
        ['key' => null, 'label' => 'الكل', 'url' => route('admin.notifications.inbox')],
        ['key' => 'unread', 'label' => 'غير مقروء', 'url' => route('admin.notifications.inbox', ['status' => 'unread'])],
        ['key' => 'read', 'label' => 'مقروء', 'url' => route('admin.notifications.inbox', ['status' => 'read'])],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">تنبيهات حسابك · تذاكر الدعم والتنبيهات التشغيلية</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">وارد الإشعارات</h2>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            @if(($stats['unread'] ?? 0) > 0)
                <form action="{{ route('admin.notifications.inbox.mark-all-read') }}" method="post" class="inline" id="inbox-mark-all-form">
                    @csrf
                    <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-check-double text-xs"></i>
                        تعيين الكل كمقروء
                    </button>
                </form>
            @endif
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.notifications.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-paper-plane text-xs"></i>
                    إرسال للطلاب
                </a>
            @endif
        </div>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-bell text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">إجمالي الوارد</p>
            <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($stats['total'] ?? 0) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-envelope-open text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">غير مقروء</p>
            <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($stats['unread'] ?? 0) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-canvas-muted text-muted">
                <i class="fas fa-check text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">مقروء</p>
            <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($readCount) }}</p>
        </article>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">قائمة الإشعارات</h3>
                <p class="mt-0.5 text-xs text-muted">اضغط أي إشعار لفتح التفاصيل أو الإجراء المرتبط</p>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach($filterTabs as $tab)
                    @php $active = $status === $tab['key'] || ($tab['key'] === null && ! request()->filled('status')); @endphp
                    <a href="{{ $tab['url'] }}"
                       class="btn-press rounded-xl px-3 py-1.5 text-xs font-medium transition {{ $active ? 'bg-accent text-white' : 'text-muted hover:bg-accent-soft hover:text-accent' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="divide-y divide-line">
            @forelse ($notifications as $notification)
                <a href="{{ $notification->action_url ?: route('admin.notifications.show', $notification) }}"
                   class="flex items-start gap-3 px-4 py-4 transition hover:bg-[#f7f8fa] sm:gap-4 sm:px-5 {{ ! $notification->is_read ? 'bg-accent-soft/30' : '' }}">
                    <div class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-xl text-sm {{ $notification->is_read ? 'bg-canvas-muted text-muted' : 'bg-accent-soft text-accent' }}">
                        <i class="{{ $notification->type_icon }}"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-semibold text-ink">{{ $notification->title }}</p>
                            @if(! $notification->is_read)
                                <span class="rounded-lg bg-metal/15 px-2 py-0.5 text-[10px] font-medium text-metal">جديد</span>
                            @endif
                        </div>
                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-muted">{{ $notification->message }}</p>
                        <p class="mt-2 text-[11px] tabular-nums text-muted">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @if(! $notification->is_read)
                        <span class="mt-2 size-2 shrink-0 rounded-full bg-accent" title="غير مقروء"></span>
                    @endif
                </a>
            @empty
                <div class="px-4 py-16 text-center sm:px-5">
                    <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <p class="text-sm font-medium text-ink">لا توجد إشعارات حالياً</p>
                    <p class="mt-1 text-xs text-muted">
                        @if($status === 'unread')
                            لا يوجد غير مقروء في الوارد.
                        @elseif($status === 'read')
                            لا توجد إشعارات مقروءة ضمن هذا الفلتر.
                        @else
                            سيظهر هنا أي تنبيه موجّه لحسابك.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">
                {{ $notifications->links() }}
            </div>
        @endif
    </article>

    <p class="text-xs text-muted">
        صفحة «إدارة الإشعارات» مخصّصة لإرسال تنبيهات للطلاب، بينما هذه الصفحة تعرض ما يصل إلى حسابك أنت.
    </p>
</div>

@push('scripts')
<script>
document.getElementById('inbox-mark-all-form')?.addEventListener('submit', function (e) {
    e.preventDefault();
    var form = this;
    var token = document.querySelector('meta[name="csrf-token"]');
    fetch(form.action, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
        },
        body: new FormData(form),
        credentials: 'same-origin'
    }).then(function () { window.location.reload(); }).catch(function () { form.submit(); });
});
</script>
@endpush
@endsection
