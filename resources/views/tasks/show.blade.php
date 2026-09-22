@extends('layouts.app')

@section('title', 'تفاصيل المهمة')
@section('page_title', 'تفاصيل المهمة')

@section('content')
<div class="mx-auto max-w-2xl space-y-4 p-4 md:p-6">
    <a href="{{ route('tasks.index') }}" class="text-sm text-[#1E4E8C]">← رجوع</a>
    <h1 class="text-2xl font-bold">{{ $task->title }}</h1>
    <p class="text-sm text-slate-500">{{ $task->status }} · {{ optional($task->due_date)->format('Y-m-d') }}</p>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
        <p class="whitespace-pre-wrap text-sm">{{ $task->description ?: '—' }}</p>
    </div>
    <a href="{{ route('tasks.edit', $task) }}" class="inline-flex h-10 items-center rounded-xl bg-[#1E4E8C] px-4 text-sm font-semibold text-white">تعديل</a>
</div>
@endsection
