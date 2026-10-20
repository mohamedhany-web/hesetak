@extends('layouts.admin')

@section('title', 'تعديل المرحلة الدراسية - حصتك')
@section('page_title', 'تعديل المرحلة الدراسية')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $icons = [
        'fas fa-graduation-cap' => 'قبعة تخرج',
        'fas fa-school' => 'مبنى',
        'fas fa-book-open' => 'كتاب',
        'fas fa-user-graduate' => 'طالب',
        'fas fa-chalkboard' => 'سبورة',
        'fas fa-language' => 'لغة',
        'fas fa-layer-group' => 'مراحل',
        'fas fa-calendar-alt' => 'تقويم',
    ];
    $selectedIcon = old('icon', $academicYear->icon ?: 'fas fa-graduation-cap');
    $selectedColor = old('color', $academicYear->color ?: '#0B3D91');
    $grades = $academicYear->grades ?? collect();
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">إدارة المحتوى · المراحل · تعديل</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $academicYear->name }}</h2>
            <p class="mt-1 text-sm text-muted">حدّث بيانات المرحلة وأدر الصفوف التي تظهر بعد اختيارها في التسجيل.</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.academic-years.show', $academicYear->id) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-eye text-xs"></i>
                عرض
            </a>
            <a href="{{ route('admin.academic-years.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

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

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الرمز</p>
            <p class="mt-1 font-mono text-lg font-semibold text-ink">{{ $academicYear->code }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الصفوف</p>
            <p class="mt-1 text-lg font-semibold tabular-nums text-ink">{{ $grades->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الحالة</p>
            <p class="mt-1 text-lg font-semibold {{ $academicYear->is_active ? 'text-success' : 'text-danger' }}">
                {{ $academicYear->is_active ? 'نشطة' : 'موقوفة' }}
            </p>
        </article>
    </section>

    <form action="{{ route('admin.academic-years.update', $academicYear->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات المرحلة</h3>
                <p class="mt-0.5 text-xs text-muted">الاسم والرمز والظهور في التسجيل وصفحة المناهج</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="{{ $labelClass }}" for="name">اسم المرحلة <span class="text-danger">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name', $academicYear->name) }}" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="code">الرمز <span class="text-danger">*</span></label>
                    <input id="code" type="text" name="code" value="{{ old('code', $academicYear->code) }}" required maxlength="32" dir="ltr" class="{{ $fieldClass }} font-mono uppercase">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="slug">رابط الصفحة العامة</label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug', $academicYear->slug) }}" dir="ltr" class="{{ $fieldClass }} font-mono">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="level_number">رقم المستوى</label>
                    <input id="level_number" type="number" name="level_number" min="1" max="20" value="{{ old('level_number', $academicYear->level_number) }}" class="{{ $fieldClass }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="tagline">شعار مختصر</label>
                    <input id="tagline" type="text" name="tagline" value="{{ old('tagline', $academicYear->tagline) }}" class="{{ $fieldClass }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="description">الوصف</label>
                    <textarea id="description" name="description" rows="4" class="{{ $areaClass }}">{{ old('description', $academicYear->description) }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="icon">الأيقونة</label>
                    <select id="icon" name="icon" class="{{ $fieldClass }}">
                        @foreach($icons as $value => $label)
                            <option value="{{ $value }}" @selected($selectedIcon === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="color">لون البطاقة</label>
                    <input id="color" type="color" name="color" value="{{ $selectedColor }}" class="h-11 w-14 cursor-pointer rounded-xl border border-line bg-surface p-1">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="order">ترتيب الظهور</label>
                    <input id="order" type="number" name="order" min="0" value="{{ old('order', $academicYear->order) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="thumbnail">صورة مصغرة</label>
                    <input id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/jpg"
                           class="block w-full text-sm text-muted file:ml-4 file:rounded-xl file:border-0 file:bg-accent-soft file:px-4 file:py-2 file:text-sm file:font-medium file:text-accent">
                </div>
                <div class="sm:col-span-2 flex flex-wrap gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <label class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas px-4">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $academicYear->is_active)) class="size-4 rounded border-line text-accent focus:ring-accent/20">
                        <span class="text-sm font-medium text-ink">مرحلة نشطة</span>
                    </label>
                    <input type="hidden" name="is_public" value="0">
                    <label class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas px-4">
                        <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $academicYear->is_public)) class="size-4 rounded border-line text-accent focus:ring-accent/20">
                        <span class="text-sm font-medium text-ink">نشر في التسجيل وصفحة المناهج</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i>
                    حفظ التعديلات
                </button>
            </div>
        </article>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">صفوف المرحلة</h3>
            <p class="mt-0.5 text-xs text-muted">بعد اختيار المرحلة في إنشاء الحساب يظهر للطالب هذه الصفوف فقط.</p>
        </div>
        <div class="p-4 sm:p-5 space-y-4">
            @if($grades->isEmpty())
                <p class="rounded-xl border border-dashed border-line bg-canvas px-4 py-6 text-center text-sm text-muted">لا صفوف بعد. أضف الصف الأول من النموذج أدناه.</p>
            @else
                <div class="space-y-3">
                    @foreach($grades as $grade)
                        <form action="{{ route('admin.academic-years.grades.update', [$academicYear->id, $grade->id]) }}" method="POST" class="flex flex-col gap-2 rounded-xl border border-line bg-canvas p-3 sm:flex-row sm:items-center">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ old('name', $grade->name) }}" required class="{{ $fieldClass }} sm:flex-1" aria-label="اسم الصف">
                            <input type="number" name="order" value="{{ old('order', $grade->order) }}" min="0" class="{{ $fieldClass }} sm:w-24" aria-label="الترتيب">
                            <input type="hidden" name="is_active" value="0">
                            <label class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl border border-line bg-surface px-3 text-xs text-ink">
                                <input type="checkbox" name="is_active" value="1" @checked($grade->is_active) class="size-4 rounded border-line text-accent">
                                ظاهر
                            </label>
                            <button type="submit" class="btn-press inline-flex h-11 items-center justify-center rounded-xl bg-accent px-4 text-xs font-medium text-white">حفظ</button>
                        </form>
                        <form action="{{ route('admin.academic-years.grades.destroy', [$academicYear->id, $grade->id]) }}" method="POST" class="-mt-1 mb-2 flex justify-end" onsubmit="return confirm('حذف صف «{{ $grade->name }}»؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-danger hover:underline">حذف الصف</button>
                        </form>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.academic-years.grades.store', $academicYear->id) }}" method="POST" class="flex flex-col gap-2 rounded-xl border border-dashed border-line p-3 sm:flex-row sm:items-center">
                @csrf
                <input type="text" name="name" required placeholder="اسم الصف الجديد — مثال: الصف الأول" class="{{ $fieldClass }} sm:flex-1">
                <input type="number" name="order" min="0" placeholder="ترتيب" class="{{ $fieldClass }} sm:w-24">
                <input type="hidden" name="is_active" value="1">
                <button type="submit" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-plus text-xs"></i>
                    إضافة صف
                </button>
            </form>
        </div>
    </article>

    <article class="overflow-hidden rounded-2xl border border-danger/20 bg-surface shadow-soft">
        <div class="border-b border-danger/15 px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-danger">حذف المرحلة</h3>
            <p class="mt-0.5 text-xs text-muted">يحذف المرحلة وصفوفها. طلاب هذه المرحلة يبقون ويمكن إعادة ربطهم لاحقاً.</p>
        </div>
        <div class="p-4 sm:p-5">
            <form action="{{ route('admin.academic-years.destroy', $academicYear->id) }}" method="POST" onsubmit="return confirm('حذف مرحلة «{{ $academicYear->name }}» وصفوفها؟ لا يمكن التراجع.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-danger/20 bg-danger/5 px-5 text-sm font-medium text-danger hover:bg-danger/10">
                    <i class="fas fa-trash text-xs"></i>
                    حذف المرحلة
                </button>
            </form>
        </div>
    </article>
</div>
@endsection
