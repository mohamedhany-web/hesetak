@extends('layouts.app')

@section('title', 'مهمة جديدة')
@section('page_title', 'مهمة جديدة')

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
<div class="mx-auto max-w-xl space-y-5 p-4 md:p-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $isRtl ? 'مهمة جديدة' : 'New task' }}</h1>
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('tasks.store') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
        @csrf
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ $isRtl ? 'العنوان' : 'Title' }} *</label>
            <input type="text" name="title" value="{{ old('title') }}" required class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ $isRtl ? 'الوصف' : 'Description' }}</label>
            <textarea name="description" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">{{ old('description') }}</textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">{{ $isRtl ? 'الأولوية' : 'Priority' }}</label>
                <select name="priority" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800">
                    @foreach(['low','medium','high'] as $p)
                        <option value="{{ $p }}" @selected(old('priority', 'medium') === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">{{ $isRtl ? 'الاستحقاق' : 'Due date' }}</label>
                <input type="date" name="due_date" value="{{ old('due_date') }}" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-600 dark:bg-slate-800">
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-[#1E4E8C] px-5 text-sm font-semibold text-white">{{ $isRtl ? 'حفظ' : 'Save' }}</button>
            <a href="{{ route('tasks.index') }}" class="inline-flex h-11 items-center rounded-xl border border-slate-200 px-4 text-sm">{{ $isRtl ? 'إلغاء' : 'Cancel' }}</a>
        </div>
    </form>
</div>
@endsection
