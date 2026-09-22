@extends('layouts.admin')

@section('title', 'أولياء الأمور والأبناء')
@section('page_title', 'أولياء الأمور والأبناء')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الطلاب · العائلة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">أولياء الأمور والأبناء</h2>
            <p class="mt-1 text-sm text-muted">إدارة ربط ولي الأمر بأبناء الطلاب عبر parent_id.</p>
        </div>
    </section>

    <form method="GET" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="flex flex-wrap gap-3">
            <div class="min-w-[220px] flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="بحث بالاسم أو البريد أو الجوال"
                       class="{{ $fieldClass }}">
            </div>
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">بحث</button>
        </div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">ولي الأمر</th>
                        <th class="px-4 py-3 text-start font-medium">الدور</th>
                        <th class="px-4 py-3 text-start font-medium">عدد الأبناء</th>
                        <th class="px-4 py-3 text-end font-medium">إدارة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($parents as $parent)
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink">{{ $parent->name }}</p>
                                <p class="text-xs text-muted">{{ $parent->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $parent->role }}</td>
                            <td class="px-4 py-3 tabular-nums font-semibold text-ink">{{ $parent->children_count }}</td>
                            <td class="px-4 py-3 text-end">
                                <a href="{{ route('admin.parents.show', $parent) }}"
                                   class="inline-flex h-9 items-center rounded-xl border border-line px-3 text-xs font-semibold text-accent hover:bg-accent-soft">فتح</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-muted">لا يوجد أولياء أمور مرتبطون بأبناء بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($parents->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $parents->links() }}</div>
        @endif
    </article>
</div>
@endsection
