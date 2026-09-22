@extends('layouts.admin')

@section('title', 'مادة جديدة')
@section('page_title', 'مادة جديدة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">مواد عامة (قديم)</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">إضافة مادة</h2>
            <p class="mt-1 text-sm text-muted">للمواد الأكاديمية الحديثة استخدم «المواد الدراسية».</p>
        </div>
        <div class="flex gap-2">
            @if(Route::has('admin.academic-subjects.create'))
                <a href="{{ route('admin.academic-subjects.create') }}" class="btn-press inline-flex h-9 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white">مادة دراسية (مراحل)</a>
            @endif
            <a href="{{ route('admin.subjects.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">رجوع</a>
        </div>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.subjects.store') }}" class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-soft">
        @csrf
        <div>
            <label class="{{ $labelClass }}" for="name">اسم المادة *</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required class="{{ $fieldClass }}">
        </div>
        <div>
            <label class="{{ $labelClass }}" for="description">الوصف</label>
            <textarea id="description" name="description" rows="4" class="{{ $areaClass }}">{{ old('description') }}</textarea>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
            نشطة
        </label>
        <button type="submit" class="btn-press inline-flex h-11 items-center rounded-xl bg-accent px-5 text-sm font-medium text-white">حفظ</button>
    </form>
</div>
@endsection
