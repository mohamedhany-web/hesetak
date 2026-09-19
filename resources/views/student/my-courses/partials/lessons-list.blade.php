<div class="space-y-3 w-full max-w-full">
    @forelse($course->lessons->sortBy('order') as $index => $lesson)
        @php
            $lessonProgress = $lesson->progress->first();
            $isCompleted = $lessonProgress && $lessonProgress->is_completed;
            $isCurrentLesson = !$isCompleted && ($index == 0 || $course->lessons->take($index)->every(function($prevLesson) {
                return $prevLesson->progress->isNotEmpty() && $prevLesson->progress->first()->is_completed;
            }));
        @endphp

        <div class="w-full max-w-full bg-white rounded-xl p-4 sm:p-5 border {{ $isCurrentLesson ? 'border-[#93B4D8] ring-2 ring-sky-100' : ($isCompleted ? 'border-emerald-200' : 'border-gray-200') }} transition-all duration-200 hover:shadow-md">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3 sm:gap-4 flex-1 min-w-0 w-full">
                    <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center
                        {{ $isCompleted ? 'bg-emerald-500 text-white' : ($isCurrentLesson ? 'bg-[#1E4E8C] text-white' : 'bg-gray-300 text-gray-500') }}">
                        @if($isCompleted)
                            <i class="fas fa-check text-sm sm:text-base"></i>
                        @elseif($isCurrentLesson)
                            <i class="fas fa-play text-sm sm:text-base"></i>
                        @else
                            <i class="fas fa-lock text-sm sm:text-base"></i>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0 w-full">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900">{{ $lesson->title }}</h3>
                            @if($isCurrentLesson)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold bg-[#1E4E8C] text-white">
                                    <i class="fas fa-star"></i> الدرس الحالي
                                </span>
                            @endif
                        </div>
                        @if($lesson->description)
                            <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed">{{ $lesson->description }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-sky-50 text-sky-700">
                                <i class="fas fa-clock text-[#1E4E8C]"></i>
                                {{ $lesson->duration_minutes ?? 0 }} دقيقة
                            </span>
                            @if($lesson->video_url)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                    <i class="fas fa-video"></i> فيديو
                                </span>
                            @endif
                            @if($lesson->attachments && $lesson->attachments != '[]' && $lesson->attachments != 'null')
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                    <i class="fas fa-paperclip"></i> مرفقات
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto pt-2 sm:pt-0 border-t border-gray-100 sm:border-t-0">
                    @if($isCompleted)
                        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-emerald-500 text-white">
                            <i class="fas fa-check-circle"></i> مكتمل
                        </span>
                        <a href="{{ route('my-courses.lesson.watch', [$course, $lesson]) }}" class="inline-flex items-center justify-center gap-2 flex-1 sm:flex-initial px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                            <i class="fas fa-eye"></i> مراجعة
                        </a>
                    @elseif($isCurrentLesson)
                        <a href="{{ route('my-courses.lesson.watch', [$course, $lesson]) }}" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 rounded-lg text-sm font-semibold bg-[#1E4E8C] hover:bg-[#152A4A] text-white transition-colors">
                            <i class="fas fa-play"></i> ابدأ الدرس
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-gray-100 text-gray-500">
                            <i class="fas fa-lock"></i> مقفل
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="w-full max-w-full text-center py-10 sm:py-12 rounded-xl bg-gray-50 border border-dashed border-gray-200">
            <div class="w-14 h-14 bg-[#E8F0FA] rounded-xl flex items-center justify-center mx-auto mb-4 text-[#1E4E8C]">
                <i class="fas fa-book text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">لا توجد دروس</h3>
            <p class="text-sm text-gray-500">لم يتم إضافة دروس لهذا الكورس بعد.</p>
        </div>
    @endforelse
</div>
