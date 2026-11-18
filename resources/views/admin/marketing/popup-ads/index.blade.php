@extends('layouts.admin')

@section('title', 'الإعلانات المنبثقة - الصفحة الرئيسية')
@section('page_title', 'الإعلانات المنبثقة (الصفحة الرئيسية)')

@section('content')
@php
    $statusBadges = [
        'live' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        'active' => 'border-line bg-accent-soft text-accent',
        'inactive' => 'border-line bg-canvas text-muted',
        'expired' => 'border-rose-100 bg-rose-50 text-rose-700',
        'scheduled' => 'border-amber-100 bg-amber-50 text-amber-700',
    ];
    $pageLabels = [
        'home' => 'الرئيسية',
        'pricing' => 'الأسعار /pricing',
        'courses' => 'الكورسات /courses',
        'package_teachers' => 'صفحة معلمي الباقة',
    ];
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسويق · الصفحة الرئيسية</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">الإعلانات المنبثقة</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">إعلان نصي يظهر كـ Pop-up على الصفحة الرئيسية. حدد المدة وعدد مرات الظهور لكل زائر.</p>
        </div>
        <a href="{{ route('admin.popup-ads.create') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
            <i class="fas fa-plus text-xs"></i>
            إعلان جديد
        </a>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">قائمة الإعلانات</h3>
                <p class="text-xs text-muted">
                    <span class="font-semibold tabular-nums text-accent">{{ $ads->total() }}</span> إعلان
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">العنوان</th>
                        <th class="px-4 py-3 text-start font-medium">مقتطف النص</th>
                        <th class="px-4 py-3 text-start font-medium">الصفحة</th>
                        <th class="px-4 py-3 text-start font-medium">الفترة</th>
                        <th class="px-4 py-3 text-start font-medium">عدد الظهور/زائر</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-end font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($ads as $ad)
                        @php
                            if ($ad->is_active && $ad->starts_at <= now() && $ad->ends_at >= now()) {
                                $statusKey = 'live';
                                $statusLabel = 'يعرض الآن';
                            } elseif ($ad->is_active && $ad->ends_at < now()) {
                                $statusKey = 'expired';
                                $statusLabel = 'منتهي — جدّد الفترة ليظهر';
                            } elseif ($ad->is_active) {
                                $statusKey = 'scheduled';
                                $statusLabel = 'مجدول';
                            } else {
                                $statusKey = 'inactive';
                                $statusLabel = 'معطل';
                            }
                        @endphp
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3 font-semibold text-ink">{{ $ad->title }}</td>
                            <td class="max-w-[200px] truncate px-4 py-3 text-muted">{{ Str::limit(strip_tags($ad->body ?? ''), 50) }}</td>
                            <td class="px-4 py-3 text-muted">{{ $pageLabels[$ad->page_key ?: 'home'] ?? $ad->page_key }}</td>
                            <td class="px-4 py-3 tabular-nums text-muted">{{ $ad->starts_at->format('Y-m-d') }} ← → {{ $ad->ends_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink">{{ $ad->max_views_per_visitor }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $statusBadges[$statusKey] }}">
                                    @if($statusKey === 'live')
                                        <i class="fas fa-eye text-[10px]"></i>
                                    @else
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                    @endif
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.popup-ads.edit', $ad) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                                       title="تعديل">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.popup-ads.destroy', $ad) }}" method="POST" class="inline"
                                          onsubmit="return confirm('حذف هذا الإعلان؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                                title="حذف">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="inline-flex size-12 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                                        <i class="fas fa-bullhorn text-lg"></i>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-ink">لا توجد إعلانات</p>
                                        <p class="mt-1 text-sm text-muted">
                                            <a href="{{ route('admin.popup-ads.create') }}" class="font-medium text-accent hover:underline">أضف إعلاناً جديداً</a>
                                        </p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ads->hasPages())
            <div class="border-t border-line px-4 py-3">
                {{ $ads->links() }}
            </div>
        @endif
    </article>
</div>
@endsection
