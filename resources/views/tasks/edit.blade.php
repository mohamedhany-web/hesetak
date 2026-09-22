@extends('layouts.app')

@section('title', 'تعديل مهمة')
@section('page_title', 'تعديل مهمة')

@section('content')
<div class="mx-auto max-w-xl space-y-5 p-4 md:p-6">
    <h1 class="text-2xl font-bold">تعديل مهمة</h1>
    <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
        @csrf
        @method('PUT')
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">العنوان *</label>
            <input type="text" name="title" value="{{ old('title', $task->title) }}" required class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">الوصف</label>
            <textarea name="description" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">{{ old('description', $task->description) }}</textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">الحالة</label>
                <select name="status" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800">
                    @foreach(['pending','in_progress','completed'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $task->status) === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">الأولوية</label>
                <select name="priority" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800">
                    @foreach(['low','medium','high'] as $p)
                        <option value="{{ $p }}" @selected(old('priority', $task->priority) === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">الاستحقاق</label>
            <input type="date" name="due_date" value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800">
        </div>
        <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-[#1E4E8C] px-5 text-sm font-semibold text-white">حفظ</button>
    </form>
</div>
@endsection
