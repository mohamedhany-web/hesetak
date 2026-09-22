@extends('layouts.admin')

@section('title', 'الباقات والأسعار - ' . config('app.name'))
@section('page_title', 'الباقات والأسعار')

@section('content')
@php
    $activeTab = request('tab', 'packages');
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp
<div class="space-y-5" x-data="{ activeTab: '{{ $activeTab }}' }">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التجارة · تسعير حصتك</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">الباقات والأسعار</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">مركز موحّد لباقات البرامج المسجّلة، أسعار البرامج، وباقات الحصص المباشرة وفق مواصفات المنصة ({{ currency_label() }} + حساب تلقائي).</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.packages.create') }}"
               x-show="activeTab === 'packages'"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-plus text-xs"></i>
                باقة برامج جديدة
            </a>
        </div>
    </section>

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

    <nav class="flex flex-wrap gap-2 rounded-2xl border border-line bg-surface p-2 shadow-soft">
        <button type="button" @click="activeTab = 'packages'"
                :class="activeTab === 'packages' ? 'bg-accent text-white' : 'text-ink-soft hover:bg-accent-soft hover:text-accent'"
                class="btn-press inline-flex h-10 items-center gap-2 rounded-xl px-4 text-sm font-medium transition">
            <i class="fas fa-box text-xs"></i>
            باقات البرامج ({{ $packageStats['total'] ?? 0 }})
        </button>
        <button type="button" @click="activeTab = 'courses'"
                :class="activeTab === 'courses' ? 'bg-accent text-white' : 'text-ink-soft hover:bg-accent-soft hover:text-accent'"
                class="btn-press inline-flex h-10 items-center gap-2 rounded-xl px-4 text-sm font-medium transition">
            <i class="fas fa-tags text-xs"></i>
            أسعار البرامج ({{ $courseStats['total'] ?? 0 }})
        </button>
    </nav>

    {{-- ===== باقات البرامج المسجّلة ===== --}}
    <div x-show="activeTab === 'packages'" x-cloak class="space-y-5">
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-box text-sm"></i></div>
                <p class="mt-3 text-xs font-medium text-muted">إجمالي الباقات</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $packageStats['total'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-check-circle text-sm"></i></div>
                <p class="mt-3 text-xs font-medium text-muted">نشطة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ $packageStats['active'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-pause-circle text-sm"></i></div>
                <p class="mt-3 text-xs font-medium text-muted">معطّلة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ $packageStats['inactive'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-star text-sm"></i></div>
                <p class="mt-3 text-xs font-medium text-muted">مميزة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $packageStats['featured'] ?? 0 }}</p>
            </article>
        </section>

        <form method="GET" action="{{ route('admin.packages.index') }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <input type="hidden" name="tab" value="packages">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="search">بحث</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="اسم أو وصف الباقة..."
                           class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="status">الحالة</label>
                    <select name="status" id="status" class="{{ $fieldClass }}">
                        <option value="">الكل</option>
                        <option value="active" @selected(request('status') === 'active')>نشطة</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>معطّلة</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="track">المسار</label>
                    <select name="track" id="track" class="{{ $fieldClass }}">
                        <option value="">كل المسارات</option>
                        @foreach(\App\Models\Package::trackLabels() as $key => $label)
                            <option value="{{ $key }}" @selected(request('track') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-filter text-xs"></i> تطبيق
                </button>
                @if(request()->hasAny(['search', 'status', 'track']))
                    <a href="{{ route('admin.packages.index', ['tab' => 'packages']) }}" class="inline-flex h-11 items-center rounded-xl border border-line px-4 text-sm text-muted hover:bg-accent-soft hover:text-accent">إعادة تعيين</a>
                @endif
            </div>
        </form>

        @if(isset($packages) && $packages->count() > 0)
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-right text-xs font-medium text-muted">
                                <th class="px-4 py-3">الباقة</th>
                                <th class="px-4 py-3">المسار</th>
                                <th class="px-4 py-3">البرامج</th>
                                <th class="px-4 py-3">السعر ({{ currency_label() }})</th>
                                <th class="px-4 py-3">الحالة</th>
                                <th class="px-4 py-3">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach($packages as $package)
                                <tr class="hover:bg-[#f8faf9]">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($package->thumbnail)
                                                <img src="{{ storage_asset($package->thumbnail) }}" alt="" class="size-11 rounded-xl object-cover">
                                            @else
                                                <div class="inline-flex size-11 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-box"></i></div>
                                            @endif
                                            <div>
                                                <div class="font-medium text-ink">{{ $package->name }}</div>
                                                @if($package->description)
                                                    <div class="mt-0.5 text-xs text-muted">{{ Str::limit($package->description, 48) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-muted">{{ $package->trackLabel() ?? '—' }}</td>
                                    <td class="px-4 py-3 tabular-nums text-ink">{{ $package->courses_count ?? 0 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold tabular-nums text-ink">{{ $package->formattedPrice(2) }}</div>
                                        @if($package->formattedOriginalPrice(2))
                                            <div class="text-xs text-muted line-through">{{ $package->formattedOriginalPrice(2) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $package->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                {{ $package->is_active ? 'نشط' : 'معطّل' }}
                                            </span>
                                            @if($package->is_featured)
                                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800">مميز</span>
                                            @endif
                                            @if($package->is_popular)
                                                <span class="inline-flex rounded-full bg-accent-soft px-2 py-0.5 text-[11px] font-medium text-accent">شائع</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.packages.show', $package) }}" class="inline-flex size-8 items-center justify-center rounded-lg border border-line text-muted hover:bg-accent-soft hover:text-accent" title="عرض"><i class="fas fa-eye text-xs"></i></a>
                                            <a href="{{ route('admin.packages.edit', $package) }}" class="inline-flex size-8 items-center justify-center rounded-lg border border-line text-muted hover:bg-accent-soft hover:text-accent" title="تعديل"><i class="fas fa-edit text-xs"></i></a>
                                            <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" onsubmit="return confirm('حذف هذه الباقة؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex size-8 items-center justify-center rounded-lg border border-line text-rose-600 hover:bg-rose-50" title="حذف"><i class="fas fa-trash text-xs"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-line px-4 py-3">
                    {{ $packages->appends(request()->except('packages_page') + ['tab' => 'packages'])->links() }}
                </div>
            </article>
        @else
            <article class="rounded-2xl border border-dashed border-line bg-surface px-6 py-14 text-center shadow-soft">
                <div class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-[#f2f5f4] text-accent"><i class="fas fa-box text-xl"></i></div>
                <h3 class="mt-4 text-lg font-semibold text-ink">لا توجد باقات برامج</h3>
                <p class="mt-1 text-sm text-muted">أنشئ باقة تجمع عدة برامج بسعر موحّد بالريال السعودي.</p>
                <a href="{{ route('admin.packages.create') }}" class="btn-press mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">إضافة باقة</a>
            </article>
        @endif
    </div>

    {{-- ===== أسعار البرامج ===== --}}
    <div x-show="activeTab === 'courses'" x-cloak class="space-y-5">
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-graduation-cap text-sm"></i></div>
                <p class="mt-3 text-xs font-medium text-muted">إجمالي البرامج</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $courseStats['total'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-gift text-sm"></i></div>
                <p class="mt-3 text-xs font-medium text-muted">مجانية</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ $courseStats['free'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-dollar-sign text-sm"></i></div>
                <p class="mt-3 text-xs font-medium text-muted">مدفوعة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $courseStats['paid'] ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent"><i class="fas fa-chart-line text-sm"></i></div>
                <p class="mt-3 text-xs font-medium text-muted">إجمالي قيمة الأسعار</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ number_format($courseStats['total_revenue'] ?? 0, 0) }} <span class="text-sm font-medium text-muted">{{ currency_label() }}</span></p>
            </article>
        </section>

        <form method="GET" action="{{ route('admin.packages.index') }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft" x-data="{ showFilters: {{ request()->hasAny(['course_status','course_level','course_language','course_category','course_active']) ? 'true' : 'false' }} }">
            <input type="hidden" name="tab" value="courses">
            <div>
                <label class="{{ $labelClass }}" for="course_search">بحث في البرامج</label>
                <input type="text" name="course_search" id="course_search" value="{{ request('course_search') }}" placeholder="عنوان، وصف، لغة، مسار..."
                       class="{{ $fieldClass }}">
            </div>
            <button type="button" @click="showFilters = !showFilters" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-muted hover:text-accent">
                <i class="fas fa-sliders-h text-xs"></i>
                فلاتر متقدمة
                <i class="fas fa-chevron-down text-[10px] transition" :class="{ 'rotate-180': showFilters }"></i>
            </button>
            <div x-show="showFilters" x-cloak class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="{{ $labelClass }}">نوع السعر</label>
                    <select name="course_status" class="{{ $fieldClass }}">
                        <option value="">الكل</option>
                        <option value="free" @selected(request('course_status') === 'free')>مجانية</option>
                        <option value="paid" @selected(request('course_status') === 'paid')>مدفوعة</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">المستوى</label>
                    <select name="course_level" class="{{ $fieldClass }}">
                        <option value="">الكل</option>
                        <option value="beginner" @selected(request('course_level') === 'beginner')>مبتدئ</option>
                        <option value="intermediate" @selected(request('course_level') === 'intermediate')>متوسط</option>
                        <option value="advanced" @selected(request('course_level') === 'advanced')>متقدم</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">اللغة / التخصص</label>
                    <select name="course_language" class="{{ $fieldClass }}">
                        <option value="">الكل</option>
                        @foreach($programmingLanguages ?? [] as $lang)
                            <option value="{{ $lang }}" @selected(request('course_language') == $lang)>{{ $lang }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">المسار</label>
                    <select name="course_category" class="{{ $fieldClass }}">
                        <option value="">الكل</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category }}" @selected(request('course_category') == $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">الحالة</label>
                    <select name="course_active" class="{{ $fieldClass }}">
                        <option value="">الكل</option>
                        <option value="1" @selected(request('course_active') === '1')>نشط</option>
                        <option value="0" @selected(request('course_active') === '0')>معطّل</option>
                    </select>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">تطبيق</button>
                @if(request()->hasAny(['course_search', 'course_status', 'course_level', 'course_language', 'course_category', 'course_active']))
                    <a href="{{ route('admin.packages.index', ['tab' => 'courses']) }}" class="inline-flex h-11 items-center rounded-xl border border-line px-4 text-sm text-muted">إعادة تعيين</a>
                @endif
            </div>
        </form>

        @if(isset($courses) && $courses->count() > 0)
            <p class="text-xs text-muted">عرض <span class="font-semibold text-ink">{{ $courses->firstItem() }}</span>–<span class="font-semibold text-ink">{{ $courses->lastItem() }}</span> من <span class="font-semibold text-ink">{{ $courses->total() }}</span></p>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($courses as $course)
                    <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft"
                             x-data="{
                                 editing: false,
                                 price: {{ (float) ($course->price ?? 0) }},
                                 isFree: {{ $course->is_free ? 'true' : 'false' }},
                                 saving: false,
                                 async save(event) {
                                     event.preventDefault();
                                     this.saving = true;
                                     try {
                                         const res = await fetch(event.target.action, {
                                             method: 'POST',
                                             headers: {
                                                 'Content-Type': 'application/json',
                                                 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                 'Accept': 'application/json'
                                             },
                                             body: JSON.stringify({ price: this.price, is_free: this.isFree || this.price == 0 })
                                         });
                                         const data = await res.json();
                                         if (data.success) { this.editing = false; location.reload(); }
                                         else { alert('تعذّر تحديث السعر'); }
                                     } catch (e) {
                                         alert('تعذّر تحديث السعر');
                                     } finally {
                                         this.saving = false;
                                     }
                                 }
                             }">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-ink">{{ $course->title }}</h3>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @if($course->programming_language)
                                        <span class="rounded-full bg-[#f2f5f4] px-2 py-0.5 text-[11px] text-ink-soft">{{ $course->programming_language }}</span>
                                    @endif
                                    @if($course->category)
                                        <span class="rounded-full bg-[#f2f5f4] px-2 py-0.5 text-[11px] text-ink-soft">{{ $course->category }}</span>
                                    @endif
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $course->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $course->is_active ? 'نشط' : 'معطّل' }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('admin.advanced-courses.show', $course) }}" class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg border border-line text-muted hover:bg-accent-soft hover:text-accent"><i class="fas fa-eye text-xs"></i></a>
                        </div>

                        <div class="mt-4 border-t border-line pt-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted">السعر الحالي</span>
                                @if($course->is_free || (float) $course->price == 0)
                                    <span class="font-medium text-emerald-700">مجاني</span>
                                @else
                                    <span class="font-semibold tabular-nums text-ink">{{ number_format((float) $course->price, 2) }} {{ currency_label() }}</span>
                                @endif
                            </div>

                            <div x-show="!editing" class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-muted">تعديل سريع</span>
                                <button type="button" @click="editing = true" class="btn-press inline-flex h-8 items-center gap-1 rounded-lg border border-line px-3 text-xs font-medium text-ink hover:bg-accent-soft hover:text-accent">
                                    <i class="fas fa-edit"></i> تعديل
                                </button>
                            </div>

                            <form x-show="editing" x-cloak action="{{ route('admin.packages.update-price', $course) }}" method="POST" @submit.prevent="save($event)" class="mt-3 space-y-2">
                                @csrf
                                <div class="flex items-center gap-2">
                                    <input type="number" x-model.number="price" step="0.01" min="0" class="h-10 flex-1 rounded-xl border border-line px-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                                    <label class="inline-flex items-center gap-1 text-xs text-muted whitespace-nowrap">
                                        <input type="checkbox" x-model="isFree" class="rounded border-line text-accent focus:ring-accent/20"> مجاني
                                    </label>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" :disabled="saving" class="btn-press inline-flex h-9 flex-1 items-center justify-center rounded-xl bg-accent text-xs font-medium text-white">حفظ</button>
                                    <button type="button" @click="editing = false; price = {{ (float) ($course->price ?? 0) }}; isFree = {{ $course->is_free ? 'true' : 'false' }}" class="inline-flex h-9 items-center rounded-xl border border-line px-3 text-xs text-muted">إلغاء</button>
                                </div>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="flex justify-center">{{ $courses->appends(request()->except('courses_page') + ['tab' => 'courses'])->links() }}</div>
        @else
            <article class="rounded-2xl border border-dashed border-line bg-surface px-6 py-14 text-center shadow-soft">
                <h3 class="text-lg font-semibold text-ink">لا توجد برامج</h3>
                <p class="mt-1 text-sm text-muted">لم يُعثر على برامج تطابق الفلاتر.</p>
            </article>
        @endif
    </div>

    {{-- ===== باقات الحصص المباشرة ===== --}}

@endsection
