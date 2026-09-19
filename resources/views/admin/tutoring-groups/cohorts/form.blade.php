@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'دفعة جديدة' : 'تعديل دفعة').' - حصتك')
@section('page_title', $mode === 'create' ? 'دفعة جديدة' : 'تعديل دفعة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $days = [1=>'الإثنين',2=>'الثلاثاء',3=>'الأربعاء',4=>'الخميس',5=>'الجمعة',6=>'السبت',7=>'الأحد'];
    $selectedDays = old('study_days', $cohort->study_days ?? []);
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">{{ $group->title }} · دفعات</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">{{ $mode === 'create' ? 'إنشاء دفعة' : 'تعديل الدفعة' }}</h2>
        </div>
        <a href="{{ route('admin.tutoring-groups.cohorts.index', $group) }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">رجوع</a>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'إعداد الدفعة',
        'body' => 'حدد هنا متى يدرس الفصل وكم مقعداً متاحاً. بعد الحفظ انتقل لصفحة الفصل لتسجيل الطلاب وتوليد الجدول تلقائياً.',
        'steps' => [
            'عنوان واضح للدفعة (مثل: دفعة مارس 2026).',
            'أيام الدراسة + وقت البداية + السعة.',
            'الحالة: مفتوحة للتسجيل أو مغلقة.',
            'بعد الحفظ: افتح الفصل ← أضف طلاب ← ولّد الحصص.',
        ],
    ])

    <form method="POST" action="{{ $mode === 'create' ? route('admin.tutoring-groups.cohorts.store', $group) : route('admin.tutoring-groups.cohorts.update', [$group, $cohort]) }}" class="space-y-5">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="title">عنوان الدفعة</label>
                    <input id="title" name="title" value="{{ old('title', $cohort->title) }}" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="slug">Slug</label>
                    <input id="slug" name="slug" value="{{ old('slug', $cohort->slug) }}" class="{{ $fieldClass }}" dir="ltr">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="starts_at">تاريخ البداية</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', optional($cohort->starts_at)->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="ends_at">تاريخ نهاية الجدول (اختياري)</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', optional($cohort->ends_at)->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="study_time">وقت الدراسة</label>
                    <input type="time" id="study_time" name="study_time" value="{{ old('study_time', $cohort->study_time ? \Illuminate\Support\Str::of($cohort->study_time)->substr(0,5) : '18:00') }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="sessions_count">عدد الحصص</label>
                    <input type="number" id="sessions_count" name="sessions_count" value="{{ old('sessions_count', $cohort->sessions_count ?: 8) }}" min="1" max="60" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="session_duration_minutes">مدة الحصة (دقيقة)</label>
                    <input type="number" id="session_duration_minutes" name="session_duration_minutes" value="{{ old('session_duration_minutes', $cohort->session_duration_minutes ?: 60) }}" min="15" max="300" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="timezone">المنطقة الزمنية</label>
                    <input id="timezone" name="timezone" value="{{ old('timezone', $cohort->timezone ?: 'Africa/Cairo') }}" class="{{ $fieldClass }}" dir="ltr">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="capacity">الحد الأقصى</label>
                    <input type="number" id="capacity" name="capacity" value="{{ old('capacity', $cohort->capacity) }}" min="1" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="min_enrollment">الحد الأدنى للبدء</label>
                    <input type="number" id="min_enrollment" name="min_enrollment" value="{{ old('min_enrollment', $cohort->min_enrollment) }}" min="1" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="status">الحالة</label>
                    <select id="status" name="status" class="{{ $fieldClass }}" required>
                        @foreach(['open'=>'مفتوحة','full'=>'مكتملة','closed'=>'مغلقة','postponed'=>'مؤجلة','completed'=>'مكتملة'] as $v=>$l)
                            <option value="{{ $v }}" @selected(old('status', $cohort->status) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="enrollment_closes_at">إغلاق الاشتراك</label>
                    <input type="datetime-local" id="enrollment_closes_at" name="enrollment_closes_at" value="{{ old('enrollment_closes_at', optional($cohort->enrollment_closes_at)->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}">
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="whatsapp_group_url">رابط واتساب المجموعة</label>
                    <input id="whatsapp_group_url" name="whatsapp_group_url" value="{{ old('whatsapp_group_url', $cohort->whatsapp_group_url) }}" class="{{ $fieldClass }}" dir="ltr">
                </div>
                <div class="md:col-span-2">
                    <p class="{{ $labelClass }}">أيام الدراسة</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($days as $num => $label)
                            <label class="inline-flex items-center gap-2 text-sm text-ink">
                                <input type="checkbox" name="study_days[]" value="{{ $num }}" @checked(in_array($num, (array)$selectedDays, false) || in_array((string)$num, (array)$selectedDays, true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $cohort->is_visible ?? true))>
                        ظاهرة في الموقع
                    </label>
                </div>
            </div>
        </article>

        <button type="submit" class="btn-press inline-flex h-11 items-center rounded-xl bg-accent px-6 text-sm font-medium text-white">حفظ</button>
    </form>
</div>
@endsection
