@extends('layouts.admin')

@section('title', 'عميل مبيعات #'.$salesLead->id)
@section('page_title', 'عميل محتمل')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المبيعات · العملاء المحتملون</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $salesLead->name }}</h2>
            <p class="mt-1 text-sm text-muted">Lead #{{ $salesLead->id }} · {{ $salesLead->status_label }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.crm.leads.show', $salesLead) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-project-diagram text-xs"></i>
                إدارة في CRM
            </a>
            <a href="{{ route('admin.sales.leads.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft">
                <i class="fas fa-arrow-right text-xs"></i>
                القائمة
            </a>
        </div>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البيانات الأساسية</h3>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ $salesLead->status_label }}</span>
        </div>
        <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
            <div>
                <dt class="text-xs font-medium text-muted">البريد</dt>
                <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->email ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-muted">الهاتف</dt>
                <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->phone ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-muted">الشركة</dt>
                <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->company ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-muted">المصدر</dt>
                <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->source_label }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-muted">كورس الاهتمام</dt>
                <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->interestedCourse?->title ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-muted">أنشأه</dt>
                <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->creator?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-muted">المسؤول</dt>
                <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->assignedTo?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-muted">تاريخ الإنشاء</dt>
                <dd class="mt-1 text-sm font-medium tabular-nums text-ink">{{ $salesLead->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
        </dl>
        @if($salesLead->notes)
            <div class="border-t border-line px-4 py-4 sm:px-5">
                <p class="text-xs font-medium text-muted">ملاحظات</p>
                <div class="mt-2 whitespace-pre-wrap rounded-xl bg-canvas px-4 py-3 text-sm text-ink">{{ $salesLead->notes }}</div>
            </div>
        @endif
    </article>

    @if($salesLead->isConverted())
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">التحويل</h3>
            </div>
            <div class="space-y-2 p-4 text-sm text-ink sm:p-5">
                @if($salesLead->converted_at)
                    <p><span class="text-muted">التاريخ:</span> {{ $salesLead->converted_at->format('Y-m-d H:i') }}</p>
                @endif
                @if($salesLead->linkedUser)
                    <p>
                        <span class="text-muted">المستخدم:</span>
                        <a href="{{ route('admin.users.show', $salesLead->linkedUser) }}" class="font-semibold text-accent hover:underline">{{ $salesLead->linkedUser->name }}</a>
                    </p>
                @endif
                @if($salesLead->convertedOrder)
                    <p>
                        <span class="text-muted">الطلب:</span>
                        <a href="{{ route('admin.orders.show', $salesLead->convertedOrder) }}" class="font-semibold text-accent hover:underline">#{{ $salesLead->convertedOrder->id }}</a>
                        — {{ $salesLead->convertedOrder->course?->title ?? '—' }}
                    </p>
                @endif
            </div>
        </article>
    @endif

    @if($salesLead->isLost() && $salesLead->lost_reason)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">سبب الخسارة</h3>
            </div>
            <div class="whitespace-pre-wrap p-4 text-sm text-ink sm:p-5">{{ $salesLead->lost_reason }}</div>
        </article>
    @endif
</div>
@endsection
