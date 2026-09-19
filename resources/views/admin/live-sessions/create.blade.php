@extends('layouts.admin')

@section('title', 'جدولة جلسة بث - حصتك')
@section('page_title', 'جدولة جلسة بث')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.live-sessions.index') }}" class="hover:text-accent">جلسات البث</a>
                <span class="mx-1 text-line">/</span>
                جدولة
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">جدولة جلسة بث</h2>
            <p class="mt-1 text-sm text-muted">لحجز موعد لاحق. للبث الفوري استخدم «ابدأ بثاً الآن» من قائمة الجلسات.</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.live-sessions.instant') }}">
                @csrf
                <button type="submit" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-video text-xs"></i> ابدأ بثاً الآن
                </button>
            </form>
            <a href="{{ route('admin.live-sessions.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm font-medium text-ink-soft hover:bg-canvas">رجوع</a>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.live-sessions.store') }}" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        @csrf
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">بيانات الجلسة</h3>
            <p class="mt-0.5 text-xs text-muted">العنوان والمضيف والموعد — ثم إعدادات الغرفة</p>
        </div>
        <div class="grid gap-4 p-4 sm:p-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}" for="title">عنوان الجلسة <span class="text-danger">*</span></label>
                <input id="title" type="text" name="title" value="{{ old('title') }}" required class="{{ $fieldClass }}" placeholder="مثال: لقاء تشغيلي — فريق الدعم">
                @error('title')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="instructor_id">المضيف <span class="text-danger">*</span></label>
                <select id="instructor_id" name="instructor_id" required class="{{ $fieldClass }}">
                    <option value="">اختر المضيف</option>
                    @foreach($instructors as $inst)
                        <option value="{{ $inst->id }}" @selected((string) old('instructor_id', auth()->id()) === (string) $inst->id)>
                            {{ $inst->name }}@if($inst->id === auth()->id()) (أنت)@elseif($inst->role === 'student') (مشترك)@endif
                        </option>
                    @endforeach
                </select>
                @error('instructor_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="course_id">الكورس (اختياري)</label>
                <select id="course_id" name="course_id" class="{{ $fieldClass }}">
                    <option value="">جلسة عامة</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ Str::limit($course->title, 50) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                @include('partials.timezone-select', [
                    'value' => old('timezone', auth()->user()?->timezoneCode()),
                    'class' => $fieldClass,
                    'labelClass' => $labelClass,
                ])
            </div>
            <div>
                <label class="{{ $labelClass }}" for="scheduled_at">موعد البث <span class="text-danger">*</span></label>
                <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" required class="{{ $fieldClass }}">
                @error('scheduled_at')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="server_id">سيرفر البث</label>
                <select id="server_id" name="server_id" class="{{ $fieldClass }}">
                    <option value="">الافتراضي</option>
                    @foreach($servers as $server)
                        <option value="{{ $server->id }}" @selected((string) old('server_id') === (string) $server->id)>{{ $server->name }} ({{ $server->domain }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="max_participants">الحد الأقصى للمشاركين</label>
                <input id="max_participants" type="number" name="max_participants" value="{{ old('max_participants', 100) }}" min="2" max="1000" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="password">كلمة مرور (اختياري)</label>
                <input id="password" type="text" name="password" value="{{ old('password') }}" class="{{ $fieldClass }}" placeholder="اتركها فارغة إن لم تلزم">
            </div>
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}" for="description">وصف الجلسة</label>
                <textarea id="description" name="description" rows="3" class="{{ $areaClass }}" placeholder="محتوى مختصر...">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="border-t border-line px-4 py-4 sm:px-5">
            <h3 class="text-sm font-semibold text-ink">إعدادات الغرفة</h3>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                @foreach([
                    ['is_recorded', false, 'تسجيل الجلسة'],
                    ['allow_chat', true, 'السماح بالشات'],
                    ['allow_screen_share', true, 'مشاركة الشاشة'],
                    ['require_enrollment', false, 'يتطلب تسجيل في الكورس'],
                    ['mute_on_join', true, 'كتم الصوت عند الدخول'],
                    ['video_off_on_join', true, 'إيقاف الفيديو عند الدخول'],
                ] as [$name, $default, $label])
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas/40 px-3 py-2.5 text-sm text-ink">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $default)) class="rounded border-line text-accent focus:ring-accent/30">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t border-line px-4 py-4 sm:px-5">
            <button type="submit" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-calendar-check text-xs"></i> حفظ الجدولة
            </button>
            <a href="{{ route('admin.live-sessions.index') }}" class="btn-press inline-flex h-10 items-center rounded-xl border border-line px-5 text-sm font-medium text-ink-soft hover:bg-canvas">إلغاء</a>
        </div>
    </form>
</div>
@endsection
