@extends('layouts.admin')

@section('title', 'تعديل اتفاقية التقسيط')
@section('page_title', 'تعديل اتفاقية التقسيط')

@section('content')
@php
    $agreement = $agreement ?? null;
    $plans = $plans ?? collect();
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المالية · التقسيط · اتفاقيات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $agreement->student->name ?? 'طالب غير معروف' }}</h2>
            <p class="mt-1 text-sm text-muted">يمكنك تغيير حالة الاتفاقية أو نقلها إلى خطة أخرى، بالإضافة إلى إضافة ملاحظات إدارية</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.installments.agreements.show', $agreement) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للتفاصيل
            </a>
        </div>
    </section>

    <div class="mx-auto max-w-3xl">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">تحديث الاتفاقية</h3>
                <p class="mt-0.5 text-xs text-muted">تعديل الحالة والملاحظات الإدارية</p>
            </div>

            <form action="{{ route('admin.installments.agreements.update', $agreement) }}" method="POST" class="space-y-6 p-4 sm:p-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">الخطة المرتبطة</label>
                        <select name="installment_plan_id" disabled class="h-11 w-full rounded-xl border border-line bg-canvas-muted px-4 text-sm text-muted">
                            <option>{{ $agreement->plan->name ?? 'خطة عامة' }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="status">حالة الاتفاقية *</label>
                        <select id="status" name="status" class="{{ $fieldClass }}">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $agreement->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="notes">ملاحظات إدارية</label>
                    <textarea id="notes" name="notes" rows="4" class="{{ $areaClass }}">{{ old('notes', $agreement->notes) }}</textarea>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line pt-5">
                    <a href="{{ route('admin.installments.agreements.show', $agreement) }}" class="btn-press inline-flex h-11 items-center rounded-xl border border-line px-5 text-sm font-medium text-ink transition hover:bg-canvas">إلغاء</a>
                    <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                        <i class="fas fa-save text-xs"></i>
                        تحديث الاتفاقية
                    </button>
                </div>
            </form>
        </article>
    </div>
</div>
@endsection
