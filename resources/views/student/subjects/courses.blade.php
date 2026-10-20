@extends('layouts.student-timeline')

@section('title', $academicSubject->name . ' - كورسات المسار')
@section('header', 'كورسات ' . $academicSubject->name)

@section('content')
<div class="space-y-8">
    <div class="bg-white border border-gray-100 rounded-3xl shadow-xl overflow-hidden">
        <div class="px-6 py-8 sm:px-10 sm:py-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="space-y-4 max-w-3xl">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-sky-100 text-sky-700 text-sm font-semibold">
                        <i class="fas fa-layer-group"></i>
                        @if($academicSubject->academicYear)
                            {{ $academicSubject->academicYear->name }} • 
                        @endif
                        {{ $academicSubject->name }}
                    </span>
                    <h1 class="text-3xl font-black text-gray-900">
                        كورسات متدرجة لبناء خبرتك داخل هذه المجموعة المهارية
                    </h1>
                    <p class="text-gray-600 text-lg">
                        اختر البرنامج المناسب لمستواك وأهدافك التربوية. الكورسات تركّز على التطبيق العملي في الصف، أدوات تدريس حديثة، وممارسات موثوقة لدعم المعلّم.
                    </p>
                </div>
                @if($academicSubject->academicYear)
                <a href="{{ route('academic-years.subjects', $academicSubject->academicYear) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white hover:bg-slate-700 transition-colors">
                    <i class="fas fa-arrow-right"></i>
                    العودة للمجموعة
                </a>
                @else
                <a href="{{ route('academic-years') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white hover:bg-slate-700 transition-colors">
                    <i class="fas fa-arrow-right"></i>
                    العودة للمسارات
                </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-8">
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5">
                    <p class="text-sm text-slate-500">عدد الكورسات</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $courseStats['total'] }}</p>
                </div>
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5">
                    <p class="text-sm text-slate-500">اللغات الأساسية</p>
                    <p class="text-lg font-semibold text-slate-900 mt-2">
                        {{ collect($courseStats['languages'])->take(3)->implode(' • ') ?: 'قريباً' }}
                    </p>
                </div>
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5">
                    <p class="text-sm text-slate-500">أطر العمل</p>
                    <p class="text-lg font-semibold text-slate-900 mt-2">
                        {{ collect($courseStats['frameworks'])->take(3)->implode(' • ') ?: 'قريباً' }}
                    </p>
                </div>
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5">
                    <p class="text-sm text-slate-500">مدة التعلم المتوسطة</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-2">
                        {{ $courseStats['average_duration'] ?? 'متغيرة' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if($courses->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($courses as $course)
                <div class="bg-white border border-gray-100 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden flex flex-col">
                    <div class="relative h-48 bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center">
                        @if(!empty($course->thumbnail))
                            <img src="{{ storage_asset($course->thumbnail) }}" alt="{{ $course->title }}" class="absolute inset-0 w-full h-full object-cover opacity-90">
                        @else
                            <i class="fas fa-play-circle text-white text-6xl"></i>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute top-4 left-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/90 text-slate-900">
                                {{ $course->level ? __($course->level) : 'مبتدئ' }}
                            </span>
                        </div>
                        <div class="absolute top-4 right-4 space-y-2 text-right">
                            @if(!$course->is_free && $course->effectivePurchasePrice() > 0)
                                @if($course->hasPromotionalPrice())
                                    <span class="inline-flex flex-col items-end gap-0.5 px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-bold shadow-lg tabular-nums">
                                        <span class="line-through opacity-85 text-[10px]">{{ number_format($course->listPriceAmount(), 0) }} {{ currency_symbol() }}</span>
                                        <span class="text-sm">{{ number_format($course->effectivePurchasePrice(), 0) }} {{ currency_symbol() }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg bg-emerald-500 text-white text-sm font-bold shadow-lg tabular-nums">
                                        {{ number_format($course->effectivePurchasePrice(), 0) }} {{ currency_symbol() }}
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-white/90 text-emerald-600 text-sm font-bold shadow-lg">
                                    مجاني
                                </span>
                            @endif
                            @if(!empty($course->rating))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-black/30 text-white text-xs font-medium">
                                    <i class="fas fa-star text-amber-400 ml-1"></i>{{ number_format($course->rating, 1) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 flex flex-col p-6 space-y-4">
                        <div class="space-y-2">
                            <h2 class="text-lg font-bold text-gray-900 leading-tight">{{ $course->title }}</h2>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                {{ Str::limit($course->description, 130) }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs text-gray-500">
                            @if($course->programming_language)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-tag text-sky-500"></i>
                                    <span>{{ $course->programming_language }}</span>
                                </div>
                            @endif
                            @if($course->framework)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-cubes text-indigo-500"></i>
                                    <span>{{ $course->framework }}</span>
                                </div>
                            @endif
                            @if($course->lessons_count)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-video text-emerald-500"></i>
                                    <span>{{ $course->lessons_count }} درس</span>
                                </div>
                            @endif
                            @if($course->duration_label)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-clock text-amber-500"></i>
                                    <span>{{ $course->duration_label }}</span>
                                </div>
                            @endif
                        </div>

                        @if($course->tech_stack && $course->tech_stack->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($course->tech_stack as $skill)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ $skill }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-4 mt-auto border-t border-slate-100">
                            <div class="text-xs text-gray-400">
                                <span>آخر تحديث</span>
                                <span class="font-semibold text-gray-500 ml-1">
                                    {{ optional($course->created_at)->diffForHumans() }}
                                </span>
                            </div>
                            <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-600 text-white hover:bg-sky-700 transition-colors text-sm font-semibold">
                                تفاصيل الكورس
                                <i class="fas fa-arrow-left text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white border border-gray-100 rounded-3xl shadow-xl p-12 text-center space-y-4">
            <div class="flex items-center justify-center">
                <span class="w-16 h-16 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center text-2xl">
                    <i class="fas fa-graduation-cap"></i>
                </span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900">لا توجد كورسات في هذه المجموعة حالياً</h3>
            <p class="text-gray-500 max-w-xl mx-auto">
                نعمل على تجهيز كورسات جديدة لهذه المجموعة. تواصل مع الدعم إذا كنت بحاجة إلى مسار بديل أو توصية بكورس متاح.
            </p>
            @if($academicSubject->academicYear)
            <a href="{{ route('academic-years.subjects', $academicSubject->academicYear) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white hover:bg-sky-700 transition-colors">
                <i class="fas fa-arrow-right"></i>
                العودة للمجموعة
            </a>
            @else
            <a href="{{ route('academic-years') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white hover:bg-sky-700 transition-colors">
                <i class="fas fa-arrow-right"></i>
                العودة للمسارات
            </a>
            @endif
        </div>
    @endif
</div>
@endsection


