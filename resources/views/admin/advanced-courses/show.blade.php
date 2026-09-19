@extends('layouts.admin')

@section('title', 'تفاصيل البرنامج - ' . config('app.name'))
@section('page_title', 'تفاصيل البرنامج')

@section('content')
@php
    $levelLabel = match($advancedCourse->level) {
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
        default => '—',
    };
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-accent">{{ __('admin.dashboard') }}</a>
                <span class="mx-1">·</span>
                <a href="{{ route('admin.advanced-courses.index') }}" class="hover:text-accent">{{ __('admin.courses_management') }}</a>
                <span class="mx-1">·</span>
                <span class="text-ink">{{ Str::limit($advancedCourse->title, 40) }}</span>
            </p>
            <h2 class="mt-1 truncate text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $advancedCourse->title }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ $advancedCourse->category ?: '—' }} · {{ $advancedCourse->instructor?->name ?? '—' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.advanced-courses.edit', $advancedCourse) }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-edit text-xs"></i>
                تعديل البرنامج
            </a>
            <a href="{{ route('admin.advanced-courses.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                العودة للبرامج
            </a>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-4">
        <div class="xl:col-span-3">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-ink">معلومات البرنامج</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $advancedCourse->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f2f5f4] text-muted' }}">
                            {{ $advancedCourse->is_active ? 'نشط' : 'معطّل' }}
                        </span>
                        @if($advancedCourse->is_featured)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800">
                                <i class="fas fa-star text-[10px]"></i>
                                مميّز
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-line px-3 py-3">
                        <p class="text-xs font-medium text-muted">العنوان</p>
                        <p class="mt-1 truncate text-sm font-semibold text-ink" title="{{ $advancedCourse->title }}">{{ Str::limit($advancedCourse->title, 25) }}</p>
                    </div>
                    <div class="rounded-xl border border-line px-3 py-3">
                        <p class="text-xs font-medium text-muted">المسار / المدرّس</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $advancedCourse->category ?? '—' }} · {{ $advancedCourse->instructor?->name ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl border border-line px-3 py-3">
                        <p class="text-xs font-medium text-muted">المستوى</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $levelLabel }}</p>
                    </div>
                    <div class="rounded-xl border border-line px-3 py-3">
                        <p class="text-xs font-medium text-muted">السعر / المدة</p>
                        <p class="mt-1 text-sm font-semibold tabular-nums text-ink">
                            @if($advancedCourse->hasPromotionalPrice())
                                <span class="text-muted line-through">{{ number_format($advancedCourse->listPriceAmount(), 0) }}</span>
                                <span class="mx-1 text-muted">←</span>
                            @endif
                            {{ number_format($advancedCourse->effectivePurchasePrice(), 0) }} {{ currency_symbol() }} · {{ $advancedCourse->duration_hours ?? 0 }} س
                        </p>
                    </div>
                </div>

                @if($advancedCourse->description)
                    <div class="mt-5">
                        <p class="text-xs font-medium text-muted">الوصف</p>
                        <div class="mt-2 rounded-xl border border-line bg-[#f8faf9] px-4 py-3 text-sm text-ink">{{ $advancedCourse->description }}</div>
                    </div>
                @endif
            </article>
        </div>

        <div class="space-y-3">
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-play-circle text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-semibold tabular-nums text-ink">{{ $stats['total_lessons'] }}</p>
                        <p class="text-xs text-muted">دروس</p>
                    </div>
                </div>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-users text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-semibold tabular-nums text-ink">{{ $stats['active_students'] }}</p>
                        <p class="text-xs text-muted">طالب نشط</p>
                    </div>
                </div>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-clock text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-semibold tabular-nums text-ink">{{ $stats['pending_orders'] }}</p>
                        <p class="text-xs text-muted">طلب معلّق</p>
                    </div>
                </div>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                        <i class="fas fa-film text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-semibold tabular-nums text-ink">{{ floor($stats['total_duration'] / 60) }}</p>
                        <p class="text-xs text-muted">ساعة محتوى</p>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft" x-data="{ activeTab: 'lessons' }">
        <div class="border-b border-line px-4 sm:px-5">
            <nav class="flex flex-wrap gap-1">
                <button type="button" @click="activeTab = 'lessons'"
                        :class="activeTab === 'lessons' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-ink'"
                        class="whitespace-nowrap border-b-2 px-3 py-3.5 text-sm font-medium transition-colors">
                    <i class="fas fa-play-circle ml-1.5 text-xs"></i>
                    الدروس ({{ $stats['total_lessons'] }})
                </button>
                <button type="button" @click="activeTab = 'students'"
                        :class="activeTab === 'students' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-ink'"
                        class="whitespace-nowrap border-b-2 px-3 py-3.5 text-sm font-medium transition-colors">
                    <i class="fas fa-users ml-1.5 text-xs"></i>
                    الطلاب ({{ $stats['total_students'] }})
                </button>
                <button type="button" @click="activeTab = 'orders'"
                        :class="activeTab === 'orders' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-ink'"
                        class="whitespace-nowrap border-b-2 px-3 py-3.5 text-sm font-medium transition-colors">
                    <i class="fas fa-shopping-cart ml-1.5 text-xs"></i>
                    الطلبات ({{ $advancedCourse->orders->count() }})
                </button>
                <button type="button" @click="activeTab = 'actions'"
                        :class="activeTab === 'actions' ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-ink'"
                        class="whitespace-nowrap border-b-2 px-3 py-3.5 text-sm font-medium transition-colors">
                    <i class="fas fa-cogs ml-1.5 text-xs"></i>
                    الإجراءات
                </button>
            </nav>
        </div>

        <div class="p-5">
            <!-- تبويب الدروس -->
            <div x-show="activeTab === 'lessons'">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold text-ink">دروس البرنامج</h4>
                    <a href="{{ route('admin.courses.lessons.create', $advancedCourse) }}"
                       class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                        <i class="fas fa-plus text-xs"></i>
                        إضافة درس
                    </a>
                </div>

                @if($advancedCourse->lessons->count() > 0)
                    <div class="space-y-2">
                        @foreach($advancedCourse->lessons as $lesson)
                            @php
                                $lessonIcon = match($lesson->type) {
                                    'video' => 'fa-play',
                                    'document' => 'fa-file-alt',
                                    'quiz' => 'fa-question-circle',
                                    default => 'fa-tasks',
                                };
                                $lessonTypeLabel = match($lesson->type) {
                                    'video' => 'فيديو',
                                    'document' => 'مستند',
                                    'quiz' => 'كويز',
                                    default => 'واجب',
                                };
                            @endphp
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line px-4 py-3 transition hover:bg-[#f8faf9]">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                                        <i class="fas {{ $lessonIcon }} text-sm"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-ink">{{ $lesson->title }}</p>
                                        <p class="text-xs text-muted">
                                            {{ $lesson->duration_minutes ?? 0 }} دقيقة · {{ $lessonTypeLabel }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $lesson->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $lesson->is_active ? 'نشط' : 'غير نشط' }}
                                    </span>
                                    <a href="{{ route('admin.courses.lessons.show', [$advancedCourse, $lesson]) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent transition hover:bg-accent-soft"
                                       title="عرض"><i class="fas fa-eye text-xs"></i></a>
                                    <button type="button" onclick="toggleLessonStatus({{ $lesson->id }})"
                                            class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-amber-700 transition hover:bg-amber-50"
                                            title="{{ $lesson->is_active ? 'إيقاف' : 'تفعيل' }}"><i class="fas fa-power-off text-xs"></i></button>
                                    <a href="{{ route('admin.courses.lessons.edit', [$advancedCourse, $lesson]) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent transition hover:bg-accent-soft"
                                       title="تعديل"><i class="fas fa-edit text-xs"></i></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-line px-6 py-12 text-center">
                        <div class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-play-circle text-xl"></i>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-ink">لا توجد دروس</h3>
                        <p class="mt-1 text-sm text-muted">ابدأ بإضافة الدروس لهذا البرنامج</p>
                        <a href="{{ route('admin.courses.lessons.create', $advancedCourse) }}"
                           class="btn-press mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                            <i class="fas fa-plus text-xs"></i>
                            إضافة أول درس
                        </a>
                    </div>
                @endif
            </div>

            <!-- تبويب الطلاب -->
            <div x-show="activeTab === 'students'" style="display: none;">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold text-ink">الطلاب المسجّلون</h4>
                    <a href="{{ route('admin.advanced-courses.students', $advancedCourse) }}"
                       class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                        <i class="fas fa-user-plus text-xs"></i>
                        إضافة طالب
                    </a>
                </div>

                @if($advancedCourse->enrollments->count() > 0)
                    <div class="overflow-x-auto rounded-xl border border-line">
                        <table class="min-w-full divide-y divide-line">
                            <thead class="bg-[#f8faf9]">
                                <tr>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted">الطالب</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted">الحالة</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted">التقدم</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted">تاريخ التسجيل</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line bg-surface">
                                @foreach($advancedCourse->enrollments as $enrollment)
                                    @php
                                        $enrClass = match($enrollment->status) {
                                            'active' => 'bg-emerald-50 text-emerald-700',
                                            'pending' => 'bg-amber-50 text-amber-800',
                                            'completed' => 'bg-accent-soft text-accent',
                                            default => 'bg-rose-50 text-rose-700',
                                        };
                                    @endphp
                                    <tr class="transition hover:bg-[#f8faf9]">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-semibold text-accent">
                                                    {{ substr($enrollment->student->name, 0, 1) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-medium text-ink">{{ $enrollment->student->name }}</div>
                                                    <div class="truncate text-xs text-muted" dir="ltr">{{ $enrollment->student->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $enrClass }}">
                                                {{ $enrollment->status_text }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 w-24 rounded-full bg-[#e8eeec]">
                                                    <div class="h-2 rounded-full bg-accent transition-all" style="width: {{ $enrollment->progress }}%"></div>
                                                </div>
                                                <span class="text-xs tabular-nums text-ink">{{ $enrollment->progress }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm tabular-nums text-muted">
                                            {{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('Y-m-d') : 'غير محدد' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <a href="{{ route('admin.online-enrollments.show', $enrollment) }}"
                                               class="font-medium text-accent hover:underline">عرض</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-line px-6 py-12 text-center">
                        <div class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-ink">لا يوجد طلاب</h3>
                        <p class="mt-1 text-sm text-muted">لم يتم تسجيل أي طالب في هذا البرنامج بعد</p>
                        <a href="{{ route('admin.advanced-courses.students', $advancedCourse) }}"
                           class="btn-press mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                            <i class="fas fa-user-plus text-xs"></i> إضافة طالب
                        </a>
                    </div>
                @endif
            </div>

            <!-- تبويب الطلبات -->
            <div x-show="activeTab === 'orders'" style="display: none;">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold text-ink">طلبات التسجيل</h4>
                    <a href="{{ route('admin.orders.index') }}?course_id={{ $advancedCourse->id }}"
                       class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                        <i class="fas fa-external-link-alt text-xs"></i>
                        عرض جميع الطلبات
                    </a>
                </div>

                @if($advancedCourse->orders->count() > 0)
                    <div class="space-y-2">
                        @foreach($advancedCourse->orders->take(10) as $order)
                            @php
                                $orderClass = match($order->status) {
                                    'pending' => 'bg-amber-50 text-amber-800',
                                    'approved' => 'bg-emerald-50 text-emerald-700',
                                    default => 'bg-rose-50 text-rose-700',
                                };
                            @endphp
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line px-4 py-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-semibold text-accent">
                                        {{ substr($order->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-ink">{{ $order->user->name }}</p>
                                        <p class="text-xs text-muted">{{ $order->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $orderClass }}">
                                        {{ $order->status_text }}
                                    </span>
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">
                                        <i class="fas fa-eye text-xs"></i> عرض
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-line px-6 py-12 text-center">
                        <div class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent">
                            <i class="fas fa-shopping-cart text-xl"></i>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-ink">لا توجد طلبات</h3>
                        <p class="mt-1 text-sm text-muted">لا توجد طلبات تسجيل لهذا البرنامج</p>
                    </div>
                @endif
            </div>

            <!-- تبويب الإجراءات -->
            <div x-show="activeTab === 'actions'" style="display: none;">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                        <h5 class="text-sm font-semibold text-ink">حالة البرنامج</h5>
                        <p class="mt-1 text-xs text-muted">تفعيل أو إيقاف البرنامج للطلاب</p>
                        <button type="button" onclick="toggleCourseStatus({{ $advancedCourse->id }})"
                                class="btn-press mt-4 inline-flex h-11 w-full items-center justify-center rounded-xl px-4 text-sm font-medium text-white {{ $advancedCourse->is_active ? 'bg-rose-600 hover:bg-rose-700' : 'bg-accent hover:bg-[#0d4f4a]' }}">
                            {{ $advancedCourse->is_active ? 'إيقاف البرنامج' : 'تفعيل البرنامج' }}
                        </button>
                    </div>

                    <div class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                        <h5 class="text-sm font-semibold text-ink">ترشيح البرنامج</h5>
                        <p class="mt-1 text-xs text-muted">عرض البرنامج في القائمة المرشّحة</p>
                        <button type="button" onclick="toggleCourseFeatured({{ $advancedCourse->id }})"
                                class="btn-press mt-4 inline-flex h-11 w-full items-center justify-center rounded-xl px-4 text-sm font-medium {{ $advancedCourse->is_featured ? 'border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-accent text-white hover:bg-[#0d4f4a]' }}">
                            {{ $advancedCourse->is_featured ? 'إلغاء الترشيح' : 'ترشيح البرنامج' }}
                        </button>
                    </div>

                    <div class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                        <h5 class="text-sm font-semibold text-ink">نسخ البرنامج</h5>
                        <p class="mt-1 text-xs text-muted">إنشاء نسخة من البرنامج والدروس</p>
                        <form action="{{ route('admin.advanced-courses.duplicate', $advancedCourse) }}" method="POST" class="mt-4">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('هل تريد إنشاء نسخة من هذا البرنامج؟')"
                                    class="btn-press inline-flex h-11 w-full items-center justify-center rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                                نسخ البرنامج
                            </button>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                        <h5 class="text-sm font-semibold text-ink">الإحصائيات</h5>
                        <p class="mt-1 text-xs text-muted">عرض إحصائيات مفصّلة للبرنامج</p>
                        <a href="{{ route('admin.advanced-courses.statistics', $advancedCourse) }}"
                           class="btn-press mt-4 inline-flex h-11 w-full items-center justify-center rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                            عرض الإحصائيات
                        </a>
                    </div>

                    <div class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                        <h5 class="text-sm font-semibold text-ink">إدارة الدروس</h5>
                        <p class="mt-1 text-xs text-muted">إضافة وتعديل دروس البرنامج</p>
                        <a href="{{ route('admin.courses.lessons.index', $advancedCourse) }}"
                           class="btn-press mt-4 inline-flex h-11 w-full items-center justify-center rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                            إدارة الدروس
                        </a>
                    </div>

                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-soft">
                        <h5 class="text-sm font-semibold text-rose-900">حذف البرنامج</h5>
                        <p class="mt-1 text-xs text-rose-700">حذف البرنامج نهائياً (لا يمكن التراجع)</p>
                        <form action="{{ route('admin.advanced-courses.destroy', $advancedCourse) }}" method="POST" class="mt-4">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('هل أنت متأكد من حذف هذا البرنامج؟ هذا الإجراء لا يمكن التراجع عنه!')"
                                    class="btn-press inline-flex h-11 w-full items-center justify-center rounded-xl bg-rose-600 px-4 text-sm font-medium text-white hover:bg-rose-700">
                                حذف البرنامج
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </article>
</div>

@push('scripts')
<script>
function toggleCourseStatus(courseId) {
    if (confirm('هل تريد تغيير حالة هذا البرنامج؟')) {
        fetch(`/admin/advanced-courses/${courseId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('حدث خطأ في تغيير حالة البرنامج');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في تغيير حالة البرنامج');
        });
    }
}

function toggleCourseFeatured(courseId) {
    if (confirm('هل تريد تغيير حالة ترشيح هذا البرنامج؟')) {
        fetch(`/admin/advanced-courses/${courseId}/toggle-featured`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('حدث خطأ في تغيير حالة الترشيح');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في تغيير حالة الترشيح');
        });
    }
}

function toggleLessonStatus(lessonId) {
    if (confirm('هل تريد تغيير حالة هذا الدرس؟')) {
        fetch(`/admin/courses/{{ $advancedCourse->id }}/lessons/${lessonId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('حدث خطأ في تغيير حالة الدرس');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في تغيير حالة الدرس');
        });
    }
}
</script>
@endpush
@endsection
