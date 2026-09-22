@extends('layouts.app')

@section('title', 'مهامي')
@section('page_title', 'مهامي')

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
<div class="mx-auto max-w-5xl space-y-5 p-4 md:p-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $isRtl ? 'مهامي' : 'My tasks' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $isRtl ? 'المهام الشخصية المرتبطة بحسابك' : 'Personal tasks for your account' }}</p>
        </div>
        <a href="{{ route('tasks.create') }}" class="inline-flex h-10 items-center rounded-xl bg-[#1E4E8C] px-4 text-sm font-semibold text-white">{{ $isRtl ? 'مهمة جديدة' : 'New task' }}</a>
    </div>

    <div class="grid gap-3 sm:grid-cols-4">
        @foreach(['total' => 'الكل', 'pending' => 'معلّق', 'in_progress' => 'جاري', 'completed' => 'مكتمل'] as $key => $label)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                <p class="text-xs text-slate-500">{{ $isRtl ? $label : $key }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums">{{ number_format($stats[$key] ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800">
                <tr>
                    <th class="px-4 py-3 text-start">{{ $isRtl ? 'العنوان' : 'Title' }}</th>
                    <th class="px-4 py-3 text-start">{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                    <th class="px-4 py-3 text-start">{{ $isRtl ? 'الاستحقاق' : 'Due' }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($tasks as $task)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('tasks.show', $task) }}" class="font-semibold text-[#1E4E8C] hover:underline">{{ $task->title }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $task->status }}</td>
                        <td class="px-4 py-3 tabular-nums text-slate-500">{{ optional($task->due_date)->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-10 text-center text-slate-500">{{ $isRtl ? 'لا مهام بعد' : 'No tasks yet' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $tasks->links() }}</div>
</div>
@endsection
