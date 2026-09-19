@extends('layouts.admin')

@section('title', 'تعديل مادة - حصتك')
@section('page_title', 'تعديل المادة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $icons = [
        'fas fa-book' => 'كتاب',
        'fas fa-book-open' => 'كتاب مفتوح',
        'fas fa-language' => 'لغة',
        'fas fa-graduation-cap' => 'تخرج',
        'fas fa-globe' => 'عالمي',
        'fas fa-calculator' => 'رياضيات',
        'fas fa-atom' => 'علوم',
        'fas fa-history' => 'تاريخ',
        'fas fa-palette' => 'فنون',
        'fas fa-laptop-code' => 'حاسوب',
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.academic-subjects.index', ['track' => $academicSubject->academic_year_id]) }}" class="hover:text-accent">المواد</a>
                · تعديل
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل المادة</h2>
            <p class="mt-1 text-sm text-muted">{{ $academicSubject->name }} · {{ $academicSubject->code }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.academic-subjects.show', $academicSubject) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                عرض الكورسات
            </a>
            <a href="{{ route('admin.academic-subjects.index', ['track' => $academicSubject->academic_year_id]) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger shadow-soft">
            <p class="mb-2 font-semibold">يرجى تصحيح ما يلي:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.academic-subjects.update', $academicSubject) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات المادة</h3>
                <p class="mt-0.5 text-xs text-muted">الاسم والرمز والسنة والترتيب</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="academic_year_id">سنة المدرسة <span class="font-normal text-muted">(اختياري)</span></label>
                    <select name="academic_year_id" id="academic_year_id" class="{{ $fieldClass }}">
                        <option value="">— مادة عامة (بدون سنة) —</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected((string) old('academic_year_id', $academicSubject->academic_year_id) === (string) $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="name">اسم المادة <span class="text-danger">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name', $academicSubject->name) }}" required maxlength="255" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="code">الرمز <span class="text-danger">*</span></label>
                    <input id="code" type="text" name="code" value="{{ old('code', $academicSubject->code) }}" required maxlength="100" dir="ltr" class="{{ $fieldClass }} font-mono">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="slug">الرابط (slug)</label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug', $academicSubject->slug) }}" maxlength="255" dir="ltr" class="{{ $fieldClass }} font-mono">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="description">الوصف</label>
                    <textarea id="description" name="description" rows="4" class="{{ $areaClass }}">{{ old('description', $academicSubject->description) }}</textarea>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">المظهر والنشر</h3>
                <p class="mt-0.5 text-xs text-muted">أيقونة ولون وترتيب وحالة التفعيل</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="icon">الأيقونة</label>
                    <select name="icon" id="icon" class="{{ $fieldClass }}">
                        @foreach($icons as $value => $label)
                            <option value="{{ $value }}" @selected(old('icon', $academicSubject->icon ?: 'fas fa-book') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="color">اللون</label>
                    <input id="color" type="color" name="color" value="{{ old('color', $academicSubject->color ?? '#0B3D91') }}" class="h-11 w-full cursor-pointer rounded-xl border border-line bg-surface p-1">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="order">ترتيب العرض</label>
                    <input id="order" type="number" name="order" min="0" value="{{ old('order', $academicSubject->order ?? 0) }}" class="{{ $fieldClass }}">
                </div>
                <div class="flex items-end">
                    <input type="hidden" name="is_active" value="0">
                    <label class="inline-flex h-11 w-full cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas px-4">
                        <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', $academicSubject->is_active ? '1' : '0') === '1') class="size-4 rounded border-line text-accent focus:ring-accent/20">
                        <span class="text-sm font-medium text-ink">مادة نشطة</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i>
                    حفظ التعديلات
                </button>
                <a href="{{ route('admin.academic-subjects.show', $academicSubject) }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-6 text-sm font-medium text-ink hover:bg-canvas">إلغاء</a>
            </div>
        </article>
    </form>

    <article class="overflow-hidden rounded-2xl border border-danger/20 bg-surface shadow-soft">
        <div class="border-b border-danger/15 px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-danger">منطقة خطرة</h3>
            <p class="mt-0.5 text-xs text-muted">لا يمكن الحذف إن وُجدت كورسات مربوطة بالمادة</p>
        </div>
        <div class="p-4 sm:p-5">
            <form method="POST" action="{{ route('admin.academic-subjects.destroy', $academicSubject) }}" onsubmit="return confirm('حذف هذه المادة؟');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-danger/20 bg-danger/5 px-5 text-sm font-medium text-danger hover:bg-danger/10">
                    <i class="fas fa-trash text-xs"></i>
                    حذف المادة
                </button>
            </form>
        </div>
    </article>
</div>
@endsection
