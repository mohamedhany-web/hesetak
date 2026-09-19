@extends('layouts.admin')

@section('title', 'تفاصيل برنامج الولاء - ' . config('app.name'))
@section('page_title', 'تفاصيل برنامج الولاء')

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
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $loyaltyProgram->name }}</h2>
            @if($loyaltyProgram->description)
                <p class="mt-1 max-w-2xl text-sm text-muted">{{ $loyaltyProgram->description }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="document.getElementById('editProgramModal').classList.remove('hidden')"
                    class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-pen text-xs"></i>
                تعديل
            </button>
            <a href="{{ route('admin.loyalty.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع
            </a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-cog text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">إعدادات البرنامج</h3>
                        <p class="mt-0.5 text-xs text-muted">قواعد كسب النقاط</p>
                    </div>
                </div>
            </div>
            <dl class="divide-y divide-line">
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">نقاط لكل شراء</dt>
                    <dd class="text-sm font-semibold tabular-nums text-ink">{{ $loyaltyProgram->points_per_purchase ?? 0 }} نقاط</dd>
                </div>
                @if($loyaltyProgram->points_per_referral)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                        <dt class="text-sm text-muted">نقاط لكل إحالة</dt>
                        <dd class="text-sm font-semibold tabular-nums text-ink">{{ $loyaltyProgram->points_per_referral }} نقاط</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
                    <dt class="text-sm text-muted">الحالة</dt>
                    <dd>
                        <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold
                            {{ $loyaltyProgram->is_active ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-rose-100 bg-rose-50 text-rose-700' }}">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            {{ $loyaltyProgram->is_active ? 'نشط' : 'معطل' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-chart-bar text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">الإحصائيات</h3>
                        <p class="mt-0.5 text-xs text-muted">المستخدمون المسجلون</p>
                    </div>
                </div>
            </div>
            <div class="p-4 sm:p-5">
                <p class="text-xs font-medium text-muted">عدد المستخدمين</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums tracking-tight text-ink">{{ $loyaltyProgram->users_count ?? $loyaltyProgram->users->count() ?? 0 }}</p>
            </div>
        </article>
    </div>

    @if($loyaltyProgram->redemption_rules)
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-exchange-alt text-sm"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-ink">قواعد الاستبدال</h3>
                        <p class="mt-0.5 text-xs text-muted">شروط استبدال النقاط</p>
                    </div>
                </div>
            </div>
            <div class="whitespace-pre-line px-4 py-4 text-sm text-muted sm:px-5">
                {{ is_array($loyaltyProgram->redemption_rules) ? json_encode($loyaltyProgram->redemption_rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $loyaltyProgram->redemption_rules }}
            </div>
        </article>
    @endif
</div>

<div id="editProgramModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('editProgramModal').classList.add('hidden')"></div>
        <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-pen text-sm"></i>
                        </span>
                        <h3 class="text-base font-semibold text-ink">تعديل برنامج الولاء</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('editProgramModal').classList.add('hidden')"
                            class="inline-flex size-9 items-center justify-center rounded-xl text-muted hover:bg-accent-soft hover:text-accent">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
            <form action="{{ route('admin.loyalty.update', $loyaltyProgram) }}" method="POST" class="space-y-4 p-4 sm:p-5">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_name" class="{{ $labelClass }}">اسم البرنامج <span class="text-rose-600">*</span></label>
                    <input type="text" name="name" id="edit_name" required value="{{ old('name', $loyaltyProgram->name) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="edit_description" class="{{ $labelClass }}">الوصف</label>
                    <textarea name="description" id="edit_description" rows="3" class="{{ $areaClass }}">{{ old('description', $loyaltyProgram->description) }}</textarea>
                </div>
                <div>
                    <label for="edit_points_per_purchase" class="{{ $labelClass }}">نقاط لكل شراء <span class="text-rose-600">*</span></label>
                    <input type="number" name="points_per_purchase" id="edit_points_per_purchase" step="0.01" min="0" required value="{{ old('points_per_purchase', $loyaltyProgram->points_per_purchase) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="edit_points_per_referral" class="{{ $labelClass }}">نقاط لكل إحالة</label>
                    <input type="number" name="points_per_referral" id="edit_points_per_referral" step="0.01" min="0" value="{{ old('points_per_referral', $loyaltyProgram->points_per_referral) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="edit_redemption_rules" class="{{ $labelClass }}">قواعد الاستبدال</label>
                    <textarea name="redemption_rules" id="edit_redemption_rules" rows="3" class="{{ $areaClass }}">{{ old('redemption_rules', is_array($loyaltyProgram->redemption_rules) ? json_encode($loyaltyProgram->redemption_rules, JSON_UNESCAPED_UNICODE) : $loyaltyProgram->redemption_rules) }}</textarea>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" {{ old('is_active', $loyaltyProgram->is_active) ? 'checked' : '' }} class="{{ $checkClass }}">
                    <label for="edit_is_active" class="text-sm font-medium text-ink">تفعيل البرنامج</label>
                </div>
                <div class="flex flex-wrap justify-end gap-3 border-t border-line pt-4">
                    <button type="button" onclick="document.getElementById('editProgramModal').classList.add('hidden')"
                            class="btn-press inline-flex h-11 items-center rounded-xl border border-line bg-surface px-6 text-sm font-medium text-ink transition hover:bg-accent-soft hover:text-accent">
                        إلغاء
                    </button>
                    <button type="submit"
                            class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                        <i class="fas fa-save text-xs"></i>
                        حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
