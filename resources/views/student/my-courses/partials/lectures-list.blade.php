<div class="space-y-4 w-full max-w-full">
    @if($course->lectures->count() > 0)
        <!-- إحصائيات المحاضرات -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mb-6">
            <div class="bg-sky-50 rounded-lg p-3 sm:p-4 border border-sky-100">
                <div class="text-xl sm:text-2xl font-bold text-[#1E4E8C]">{{ $course->lectures->where('status', 'scheduled')->count() }}</div>
                <div class="text-xs font-medium text-gray-600">مجدولة</div>
            </div>
            <div class="bg-amber-50 rounded-lg p-3 sm:p-4 border border-amber-100">
                <div class="text-xl sm:text-2xl font-bold text-amber-600">{{ $course->lectures->where('status', 'in_progress')->count() }}</div>
                <div class="text-xs font-medium text-gray-600">قيد التنفيذ</div>
            </div>
            <div class="bg-emerald-50 rounded-lg p-3 sm:p-4 border border-emerald-100">
                <div class="text-xl sm:text-2xl font-bold text-emerald-600">{{ $course->lectures->where('status', 'completed')->count() }}</div>
                <div class="text-xs font-medium text-gray-600">مكتملة</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-100">
                <div class="text-xl sm:text-2xl font-bold text-gray-700">{{ $course->lectures->count() }}</div>
                <div class="text-xs font-medium text-gray-600">إجمالي</div>
            </div>
        </div>

        @if($lecturesByLesson->count() > 0)
            @foreach($lecturesByLesson as $lessonId => $lectures)
                @php
                    $lesson = $course->lessons->find($lessonId);
                @endphp
                <div class="mb-6 w-full max-w-full">
                    @if($lesson)
                        <h4 class="text-base sm:text-lg font-bold text-gray-900 mb-3 flex flex-wrap items-center gap-2 bg-sky-50 px-4 py-3 rounded-lg border border-sky-100">
                            <i class="fas fa-book text-[#1E4E8C]"></i>
                            <span>{{ $lesson->title }}</span>
                            <span class="mr-auto bg-[#1E4E8C] text-white text-xs px-2.5 py-1 rounded-full font-semibold">{{ $lectures->count() }} محاضرة</span>
                        </h4>
                    @else
                        <h4 class="text-base sm:text-lg font-bold text-gray-900 mb-3 flex flex-wrap items-center gap-2 bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                            <i class="fas fa-chalkboard-teacher text-[#1E4E8C]"></i>
                            <span>محاضرات عامة</span>
                            <span class="mr-auto bg-[#1E4E8C] text-white text-xs px-2.5 py-1 rounded-full font-semibold">{{ $lectures->count() }} محاضرة</span>
                        </h4>
                    @endif

                    <div class="space-y-2 sm:space-y-3">
                        @foreach($lectures->sortByDesc('scheduled_at') as $lecture)
                            @php
                                $statusText = [
                                    'scheduled' => 'مجدولة',
                                    'in_progress' => 'قيد التنفيذ',
                                    'completed' => 'مكتملة',
                                    'cancelled' => 'ملغاة',
                                ][$lecture->status] ?? 'غير محدد';
                                $statusBg = [
                                    'scheduled' => 'bg-sky-50 text-sky-700 border-sky-100',
                                    'in_progress' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'cancelled' => 'bg-gray-100 text-gray-600 border-gray-200',
                                ][$lecture->status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                                $iconBg = [
                                    'scheduled' => 'bg-[#1E4E8C]',
                                    'in_progress' => 'bg-amber-500',
                                    'completed' => 'bg-emerald-500',
                                    'cancelled' => 'bg-gray-400',
                                ][$lecture->status] ?? 'bg-gray-400';
                            @endphp

                            <div class="w-full max-w-full bg-white rounded-xl p-4 sm:p-5 border border-gray-200 hover:shadow-md transition-all duration-200">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div class="flex items-start gap-3 flex-1 min-w-0 w-full">
                                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-lg {{ $iconBg }} text-white flex items-center justify-center">
                                            @if($lecture->status === 'scheduled')
                                                <i class="fas fa-calendar text-sm"></i>
                                            @elseif($lecture->status === 'in_progress')
                                                <i class="fas fa-play-circle text-sm"></i>
                                            @elseif($lecture->status === 'completed')
                                                <i class="fas fa-check-circle text-sm"></i>
                                            @else
                                                <i class="fas fa-times-circle text-sm"></i>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0 w-full">
                                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                                <h4 class="text-base sm:text-lg font-bold text-gray-900">{{ $lecture->title }}</h4>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold border {{ $statusBg }}">{{ $statusText }}</span>
                                            </div>
                                            @if($lecture->description)
                                                <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed">{{ $lecture->description }}</p>
                                            @endif
                                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                                    <i class="fas fa-calendar-alt"></i> {{ $lecture->scheduled_at->format('Y/m/d') }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                                    <i class="fas fa-clock"></i> {{ $lecture->scheduled_at->format('H:i') }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                                    {{ $lecture->duration_minutes }} دقيقة
                                                </span>
                                                @if($lecture->instructor)
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                                        <i class="fas fa-user-tie"></i> {{ $lecture->instructor->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 flex-shrink-0 w-full sm:w-auto pt-2 sm:pt-0 border-t border-gray-100 sm:border-t-0">
                                        @if($lecture->recording_url)
                                            <a href="{{ $lecture->recording_url }}" target="_blank" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                                                <i class="fas fa-play-circle"></i> التسجيل
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <div class="space-y-2 sm:space-y-3 w-full max-w-full">
                @foreach($course->lectures->sortByDesc('scheduled_at') as $lecture)
                    @php
                        $statusText = [
                            'scheduled' => 'مجدولة',
                            'in_progress' => 'قيد التنفيذ',
                            'completed' => 'مكتملة',
                            'cancelled' => 'ملغاة',
                        ][$lecture->status] ?? 'غير محدد';
                        $statusBg = [
                            'scheduled' => 'bg-sky-50 text-sky-700 border-sky-100',
                            'in_progress' => 'bg-amber-50 text-amber-700 border-amber-100',
                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                            'cancelled' => 'bg-gray-100 text-gray-600 border-gray-200',
                        ][$lecture->status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                        $iconBg = [
                            'scheduled' => 'bg-[#1E4E8C]',
                            'in_progress' => 'bg-amber-500',
                            'completed' => 'bg-emerald-500',
                            'cancelled' => 'bg-gray-400',
                        ][$lecture->status] ?? 'bg-gray-400';
                    @endphp

                    <div class="w-full max-w-full bg-white rounded-xl p-4 sm:p-5 border border-gray-200 hover:shadow-md transition-all duration-200">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1 min-w-0 w-full">
                                <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-lg {{ $iconBg }} text-white flex items-center justify-center">
                                    @if($lecture->status === 'scheduled')
                                        <i class="fas fa-calendar text-sm"></i>
                                    @elseif($lecture->status === 'in_progress')
                                        <i class="fas fa-play-circle text-sm"></i>
                                    @elseif($lecture->status === 'completed')
                                        <i class="fas fa-check-circle text-sm"></i>
                                    @else
                                        <i class="fas fa-times-circle text-sm"></i>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 w-full">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h4 class="text-base sm:text-lg font-bold text-gray-900">{{ $lecture->title }}</h4>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold border {{ $statusBg }}">{{ $statusText }}</span>
                                    </div>
                                    @if($lecture->description)
                                        <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed">{{ $lecture->description }}</p>
                                    @endif
                                    <div class="flex flex-wrap items-center gap-2 mt-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                            <i class="fas fa-calendar-alt"></i> {{ $lecture->scheduled_at->format('Y/m/d') }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                            <i class="fas fa-clock"></i> {{ $lecture->scheduled_at->format('H:i') }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                            {{ $lecture->duration_minutes }} دقيقة
                                        </span>
                                        @if($lecture->instructor)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-user-tie"></i> {{ $lecture->instructor->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 flex-shrink-0 w-full sm:w-auto pt-2 sm:pt-0 border-t border-gray-100 sm:border-t-0">
                                @if($lecture->recording_url)
                                    <a href="{{ $lecture->recording_url }}" target="_blank" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                                        <i class="fas fa-play-circle"></i> التسجيل
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="w-full max-w-full text-center py-10 sm:py-12 rounded-xl bg-gray-50 border border-dashed border-gray-200">
            <div class="w-14 h-14 bg-[#E8F0FA] rounded-xl flex items-center justify-center mx-auto mb-4 text-[#1E4E8C]">
                <i class="fas fa-chalkboard-teacher text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">لا توجد محاضرات</h3>
            <p class="text-sm text-gray-500">لم يتم جدولة أي محاضرات لهذا الكورس بعد.</p>
        </div>
    @endif
</div>
