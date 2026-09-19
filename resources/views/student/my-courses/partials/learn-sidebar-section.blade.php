@php
    $depth = $depth ?? 0;
    $sectionItemCount = $section->activeItems->filter(fn($ci) => $ci->item && !($ci->item instanceof \App\Models\CourseLesson))->count();
    $isSectionLocked = $section->is_locked ?? false;
@endphp
<div class="mb-4 {{ $depth > 0 ? 'pr-2 border-r-2 border-slate-100' : '' }} {{ $isSectionLocked ? 'opacity-80' : '' }}" style="{{ $depth > 0 ? 'margin-right: ' . ($depth * 0.5) . 'rem;' : '' }}">
    <div class="curriculum-section-header mb-2 {{ $isSectionLocked ? 'section-locked' : '' }}"
         :class="{ 'collapsed': isSectionCollapsed({{ $section->id }}) }"
         @click="toggleSection({{ $section->id }})"
         role="button"
         tabindex="0"
         @keydown.enter.prevent="toggleSection({{ $section->id }})"
         @keydown.space.prevent="toggleSection({{ $section->id }})">
        <span class="flex items-center gap-1.5">
            @if($isSectionLocked)
                <i class="fas fa-lock text-amber-500 text-[10px]" title="أكمل القسم السابق لفتح هذا القسم"></i>
            @else
                <i class="fas fa-folder text-[#2A6BB5]/90 text-[10px]"></i>
            @endif
            <span>{{ $section->title }}</span>
            @if($sectionItemCount > 0)
                <span class="text-gray-500 text-[10px]">({{ $sectionItemCount }})</span>
            @endif
        </span>
        <i class="fas fa-chevron-down curriculum-section-chevron"></i>
    </div>
    <div x-show="!isSectionCollapsed({{ $section->id }})" x-transition>
        @foreach($section->activeItems as $curriculumItem)
            @php
                $item = $curriculumItem->item;
                if (!$item) continue;
                if ($item instanceof \App\Models\CourseLesson) continue;

                $isCompleted = false;
                $isCurrent = false;
                $isLocked = $isSectionLocked;

                if ($item instanceof \App\Models\CourseLesson) {
                    $lessonProgress = $item->progress->first();
                    $isCompleted = $lessonProgress && $lessonProgress->is_completed;
                    $previousItems = $section->activeItems->where('order', '<', $curriculumItem->order);
                    $allPreviousCompleted = true;
                    foreach ($previousItems as $prevItem) {
                        if ($prevItem->item instanceof \App\Models\CourseLesson) {
                            $prevProgress = $prevItem->item->progress->first();
                            if (!$prevProgress || !$prevProgress->is_completed) {
                                $allPreviousCompleted = false;
                                break;
                            }
                        }
                    }
                    $isCurrent = !$isCompleted && ($curriculumItem->order == 1 || $allPreviousCompleted);
                    $isLocked = $isSectionLocked || (!$isCurrent && !$isCompleted);
                } elseif ($item instanceof \App\Models\Lecture) {
                    $watchProgress = $item->watchProgress->first();
                    $minPercent = $item->min_watch_percent_to_unlock_next;
                    $threshold = $minPercent !== null ? (int) $minPercent : 90;
                    $isCompleted = $watchProgress && (int) $watchProgress->progress_percent >= $threshold;
                    $prevLecturesInSection = $section->activeItems->where('order', '<', $curriculumItem->order)->filter(fn($i) => $i->item instanceof \App\Models\Lecture);
                    $lastPrevLecture = $prevLecturesInSection->sortByDesc('order')->first();
                    if ($lastPrevLecture) {
                        $prevLec = $lastPrevLecture->item;
                        $prevWp = $prevLec->watchProgress->first();
                        $prevMin = $prevLec->min_watch_percent_to_unlock_next;
                        $prevThreshold = $prevMin !== null ? (int) $prevMin : 90;
                        $isLocked = $isLocked || !$prevWp || (int) $prevWp->progress_percent < $prevThreshold;
                    }
                    $isCurrent = !$isCompleted && !$isLocked;
                } elseif ($item instanceof \App\Models\AdvancedExam || $item instanceof \App\Models\Exam) {
                    // الامتحان يُعتبر مكتمل إذا كانت أفضل محاولة مساوية أو أعلى من درجة النجاح
                    $passing = (float) ($item->passing_marks ?? 0);
                    $bestAttempt = $item->attempts->sortByDesc('submitted_at')->first();
                    $isCompleted = $bestAttempt && $bestAttempt->score !== null && (float) $bestAttempt->score >= $passing;

                    // فتح الامتحان يعتمد على إكمال العناصر السابقة في نفس القسم (فيديوهات / أنماط / واجبات)
                    $prevItems = $section->activeItems->where('order', '<', $curriculumItem->order);
                    foreach ($prevItems as $prevItem) {
                        $prev = $prevItem->item;
                        if (!$prev) continue;

                        if ($prev instanceof \App\Models\Lecture) {
                            $wp = $prev->watchProgress->first();
                            $minPercent = $prev->min_watch_percent_to_unlock_next;
                            $threshold = $minPercent !== null ? (int) $minPercent : 90;
                            if (!$wp || (int) $wp->progress_percent < $threshold) {
                                $isLocked = true;
                                break;
                            }
                        } elseif ($prev instanceof \App\Models\CourseLesson) {
                            $lp = $prev->progress->first();
                            if (!$lp || !$lp->is_completed) {
                                $isLocked = true;
                                break;
                            }
                        } elseif ($prev instanceof \App\Models\Assignment) {
                            if ($prev->submissions->where('student_id', auth()->id())->isEmpty()) {
                                $isLocked = true;
                                break;
                            }
                        }
                    }

                    $isCurrent = !$isCompleted && !$isLocked && !$isSectionLocked;
                }
            @endphp

            <div class="curriculum-item {{ $isCompleted ? 'completed' : '' }} {{ $isCurrent ? 'active' : '' }} {{ $isLocked ? 'locked' : '' }}"
                 data-section-id="{{ $section->id }}"
                 @if($item instanceof \App\Models\CourseLesson)
                     @click="currentSectionDescription = (window.learnSectionDescriptions || {})[$event.currentTarget.dataset.sectionId] || ''; if ({{ $isLocked ? 'true' : 'false' }}) return; selectedLesson = {{ $item->id }}; loadLesson({{ $item->id }})"
                 @elseif($item instanceof \App\Models\Lecture)
                     @click="currentSectionDescription = (window.learnSectionDescriptions || {})[$event.currentTarget.dataset.sectionId] || ''; if ({{ $isLocked ? 'true' : 'false' }}) return; loadLecture({{ $item->id }})"
                 @elseif($item instanceof \App\Models\Assignment)
                     @click="currentSectionDescription = (window.learnSectionDescriptions || {})[$event.currentTarget.dataset.sectionId] || ''; loadAssignment({{ $item->id }})"
                 @elseif($item instanceof \App\Models\AdvancedExam || $item instanceof \App\Models\Exam)
                     @click="currentSectionDescription = (window.learnSectionDescriptions || {})[$event.currentTarget.dataset.sectionId] || ''; loadExam({{ $item->id }})"
                 @endif
                 x-show="!searchQuery || '{{ strtolower($item->title) }}'.includes(searchQuery.toLowerCase())">
                <div class="flex items-start gap-2">
                    <div class="flex-shrink-0 mt-0.5">
                        @if($item instanceof \App\Models\CourseLesson)
                            @if($isCompleted)
                                <div class="w-6 h-6 bg-green-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-check text-white text-[10px]"></i>
                                </div>
                            @elseif($isCurrent)
                                <div class="w-6 h-6 bg-[#1E4E8C] rounded-md flex items-center justify-center animate-pulse">
                                    <i class="fas fa-play text-white text-[10px]"></i>
                                </div>
                            @else
                                <div class="w-6 h-6 bg-gray-600 rounded-md flex items-center justify-center">
                                    <i class="fas fa-lock text-white text-[10px]"></i>
                                </div>
                            @endif
                        @elseif($item instanceof \App\Models\Lecture)
                            @if($isCompleted)
                                <div class="w-6 h-6 bg-green-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-check text-white text-[10px]"></i>
                                </div>
                            @elseif($isLocked)
                                <div class="w-6 h-6 bg-gray-600 rounded-md flex items-center justify-center">
                                    <i class="fas fa-lock text-white text-[10px]"></i>
                                </div>
                            @else
                                <div class="w-6 h-6 bg-[#1E4E8C] rounded-md flex items-center justify-center">
                                    <i class="fas fa-chalkboard-teacher text-white text-[10px]"></i>
                                </div>
                            @endif
                        @elseif($item instanceof \App\Models\Assignment)
                            <div class="w-6 h-6 bg-purple-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-tasks text-white text-[10px]"></i>
                            </div>
                        @elseif($item instanceof \App\Models\AdvancedExam || $item instanceof \App\Models\Exam)
                            @if($isCompleted)
                                <div class="w-6 h-6 bg-green-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-check text-white text-[10px]"></i>
                                </div>
                            @elseif($isCurrent)
                                <div class="w-6 h-6 bg-indigo-500 rounded-md flex items-center justify-center animate-pulse">
                                    <i class="fas fa-clipboard-check text-white text-[10px]"></i>
                                </div>
                            @else
                                <div class="w-6 h-6 bg-gray-600 rounded-md flex items-center justify-center">
                                    <i class="fas fa-lock text-white text-[10px]"></i>
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="curriculum-item-title">{{ $item->title }}</div>
                        <div class="curriculum-item-meta">
                            @if($item instanceof \App\Models\CourseLesson)
                                <span><i class="fas fa-video ml-1"></i> درس</span>
                                @if($item->duration_minutes)
                                    <span><i class="fas fa-clock ml-1"></i> {{ $item->duration_minutes }} دقيقة</span>
                                @endif
                            @elseif($item instanceof \App\Models\Lecture)
                                <span><i class="fas fa-chalkboard-teacher ml-1"></i> محاضرة</span>
                                @if($item->scheduled_at)
                                    <span><i class="fas fa-calendar ml-1"></i> {{ $item->scheduled_at->format('Y/m/d') }}</span>
                                @endif
                            @elseif($item instanceof \App\Models\Assignment)
                                <span><i class="fas fa-tasks ml-1"></i> واجب</span>
                                @if($item->due_date)
                                    <span><i class="fas fa-calendar ml-1"></i> {{ $item->due_date->format('Y/m/d') }}</span>
                                @endif
                            @elseif($item instanceof \App\Models\AdvancedExam || $item instanceof \App\Models\Exam)
                                <span><i class="fas fa-clipboard-check ml-1"></i> امتحان</span>
                                @if(isset($item->start_date) && $item->start_date)
                                    <span><i class="fas fa-calendar ml-1"></i> {{ $item->start_date->format('Y/m/d') }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if($section->children && $section->children->count() > 0)
            @foreach($section->children as $child)
                @include('student.my-courses.partials.learn-sidebar-section', ['section' => $child, 'depth' => $depth + 1])
            @endforeach
        @endif
    </div>
</div>
