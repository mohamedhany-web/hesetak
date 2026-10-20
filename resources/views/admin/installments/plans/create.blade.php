@extends('layouts.admin')

@section('title', 'إضافة خطة تقسيط جديدة - ' . config('app.name'))
@section('page_title', 'إضافة خطة تقسيط جديدة')

@section('content')
@php
    $frequencyUnits = $frequencyUnits ?? [];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المالية · التقسيط · خطط</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إنشاء خطة تقسيط</h2>
            <p class="mt-1 text-sm text-muted">اربط الخطة بكورس معين، حدّد قيمة الأقساط وفتراتها، واختر إن كان النظام سيولّد الخطط تلقائياً عند تسجيل الطالب</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.installments.plans.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة لقائمة الخطط
            </a>
        </div>
    </section>

    <div class="mx-auto max-w-4xl">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات الخطة</h3>
                <p class="mt-0.5 text-xs text-muted">أدخل التفاصيل الأساسية، ثم تابع بتحديد الأقساط والدورية</p>
            </div>

            <form action="{{ route('admin.installments.plans.store') }}" method="POST" class="space-y-6 p-4 sm:p-5">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}" for="name">اسم الخطة *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="{{ $fieldClass }}">
                        @error('name')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="advanced_course_id">الكورس المرتبط (اختياري)</label>
                        <select id="advanced_course_id" name="advanced_course_id" class="{{ $fieldClass }}">
                            <option value="">خطة عامة</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('advanced_course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->title }} ({{ number_format($course->price ?? 0, 2) }} {{ currency_symbol() }})
                                </option>
                            @endforeach
                        </select>
                        @error('advanced_course_id')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="description">تفاصيل الخطة</label>
                    <textarea id="description" name="description" rows="3" class="{{ $areaClass }}" placeholder="أضف تفاصيل توضيحية إضافية عن خطة التقسيط">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-line bg-accent-soft/50 px-4 py-3 text-xs leading-relaxed text-ink">
                    <strong class="mb-1 block text-sm font-semibold">نصائح سريعة</strong>
                    - اترك المبلغ الإجمالي فارغاً ليتم استخدام سعر الكورس إن وجد.
                    <br>- يمكنك استخدام الخطة لكورس واحد أو كخطة عامة لكافة الطلاب.
                </div>

                <div class="rounded-xl border border-line bg-canvas/40 p-4 sm:p-5">
                    <h4 class="mb-4 text-sm font-semibold text-ink">الجانب المالي</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $labelClass }}" for="total_amount">إجمالي المبلغ</label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" id="total_amount" name="total_amount" value="{{ old('total_amount') }}"
                                       class="{{ $fieldClass }} ps-12" placeholder="يتم استخدام سعر الكورس إن تركته فارغًا">
                                <span class="absolute inset-y-0 start-4 flex items-center text-xs font-medium text-muted">{{ currency_symbol() }}</span>
                            </div>
                            @error('total_amount')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="deposit_amount">الدفعة المقدمة</label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" id="deposit_amount" name="deposit_amount" value="{{ old('deposit_amount', 0) }}"
                                       class="{{ $fieldClass }} ps-12">
                                <span class="absolute inset-y-0 start-4 flex items-center text-xs font-medium text-muted">{{ currency_symbol() }}</span>
                            </div>
                            @error('deposit_amount')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="installments_count">عدد الأقساط *</label>
                            <input type="number" min="1" max="36" id="installments_count" name="installments_count" value="{{ old('installments_count', 6) }}" required class="{{ $fieldClass }}">
                            @error('installments_count')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-line bg-canvas/40 p-4 sm:p-5">
                    <h4 class="mb-4 text-sm font-semibold text-ink">الدورية والسماح</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $labelClass }}" for="frequency_unit">وحدة الدورية *</label>
                            <select id="frequency_unit" name="frequency_unit" class="{{ $fieldClass }}">
                                @foreach($frequencyUnits as $key => $label)
                                    <option value="{{ $key }}" {{ old('frequency_unit', 'month') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('frequency_unit')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="frequency_interval">الفاصل الزمني *</label>
                            <input type="number" min="1" max="12" id="frequency_interval" name="frequency_interval" value="{{ old('frequency_interval', 1) }}" required class="{{ $fieldClass }}">
                            @error('frequency_interval')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="grace_period_days">فترة السماح (أيام)</label>
                            <input type="number" min="0" max="30" id="grace_period_days" name="grace_period_days" value="{{ old('grace_period_days', 0) }}" class="{{ $fieldClass }}">
                            @error('grace_period_days')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="inline-flex items-center gap-3 text-sm font-medium text-ink">
                        <input type="checkbox" name="auto_generate_on_enrollment" value="1" {{ old('auto_generate_on_enrollment') ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent/20">
                        إنشاء جدول الأقساط تلقائيًا عند تفعيل التسجيل
                    </label>
                    <label class="inline-flex items-center gap-3 text-sm font-medium text-ink">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent/20">
                        تفعيل الخطة فورًا
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line pt-5">
                    <a href="{{ route('admin.installments.plans.index') }}" class="btn-press inline-flex h-11 items-center rounded-xl border border-line px-5 text-sm font-medium text-ink transition hover:bg-canvas">إلغاء</a>
                    <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                        <i class="fas fa-save text-xs"></i>
                        حفظ الخطة
                    </button>
                </div>
            </form>
        </article>
    </div>
</div>
@endsection
