@extends('layouts.admin')

@section('title', 'تعديل جلسة البث - حصتك')
@section('page_title', 'تعديل جلسة البث')

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
                <a href="{{ route('admin.live-sessions.show', $liveSession) }}" class="hover:text-accent">{{ Str::limit($liveSession->title, 28) }}</a>
                <span class="mx-1 text-line">/</span>
                تعديل
            </p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تعديل الجلسة</h2>
        </div>
        <a href="{{ route('admin.live-sessions.show', $liveSession) }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm font-medium text-ink-soft hover:bg-canvas">رجوع</a>
    </section>

    <form method="POST" action="{{ route('admin.live-sessions.update', $liveSession) }}" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        @csrf @method('PUT')
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">بيانات الجلسة</h3>
        </div>
        <div class="grid gap-4 p-4 sm:p-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}" for="title">عنوان الجلسة <span class="text-danger">*</span></label>
                <input id="title" type="text" name="title" value="{{ old('title', $liveSession->title) }}" required class="{{ $fieldClass }}">
                @error('title')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="instructor_id">المضيف <span class="text-danger">*</span></label>
                <select id="instructor_id" name="instructor_id" required class="{{ $fieldClass }}">
                    @foreach($instructors as $inst)
                        <option value="{{ $inst->id }}" @selected((string) old('instructor_id', $liveSession->instructor_id) === (string) $inst->id)>
                            {{ $inst->name }}@if($inst->id === auth()->id()) (أنت)@elseif($inst->role === 'student') (مشترك)@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="course_id">الكورس</label>
                <select id="course_id" name="course_id" class="{{ $fieldClass }}">
                    <option value="">جلسة عامة</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) old('course_id', $liveSession->course_id) === (string) $course->id)>{{ Str::limit($course->title, 50) }}</option>
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
                <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', \App\Support\AppTimezone::datetimeLocalValue($liveSession->scheduled_at, old('timezone', auth()->user()?->timezoneCode()))) }}" required class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="server_id">سيرفر البث</label>
                <select id="server_id" name="server_id" class="{{ $fieldClass }}">
                    <option value="">الافتراضي</option>
                    @foreach($servers as $server)
                        <option value="{{ $server->id }}" @selected((string) old('server_id', $liveSession->server_id) === (string) $server->id)>{{ $server->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="max_participants">الحد الأقصى</label>
                <input id="max_participants" type="number" name="max_participants" value="{{ old('max_participants', $liveSession->max_participants) }}" min="2" max="1000" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="password">كلمة مرور</label>
                <input id="password" type="text" name="password" value="{{ old('password', $liveSession->password) }}" class="{{ $fieldClass }}">
            </div>
            <div class="md:col-span-2">
                <label class="{{ $labelClass }}" for="description">وصف الجلسة</label>
                <textarea id="description" name="description" rows="3" class="{{ $areaClass }}">{{ old('description', $liveSession->description) }}</textarea>
            </div>
        </div>

        <div class="border-t border-line px-4 py-4 sm:px-5">
            <h3 class="text-sm font-semibold text-ink">إعدادات الغرفة</h3>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                @foreach([
                    ['is_recorded', 'تسجيل الجلسة'],
                    ['allow_chat', 'السماح بالشات'],
                    ['allow_screen_share', 'مشاركة الشاشة'],
                    ['require_enrollment', 'يتطلب تسجيل في الكورس'],
                    ['mute_on_join', 'كتم الصوت عند الدخول'],
                    ['video_off_on_join', 'إيقاف الفيديو عند الدخول'],
                ] as [$name, $label])
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas/40 px-3 py-2.5 text-sm text-ink">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $liveSession->{$name})) class="rounded border-line text-accent focus:ring-accent/30">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap gap-2 border-t border-line px-4 py-4 sm:px-5">
            <button type="submit" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-save text-xs"></i> حفظ التعديلات
            </button>
            <a href="{{ route('admin.live-sessions.show', $liveSession) }}" class="btn-press inline-flex h-10 items-center rounded-xl border border-line px-5 text-sm font-medium text-ink-soft hover:bg-canvas">إلغاء</a>
        </div>
    </form>
</div>
@endsection
