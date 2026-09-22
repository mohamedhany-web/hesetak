@extends('layouts.admin')

@section('title', __('admin.courses_management') . ' - ' . config('app.name'))
@section('page_title', __('admin.courses_management'))

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">المحتوى · البرامج المسجّلة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ __('admin.courses_management') }}</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">إدارة البرامج التعليمية، الدروس، والظهور في المنصة.</p>
        </div>
        <a href="{{ route('admin.advanced-courses.create') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
            <i class="fas fa-plus text-xs"></i>
            إضافة برنامج
        </a>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'الكورسات المسجّلة ≠ دروس 1:1',
        'body' => 'هنا مسار الكورسات المستقلة (محتوى مسجّل باشتراك). حجز الحصص المباشرة 1:1 مسار منفصل يعتمد على باقات الرصيد + مطابقة المعلم (مرحلة/مادة/منهج).',
        'steps' => [
            'أنشئ برنامجاً واربطه بسنة/مادة إن لزم.',
            'أضف الدروس والمحتوى وفعّل الظهور.',
            'تسجيل الطلاب في الكورس منفصل عن تسكينهم في دفعة فصل حي.',
        ],
    ])

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">
            <i class="fas fa-exclamation-circle ml-1"></i> {{ session('error') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.advanced-courses.index') }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-1">
                <label class="{{ $labelClass }}" for="search">بحث</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       placeholder="عنوان أو وصف..."
                       class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="course_category_id">المسار</label>
                <select name="course_category_id" id="course_category_id" class="{{ $fieldClass }}">
                    <option value="">جميع المسارات</option>
                    @foreach($courseCategoryOptions as $cc)
                        <option value="{{ $cc->id }}" @selected((string) request('course_category_id') === (string) $cc->id)>{{ $cc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="delivery_type">نوع التعلّم</label>
                <select name="delivery_type" id="delivery_type" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    <option value="group" @selected(request('delivery_type') === 'group')>جماعي</option>
                    <option value="one_to_one" @selected(request('delivery_type') === 'one_to_one')>فردي 1:1</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select name="status" id="status" class="{{ $fieldClass }}">
                    <option value="">الكل</option>
                    <option value="active" @selected(request('status') === 'active')>نشط</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>معطّل</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-press inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-filter text-xs"></i> تطبيق
                </button>
                @if(request()->hasAny(['search', 'course_category_id', 'delivery_type', 'status']))
                    <a href="{{ route('admin.advanced-courses.index') }}"
                       class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-muted transition hover:bg-accent-soft hover:text-accent"
                       title="إعادة تعيين">
                        <i class="fas fa-times text-xs"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if($courses->count() > 0)
        <p class="text-xs text-muted">
            عرض <span class="font-semibold tabular-nums text-ink">{{ $courses->firstItem() }}</span>–<span class="font-semibold tabular-nums text-ink">{{ $courses->lastItem() }}</span>
            من <span class="font-semibold tabular-nums text-ink">{{ $courses->total() }}</span>
        </p>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($courses as $course)
                @php $isSolo = ($course->delivery_type ?? 'group') === 'one_to_one'; @endphp
                <article class="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="flex items-start justify-between gap-3 border-b border-line px-4 py-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-ink">{{ $course->title }}</h3>
                            <p class="mt-0.5 truncate text-xs text-muted">{{ $course->instructor?->name ?? 'بدون معلّم' }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $course->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f2f5f4] text-muted' }}">
                                {{ $course->is_active ? 'نشط' : 'معطّل' }}
                            </span>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $isSolo ? 'bg-amber-50 text-amber-800' : 'bg-accent-soft text-accent' }}">
                                {{ $isSolo ? 'فردي 1:1' : 'جماعي' }}
                            </span>
                            @if($course->is_featured)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800">
                                    <i class="fas fa-star text-[9px]"></i> مميّز
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 space-y-3 p-4">
                        @if($course->description)
                            <p class="line-clamp-2 text-xs leading-relaxed text-muted">{{ Str::limit(strip_tags($course->description), 110) }}</p>
                        @endif

                        <div class="space-y-1.5 text-xs text-ink-soft">
                            @if($course->category)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-tag w-4 text-muted"></i>
                                    <span>{{ $course->category }}</span>
                                </div>
                            @endif
                            <div class="flex items-center gap-2">
                                @if(! $course->is_free && $course->effectivePurchasePrice() > 0)
                                    <i class="fas fa-dollar-sign w-4 text-muted"></i>
                                    <span class="tabular-nums font-medium text-ink">
                                        @if($course->hasPromotionalPrice())
                                            <span class="ml-1 text-muted line-through">{{ number_format($course->listPriceAmount()) }}</span>
                                        @endif
                                        {{ number_format($course->effectivePurchasePrice()) }} {{ currency_symbol() }}
                                    </span>
                                @else
                                    <i class="fas fa-gift w-4 text-emerald-600"></i>
                                    <span class="font-medium text-emerald-700">مجاني</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-muted">
                                <i class="fas fa-clock w-4"></i>
                                <span class="tabular-nums">{{ $course->created_at->format('Y-m-d') }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 border-t border-line pt-3">
                            <div class="rounded-xl border border-line px-2 py-2 text-center">
                                <p class="text-base font-semibold tabular-nums text-ink">{{ $course->lessons_count ?? 0 }}</p>
                                <p class="text-[10px] text-muted">درس</p>
                            </div>
                            <div class="rounded-xl border border-line px-2 py-2 text-center">
                                <p class="text-base font-semibold tabular-nums text-ink">{{ $course->enrollments_count ?? 0 }}</p>
                                <p class="text-[10px] text-muted">طالب</p>
                            </div>
                            <div class="rounded-xl border border-line px-2 py-2 text-center">
                                <p class="text-base font-semibold tabular-nums text-ink">{{ $course->orders_count ?? 0 }}</p>
                                <p class="text-[10px] text-muted">طلب</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 border-t border-line px-4 py-3">
                        <div class="flex flex-wrap gap-1.5">
                            <a href="{{ route('admin.advanced-courses.show', $course) }}"
                               class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-[11px] font-medium text-ink hover:bg-accent-soft hover:text-accent">
                                <i class="fas fa-eye"></i> عرض
                            </a>
                            <a href="{{ route('admin.courses.lessons.index', $course) }}"
                               class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-[11px] font-medium text-ink hover:bg-accent-soft hover:text-accent">
                                <i class="fas fa-play-circle"></i> الدروس
                            </a>
                            <a href="{{ route('admin.courses.lessons.create', $course) }}"
                               class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-[11px] font-medium text-ink hover:bg-accent-soft hover:text-accent">
                                <i class="fas fa-plus"></i> درس
                            </a>
                            <a href="{{ route('admin.advanced-courses.orders', $course) }}"
                               class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-[11px] font-medium text-ink hover:bg-accent-soft hover:text-accent">
                                <i class="fas fa-shopping-cart"></i> الطلبات
                            </a>
                        </div>
                        <div class="flex flex-wrap gap-1.5 border-t border-line pt-2">
                            <button type="button" onclick="toggleCourseStatus({{ $course->id }})"
                                    class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-[11px] font-medium {{ $course->is_active ? 'text-rose-700 hover:bg-rose-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                <i class="fas {{ $course->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                {{ $course->is_active ? 'إيقاف' : 'تفعيل' }}
                            </button>
                            <button type="button" onclick="toggleCourseFeatured({{ $course->id }})"
                                    class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-[11px] font-medium {{ $course->is_featured ? 'text-amber-800 hover:bg-amber-50' : 'text-ink-soft hover:bg-accent-soft hover:text-accent' }}">
                                <i class="fas fa-star"></i>
                                {{ $course->is_featured ? 'إلغاء التمييز' : 'تمييز' }}
                            </button>
                            <a href="{{ route('admin.advanced-courses.edit', $course) }}"
                               class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-[11px] font-medium text-ink hover:bg-accent-soft hover:text-accent">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <form method="POST" action="{{ route('admin.advanced-courses.destroy', $course) }}" class="inline" onsubmit="return confirm('حذف هذا البرنامج؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-press inline-flex h-8 items-center gap-1.5 rounded-lg border border-line px-2.5 text-[11px] font-medium text-rose-700 hover:bg-rose-50">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="flex justify-center">
            {{ $courses->appends(request()->query())->links() }}
        </div>
    @else
        <article class="rounded-2xl border border-dashed border-line bg-surface px-6 py-14 text-center shadow-soft">
            <div class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-graduation-cap text-xl"></i>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-ink">لا توجد برامج</h3>
            <p class="mt-1 text-sm text-muted">لم يُعثر على برامج تطابق الفلاتر. يمكنك إضافة برنامج جديد.</p>
            <a href="{{ route('admin.advanced-courses.create') }}"
               class="btn-press mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-plus text-xs"></i>
                إضافة برنامج
            </a>
        </article>
    @endif
</div>

@push('scripts')
<script>
function toggleCourseStatus(courseId) {
    if (!confirm('تغيير حالة هذا البرنامج؟')) return;
    fetch(`/admin/advanced-courses/${courseId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); else alert('تعذّر تغيير الحالة'); })
    .catch(() => alert('تعذّر تغيير الحالة'));
}

function toggleCourseFeatured(courseId) {
    if (!confirm('تغيير تمييز هذا البرنامج؟')) return;
    fetch(`/admin/advanced-courses/${courseId}/toggle-featured`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); else alert('تعذّر تغيير التمييز'); })
    .catch(() => alert('تعذّر تغيير التمييز'));
}
</script>
@endpush
@endsection
