@extends('layouts.admin')

@section('title', 'ولي الأمر - ' . $parent->name)
@section('page_title', 'إدارة أبناء ولي الأمر')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
    @endif

    <nav class="flex flex-wrap items-center gap-1 text-sm text-muted">
        <a href="{{ route('admin.parents.index') }}" class="font-medium text-accent hover:underline">أولياء الأمور</a>
        <span>/</span>
        <span class="text-ink">{{ $parent->name }}</span>
    </nav>

    <section>
        <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ $parent->name }}</h2>
        <p class="mt-1 text-sm text-muted">{{ $parent->email }} · {{ $parent->phone ?? 'بدون جوال' }}</p>
    </section>

    <div class="grid gap-5 lg:grid-cols-2">
        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <h3 class="text-base font-semibold text-ink">الأبناء المرتبطون</h3>
            <ul class="mt-4 space-y-3">
                @forelse($parent->children as $child)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-canvas/50 px-3 py-2.5">
                        <div>
                            <p class="font-semibold text-ink">{{ $child->name }}</p>
                            <p class="text-xs text-muted">{{ $child->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.parents.unlink', $parent) }}"
                              onsubmit="return confirm('فك ارتباط هذا الطالب؟');">
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $child->id }}">
                            <button type="submit" class="inline-flex h-9 items-center rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-semibold text-rose-700">فك الارتباط</button>
                        </form>
                    </li>
                @empty
                    <li class="text-sm text-muted">لا يوجد أبناء مرتبطون.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <h3 class="text-base font-semibold text-ink">ربط طالب</h3>
            <p class="mt-1 text-xs text-muted">يُقبل دور student فقط.</p>
            <form method="POST" action="{{ route('admin.parents.link', $parent) }}" class="mt-4 space-y-3">
                @csrf
                <select name="student_id" required class="{{ $fieldClass }}">
                    <option value="">اختر طالباً…</option>
                    @foreach($availableStudents as $student)
                        <option value="{{ $student->id }}" @selected((int) old('student_id') === (int) $student->id)>
                            {{ $student->name }} — {{ $student->email }}
                            @if((int) $student->parent_id === (int) $parent->id) (مرتبط) @endif
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    ربط الابن
                </button>
            </form>
        </article>
    </div>
</div>
@endsection
