@extends('layouts.admin')

@section('title', 'إضافة ولي أمر')
@section('page_title', 'إضافة ولي أمر')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الطلاب · العائلة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إضافة ولي أمر</h2>
            <p class="mt-1 text-sm text-muted">إنشاء حساب جديد أو ترقية مستخدم موجود بالبريد/الجوال، مع ربط طالب اختياري.</p>
        </div>
        <a href="{{ route('admin.parents.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft">
            <i class="fas fa-arrow-right text-xs"></i>
            العودة
        </a>
    </section>

    @if(session('error'))
        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.parents.store') }}" class="space-y-5 max-w-xl">
            @csrf

            <div>
                <label class="{{ $labelClass }}">الاسم</label>
                <input type="text" name="name" value="{{ old('name') }}" class="{{ $fieldClass }}" placeholder="مطلوب عند إنشاء حساب جديد">
                @error('name')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">البريد الإلكتروني</label>
                <input type="email" name="email" value="{{ old('email') }}" class="{{ $fieldClass }}" dir="ltr">
                @error('email')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">الجوال</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="{{ $fieldClass }}" dir="ltr">
                @error('phone')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">كلمة المرور</label>
                <input type="password" name="password" class="{{ $fieldClass }}" autocomplete="new-password" placeholder="مطلوبة للحساب الجديد فقط">
                @error('password')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">ربط طالب (اختياري)</label>
                <select name="student_id" class="{{ $fieldClass }}">
                    <option value="">— بدون ربط —</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} @if($student->email)({{ $student->email }})@endif</option>
                    @endforeach
                </select>
                @error('student_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-save text-xs"></i>
                    حفظ
                </button>
            </div>
        </form>
    </article>
</div>
@endsection
