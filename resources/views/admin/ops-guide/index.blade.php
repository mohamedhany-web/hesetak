@extends('layouts.admin')

@section('title', 'مساعد التشغيل')
@section('header', 'مساعد التشغيل — مرجع لوحة الإدارة')

@section('content')
@php
    $sections = $sections ?? [];
@endphp

<div class="space-y-6" x-data="{ q: '', active: '{{ $sections[0]['id'] ?? '' }}' }">
    <section class="rounded-2xl border border-accent/20 bg-gradient-to-l from-accent-soft/80 via-surface to-surface p-5 sm:p-6 shadow-soft">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide text-accent">مرجع التشغيل الداخلي</p>
                <h2 class="mt-1 text-xl font-black text-ink sm:text-2xl">شرح لوحة تحكم حصتك بالتفصيل</h2>
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ink-soft">
                    هذه الصفحة هي المرجع الرئيسي لأقسام السايدبار: ماذا يفعل كل قسم، متى تستخدمه، وكيف تتخذ القرار التشغيلي.
                    السؤال اليومي للإدارة: <strong class="text-ink">إيه اللي يحتاج تدخّل الآن؟</strong>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold text-ink hover:bg-canvas">
                    <i class="fas fa-chart-line text-accent"></i> لوحة التحكم
                </a>
                <a href="{{ route('admin.academy-insights.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-accent px-3 py-2 text-sm font-semibold text-white shadow-soft">
                    <i class="fas fa-brain"></i> ابدأ من التحليلات
                </a>
            </div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-line bg-surface/80 p-3">
                <p class="text-[11px] font-bold text-muted">عدد الأقسام</p>
                <p class="mt-1 text-2xl font-black text-ink">{{ count($sections) }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface/80 p-3">
                <p class="text-[11px] font-bold text-muted">آخر تحديث للمحتوى</p>
                <p class="mt-1 text-sm font-bold text-ink">{{ optional($updatedAt ?? now())->format('Y-m-d H:i') }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface/80 p-3">
                <p class="text-[11px] font-bold text-muted">بحث سريع</p>
                <input type="search" x-model="q" placeholder="ابحث عن قسم أو أداة…"
                       class="mt-1 w-full rounded-lg border border-line bg-canvas px-3 py-2 text-sm text-ink outline-none focus:border-accent">
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="xl:sticky xl:top-4 xl:self-start">
            <nav class="rounded-2xl border border-line bg-surface p-3 shadow-soft" aria-label="فهرس الأقسام">
                <p class="px-2 pb-2 text-xs font-bold text-muted">أقسام السايدبار</p>
                <ul class="max-h-[70vh] space-y-0.5 overflow-y-auto">
                    @foreach($sections as $section)
                        <li>
                            <a href="#ops-{{ $section['id'] }}"
                               @click="active = '{{ $section['id'] }}'"
                               class="flex items-center gap-2 rounded-xl px-2.5 py-2 text-sm font-semibold transition"
                               :class="active === '{{ $section['id'] }}' ? 'bg-accent-soft text-accent' : 'text-ink-soft hover:bg-canvas hover:text-ink'">
                                <i class="{{ $section['icon'] }} w-4 text-center text-xs opacity-80"></i>
                                <span class="truncate">{{ $section['title'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </aside>

        <div class="space-y-8">
            @foreach($sections as $section)
                <section id="ops-{{ $section['id'] }}"
                         class="scroll-mt-24 rounded-2xl border border-line bg-surface shadow-soft overflow-hidden"
                         x-show="!q || ('{{ addslashes(mb_strtolower($section['title'].' '.$section['summary'].' '.collect($section['items'])->pluck('title')->implode(' '))) }}').includes(q.toLowerCase())">
                    <header class="border-b border-line bg-canvas/70 px-4 py-4 sm:px-5">
                        <div class="flex flex-wrap items-start gap-3">
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl bg-accent text-white shadow-soft">
                                <i class="{{ $section['icon'] }}"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-black text-ink">{{ $section['title'] }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $section['summary'] }}</p>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold">
                                    <span class="rounded-full bg-accent-soft px-2.5 py-1 text-accent">لـ: {{ $section['audience'] }}</span>
                                    <span class="rounded-full bg-canvas px-2.5 py-1 text-ink-soft border border-line">قرار: {{ $section['decision'] }}</span>
                                </div>
                            </div>
                        </div>
                    </header>

                    <div class="grid gap-0 lg:grid-cols-2">
                        <div class="border-b border-line p-4 sm:p-5 lg:border-b-0 lg:border-l">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">كيف تستخدم القسم</p>
                            <ol class="mt-3 space-y-2">
                                @foreach($section['steps'] as $i => $step)
                                    <li class="flex gap-2 text-sm text-ink-soft">
                                        <span class="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-accent-soft text-[11px] font-black text-accent">{{ $i + 1 }}</span>
                                        <span>{{ $step }}</span>
                                    </li>
                                @endforeach
                            </ol>

                            <div class="mt-5 overflow-hidden rounded-xl border border-line bg-canvas">
                                @if(! empty($section['screenshot_url']))
                                    <img src="{{ $section['screenshot_url'] }}"
                                         alt="لقطة شاشة: {{ $section['title'] }}"
                                         class="block w-full object-cover object-top"
                                         loading="lazy"
                                         style="max-height:280px">
                                @else
                                    <div class="flex h-44 flex-col items-center justify-center gap-2 bg-gradient-to-br from-canvas to-accent-soft/40 px-4 text-center">
                                        <i class="{{ $section['icon'] }} text-2xl text-accent"></i>
                                        <p class="text-sm font-bold text-ink">{{ $section['title'] }}</p>
                                        <p class="text-xs text-muted">لقطة الشاشة تُضاف بعد التقاطها من لوحة التحكم</p>
                                    </div>
                                @endif
                                <div class="border-t border-line px-3 py-2 text-[11px] font-semibold text-muted">
                                    لقطة من واجهة القسم داخل لوحة الإدارة
                                </div>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">أدوات السايدبار داخل القسم</p>
                            <div class="mt-3 space-y-3">
                                @foreach($section['items'] as $item)
                                    <article class="rounded-xl border border-line bg-canvas/50 p-3.5">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <h4 class="text-sm font-black text-ink">{{ $item['title'] }}</h4>
                                            @if(! empty($item['url']))
                                                <a href="{{ $item['url'] }}" class="inline-flex items-center gap-1 text-xs font-bold text-accent hover:underline">
                                                    فتح الصفحة <i class="fas fa-external-link-alt text-[10px]"></i>
                                                </a>
                                            @endif
                                        </div>
                                        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ $item['body'] }}</p>
                                        @if(! empty($item['tips']))
                                            <ul class="mt-2 space-y-1">
                                                @foreach($item['tips'] as $tip)
                                                    <li class="flex gap-2 text-xs font-semibold text-muted">
                                                        <i class="fas fa-check text-accent mt-0.5"></i>
                                                        <span>{{ $tip }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <section class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
        <h3 class="text-base font-black text-ink">قواعد تشغيل ثابتة (حصتك)</h3>
        <ul class="mt-3 grid gap-2 sm:grid-cols-2">
            <li class="rounded-xl border border-line bg-canvas px-3 py-2.5 text-sm text-ink-soft">المطابقة ثلاثية: مرحلة + مادة + نوع منهج.</li>
            <li class="rounded-xl border border-line bg-canvas px-3 py-2.5 text-sm text-ink-soft">تبديل المعلم بلا خصم من رصيد المحفظة.</li>
            <li class="rounded-xl border border-line bg-canvas px-3 py-2.5 text-sm text-ink-soft">الحصة لا تُقفل مالياً قبل تقرير المعلم الإلزامي.</li>
            <li class="rounded-xl border border-line bg-canvas px-3 py-2.5 text-sm text-ink-soft">تأكيد الدفع من webhook البوابة فقط.</li>
            <li class="rounded-xl border border-line bg-canvas px-3 py-2.5 text-sm text-ink-soft">صلاحية تُتحقق عند التنفيذ، لا بإخفاء الزر فقط.</li>
            <li class="rounded-xl border border-line bg-canvas px-3 py-2.5 text-sm text-ink-soft">الحضور الجغرافي المعلن: أمريكا · مصر · السعودية فقط.</li>
        </ul>
    </section>
</div>
@endsection
