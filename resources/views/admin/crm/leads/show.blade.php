@extends('layouts.admin')

@section('title', 'عميل محتمل #'.$salesLead->id)
@section('page_title', $salesLead->name)

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $stages = \App\Models\SalesLead::pipelineStages();
    $statusLabels = \App\Models\SalesLead::statusLabels();
    $currentIdx = $salesLead->pipelineIndex();
    $isLost = $salesLead->status === \App\Models\SalesLead::STATUS_CLOSED_LOST;
@endphp

<div class="space-y-5">

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المبيعات · CRM · العملاء المحتملون · #{{ $salesLead->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $salesLead->name }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ $salesLead->source_label }}
                @if($salesLead->created_at)
                    · أُنشئ {{ $salesLead->created_at->format('Y-m-d') }}
                @endif
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.crm.leads.edit', $salesLead) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-pen text-xs"></i>
                تعديل
            </a>
            <a href="{{ route('admin.crm.leads.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                القائمة
            </a>
            <form method="POST" action="{{ route('admin.crm.leads.destroy', $salesLead) }}" onsubmit="return confirm('حذف هذا العميل المحتمل؟');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-rose-600 transition hover:border-rose-300 hover:bg-rose-50">
                    <i class="fas fa-trash text-xs"></i>
                    حذف
                </button>
            </form>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- مسار البيع --}}
    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-base font-semibold text-ink">مسار البيع</h3>
                <p class="mt-0.5 text-xs text-muted">مراحل المتابعة حتى الإغلاق</p>
            </div>
            <span class="inline-flex rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">{{ $salesLead->status_label }}</span>
        </div>
        <div class="overflow-x-auto px-4 py-4 sm:px-5">
            <ol class="flex min-w-max gap-1.5">
                @foreach($stages as $i => $stage)
                    @php
                        $done = ! $isLost && $currentIdx >= 0 && $i < $currentIdx;
                        $active = ! $isLost && $stage === $salesLead->status;
                    @endphp
                    <li class="flex items-center gap-1.5">
                        <span @class([
                            'inline-flex items-center rounded-lg border px-2.5 py-1.5 text-[11px] font-medium whitespace-nowrap sm:text-xs',
                            'border-accent bg-accent text-white' => $done || $active,
                            'ring-2 ring-accent/25' => $active,
                            'border-line bg-canvas text-muted' => ! $done && ! $active,
                        ])>{{ $statusLabels[$stage] ?? $stage }}</span>
                        @if(! $loop->last)
                            <span class="text-xs text-muted">›</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
        @if($isLost)
            <div class="border-t border-line px-4 py-3 text-xs text-rose-700 sm:px-5">
                مغلق كخاسر{{ $salesLead->lost_reason ? ': '.$salesLead->lost_reason : '' }}
            </div>
        @endif
    </article>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- البيانات --}}
        <div class="space-y-5 xl:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">بيانات العميل</h3>
                </div>
                <dl class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <dt class="text-xs font-medium text-muted">مالك التسويق (ثابت)</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $salesLead->marketingOwner?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">موظف المبيعات</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink">{{ $salesLead->assignedTo?->name ?? '—' }}</dd>
                    </div>
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
                        <dt class="text-xs font-medium text-muted">مجموعة الفريق</dt>
                        <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->crmGroup?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted">أنشأه</dt>
                        <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->creator?->name ?? '—' }}</dd>
                    </div>
                    @if($salesLead->crmGroup?->teamLeader)
                        <div>
                            <dt class="text-xs font-medium text-muted">قائد المجموعة</dt>
                            <dd class="mt-1 text-sm font-medium text-ink">{{ $salesLead->crmGroup->teamLeader->name }}</dd>
                        </div>
                    @endif
                </dl>
                @if($salesLead->notes)
                    <div class="border-t border-line px-4 py-4 sm:px-5">
                        <p class="text-xs font-medium text-muted">ملاحظات</p>
                        <div class="mt-2 whitespace-pre-wrap rounded-xl bg-canvas px-4 py-3 text-sm text-ink">{{ $salesLead->notes }}</div>
                    </div>
                @endif
            </article>

            @if($salesLead->commissions->isNotEmpty())
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">العمولات</h3>
                    </div>
                    <div class="divide-y divide-line">
                        @foreach($salesLead->commissions as $c)
                            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-5">
                                <div>
                                    <p class="text-sm font-medium text-ink">{{ $c->user?->name }}</p>
                                    <p class="text-xs text-muted">{{ $c->typeLabel() }}</p>
                                </div>
                                <div class="text-end">
                                    <p class="text-sm font-semibold tabular-nums text-accent">{{ number_format($c->commission_amount_egp, 2) }} {{ currency_symbol() }}</p>
                                    <p class="text-xs text-muted">{{ $c->statusLabel() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endif

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-4 sm:px-5">
                    <div>
                        <h3 class="text-base font-semibold text-ink">سجل المتابعة</h3>
                        <p class="mt-0.5 text-xs text-muted">{{ $salesLead->auditLogs->count() }} حدث</p>
                    </div>
                    <a href="{{ route('admin.crm.audit.index', ['lead_id' => $salesLead->id]) }}" class="text-xs font-medium text-accent hover:underline">عرض في السجل الكامل</a>
                </div>
                <div class="max-h-72 divide-y divide-line overflow-y-auto">
                    @forelse($salesLead->auditLogs as $log)
                        <div class="flex flex-wrap items-start justify-between gap-2 px-4 py-3 sm:px-5">
                            <div>
                                <span class="inline-flex rounded-lg bg-accent-soft px-2 py-0.5 text-[11px] font-medium text-accent">{{ $log->actionLabel() }}</span>
                                <p class="mt-1 text-xs text-muted">{{ $log->user?->name ?? 'نظام' }}</p>
                            </div>
                            <time class="text-xs tabular-nums text-muted">{{ $log->created_at?->format('Y-m-d H:i') }}</time>
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center text-sm text-muted sm:px-5">لا سجلات بعد</div>
                    @endforelse
                </div>
            </article>
        </div>

        {{-- إجراءات جانبية --}}
        <div class="space-y-5">
            @if(! $salesLead->isClosed())
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">تعيين للمبيعات</h3>
                        <p class="mt-0.5 text-xs text-muted">تصبح الحالة «مُعيَّن للمبيعات» بعد الحفظ</p>
                    </div>
                    <form method="POST" action="{{ route('admin.crm.leads.assign', $salesLead) }}" class="space-y-4 p-4 sm:p-5">
                        @csrf
                        <div>
                            <label class="{{ $labelClass }}" for="assigned_to">موظف المبيعات</label>
                            <select name="assigned_to" id="assigned_to" required class="{{ $fieldClass }}">
                                <option value="">اختر موظفاً</option>
                                @foreach($salesUsers as $u)
                                    <option value="{{ $u->id }}" @selected((int) $salesLead->assigned_to === (int) $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="crm_group_id">مجموعة الفريق</label>
                            <select name="crm_group_id" id="crm_group_id" class="{{ $fieldClass }}">
                                <option value="">— اختياري —</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g->id }}" @selected((int) $salesLead->crm_group_id === (int) $g->id)>{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                            <i class="fas fa-user-check text-xs"></i>
                            تعيين
                        </button>
                    </form>
                </article>
            @endif

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">تغيير الحالة</h3>
                    <p class="mt-0.5 text-xs text-muted">رقابة الإدارة — يُسجَّل في المتابعة</p>
                </div>
                <form method="POST" action="{{ route('admin.crm.leads.transition', $salesLead) }}" class="space-y-4 p-4 sm:p-5">
                    @csrf
                    <div>
                        <label class="{{ $labelClass }}" for="status">الحالة الجديدة</label>
                        <select name="status" id="status" required class="{{ $fieldClass }}">
                            <option value="">اختر الحالة</option>
                            <optgroup label="انتقالات مسموحة">
                                @forelse($nextStatuses ?? [] as $st)
                                    <option value="{{ $st }}">{{ $statusLabels[$st] ?? $st }}</option>
                                @empty
                                    <option value="" disabled>لا انتقالات عادية</option>
                                @endforelse
                            </optgroup>
                            <optgroup label="كل الحالات (مع فرض الحالة)">
                                @foreach($statusLabels as $st => $label)
                                    @if($st !== $salesLead->status)
                                        <option value="{{ $st }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="force" value="1" class="rounded border-line text-accent focus:ring-accent/20">
                        فرض أي حالة
                    </label>
                    <div>
                        <label class="{{ $labelClass }}" for="note">ملاحظة</label>
                        <textarea name="note" id="note" rows="3" class="{{ $areaClass }}" placeholder="سبب التغيير (موصى به)"></textarea>
                    </div>
                    <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:border-accent/30 hover:text-accent">
                        <i class="fas fa-exchange-alt text-xs"></i>
                        تحديث الحالة
                    </button>
                </form>
            </article>

            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-ink">إضافة ملاحظة</h3>
                </div>
                <form method="POST" action="{{ route('admin.crm.leads.note', $salesLead) }}" class="space-y-4 p-4 sm:p-5">
                    @csrf
                    <textarea name="note" rows="3" required class="{{ $areaClass }}" placeholder="اكتب ملاحظة للمتابعة…"></textarea>
                    <button type="submit" class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-sticky-note text-xs"></i>
                        حفظ الملاحظة
                    </button>
                </form>
            </article>
        </div>
    </div>
</div>
@endsection
