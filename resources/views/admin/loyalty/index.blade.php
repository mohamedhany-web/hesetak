@extends('layouts.admin')

@section('title', 'برامج الولاء - ' . config('app.name'))
@section('page_title', 'برامج الولاء')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $checkClass = 'size-4 rounded border-line text-accent focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · الولاء</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">برامج الولاء</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">إدارة برامج نقاط الولاء</p>
        </div>
        <button type="button" onclick="document.getElementById('createProgramModal').classList.remove('hidden')"
                class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
            <i class="fas fa-plus text-xs"></i>
            إضافة برنامج جديد
        </button>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(isset($stats))
        <section class="grid gap-3 sm:grid-cols-2">
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                    <i class="fas fa-star text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">إجمالي البرامج</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-ink">{{ $stats['total'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <i class="fas fa-check-circle text-sm"></i>
                </div>
                <p class="mt-3 text-xs font-medium text-muted">النشطة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-emerald-700">{{ $stats['active'] ?? 0 }}</p>
            </article>
        </section>
    @endif

    @if(isset($programs) && $programs->count() > 0)
        <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($programs as $program)
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-base font-semibold text-ink">{{ $program->name }}</h3>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                                {{ $program->is_active ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-rose-100 bg-rose-50 text-rose-700' }}">
                                <span class="size-1.5 rounded-full bg-current"></span>
                                {{ $program->is_active ? 'نشط' : 'معطل' }}
                            </span>
                        </div>
                        @if($program->description)
                            <p class="mt-2 text-sm text-muted">{{ Str::limit($program->description, 100) }}</p>
                        @endif
                    </div>
                    <dl class="space-y-2 px-4 py-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">نقاط لكل شراء</dt>
                            <dd class="font-semibold tabular-nums text-ink">{{ $program->points_per_purchase ?? 0 }}</dd>
                        </div>
                        @if($program->points_per_referral)
                            <div class="flex justify-between gap-3">
                                <dt class="text-muted">نقاط لكل إحالة</dt>
                                <dd class="font-semibold tabular-nums text-ink">{{ $program->points_per_referral }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">عدد المستخدمين</dt>
                            <dd class="font-semibold tabular-nums text-ink">{{ $program->users_count ?? 0 }}</dd>
                        </div>
                    </dl>
                    <div class="border-t border-line px-4 py-3">
                        <a href="{{ route('admin.loyalty.show', $program) }}"
                           class="text-sm font-medium text-accent hover:underline">عرض التفاصيل</a>
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <article class="rounded-2xl border border-line bg-surface p-12 text-center shadow-soft">
            <div class="mx-auto inline-flex size-16 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-star text-2xl"></i>
            </div>
            <p class="mt-4 text-lg font-semibold text-ink">لا توجد برامج ولاء</p>
        </article>
    @endif
</div>

<div id="createProgramModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('createProgramModal').classList.add('hidden')"></div>
        <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-plus text-sm"></i>
                        </span>
                        <h3 class="text-base font-semibold text-ink">إضافة برنامج ولاء جديد</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('createProgramModal').classList.add('hidden')"
                            class="inline-flex size-9 items-center justify-center rounded-xl text-muted hover:bg-accent-soft hover:text-accent">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
            <form action="{{ route('admin.loyalty.store') }}" method="POST" class="space-y-4 p-4 sm:p-5">
                @csrf
                <div>
                    <label for="modal_name" class="{{ $labelClass }}">اسم البرنامج <span class="text-rose-600">*</span></label>
                    <input type="text" name="name" id="modal_name" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="modal_description" class="{{ $labelClass }}">الوصف</label>
                    <textarea name="description" id="modal_description" rows="3" class="{{ $areaClass }}"></textarea>
                </div>
                <div>
                    <label for="modal_points_per_purchase" class="{{ $labelClass }}">نقاط لكل شراء <span class="text-rose-600">*</span></label>
                    <input type="number" name="points_per_purchase" id="modal_points_per_purchase" step="0.01" min="0" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="modal_points_per_referral" class="{{ $labelClass }}">نقاط لكل إحالة</label>
                    <input type="number" name="points_per_referral" id="modal_points_per_referral" step="0.01" min="0" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="modal_redemption_rules" class="{{ $labelClass }}">قواعد الاستبدال</label>
                    <textarea name="redemption_rules" id="modal_redemption_rules" rows="3" class="{{ $areaClass }}"></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="modal_is_active" value="1" checked class="{{ $checkClass }}">
                    <label for="modal_is_active" class="text-sm font-medium text-ink">تفعيل البرنامج</label>
                </div>
                <div class="flex flex-wrap justify-end gap-3 border-t border-line pt-4">
                    <button type="button" onclick="document.getElementById('createProgramModal').classList.add('hidden')"
                            class="btn-press inline-flex h-11 items-center rounded-xl border border-line bg-surface px-6 text-sm font-medium text-ink transition hover:bg-accent-soft hover:text-accent">
                        إلغاء
                    </button>
                    <button type="submit"
                            class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                        <i class="fas fa-save text-xs"></i>
                        حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
