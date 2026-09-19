@extends('layouts.admin')

@section('title', 'منشئ نموذج التوظيف - حصتك')
@section('page_title', 'منشئ نموذج التقديم')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $settings = $form->settings ?? [];
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التوظيف · منشئ النماذج</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">نموذج تقديم المعلمين</h2>
            <p class="mt-1 text-sm text-muted">أضف خانات بأنواع مختلفة (مثل Google Forms)، حدّد الإجباري والاختياري، والبيانات تظهر في مراجعة الطلبات.</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('public.tutor.apply') }}" target="_blank" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:text-accent">
                <i class="fas fa-external-link-alt text-xs"></i> معاينة التقديم
            </a>
            <a href="{{ route('admin.tutor-applications.hub') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                لوحة التوظيف
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
        <div class="rounded-2xl border border-danger/20 bg-surface px-4 py-3 text-sm text-danger shadow-soft">
            {{ $errors->first() }}
        </div>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">إعدادات النموذج</h3>
        </div>
        <form method="POST" action="{{ route('admin.hiring-form.update') }}" class="grid gap-4 p-4 sm:p-5 md:grid-cols-2">
            @csrf
            @method('PUT')
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}" for="title">عنوان النموذج</label>
                <input id="title" name="title" value="{{ old('title', $form->title) }}" required class="{{ $fieldClass }}">
            </div>
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}" for="description">الوصف</label>
                <textarea id="description" name="description" rows="3" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink">{{ old('description', $form->description) }}</textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-ink">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $form->is_published)) class="rounded border-line text-accent focus:ring-accent/30">
                منشور للمتقدمين
            </label>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-ink">
                <input type="checkbox" name="require_intro_video" value="1" @checked(old('require_intro_video', $settings['require_intro_video'] ?? true)) class="rounded border-line text-accent focus:ring-accent/30">
                إلزام فيديو تعريفي (ملف أو رابط)
            </label>
            <div class="md:col-span-2">
                <button class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">حفظ الإعدادات</button>
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">إضافة خانة جديدة</h3>
            <p class="mt-0.5 text-xs text-muted">للقوائم والاختيارات: اكتب كل خيار في سطر. يمكن `قيمة|التسمية`</p>
        </div>
        <form method="POST" action="{{ route('admin.hiring-form.fields.store') }}" class="grid gap-4 p-4 sm:p-5 md:grid-cols-2">
            @csrf
            <div>
                <label class="{{ $labelClass }}">نوع الخانة</label>
                <select name="type" class="{{ $fieldClass }}" required>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">عنوان الخانة</label>
                <input name="label" required class="{{ $fieldClass }}" placeholder="مثال: هل لديك خبرة في الألمانية؟">
            </div>
            <div>
                <label class="{{ $labelClass }}">نص مساعد</label>
                <input name="help_text" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Placeholder</label>
                <input name="placeholder" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">ربط بحقل النظام (للتفعيل)</label>
                <select name="system_key" class="{{ $fieldClass }}">
                    <option value="">— بدون ربط (خانة مخصصة) —</option>
                    @foreach($systemKeys as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">نوع الملف (إن كانت خانة ملف)</label>
                <select name="file_kind" class="{{ $fieldClass }}">
                    <option value="any">أي ملف مسموح</option>
                    <option value="image">صورة فقط</option>
                    <option value="pdf">PDF فقط</option>
                    <option value="image_pdf">صورة أو PDF</option>
                    <option value="video">فيديو</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}">خيارات (select / radio / checkbox)</label>
                <textarea name="options_text" rows="4" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink" placeholder="خيار 1&#10;خيار 2&#10;male|ذكر"></textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-ink">
                <input type="checkbox" name="is_required" value="1" class="rounded border-line text-accent focus:ring-accent/30">
                إجباري
            </label>
            <div class="md:col-span-2">
                <button class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-plus text-xs"></i> إضافة الخانة
                </button>
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">الخانات ({{ $form->fields->count() }})</h3>
        </div>
        <ul class="divide-y divide-line">
            @forelse($form->fields as $field)
                <li class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink">
                                {{ $field->label }}
                                @if($field->is_required)<span class="text-danger">*</span>@endif
                            </p>
                            <p class="mt-1 text-xs text-muted">
                                {{ $field->typeLabel() }}
                                @if($field->system_key) · مربوط: {{ $systemKeys[$field->system_key] ?? $field->system_key }} @endif
                                @unless($field->is_active) · <span class="text-danger">معطّل</span>@endunless
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <form method="POST" action="{{ route('admin.hiring-form.fields.move', [$field, 'up']) }}">@csrf
                                <button class="btn-press inline-flex size-8 items-center justify-center rounded-lg border border-line text-muted hover:text-accent" title="أعلى"><i class="fas fa-arrow-up text-xs"></i></button>
                            </form>
                            <form method="POST" action="{{ route('admin.hiring-form.fields.move', [$field, 'down']) }}">@csrf
                                <button class="btn-press inline-flex size-8 items-center justify-center rounded-lg border border-line text-muted hover:text-accent" title="أسفل"><i class="fas fa-arrow-down text-xs"></i></button>
                            </form>
                            <form method="POST" action="{{ route('admin.hiring-form.fields.destroy', $field) }}" onsubmit="return confirm('حذف هذه الخانة؟')">
                                @csrf @method('DELETE')
                                <button class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-danger/10 text-danger" title="حذف"><i class="fas fa-trash text-xs"></i></button>
                            </form>
                        </div>
                    </div>

                    <details class="mt-3 rounded-xl border border-line bg-canvas">
                        <summary class="cursor-pointer px-3 py-2 text-xs font-semibold text-accent">تعديل الخانة</summary>
                        <form method="POST" action="{{ route('admin.hiring-form.fields.update', $field) }}" class="grid gap-3 p-3 md:grid-cols-2">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="{{ $labelClass }}">النوع</label>
                                <select name="type" class="{{ $fieldClass }}">
                                    @foreach($typeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($field->type === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">العنوان</label>
                                <input name="label" value="{{ $field->label }}" required class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">نص مساعد</label>
                                <input name="help_text" value="{{ $field->help_text }}" class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">ربط النظام</label>
                                <select name="system_key" class="{{ $fieldClass }}">
                                    <option value="">— بدون —</option>
                                    @foreach($systemKeys as $value => $label)
                                        <option value="{{ $value }}" @selected($field->system_key === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">نوع الملف</label>
                                <select name="file_kind" class="{{ $fieldClass }}">
                                    @foreach(['any'=>'أي','image'=>'صورة','pdf'=>'PDF','image_pdf'=>'صورة/PDF','video'=>'فيديو'] as $v => $l)
                                        <option value="{{ $v }}" @selected(($field->settings['file_kind'] ?? 'any') === $v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="{{ $labelClass }}">الخيارات</label>
                                <textarea name="options_text" rows="3" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm">@foreach($field->options ?? [] as $opt){{ is_array($opt) ? (($opt['value'] ?? '').'|'.($opt['label'] ?? '')) : $opt }}
@endforeach</textarea>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-ink">
                                <input type="hidden" name="is_required" value="0">
                                <input type="checkbox" name="is_required" value="1" @checked($field->is_required) class="rounded border-line text-accent">
                                إجباري
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-ink">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($field->is_active) class="rounded border-line text-accent">
                                مفعّل
                            </label>
                            <div class="md:col-span-2">
                                <button class="btn-press inline-flex h-9 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white">حفظ التعديل</button>
                            </div>
                        </form>
                    </details>
                </li>
            @empty
                <li class="px-5 py-12 text-center text-sm text-muted">لا توجد خانات بعد.</li>
            @endforelse
        </ul>
    </article>
</div>
@endsection
