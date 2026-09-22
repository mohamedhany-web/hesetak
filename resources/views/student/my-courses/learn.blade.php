@extends('layouts.student-timeline')

@section('title', $course->title . ' - ' . __('student.learn'))
@section('header', '')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@php
    // تحضير بيانات المحاضرات للـ JavaScript (مع المواد الظاهرة للطالب + تقدم المشاهدة + نسبة فتح التالي)
    $currentUser = auth()->user();
    $lecturesData = $course->lectures->map(function($lecture) use ($course, $currentUser) {
        $lecture->refresh();
        $recordingUrl = \DB::table('lectures')->where('id', $lecture->id)->value('recording_url');
        $videoPlatform = \DB::table('lectures')->where('id', $lecture->id)->value('video_platform');
        $recordingFilePath = \Illuminate\Support\Facades\Schema::hasColumn('lectures', 'recording_file_path')
            ? \DB::table('lectures')->where('id', $lecture->id)->value('recording_file_path')
            : ($lecture->recording_file_path ?? null);
        $recordingUrlFinal = $recordingUrl ? trim($recordingUrl) : ($lecture->recording_url ? trim($lecture->recording_url) : null);
        $videoPlatformFinal = $videoPlatform ? trim(strtolower($videoPlatform)) : ($lecture->video_platform ? trim(strtolower($lecture->video_platform)) : null);
        $recordingFileUrl = $recordingFilePath
            ? route('my-courses.lectures.recording-stream', [$course->id, $lecture->id])
            : null;
        if ((! $recordingUrlFinal) && $recordingFileUrl) {
            $recordingUrlFinal = $recordingFileUrl;
            $videoPlatformFinal = 'direct';
        }
        $materials = $lecture->materials()->where('is_visible_to_student', true)->orderBy('sort_order')->get()->map(function($m) use ($course, $lecture) {
            return [
                'id' => $m->id,
                'title' => $m->title ?: $m->file_name,
                'file_name' => $m->file_name,
                'download_url' => route('my-courses.lectures.material.download', [$course->id, $lecture->id, $m->id]),
            ];
        })->values()->all();
        $videoQuestions = $lecture->videoQuestions()->orderBy('timestamp_seconds')->get()->filter(function($vq) use ($currentUser) {
            $showCount = $vq->show_count;
            if ($showCount === null || $showCount == 0) return true;
            $answered = \App\Models\LectureVideoQuestionAnswer::where('lecture_video_question_id', $vq->id)->where('user_id', $currentUser->id)->count();
            return $answered < $showCount;
        })->map(function($vq) {
            $payload = $vq->getPayloadForStudent();
            $showEveryTime = $vq->show_count === null || $vq->show_count == 0;
            return [
                'id' => $vq->id,
                'timestamp_seconds' => $vq->timestamp_seconds,
                'text' => $payload['text'] ?? '',
                'options' => $payload['options'] ?? [],
                'type' => $payload['type'] ?? 'multiple_choice',
                'points' => $vq->points,
                'on_wrong' => $vq->on_wrong,
                'rewind_seconds' => $vq->rewind_seconds,
                'show_every_time' => $showEveryTime,
            ];
        })->values()->all();
        $watchProgress = \App\Models\LectureWatchProgress::where('lecture_id', $lecture->id)->where('user_id', $currentUser->id)->first();
        $progressData = $watchProgress ? [
            'progress_percent' => (int) $watchProgress->progress_percent,
            'is_completed' => (bool) $watchProgress->is_completed,
            'watch_time_seconds' => (int) $watchProgress->watch_time_seconds,
            'video_duration_seconds' => (int) $watchProgress->video_duration_seconds,
        ] : null;
        return [
            'id' => $lecture->id,
            'title' => $lecture->title,
            'description' => $lecture->description,
            'scheduled_at' => $lecture->scheduled_at ? $lecture->scheduled_at->toIso8601String() : null,
            'scheduled_at_formatted' => $lecture->scheduled_at ? $lecture->scheduled_at->format('Y/m/d H:i') : null,
            'duration_minutes' => $lecture->duration_minutes ?? 60,
            'min_watch_percent_to_unlock_next' => $lecture->min_watch_percent_to_unlock_next,
            'recording_url' => $recordingUrlFinal,
            'recording_file_url' => $recordingFileUrl,
            'video_platform' => $videoPlatformFinal,
            'notes' => $lecture->notes ?? null,
            'materials' => $materials,
            'video_questions' => $videoQuestions,
            'progress' => $progressData,
        ];
    })->keyBy('id');
    
    $lecturesDataJson = json_encode($lecturesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
@endphp

@section('content')
<script type="application/json" id="learn-lectures-data">{!! $lecturesDataJson !!}</script>
<script type="application/json" id="learn-next-item-map">{!! json_encode($nextItemByLectureId ?? []) !!}</script>
@php
    $locale = app()->getLocale();
    $progressPct = min(100, (float) ($progress ?? 0));
    $doneCount = (int) ($completedLessons ?? 0);
    $totalCount = (int) ($totalLessons ?? 0);
@endphp
<div class="learn-page st-course-learn"
     data-course-id="{{ $course->id }}"
     data-course-progress="{{ $progressPct }}"
     data-total-items="{{ $totalCount }}"
     data-completed-items="{{ $doneCount }}"
     data-lectures-url="{{ route('my-courses.lectures.show', [$course, '_LID_']) }}"
     :data-font-size="fontSize"
     x-data="courseFocusMode()"
     @keydown.escape.window="if (focusMode) { focusMode = false } else { window.location.href='{{ route('my-courses.show', $course) }}' }"
     @keydown.ctrl.f.window.prevent="document.querySelector('.st-course-learn__search input')?.focus()"
     @keydown.ctrl.p.window.prevent="printCurriculum()"
     x-init="
         const descEl = document.getElementById('learn-section-descriptions');
         if (descEl) try { window.learnSectionDescriptions = JSON.parse(descEl.textContent); } catch(e) { window.learnSectionDescriptions = {}; }
         else window.learnSectionDescriptions = {};
         window._learnComp = this;
         window.addEventListener('learn-lecture-progress', (e) => {
             if (e.detail && typeof e.detail.progress_percent === 'number') _learnComp.lectureProgressPercent = e.detail.progress_percent;
         });
         window.addEventListener('learn-open-next-item', (e) => {
             var d = e.detail || {};
             if (d.type === 'lecture' && d.id) _learnComp.loadLecture(d.id);
             else if (d.type === 'lesson' && d.id) _learnComp.loadLesson(d.id);
             else if (d.type === 'exam' && d.id) _learnComp.loadExam(d.id);
             else if (d.type === 'assignment' && d.id) _learnComp.loadAssignment(d.id);
         });
     ">

    <div x-show="!focusMode">
        @include('partials.student-timeline-top', [
            'locale' => $locale,
            'pageTitle' => __('student_timeline.courses_learn'),
            'crumbs' => [
                ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
                ['label' => __('student.my_courses'), 'url' => route('my-courses.index')],
                ['label' => $course->title, 'url' => route('my-courses.show', $course)],
                ['label' => __('student_timeline.courses_learn'), 'url' => null],
            ],
        ])
    </div>

    <section x-show="!focusMode" class="st-join-hero st-course-learn__hero" aria-label="{{ $course->title }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student_timeline.courses_learn') }}</p>
            <h2 class="st-join-hero__title">{{ $course->title }}</h2>
            <p class="st-join-hero__meta">
                {{ __('student_timeline.courses_learn_progress', ['done' => $doneCount, 'total' => $totalCount]) }}
                · {{ number_format($progressPct, 0) }}%
            </p>
            <div class="st-course-learn__progress" aria-hidden="true">
                <span class="learn-progress-fill" style="width: {{ $progressPct }}%"></span>
            </div>
        </div>
        <div class="st-join-hero__actions">
            <button type="button" @click="toggleFocusMode()" class="st-pill st-pill--solid">
                <i class="fas fa-expand-arrows-alt" aria-hidden="true"></i>
                {{ __('student_timeline.courses_learn_focus') }}
            </button>
            <button type="button" @click="toggleFullscreen()" class="st-pill st-pill--outline">
                <i class="fas" :class="isFullscreen ? 'fa-compress' : 'fa-expand'" aria-hidden="true"></i>
                {{ __('student_timeline.courses_learn_fullscreen') }}
            </button>
            <a href="{{ route('my-courses.show', $course) }}" class="st-pill st-pill--outline">{{ __('student_timeline.courses_back') }}</a>
        </div>
    </section>

    <div x-show="focusMode" x-cloak class="st-course-learn__focusbar">
        <button type="button" @click="focusMode = false" class="st-pill st-pill--outline">
            <i class="fas fa-compress-arrows-alt" aria-hidden="true"></i>
            {{ __('student_timeline.courses_learn_exit_focus') }}
        </button>
        <div class="st-course-learn__focusbar-meta">
            <div class="st-course-learn__focusbar-track" aria-hidden="true">
                <span class="learn-progress-fill" style="width: {{ $progressPct }}%"></span>
            </div>
            <span class="learn-progress-count">{{ $doneCount }}/{{ $totalCount }}</span>
            <span class="learn-progress-pct">{{ number_format($progressPct, 0) }}%</span>
        </div>
    </div>

    <div class="st-course-learn__grid">
        <aside class="st-course-learn__aside learn-focus-sidebar" aria-label="{{ __('student_timeline.courses_learn_curriculum') }}">
            <div class="st-course-learn__aside-head">
                <h3 class="st-course-learn__aside-title">
                    <i class="fas fa-list" aria-hidden="true"></i>
                    {{ __('student_timeline.courses_learn_curriculum') }}
                </h3>
                <div class="st-course-learn__aside-progress">
                    <div class="st-course-learn__aside-progress-track" aria-hidden="true">
                        <span style="width: {{ $progressPct }}%"></span>
                    </div>
                    <strong class="learn-progress-count">{{ $doneCount }}/{{ $totalCount }}</strong>
                    <em class="learn-progress-pct">{{ number_format($progressPct, 0) }}%</em>
                </div>
                <div class="st-course-learn__search search-box">
                    <input type="search"
                           x-model="searchQuery"
                           placeholder="{{ __('student_timeline.courses_learn_search') }}"
                           @keydown.escape="searchQuery = ''">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </div>
            </div>

            <div class="st-course-learn__curriculum focus-sidebar-content">
                @if(isset($sidebarExams) && $sidebarExams->count() > 0)
                    <div class="mb-3">
                        <div class="curriculum-section-header"
                             :class="{ 'collapsed': isSectionCollapsed('sidebar-exams') }"
                             @click="toggleSection('sidebar-exams')"
                             role="button"
                             tabindex="0"
                             @keydown.enter.prevent="toggleSection('sidebar-exams')"
                             @keydown.space.prevent="toggleSection('sidebar-exams')">
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-clipboard-check text-[#2A6BB5]/90 text-[10px]"></i>
                                <span>{{ __('student_timeline.courses_learn_exams') }}</span>
                                <span class="text-gray-500 text-[10px]">({{ $sidebarExams->count() }})</span>
                            </span>
                            <i class="fas fa-chevron-down curriculum-section-chevron"></i>
                        </div>
                        <div x-show="!isSectionCollapsed('sidebar-exams')" x-cloak x-transition>
                            @foreach($sidebarExams as $exam)
                                <div class="curriculum-item"
                                     @click="loadExam({{ $exam->id }})"
                                     x-show="!searchQuery || '{{ strtolower(addslashes($exam->title)) }}'.includes(searchQuery.toLowerCase())">
                                    <div class="flex items-start gap-2">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div class="w-6 h-6 bg-indigo-500 rounded-md flex items-center justify-center">
                                                <i class="fas fa-clipboard-check text-white text-[10px]"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="curriculum-item-title">{{ $exam->title }}</div>
                                            <div class="curriculum-item-meta">
                                                <span><i class="fas fa-clock text-[10px] ml-0.5"></i> {{ $exam->duration_minutes }} د</span>
                                                <span><i class="fas fa-star text-[10px] ml-0.5"></i> {{ $exam->total_marks }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(isset($sections) && $sections->count() > 0)
                    <script type="application/json" id="learn-section-descriptions">@json($sectionDescriptions ?? [])</script>
                    @foreach($sections as $section)
                        @include('student.my-courses.partials.learn-sidebar-section', ['section' => $section, 'depth' => 0])
                    @endforeach
                @else
                    <div class="st-course-learn__empty" style="min-height:180px;padding:28px 12px">
                        <p>{{ __('student_timeline.courses_learn_empty_curriculum') }}</p>
                        <p>{{ __('student_timeline.courses_learn_empty_curriculum_hint') }}</p>
                    </div>
                @endif
            </div>
        </aside>

        <section class="st-course-learn__stage learn-focus-content" aria-label="{{ __('student_timeline.courses_learn') }}">
            <div class="st-course-learn__stage-body focus-main-content-wrapper">
                <div x-show="!selectedLesson && !selectedLecture" class="st-course-learn__empty empty-content-state">
                    <div class="st-course-learn__empty-ico" aria-hidden="true">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3>{{ __('student_timeline.courses_learn_welcome', ['title' => $course->title]) }}</h3>
                    <p>{{ __('student_timeline.courses_learn_pick') }}</p>
                    <div class="st-course-learn__empty-meta">
                        <span>{{ __('student_timeline.courses_learn_progress', ['done' => $doneCount, 'total' => $totalCount]) }}</span>
                        <span>{{ number_format($progressPct, 0) }}%</span>
                    </div>
                </div>

                <div x-show="currentSectionDescription" x-cloak x-transition class="st-course-learn__section-note">
                    <p class="whitespace-pre-wrap" x-text="currentSectionDescription"></p>
                </div>

                <div x-show="selectedLesson && !selectedLecture && !showVideoPlayer" x-cloak x-transition class="lesson-content-viewer">
                    <div x-html="lessonContent"></div>
                </div>

                <div x-show="(selectedLesson && showVideoPlayer) || (selectedLecture && showVideoPlayer)"
                     x-cloak
                     x-transition
                     class="st-course-learn__player lesson-video-viewer">
                    <div x-show="selectedLesson && !selectedLecture" x-cloak class="lesson-details-bar">
                        <span class="lesson-meta">التقدم: <span x-text="videoProgressPercent || 0">0</span>%</span>
                        <span class="lesson-meta">الوقت: <span x-text="videoTimeCurrent || '0:00'">0:00</span> / <span x-text="currentLessonDuration ? (currentLessonDuration + ' د') : (videoTimeTotal || '0:00')">0:00</span></span>
                        <img x-show="currentLessonThumbnail" :src="currentLessonThumbnail" alt="" class="lesson-thumb" />
                        <span class="lesson-title-text truncate" x-text="currentLessonTitle || 'الدرس'">الدرس</span>
                        <button type="button"
                                @click="markLessonComplete()"
                                :disabled="currentLessonCompleted"
                                :class="currentLessonCompleted ? 'btn-lesson-complete completed' : 'btn-lesson-complete'">
                            <i class="fas fa-check text-white"></i>
                            <span x-text="currentLessonCompleted ? 'تم إكمال الدرس بنجاح!' : 'تم إكمال الدرس بنجاح!'">تم إكمال الدرس بنجاح!</span>
                        </button>
                        <button type="button" class="btn-share" title="مشاركة"><i class="fas fa-share-alt"></i> مشاركة</button>
                    </div>

                    <div class="st-course-learn__watchbar" id="learn-watch-percent-bar">
                        <div class="st-course-learn__watchbar-row">
                            <span>{{ __('student_timeline.courses_learn_watch_pct') }}</span>
                            <template x-if="selectedLecture">
                                <span id="lecture-watch-pct-text">0.0%</span>
                            </template>
                            <span x-show="selectedLesson && showVideoPlayer" x-cloak x-text="(Math.round((videoProgressPercent || 0) * 10) / 10).toFixed(1) + '%'">0.0%</span>
                        </div>
                        <div class="st-course-learn__watchbar-track">
                            <template x-if="selectedLecture">
                                <span id="lecture-watch-pct-fill" style="width: 0%;"></span>
                            </template>
                            <span x-show="selectedLesson && showVideoPlayer" x-cloak :style="'width: ' + Math.min(100, Math.max(0, videoProgressPercent || 0)) + '%'"></span>
                        </div>
                    </div>

                    <div class="st-course-learn__frame" x-show="(selectedLesson && showVideoPlayer) || (selectedLecture && showVideoPlayer)" x-cloak>
                        <div x-show="selectedLecture && showVideoPlayer" x-cloak id="learn-video-embed"></div>
                        <div x-show="selectedLesson && showVideoPlayer" x-cloak>
                            @include('student.my-courses.partials.video-player')
                        </div>
                    </div>
                </div>

                <div x-show="selectedLecture && !showVideoPlayer" x-cloak x-transition class="lesson-content-viewer">
                    <div x-html="lectureContent"></div>
                </div>

                <div x-show="selectedLecture && lectureMaterials && lectureMaterials.length" x-cloak x-transition class="st-course-learn__materials">
                    <div class="st-course-learn__materials-head">
                        <h3>
                            <span><i class="fas fa-paperclip" aria-hidden="true"></i></span>
                            {{ __('student_timeline.courses_learn_materials') }}
                            <span class="st-course-learn__materials-count" x-text="lectureMaterials.length"></span>
                        </h3>
                    </div>
                    <div class="st-course-learn__materials-grid">
                        <template x-for="mat in lectureMaterials" :key="mat.id">
                            <a :href="mat.download_url" target="_blank" rel="noopener" class="st-course-learn__mat">
                                <span class="st-course-learn__mat-ico"><i class="fas" :class="getMaterialIconClass(mat)"></i></span>
                                <span class="st-course-learn__mat-copy">
                                    <strong x-text="mat.title"></strong>
                                    <small x-text="mat.file_name"></small>
                                </span>
                                <span class="st-course-learn__mat-dl"><i class="fas fa-download"></i></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection


@push('scripts')
<script>
function courseFocusMode() {
    // قراءة بيانات المحاضرات من عنصر script (أدق من data attribute مع روابط طويلة)
    let lecturesData = {};
    const scriptEl = document.getElementById('learn-lectures-data');
    if (scriptEl && scriptEl.textContent) {
        try {
            lecturesData = JSON.parse(scriptEl.textContent);
        } catch (e) {
            console.error('Error parsing lectures data:', e);
        }
    }
    
    return {
        searchQuery: '',
        showLessons: true,
        showLectures: true,
        fontSize: 'medium',
        focusMode: false,
        collapsedSections: [],
        currentSectionDescription: '',
        sidebarOpen: false,
        sidebarClosed: false,
        selectedLesson: null,
        selectedLecture: null,
        lessonContent: '',
        lectureContent: '',
        lectureMaterials: [],
        lecturesData: lecturesData,
        progressInterval: null,
        isFullscreen: false,
        showVideoPlayer: false,
        currentLessonVideoUrl: null,
        currentLessonId: null,
        currentLessonTitle: '',
        currentLessonThumbnail: '',
        currentLessonDuration: null,
        currentLessonCompleted: false,
        videoProgressPercent: 0,
        lectureProgressPercent: 0,
        videoTimeCurrent: '0:00',
        videoTimeTotal: '0:00',
        lastVideoProgressPercent: 0,
        lastVideoWatchTimeSec: 0,
        lastVideoDurationSec: 0,
        watchedSeconds: 0,
        lastReportedTime: null,
        SEEK_THRESHOLD: 2.5,
        async loadLesson(lessonId) {
            this.selectedLesson = lessonId;
            this.selectedLecture = null;
            this.showVideoPlayer = false;
            this.currentLessonVideoUrl = null;
            this.currentLessonId = lessonId;
            this.lessonContent = '<div class="text-center p-8"><i class="fas fa-spinner fa-spin text-4xl text-[#1E4E8C] mb-4"></i><p class="text-gray-600">جاري تحميل الدرس...</p></div>';
            
            try {
                // جلب بيانات الدرس من API
                const response = await fetch(`/api/lessons/${lessonId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.error || 'فشل تحميل الدرس');
                }
                
                const lesson = await response.json();
                this.currentLessonTitle = lesson.title || '';
                this.currentLessonDuration = lesson.duration_minutes || null;
                this.currentLessonThumbnail = this.getYoutubeThumb(lesson.video_url) || '';
                this.currentLessonCompleted = !!(lesson.progress && lesson.progress.is_completed);
                this.watchedSeconds = (lesson.progress && lesson.progress.watch_time != null) ? lesson.progress.watch_time : 0;
                this.lastReportedTime = null;
                const pct = (lesson.progress && lesson.progress.progress_percent != null) ? lesson.progress.progress_percent : 0;
                const watchSec = this.watchedSeconds;
                const durSec = (lesson.duration_minutes && lesson.duration_minutes > 0) ? lesson.duration_minutes * 60 : 0;
                this.reportVideoProgress(pct, watchSec, durSec);
                
                // إذا كان هناك فيديو، اعرض جزء المشاهدة
                if (lesson.video_url) {
                    // التحقق من نوع الفيديو
                    const isExternalVideo = this.isExternalVideo(lesson.video_url);
                    
                    // عرض جزء المشاهدة للفيديو
                    this.showVideoPlayer = true;
                    this.currentLessonVideoUrl = lesson.video_url;
                    
                    let platform = null;
                    if (lesson.video_url.includes('youtube.com') || lesson.video_url.includes('youtu.be')) platform = 'youtube';
                    else if (lesson.video_url.includes('vimeo.com')) platform = 'vimeo';
                    else if (lesson.video_url.includes('drive.google.com')) platform = 'google_drive';
                    else if (lesson.video_url.match(/\.(mp4|webm|ogg|avi|mov)(\?.*)?$/i)) platform = 'direct';
                    [100, 250, 500].forEach(delay => {
                        setTimeout(() => {
                            const videoContainer = document.querySelector('#video-container');
                            if (videoContainer && videoContainer.__x) {
                                const v = videoContainer.__x.$data;
                                if (v && v.loadVideo && (v.currentLessonVideoUrl !== lesson.video_url || !v.currentSourceType)) {
                                    v.currentLessonVideoUrl = lesson.video_url;
                                    v.loadVideo(lesson.video_url, platform);
                                }
                            }
                        }, delay);
                    });
                    
                    // تحديث تقدم المشاهدة
                    this.trackLessonProgress(lessonId);
                    return;
                }
                
                // بناء محتوى HTML للدرس (بدون فيديو)
                let html = '<div class="lesson-viewer space-y-6 w-full">';
                
                // العنوان والوصف
                html += '<div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200 w-full">';
                html += '<h2 class="text-3xl font-black text-gray-900 mb-4">' + this.escapeHtml(lesson.title) + '</h2>';
                if (lesson.description) {
                    html += '<p class="text-gray-700 leading-relaxed mb-4">' + this.escapeHtml(lesson.description) + '</p>';
                }
                html += '<div class="grid grid-cols-2 gap-4 text-sm">';
                if (lesson.duration_minutes) {
                    html += '<div class="flex items-center gap-2 text-gray-600"><i class="fas fa-clock text-[#1E4E8C]"></i><span class="font-semibold">المدة:</span> ' + lesson.duration_minutes + ' دقيقة</div>';
                }
                html += '<div class="flex items-center gap-2 text-gray-600"><i class="fas fa-' + (lesson.type === 'video' ? 'video' : lesson.type === 'quiz' ? 'question-circle' : 'file-alt') + ' text-[#1E4E8C]"></i><span class="font-semibold">النوع:</span> ' + (lesson.type === 'video' ? 'فيديو' : lesson.type === 'quiz' ? 'كويز' : 'مستند') + '</div>';
                html += '</div></div>';
                
                // المحتوى النصي
                if (lesson.content) {
                    html += '<div class="bg-white border-2 border-gray-200 rounded-xl p-6 w-full">';
                    html += '<div class="prose max-w-none text-gray-700 leading-relaxed">' + lesson.content + '</div>';
                    html += '</div>';
                }
                
                // المرفقات
                if (lesson.attachments && Array.isArray(lesson.attachments) && lesson.attachments.length > 0) {
                    html += '<div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-6 w-full">';
                    html += '<h3 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-paperclip text-[#1E4E8C]"></i><span>المرفقات</span></h3>';
                    html += '<div class="space-y-2">';
                    lesson.attachments.forEach(attachment => {
                        const fileName = attachment.name || attachment.url || 'مرفق';
                        const fileUrl = attachment.url || attachment;
                        html += '<a href="' + this.escapeHtml(fileUrl) + '" target="_blank" class="block bg-white border-2 border-gray-300 rounded-lg p-4 hover:bg-gray-50 transition-all hover:shadow-lg w-full"><div class="flex items-center justify-between"><div class="flex items-center gap-3"><i class="fas fa-file text-[#1E4E8C] text-xl"></i><div><div class="font-bold text-gray-900">' + this.escapeHtml(fileName) + '</div></div></div><i class="fas fa-external-link-alt text-gray-400"></i></div></a>';
                    });
                    html += '</div></div>';
                }
                
                html += '</div>';
                this.lessonContent = html;
                
                // تحديث تقدم المشاهدة (حتى بدون فيديو)
                this.trackLessonProgress(lessonId);
                
            } catch (error) {
                console.error('Error loading lesson:', error);
                this.lessonContent = '<div class="text-center text-red-600 p-8"><i class="fas fa-exclamation-circle text-4xl mb-4"></i><p class="text-xl font-bold">حدث خطأ أثناء تحميل الدرس</p><p class="text-sm text-gray-600 mt-2">' + this.escapeHtml(error.message) + '</p></div>';
            }
        },
        reportVideoProgress(percent, currentSec, durationSec) {
            this.videoProgressPercent = percent;
            this.lastVideoProgressPercent = percent;
            this.lastVideoWatchTimeSec = currentSec;
            this.lastVideoDurationSec = durationSec;
            this.videoTimeCurrent = this.formatVideoTime(currentSec);
            this.videoTimeTotal = durationSec > 0 ? this.formatVideoTime(durationSec) : (this.currentLessonDuration ? this.currentLessonDuration + ' د' : '0:00');
        },
        reportVideoProgressFromPlayer(currentSec, durationSec, isPlaying) {
            const t = Number(currentSec) || 0;
            const dur = Number(durationSec) || 0;
            const playing = !!isPlaying;
            this.videoTimeCurrent = this.formatVideoTime(t);
            if (dur > 0) this.videoTimeTotal = this.formatVideoTime(dur);
            if (!Number.isFinite(dur) || dur <= 0) {
                this.lastReportedTime = t;
                return;
            }
            if (this.lastReportedTime === null) {
                this.lastReportedTime = t;
            } else if (playing) {
                const delta = t - this.lastReportedTime;
                if (delta >= 0 && delta <= this.SEEK_THRESHOLD) {
                    this.watchedSeconds = Math.min(dur, this.watchedSeconds + delta);
                }
                this.lastReportedTime = t;
            } else {
                this.lastReportedTime = t;
            }
            const pct = Math.min(100, (this.watchedSeconds / dur) * 100);
            this.lastVideoProgressPercent = pct;
            this.lastVideoWatchTimeSec = this.watchedSeconds;
            this.lastVideoDurationSec = dur;
            this.videoProgressPercent = pct;
        },
        formatVideoTime(seconds) {
            const s = Math.floor(Number(seconds) || 0);
            const m = Math.floor(s / 60);
            const h = Math.floor(m / 60);
            if (h > 0) return h + ':' + String(m % 60).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
            return m + ':' + String(s % 60).padStart(2, '0');
        },
        trackLessonProgress(lessonId) {
            if (this.progressInterval) clearInterval(this.progressInterval);
            this.progressInterval = setInterval(async () => {
                const pct = this.lastVideoProgressPercent || 0;
                const watchTime = this.lastVideoWatchTimeSec || 0;
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const res = await fetch(`{{ route('my-courses.lesson.progress', [$course, ':lessonId']) }}`.replace(':lessonId', lessonId), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({
                            watch_time: watchTime,
                            completed: pct >= 90,
                            progress_percent: pct
                        })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success && data.course_progress != null) {
                            const wrapper = document.querySelector('.learn-page');
                            if (wrapper) wrapper.dataset.courseProgress = data.course_progress;
                            if (data.total_items != null) wrapper.dataset.totalItems = data.total_items;
                            if (data.completed_items != null) wrapper.dataset.completedItems = data.completed_items;
                            if (pct >= 90) this.currentLessonCompleted = true;
                            updateProgressBar();
                        }
                    }
                } catch (e) { console.error('Error tracking progress:', e); }
            }, 15000);
        },
        trackLectureProgress(lectureId) {
            // تتبع تقدم المحاضرة (يمكن ربطه لاحقاً بـ API إن وُجد)
            if (this.progressInterval) clearInterval(this.progressInterval);
            this.progressInterval = null;
        },
        async loadLecture(lectureId) {
            this.selectedLecture = lectureId;
            this.selectedLesson = null;
            this.showVideoPlayer = false;
            this.currentLessonVideoUrl = null;
            this.lectureMaterials = [];
            
            const lectures = this.lecturesData || {};
            const lectureIdStr = String(lectureId);
            const lectureIdNum = parseInt(lectureId);
            let lecture = lectures[lectureIdStr] || lectures[lectureIdNum] || lectures[lectureId];
            
            if (!lecture) {
                Object.keys(lectures).forEach(key => {
                    const l = lectures[key];
                    if (l && (l.id == lectureId || String(l.id) === String(lectureId))) lecture = l;
                });
            }
            
            // إذا لم توجد المحاضرة محلياً أو لا يوجد فيها رابط فيديو، جلبها من الخادم
            const courseId = this.$el.closest('[data-course-id]')?.dataset?.courseId;
            const lecturesUrlTemplate = this.$el.closest('[data-lectures-url]')?.dataset?.lecturesUrl;
            if ((!lecture || !(lecture.recording_url && lecture.recording_url.trim())) && courseId && lecturesUrlTemplate) {
                try {
                    const url = lecturesUrlTemplate.replace('_LID_', lectureId);
                    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (res.ok) {
                        const fromApi = await res.json();
                        if (fromApi && fromApi.id) {
                            lecture = fromApi;
                            if (!this.lecturesData) this.lecturesData = {};
                            this.lecturesData[lectureIdStr] = fromApi;
                            this.lecturesData[lectureIdNum] = fromApi;
                        }
                    }
                } catch (e) { console.warn('Fetch lecture data failed:', e); }
            }
            
            if (!lecture) {
                this.lectureContent = '<div class="text-center text-red-600 p-8"><i class="fas fa-exclamation-circle text-4xl mb-4"></i><p class="text-xl font-bold">المحاضرة غير موجودة</p><p class="text-sm mt-2">ID: ' + lectureId + '</p></div>';
                return;
            }

            this.lectureMaterials = lecture.materials || [];
            this.lectureProgressPercent = (lecture.progress && lecture.progress.progress_percent != null) ? lecture.progress.progress_percent : 0;
            this.watchedSeconds = (lecture.progress && lecture.progress.watch_time_seconds != null) ? Number(lecture.progress.watch_time_seconds) : 0;
            this.lastReportedTime = null;
            
            // إذا كان هناك فيديو: نفس أسلوب البوب أب في المنهج — بناء HTML المعاينة ووضعه في حاوية واحدة
            if (lecture.recording_url && lecture.recording_url.trim() !== '') {
                this.showVideoPlayer = true;
                this.currentLessonVideoUrl = lecture.recording_url;
                let platform = (lecture.video_platform && String(lecture.video_platform).trim()) ? String(lecture.video_platform).trim().toLowerCase() : null;
                if (!platform) {
                    const u = lecture.recording_url;
                    if (u.includes('youtube.com') || u.includes('youtu.be')) platform = 'youtube';
                    else if (u.includes('vimeo.com')) platform = 'vimeo';
                    else if (u.includes('drive.google.com')) platform = 'google_drive';
                    else if (u.includes('mediadelivery.net')) platform = 'bunny';
                    else if (u.match(/\.(mp4|webm|ogg|avi|mov)(\?.*)?$/i)) platform = 'direct';
                }
                const url = lecture.recording_url.trim();
                const courseId = this.$el.closest('[data-course-id]')?.dataset?.courseId;
                const canControl = (platform === 'youtube' || platform === 'vimeo' || platform === 'bunny');
                if (canControl && courseId) {
                    const container = document.getElementById('learn-video-embed');
                    if (container) window.initLectureVideoWithQuestions(container, lecture, platform, url, courseId, lectureId);
                } else {
                    const embedHtml = this.buildLectureVideoEmbedHtml(url, platform);
                    const inject = () => {
                        const container = document.getElementById('learn-video-embed');
                        if (container && embedHtml) container.innerHTML = embedHtml;
                        else if (container) container.innerHTML = '<div class="flex items-center justify-center text-white h-full"><p>لا يمكن عرض الفيديو</p></div>';
                    };
                    this.$nextTick(inject);
                    setTimeout(inject, 50);
                    setTimeout(inject, 200);
                }
                this.trackLectureProgress(lectureId);
                return;
            }
            
            // بناء محتوى HTML (بدون فيديو)
            let html = '<div class="lecture-viewer space-y-6 w-full">';
            
            // العنوان والوصف
            html += '<div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200 w-full">';
            html += '<h2 class="text-3xl font-black text-gray-900 mb-4">' + this.escapeHtml(lecture.title) + '</h2>';
            if (lecture.description) {
                html += '<p class="text-gray-700 leading-relaxed mb-4">' + this.escapeHtml(lecture.description) + '</p>';
            }
            html += '<div class="grid grid-cols-2 gap-4 text-sm">';
            html += '<div class="flex items-center gap-2 text-gray-600"><i class="fas fa-calendar text-[#1E4E8C]"></i><span class="font-semibold">التاريخ:</span> ' + (lecture.scheduled_at_formatted || '') + '</div>';
            html += '<div class="flex items-center gap-2 text-gray-600"><i class="fas fa-clock text-[#1E4E8C]"></i><span class="font-semibold">المدة:</span> ' + (lecture.duration_minutes || 60) + ' دقيقة</div>';
            html += '</div></div>';
            
            // رسالة عدم وجود فيديو
            html += '<div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-6 text-center w-full">';
            html += '<i class="fas fa-video text-gray-400 text-3xl mb-3"></i>';
            html += '<p class="text-gray-600 font-semibold">لا يوجد فيديو متاح لهذه المحاضرة</p></div>';
            
            // الملاحظات
            if (lecture.notes) {
                html += '<div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-6 w-full">';
                html += '<h3 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-sticky-note text-[#1E4E8C]"></i><span>ملاحظات</span></h3>';
                html += '<div class="text-gray-700 leading-relaxed whitespace-pre-wrap">' + this.escapeHtml(lecture.notes) + '</div>';
                html += '</div>';
            }
            
            html += '</div>';
            this.lectureContent = html;
        },
        loadAssignment(assignmentId) {
            this.lectureContent = '<div class="text-center text-gray-600 p-8"><i class="fas fa-tasks text-4xl mb-4"></i><p class="text-xl font-bold">عرض الواجب قريباً</p></div>';
        },
        async loadExam(examId) {
            this.selectedLesson = null;
            this.selectedLecture = null;
            this.lectureContent = '<div class="text-center p-8"><i class="fas fa-spinner fa-spin text-4xl text-[#1E4E8C] mb-4"></i><p class="text-gray-600">جاري تحميل الاختبار...</p></div>';

            try {
                const response = await fetch(`/student/exams/${examId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('فشل تحميل الاختبار');
                }

                const exam = await response.json();

                let html = '<div class="exam-viewer space-y-6 w-full">';
                html += '<div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6 border-2 border-indigo-200 w-full">';
                html += '<h2 class="text-3xl font-black text-gray-900 mb-4">' + this.escapeHtml(exam.title) + '</h2>';
                if (exam.description) {
                    html += '<p class="text-gray-700 leading-relaxed mb-4">' + this.escapeHtml(exam.description) + '</p>';
                }
                html += '<div class="grid grid-cols-2 gap-4 text-sm">';
                html += '<div class="flex items-center gap-2 text-gray-600"><i class="fas fa-clock text-indigo-600"></i><span class="font-semibold">المدة:</span> ' + exam.duration_minutes + ' دقيقة</div>';
                html += '<div class="flex items-center gap-2 text-gray-600"><i class="fas fa-star text-indigo-600"></i><span class="font-semibold">الدرجة الكلية:</span> ' + exam.total_marks + '</div>';
                html += '<div class="flex items-center gap-2 text-gray-600"><i class="fas fa-check-circle text-indigo-600"></i><span class="font-semibold">درجة النجاح:</span> ' + exam.passing_marks + '</div>';
                html += '<div class="flex items-center gap-2 text-gray-600"><i class="fas fa-redo text-indigo-600"></i><span class="font-semibold">المحاولات:</span> ' + (exam.attempts_allowed == 0 ? 'غير محدود' : exam.attempts_allowed) + '</div>';
                html += '</div></div>';

                if (exam.instructions) {
                    html += '<div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6 w-full">';
                    html += '<h3 class="font-bold text-blue-900 mb-2">تعليمات الاختبار:</h3>';
                    html += '<p class="text-blue-800 whitespace-pre-wrap">' + this.escapeHtml(exam.instructions) + '</p>';
                    html += '</div>';
                }

                html += '<div class="text-center mt-6 space-y-3">';
                html += '<a href="/student/exams/' + examId + '" class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-8 py-4 rounded-xl font-bold shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all duration-300 transform hover:scale-105">';
                html += '<i class="fas fa-play"></i>';
                html += '<span>بدء الاختبار</span>';
                html += '</a>';
                html += '<div class="text-sm text-gray-600 font-medium">';
                html += '<p><i class="fas fa-info-circle text-indigo-600 ml-1"></i> سيتم فتح صفحة الاختبار في نافذة جديدة</p>';
                html += '</div>';
                html += '</div>';

                html += '</div>';
                this.lectureContent = html;

            } catch (error) {
                console.error('Error loading exam:', error);
                this.lectureContent = '<div class="text-center text-red-600 p-8"><i class="fas fa-exclamation-triangle text-4xl mb-4"></i><p class="text-xl font-bold">فشل تحميل الاختبار</p></div>';
            }
        },
        getMaterialIconClass(mat) {
            const n = (mat && mat.file_name ? mat.file_name : '').toLowerCase();
            if (/\.(xlsx|xls)$/.test(n)) return 'fa-file-excel text-emerald-600 dark:text-emerald-400';
            if (n.endsWith('.pdf')) return 'fa-file-pdf text-red-600 dark:text-red-400';
            if (/\.(docx?|doc)$/.test(n)) return 'fa-file-word text-blue-600 dark:text-blue-400';
            if (/\.(pptx?|ppt)$/.test(n)) return 'fa-file-powerpoint text-orange-600 dark:text-orange-400';
            if (/\.(zip|rar|7z)$/.test(n)) return 'fa-file-archive text-amber-600 dark:text-amber-400';
            return 'fa-file-alt text-[#1E4E8C] dark:text-[#2A6BB5]';
        },
        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        /** نفس أسلوب معاينة الفيديو في بوب أب إضافة المحاضرة بالمنهج — بناء HTML الـ iframe/video حسب المنصة */
        buildLectureVideoEmbedHtml(url, platform) {
            if (!url || !platform) return '';
            const u = String(url).trim();
            let html = '';
            if (platform === 'youtube') {
                let videoId = (u.match(/[?&]v=([a-zA-Z0-9_-]{11})/) || [])[1] || (u.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/) || [])[1] || (u.match(/embed\/([a-zA-Z0-9_-]{11})/) || [])[1];
                if (videoId) {
                    const origin = encodeURIComponent(window.location.origin);
                    html = '<iframe src="https://www.youtube.com/embed/' + videoId + '?rel=0&modestbranding=1&showinfo=0&controls=1&enablejsapi=1&origin=' + origin + '&autoplay=0" width="100%" height="100%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="border-radius: 0.75rem;"></iframe>';
                }
            } else if (platform === 'vimeo') {
                const m = u.match(/vimeo\.com\/(?:.*\/)?(\d+)/);
                if (m && m[1]) html = '<iframe src="https://player.vimeo.com/video/' + m[1] + '?title=0&byline=0&portrait=0&controls=1" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="border-radius: 0.75rem;"></iframe>';
            } else if (platform === 'google_drive') {
                const m = u.match(/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/);
                if (m && m[1]) html = '<iframe src="https://drive.google.com/file/d/' + m[1] + '/preview" width="100%" height="100%" frameborder="0" allow="autoplay" style="border-radius: 0.75rem;"></iframe>';
            } else if (platform === 'direct') {
                if (/\.(mp4|webm|ogg|avi|mov)(\?.*)?$/i.test(u)) {
                    const esc = u.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    html = '<video controls width="100%" height="100%" style="max-height: 100%; border-radius: 0.75rem;" class="w-full h-full"><source src="' + esc + '" type="video/mp4">متصفحك لا يدعم تشغيل الفيديو.</video>';
                }
            } else if (platform === 'bunny') {
                const m = u.match(/mediadelivery\.net\/embed\/(\d+)\/([a-zA-Z0-9_-]+)/);
                if (m && m[1] && m[2]) {
                    const embedUrl = u.split('?')[0];
                    const src = embedUrl.startsWith('http') ? embedUrl : ('https://' + embedUrl.replace(/^\/+/, ''));
                    html = '<iframe src="' + src.replace(/"/g, '&quot;') + '" width="100%" height="100%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen style="border-radius: 0.75rem;"></iframe>';
                }
            }
            return html;
        },
        getYoutubeThumb(url) {
            if (!url) return '';
            const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
            return m ? 'https://img.youtube.com/vi/' + m[1] + '/default.jpg' : '';
        },
        async markLessonComplete() {
            const lessonId = this.selectedLesson || this.currentLessonId;
            if (!lessonId || this.currentLessonCompleted) return;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const res = await fetch('/my-courses/{{ $course->id }}/lessons/' + lessonId + '/progress', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ completed: true, watch_time: 0 })
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.success) {
                        this.currentLessonCompleted = true;
                        if (this.$el.dataset.courseProgress !== undefined && data.course_progress != null)
                            this.$el.dataset.courseProgress = data.course_progress;
                        if (data.total_items != null) this.$el.dataset.totalItems = data.total_items;
                        if (data.completed_items != null) this.$el.dataset.completedItems = data.completed_items;
                        updateProgressBar();
                    }
                }
            } catch (e) { console.error(e); }
        },
        generateVideoHtml(url, platform) {
            if (!url) return null;
            
            // YouTube
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                let videoId = null;
                const watchMatch = url.match(/[?&]v=([a-zA-Z0-9_-]{11})/);
                if (watchMatch && watchMatch[1]) {
                    videoId = watchMatch[1];
                } else {
                    const shortMatch = url.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/);
                    if (shortMatch && shortMatch[1]) {
                        videoId = shortMatch[1];
                    }
                }
                if (videoId) {
                    const origin = encodeURIComponent(window.location.origin);
                    return '<iframe src="https://www.youtube.com/embed/' + videoId + '?rel=0&modestbranding=1&showinfo=0&controls=1&enablejsapi=1&origin=' + origin + '" width="100%" height="100%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="border-radius: 0.75rem;"></iframe>';
                }
            }
            
            // Vimeo
            if (url.includes('vimeo.com')) {
                const vimeoMatch = url.match(/vimeo\.com\/(?:.*\/)?(\d+)/);
                if (vimeoMatch && vimeoMatch[1]) {
                    const videoId = vimeoMatch[1];
                    return '<iframe src="https://player.vimeo.com/video/' + videoId + '?title=0&byline=0&portrait=0&controls=1" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="border-radius: 0.75rem;"></iframe>';
                }
            }
            
            // Google Drive
            if (url.includes('drive.google.com')) {
                const driveMatch = url.match(/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/);
                if (driveMatch && driveMatch[1]) {
                    const fileId = driveMatch[1];
                    return '<iframe src="https://drive.google.com/file/d/' + fileId + '/preview" width="100%" height="100%" frameborder="0" allow="autoplay" style="border-radius: 0.75rem;"></iframe>';
                }
            }
            
            // Direct video
            if (url.match(/\.(mp4|webm|ogg|avi|mov)(\?.*)?$/i)) {
                return '<video width="100%" height="100%" controls style="border-radius: 0.75rem;"><source src="' + this.escapeHtml(url) + '" type="video/mp4">متصفحك لا يدعم تشغيل الفيديو.</video>';
            }
            
            // Bunny.net (Bunny Stream) - نفس صيغة صفحة المنهج
            if (url.includes('mediadelivery.net')) {
                const bunnyMatch = url.match(/mediadelivery\.net\/embed\/(\d+)\/([a-zA-Z0-9_-]+)/);
                if (bunnyMatch && bunnyMatch[1] && bunnyMatch[2]) {
                    const embedUrl = url.split('?')[0];
                    const src = embedUrl.startsWith('http') ? embedUrl : ('https://' + embedUrl.replace(/^\/+/, ''));
                    return '<iframe src="' + this.escapeHtml(src) + '" width="100%" height="100%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen style="border-radius: 0.75rem;"></iframe>';
                }
            }
            
            return null;
        },
        toggleSection(section) {
            const index = this.collapsedSections.indexOf(section);
            if (index > -1) {
                this.collapsedSections.splice(index, 1);
            } else {
                this.collapsedSections.push(section);
            }
        },
        isSectionCollapsed(section) {
            return this.collapsedSections.includes(section);
        },
        filterItems() {
            const query = this.searchQuery.toLowerCase();
            const items = document.querySelectorAll('.lesson-item, .lecture-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        },
        printCurriculum() {
            window.print();
        },
        toggleFocusMode() {
            this.focusMode = !this.focusMode;
            document.body.classList.toggle('learn-focus-mode', this.focusMode);
        },
        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    this.isFullscreen = true;
                }).catch(err => {
                    console.error('Error entering fullscreen:', err);
                });
            } else {
                document.exitFullscreen().then(() => {
                    this.isFullscreen = false;
                }).catch(err => {
                    console.error('Error exiting fullscreen:', err);
                });
            }
        },
        updateProgressBar() {
            const wrapper = document.querySelector('.learn-page');
            if (!wrapper) return;
            const pct = Math.min(100, parseFloat(wrapper.dataset.courseProgress) || 0);
            document.querySelectorAll('.learn-progress-fill').forEach(el => { el.style.width = pct + '%'; });
            const total = wrapper.dataset.totalItems;
            const completed = wrapper.dataset.completedItems;
            if (total !== undefined && completed !== undefined) {
                document.querySelectorAll('.learn-progress-count').forEach(el => { el.textContent = completed + '/' + total; });
                document.querySelectorAll('.learn-progress-pct').forEach(el => { el.textContent = Math.round(pct) + '%'; });
            }
        },
        isExternalVideo(url) {
            if (!url) return false;
            return url.includes('youtube.com') || 
                   url.includes('youtu.be') || 
                   url.includes('vimeo.com') ||
                   url.includes('drive.google.com') ||
                   url.includes('mediadelivery.net');
        },
        async loadProtectedVideo(lessonId, videoUrl) {
            try {
                // للفيديوهات المحلية المحمية، نستخدم المشغل المدمج مع حماية
                // الفيديو يتم بثه عبر route محمي
                this.showVideoPlayer = true;
                
                // إذا كان الفيديو محلي (ليس YouTube/Vimeo)، استخدم route محمي
                if (!this.isExternalVideo(videoUrl)) {
                    // استخدام route محمي للفيديو
                    this.currentLessonVideoUrl = `/api/video/stream/${lessonId}?token=${encodeURIComponent(this.generateSessionToken())}`;
                } else {
                    // فيديو خارجي - استخدم الرابط مباشرة
                    this.currentLessonVideoUrl = videoUrl;
                }
                
            } catch (error) {
                console.error('Error loading protected video:', error);
                this.lessonContent = '<div class="text-center text-red-600 p-8"><i class="fas fa-exclamation-circle text-4xl mb-4"></i><p class="text-xl font-bold">فشل في تحميل الفيديو المحمي</p><p class="text-sm text-gray-600 mt-2">' + this.escapeHtml(error.message) + '</p></div>';
            }
        },
        generateSessionToken() {
            // توليد token بسيط للجلسة (يمكن تطويره لاحقاً)
            return btoa(Date.now().toString() + Math.random().toString()).substring(0, 32);
        }
    };
}

// مشغل الفيديو - عرض رابط الفيديو فقط (iframe / video بالتحكم الأصلي للمنصة)
function videoPlayer() {
    return {
        currentLessonVideoUrl: null,
        watchersSetup: false,
        get currentVideoUrl() {
            return this.currentLessonVideoUrl;
        },
        set currentVideoUrl(value) {
            this.currentLessonVideoUrl = value;
            if (value) this.loadVideo(value);
        },
        init() {
            this.setupParentWatcher();
            setTimeout(() => this.setupParentWatcher(), 150);
            setTimeout(() => this.setupParentWatcher(), 400);
        },
        setupParentWatcher() {
            const parent = this.$el.closest('[x-data*="courseFocusMode"]');
            if (!parent || !parent.__x) return;
            const parentData = parent.__x.$data;
            if (parentData.showVideoPlayer && parentData.currentLessonVideoUrl) {
                this.currentLessonVideoUrl = parentData.currentLessonVideoUrl;
                this.loadVideo(parentData.currentLessonVideoUrl, this.detectPlatform(parentData.currentLessonVideoUrl));
            }
            if (!this.watchersSetup) {
                parent.__x.$watch('currentLessonVideoUrl', (value) => {
                    if (value && value !== this.currentLessonVideoUrl) {
                        this.currentLessonVideoUrl = value;
                        this.loadVideo(value, this.detectPlatform(value));
                    }
                });
                parent.__x.$watch('showVideoPlayer', (value) => {
                    if (value && parentData.currentLessonVideoUrl) {
                        this.currentLessonVideoUrl = parentData.currentLessonVideoUrl;
                        this.loadVideo(parentData.currentLessonVideoUrl, this.detectPlatform(parentData.currentLessonVideoUrl));
                    }
                });
                this.watchersSetup = true;
            }
        },
        getSurface() {
            const s = this.$el && this.$el.querySelector('#video-surface');
            return s || document.querySelector('#video-container #video-surface');
        },
        detectPlatform(url) {
            if (!url) return null;
            if (url.includes('youtube.com') || url.includes('youtu.be')) return 'youtube';
            if (url.includes('vimeo.com')) return 'vimeo';
            if (url.includes('drive.google.com')) return 'google_drive';
            if (url.includes('iframe.mediadelivery.net') || url.includes('mediadelivery.net')) return 'bunny';
            if (url.match(/\.(mp4|webm|ogg|avi|mov)(\?.*)?$/i) || url.includes('/api/video/stream/')) return 'direct';
            return null;
        },
        getYoutubeVideoId(url) {
            const m = url.match(/[?&]v=([a-zA-Z0-9_-]{11})/) || url.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/) || url.match(/embed\/([a-zA-Z0-9_-]{11})/);
            return m ? m[1] : null;
        },
        getVimeoVideoId(url) {
            const m = url.match(/vimeo\.com\/(?:.*\/)?(\d+)/) || url.match(/player\.vimeo\.com\/video\/(\d+)/);
            return m ? m[1] : null;
        },
        getDriveFileId(url) {
            const m = url.match(/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/) || url.match(/drive\.google\.com\/open\?id=([a-zA-Z0-9_-]+)/);
            return m ? m[1] : null;
        },
        getBunnyEmbedUrl(url) {
            if (!url || !url.includes('mediadelivery.net')) return null;
            const trimmed = String(url).trim();
            // نفس منطق صفحة المنهج: أي رابط يحتوي embed/libraryId/videoId
            const m = trimmed.match(/mediadelivery\.net\/embed\/(\d+)\/([a-zA-Z0-9_-]+)/);
            if (m && m[1] && m[2]) {
                // إزالة query string مثل صفحة curriculum ثم استخدام الرابط
                const embedUrl = trimmed.split('?')[0];
                if (!embedUrl.startsWith('http')) return 'https://' + embedUrl.replace(/^\/+/, '');
                return embedUrl;
            }
            // رابط Bunny بدون نمط embed (نادر): نعيده كما هو بعد إزالة الـ query
            const noQuery = trimmed.split('?')[0];
            return noQuery.startsWith('http') ? noQuery : ('https://' + noQuery.replace(/^\/+/, ''));
        },
        loadVideo(videoUrl, platform = null) {
            if (this.ytProgressInterval) { clearInterval(this.ytProgressInterval); this.ytProgressInterval = null; }
            if (!videoUrl) {
                this.currentLessonVideoUrl = null;
                return;
            }
            this.currentLessonVideoUrl = videoUrl;
            const surface = this.getSurface();
            if (!surface) {
                this.$nextTick && this.$nextTick(() => this.loadVideo(videoUrl, platform));
                setTimeout(() => this.loadVideo(videoUrl, platform), 200);
                return;
            }
            platform = platform || this.detectPlatform(videoUrl);
            surface.innerHTML = '';

            if (platform === 'youtube') {
                const vid = this.getYoutubeVideoId(videoUrl);
                if (!vid) return;
                const iframe = document.createElement('iframe');
                iframe.id = 'yt-player-' + Date.now();
                iframe.src = 'https://www.youtube.com/embed/' + vid + '?rel=0&modestbranding=1&enablejsapi=1&origin=' + encodeURIComponent(window.location.origin);
                iframe.className = 'absolute inset-0 w-full h-full border-0';
                iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                iframe.allowFullscreen = true;
                surface.appendChild(iframe);
                this.setupYoutubeProgressTracking(surface, vid, iframe.id);
            } else if (platform === 'vimeo') {
                const vid = this.getVimeoVideoId(videoUrl);
                if (!vid) return;
                const iframe = document.createElement('iframe');
                iframe.src = 'https://player.vimeo.com/video/' + vid + '?title=0&byline=0&portrait=0';
                iframe.className = 'absolute inset-0 w-full h-full border-0';
                iframe.allow = 'autoplay; fullscreen; picture-in-picture';
                iframe.allowFullscreen = true;
                surface.appendChild(iframe);
            } else if (platform === 'direct') {
                const video = document.createElement('video');
                video.className = 'absolute inset-0 w-full h-full object-contain';
                video.controls = true;
                video.setAttribute('playsinline', '');
                const src = this.escapeHtml(videoUrl);
                video.innerHTML = '<source src="' + src + '" type="video/mp4">';
                surface.appendChild(video);
                this.attachVideoProgressTracking(video);
            } else if (platform === 'google_drive') {
                const fileId = this.getDriveFileId(videoUrl);
                if (!fileId) return;
                const iframe = document.createElement('iframe');
                iframe.src = 'https://drive.google.com/file/d/' + fileId + '/preview';
                iframe.className = 'absolute inset-0 w-full h-full border-0';
                surface.appendChild(iframe);
            } else if (platform === 'bunny') {
                const embedUrl = this.getBunnyEmbedUrl(videoUrl);
                if (!embedUrl) return;
                const iframe = document.createElement('iframe');
                iframe.src = embedUrl;
                iframe.className = 'absolute inset-0 w-full h-full border-0';
                iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture');
                iframe.allowFullscreen = true;
                surface.appendChild(iframe);
            }
        },
        attachVideoProgressTracking(video) {
            const report = () => {
                if (!video) return;
                const ct = video.currentTime || 0, dur = video.duration;
                if (Number.isFinite(ct) && Number.isFinite(dur) && dur > 0) {
                    const isPlaying = !video.paused;
                    window.dispatchEvent(new CustomEvent('video-progress-report', { detail: { currentSec: ct, durationSec: dur, isPlaying } }));
                }
            };
            video.addEventListener('loadedmetadata', report);
            video.addEventListener('timeupdate', report);
            video.addEventListener('durationchange', report);
            video.addEventListener('play', report);
            video.addEventListener('pause', report);
            video.addEventListener('progress', () => { if (video.duration && isFinite(video.duration)) report(); });
            if (video.readyState >= 1 && video.duration && isFinite(video.duration)) report();
        },
        setupYoutubeProgressTracking(surface, vid, iframeId) {
            const self = this;
            if (self.ytProgressInterval) { clearInterval(self.ytProgressInterval); self.ytProgressInterval = null; }
            const loadYT = () => {
                if (!window.YT || !window.YT.Player) return;
                const el = document.getElementById(iframeId);
                if (!el) return;
                try {
                    const player = new window.YT.Player(iframeId, {
                        events: {
                            onStateChange: function(e) {
                                if (e.data === 1) {
                                    if (self.ytProgressInterval) clearInterval(self.ytProgressInterval);
                                    self.ytProgressInterval = setInterval(function poll() {
                                        try {
                                            const p = e.target;
                                            if (p && typeof p.getCurrentTime === 'function') {
                                                const ct = p.getCurrentTime();
                                                const dur = p.getDuration();
                                                if (typeof ct === 'number' && typeof dur === 'number' && dur > 0) {
                                                    window.dispatchEvent(new CustomEvent('video-progress-report', { detail: { currentSec: ct, durationSec: dur, isPlaying: true } }));
                                                }
                                            }
                                        } catch (err) {}
                                    }, 800);
                                }
                                if (e.data === 0 || e.data === 2) {
                                    if (self.ytProgressInterval) { clearInterval(self.ytProgressInterval); self.ytProgressInterval = null; }
                                }
                            }
                        }
                    });
                    self.ytPlayer = player;
                } catch (err) { console.warn('YT Player init:', err); }
            };
            if (window.YT && window.YT.Player) {
                setTimeout(loadYT, 800);
                return;
            }
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            const first = document.getElementsByTagName('script')[0];
            first.parentNode.insertBefore(tag, first);
            const prevReady = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function() {
                if (prevReady) prevReady();
                setTimeout(loadYT, 500);
            };
        },
        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
}
</script>
<script>
(function() {
    function getYoutubeVideoId(url) {
        if (!url) return null;
        var u = String(url).trim();
        return (u.match(/[?&]v=([a-zA-Z0-9_-]{11})/) || [])[1] || (u.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/) || [])[1] || (u.match(/embed\/([a-zA-Z0-9_-]{11})/) || [])[1] || null;
    }
    function getVimeoVideoId(url) {
        if (!url) return null;
        var m = String(url).trim().match(/vimeo\.com\/(?:.*\/)?(\d+)/);
        return m && m[1] ? m[1] : null;
    }
    window.initLectureVideoWithQuestions = function(container, lecture, platform, url, courseId, lectureId) {
        if (!container || !lecture) return;
        var questions = (lecture.video_questions && lecture.video_questions.length) ? lecture.video_questions : [];
        var shownIds = new Set();
        var currentQuestion = null;
        var player = null;
        var checkInterval = null;
        var lastProgressSentAt = 0;
        var overlay = null;
        var submitBtn = null;
        var optionsEl = null;
        var textEl = null;
        var startFromSec = (lecture.progress && lecture.progress.watch_time_seconds > 0) ? Math.floor(lecture.progress.watch_time_seconds) : 0;
        var savedDurationSec = (lecture.progress && lecture.progress.video_duration_seconds > 0) ? lecture.progress.video_duration_seconds : 0;
        var durationMinutesFromLecture = (lecture.duration_minutes && parseInt(lecture.duration_minutes, 10) > 0) ? parseInt(lecture.duration_minutes, 10) : 0;
        var fallbackDurationSec = durationMinutesFromLecture > 0 ? durationMinutesFromLecture * 60 : savedDurationSec;
        var hasOpenedNext = false;
        var minPercentToUnlock = (lecture.min_watch_percent_to_unlock_next != null && lecture.min_watch_percent_to_unlock_next !== '') ? parseInt(lecture.min_watch_percent_to_unlock_next, 10) : 90;

        function updateLectureBar(pct) {
            var elText = document.getElementById('lecture-watch-pct-text');
            var elFill = document.getElementById('lecture-watch-pct-fill');
            if (elText) elText.textContent = (Math.round((pct || 0) * 10) / 10).toFixed(1) + '%';
            if (elFill) elFill.style.width = Math.min(100, Math.max(0, pct || 0)) + '%';
        }
        var initialPct = (lecture.progress && lecture.progress.progress_percent != null) ? lecture.progress.progress_percent : 0;
        setTimeout(function() { updateLectureBar(initialPct); }, 400);

        function seekToStartPosition() {
            if (startFromSec <= 0 || !player) return;
            if (platform === 'youtube' && player.seekTo) {
                player.seekTo(startFromSec, true);
            } else if (platform === 'vimeo' && player.setCurrentTime) {
                player.setCurrentTime(startFromSec);
            } else if (platform === 'bunny' && player.setCurrentTime) {
                player.setCurrentTime(startFromSec);
            }
        }

        container.innerHTML = '<div id="lecture-yt-player-box" class="absolute inset-0 w-full h-full"></div>' +
            '<div id="lecture-vq-overlay" class="hidden absolute inset-0 bg-black/85 flex items-center justify-center p-4 z-20" style="direction:rtl">' +
            '<div id="lecture-vq-card" class="bg-white rounded-2xl p-6 max-w-lg w-full max-h-[90%] overflow-y-auto shadow-xl">' +
            '<div id="lecture-vq-question-view">' +
            '<h3 class="text-lg font-bold text-slate-800 mb-2">سؤال</h3>' +
            '<p id="lecture-vq-text" class="text-slate-700 mb-4"></p>' +
            '<div id="lecture-vq-options" class="space-y-2 mb-4"></div>' +
            '<button type="button" id="lecture-vq-submit" class="w-full py-2.5 bg-[#E8F0FA]0 hover:bg-[#152A4A] text-white rounded-xl font-semibold">إرسال</button>' +
            '</div>' +
            '<div id="lecture-vq-feedback-view" class="hidden text-center">' +
            '<p id="lecture-vq-result-label" class="text-xl font-bold mb-2"></p>' +
            '<p id="lecture-vq-result-emoji" class="text-4xl mb-3"></p>' +
            '<p id="lecture-vq-result-message" class="text-slate-600 mb-4"></p>' +
            '<button type="button" id="lecture-vq-continue-btn" class="w-full py-2.5 bg-[#E8F0FA]0 hover:bg-[#152A4A] text-white rounded-xl font-semibold">متابعة</button>' +
            '</div></div></div>';
        overlay = document.getElementById('lecture-vq-overlay');
        submitBtn = document.getElementById('lecture-vq-submit');
        optionsEl = document.getElementById('lecture-vq-options');
        textEl = document.getElementById('lecture-vq-text');
        var questionView = document.getElementById('lecture-vq-question-view');
        var feedbackView = document.getElementById('lecture-vq-feedback-view');
        var resultLabel = document.getElementById('lecture-vq-result-label');
        var resultEmoji = document.getElementById('lecture-vq-result-emoji');
        var resultMessage = document.getElementById('lecture-vq-result-message');
        var continueBtn = document.getElementById('lecture-vq-continue-btn');
        var correctMessages = [
            'واو! عقلك يعمل بشكل ممتاز اليوم 🧠✨',
            'ماشاء الله! إجابة ذكية جداً 🎯🔥',
            'برافو! أنت منتبه ومتابع 👏💡',
            'صح ١٠٠٪! استمر هيك 🌟👍',
            'فهمت الفكرة صح، رائع! 🏆😊',
            'إجابة صحيحة بامتياز! متفوق اليوم 🎓✨'
        ];
        var wrongMessages = [
            'لا بأس! جرّب التركيز والمشاهدة مرة أخرى 🔄💪',
            'هيك نتعلم! رجّع شوي وشوف الجزء مرة تانية 📚😊',
            'غلطة بسيطة، المهم إنك تحاول 💪❤️',
            'راجع الدقيقة اللي فاتت وارجع جرب 🎬✨',
            'ما في مشكلة، كلنا بنتعلم من الأخطاء 🌱🙌',
            'شوي تركيز وراح تضبط! أنت قادر 💯🔥'
        ];
        var continueHandler = null;

        function showQuestion(q) {
            currentQuestion = q;
            if (questionView) questionView.classList.remove('hidden');
            if (feedbackView) feedbackView.classList.add('hidden');
            if (textEl) textEl.textContent = q.text || '';
            if (optionsEl) {
                optionsEl.innerHTML = '';
                (q.options || []).forEach(function(opt, i) {
                    var label = document.createElement('label');
                    label.className = 'flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 cursor-pointer';
                    var radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'lecture_vq_answer';
                    radio.value = opt;
                    radio.className = 'text-[#1E4E8C]';
                    label.appendChild(radio);
                    label.appendChild(document.createTextNode(opt));
                    optionsEl.appendChild(label);
                });
            }
            if (overlay) overlay.classList.remove('hidden');
        }
        function showFeedback(correct, data) {
            if (questionView) questionView.classList.add('hidden');
            if (feedbackView) feedbackView.classList.remove('hidden');
            if (resultLabel) {
                resultLabel.textContent = correct ? 'إجابة صحيحة ✓' : 'إجابة خاطئة';
                resultLabel.className = 'text-xl font-bold mb-2 ' + (correct ? 'text-emerald-600' : 'text-amber-600');
            }
            if (resultEmoji) resultEmoji.textContent = correct ? '🎉' : '💪';
            if (resultMessage) {
                var arr = correct ? correctMessages : wrongMessages;
                resultMessage.textContent = arr[Math.floor(Math.random() * arr.length)];
            }
            if (continueHandler && continueBtn) continueBtn.removeEventListener('click', continueHandler);
            continueHandler = function() {
                hideOverlay();
                if (submitBtn) submitBtn.disabled = false;
                if (data.on_wrong === 'rewind' && !data.correct && data.rewind_seconds) doRewind(data.rewind_seconds || 0);
                else doContinue();
            };
            if (continueBtn) continueBtn.addEventListener('click', continueHandler);
        }
        function hideOverlay() {
            if (overlay) overlay.classList.add('hidden');
            currentQuestion = null;
        }
        function doRewind(rewindSec) {
            if (!player) return;
            if (platform === 'youtube' && player.getCurrentTime && player.seekTo && player.playVideo) {
                var t = player.getCurrentTime();
                player.seekTo(Math.max(0, t - rewindSec), true);
                player.playVideo();
                return;
            }
            if (platform === 'vimeo' && player.getCurrentTime) {
                player.getCurrentTime().then(function(sec) {
                    var t = sec || 0;
                    player.setCurrentTime(Math.max(0, t - rewindSec)).then(function() { player.play(); });
                });
                return;
            }
            if (platform === 'bunny' && player.getCurrentTime && player.setCurrentTime && player.play) {
                player.getCurrentTime(function(sec) {
                    var t = sec || 0;
                    player.setCurrentTime(Math.max(0, t - rewindSec));
                    player.play();
                });
                return;
            }
        }
        function doContinue() {
            if (player && platform === 'youtube' && player.playVideo) player.playVideo();
            if (player && platform === 'vimeo' && player.play) player.play();
            if (player && platform === 'bunny' && player.play) player.play();
        }
        function onSubmit() {
            if (!currentQuestion) return;
            var selected = document.querySelector('input[name="lecture_vq_answer"]:checked');
            var answer = selected ? selected.value : '';
            if (!answer) { alert('اختر إجابة'); return; }
            if (submitBtn) submitBtn.disabled = true;
            var answerUrl = '/my-courses/' + courseId + '/lectures/' + lectureId + '/video-questions/' + currentQuestion.id + '/answer';
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(answerUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ answer: answer })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (!(currentQuestion && currentQuestion.show_every_time)) shownIds.add(currentQuestion.id);
                showFeedback(!!data.correct, data);
            }).catch(function() {
                if (submitBtn) submitBtn.disabled = false;
                hideOverlay();
                doContinue();
            });
        }
        if (submitBtn) submitBtn.addEventListener('click', onSubmit);

        function startTimeCheck() {
            if (checkInterval) return;
            checkInterval = setInterval(function() {
                if (currentQuestion) return;
                var t = 0;
                if (platform === 'youtube' && player && player.getCurrentTime) t = player.getCurrentTime();
                else if (platform === 'vimeo' && player && player.getCurrentTime) {
                    player.getCurrentTime().then(function(sec) {
                        t = sec;
                        for (var i = 0; i < questions.length; i++) {
                            var q = questions[i];
                            if (q.show_at_end) continue;
                            if (t >= q.timestamp_seconds && !shownIds.has(q.id)) {
                                if (player.pause) player.pause();
                                showQuestion(q);
                                break;
                            }
                        }
                        var currentSec = t;
                        var durForBar = fallbackDurationSec;
                        if (player.getDuration) {
                            player.getDuration().then(function(d) {
                                durForBar = (d && d > 0) ? d : fallbackDurationSec;
                                if (durForBar > 0 && typeof currentSec === 'number' && currentSec >= 0) {
                                    window.dispatchEvent(new CustomEvent('video-progress-report', { detail: { currentSec: currentSec, durationSec: durForBar, isPlaying: true } }));
                                    window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: Math.min(100, Math.round((currentSec / durForBar) * 100)) } }));
                                    updateLectureBar(Math.min(100, Math.round((currentSec / durForBar) * 100)));
                                }
                                var now = Date.now();
                                if (!lastProgressSentAt || now - lastProgressSentAt > 5000) {
                                    lastProgressSentAt = now;
                                    var send = function(cs, ds) {
                                        var dur = (ds && ds > 0) ? ds : (savedDurationSec > 0 ? savedDurationSec : fallbackDurationSec);
                                        if (!dur) return;
                                        var pct = Math.min(100, Math.round((cs / dur) * 100));
                                        window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: pct } }));
                                        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                                        fetch('/my-courses/' + courseId + '/lectures/' + lectureId + '/progress', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                            body: JSON.stringify({ current_sec: cs, duration_sec: dur })
                                        }).then(function(r) { return r.json(); }).then(function(data) {
                                            if (data && data.success) {
                                                var wrapper = document.querySelector('.learn-page');
                                                if (wrapper && data.course_progress != null) wrapper.dataset.courseProgress = data.course_progress;
                                                if (data.total_items != null) wrapper.dataset.totalItems = data.total_items;
                                                if (data.completed_items != null) wrapper.dataset.completedItems = data.completed_items;
                                                if (typeof updateProgressBar === 'function') updateProgressBar();
                                                if (typeof data.progress_percent === 'number') {
                                                    window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: data.progress_percent } }));
                                                }
                                                if (!hasOpenedNext && (data.is_completed || (data.progress_percent >= minPercentToUnlock))) {
                                                    hasOpenedNext = true;
                                                    try {
                                                        var nextMap = document.getElementById('learn-next-item-map');
                                                        var nextByLecture = nextMap && nextMap.textContent ? JSON.parse(nextMap.textContent) : {};
                                                        var nextItem = nextByLecture[lectureId];
                                                        if (nextItem && nextItem.type && nextItem.id) {
                                                            window.dispatchEvent(new CustomEvent('learn-open-next-item', { detail: { type: nextItem.type, id: nextItem.id } }));
                                                        }
                                                    } catch (err) { console.warn('learn-open-next', err); }
                                                }
                                            }
                                        }).catch(function() {});
                                    };
                                    send(currentSec, durForBar);
                                }
                            });
                        } else if (durForBar > 0 && typeof currentSec === 'number' && currentSec >= 0) {
                            window.dispatchEvent(new CustomEvent('video-progress-report', { detail: { currentSec: currentSec, durationSec: durForBar, isPlaying: true } }));
                            window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: Math.min(100, Math.round((currentSec / durForBar) * 100)) } }));
                            updateLectureBar(Math.min(100, Math.round((currentSec / durForBar) * 100)));
                        }
                    });
                    return;
                } else if (platform === 'bunny' && player && player.getCurrentTime) {
                    player.getCurrentTime(function(sec) {
                        t = sec || 0;
                        for (var i = 0; i < questions.length; i++) {
                            var q = questions[i];
                            if (q.show_at_end) continue;
                            if (t >= q.timestamp_seconds && !shownIds.has(q.id)) {
                                if (player.pause) player.pause();
                                showQuestion(q);
                                break;
                            }
                        }
                        var currentSec = t;
                        var durForBar = fallbackDurationSec;
                        if (player.getDuration) {
                            player.getDuration(function(d) {
                                durForBar = (d && d > 0) ? d : fallbackDurationSec;
                                if (durForBar > 0 && typeof currentSec === 'number' && currentSec >= 0) {
                                    window.dispatchEvent(new CustomEvent('video-progress-report', { detail: { currentSec: currentSec, durationSec: durForBar, isPlaying: true } }));
                                    window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: Math.min(100, Math.round((currentSec / durForBar) * 100)) } }));
                                    updateLectureBar(Math.min(100, Math.round((currentSec / durForBar) * 100)));
                                }
                                var now = Date.now();
                                if (!lastProgressSentAt || now - lastProgressSentAt > 5000) {
                                    lastProgressSentAt = now;
                                    var send = function(cs, ds) {
                                        var dur = (ds && ds > 0) ? ds : (savedDurationSec > 0 ? savedDurationSec : fallbackDurationSec);
                                        if (!dur) return;
                                        var pct = Math.min(100, Math.round((cs / dur) * 100));
                                        window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: pct } }));
                                        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                                        fetch('/my-courses/' + courseId + '/lectures/' + lectureId + '/progress', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                            body: JSON.stringify({ current_sec: cs, duration_sec: dur })
                                        }).then(function(r) { return r.json(); }).then(function(data) {
                                            if (data && data.success) {
                                                var wrapper = document.querySelector('.learn-page');
                                                if (wrapper && data.course_progress != null) wrapper.dataset.courseProgress = data.course_progress;
                                                if (data.total_items != null) wrapper.dataset.totalItems = data.total_items;
                                                if (data.completed_items != null) wrapper.dataset.completedItems = data.completed_items;
                                                if (typeof updateProgressBar === 'function') updateProgressBar();
                                                if (typeof data.progress_percent === 'number') {
                                                    window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: data.progress_percent } }));
                                                }
                                                if (!hasOpenedNext && (data.is_completed || (data.progress_percent >= minPercentToUnlock))) {
                                                    hasOpenedNext = true;
                                                    try {
                                                        var nextMap = document.getElementById('learn-next-item-map');
                                                        var nextByLecture = nextMap && nextMap.textContent ? JSON.parse(nextMap.textContent) : {};
                                                        var nextItem = nextByLecture[lectureId];
                                                        if (nextItem && nextItem.type && nextItem.id) {
                                                            window.dispatchEvent(new CustomEvent('learn-open-next-item', { detail: { type: nextItem.type, id: nextItem.id } }));
                                                        }
                                                    } catch (err) { console.warn('learn-open-next', err); }
                                                }
                                            }
                                        }).catch(function() {});
                                    };
                                    send(currentSec, durForBar);
                                }
                            });
                        } else if (durForBar > 0 && typeof currentSec === 'number' && currentSec >= 0) {
                            window.dispatchEvent(new CustomEvent('video-progress-report', { detail: { currentSec: currentSec, durationSec: durForBar, isPlaying: true } }));
                            window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: Math.min(100, Math.round((currentSec / durForBar) * 100)) } }));
                            updateLectureBar(Math.min(100, Math.round((currentSec / durForBar) * 100)));
                        }
                    });
                    return;
                }
                // تحديث شريط النسبة باستمرار (كل ثانية) ثم إرسال للسيرفر كل 5 ثوانٍ — نفس آلية الدروس: video-progress-report
                if (player && typeof t === 'number' && t >= 0) {
                    var durForBar = (platform === 'youtube' && player.getDuration) ? player.getDuration() : null;
                    if (durForBar === 0 || !durForBar) durForBar = fallbackDurationSec;
                    if (durForBar > 0) {
                        var pctBar = Math.min(100, Math.round((t / durForBar) * 100));
                        window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: pctBar } }));
                        var isPlaying = (platform === 'youtube' && player.getPlayerState && player.getPlayerState() === 1);
                        window.dispatchEvent(new CustomEvent('video-progress-report', { detail: { currentSec: t, durationSec: durForBar, isPlaying: !!isPlaying } }));
                        updateLectureBar(pctBar);
                    }
                    var now = Date.now();
                    if (!lastProgressSentAt || now - lastProgressSentAt > 5000) {
                        lastProgressSentAt = now;
                        var send = function(currentSec, durationSec) {
                            var dur = (durationSec && durationSec > 0) ? durationSec : (savedDurationSec > 0 ? savedDurationSec : fallbackDurationSec);
                            if (!dur) return;
                            var pct = Math.min(100, Math.round((currentSec / dur) * 100));
                            window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: pct } }));
                            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                            fetch('/my-courses/' + courseId + '/lectures/' + lectureId + '/progress', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                body: JSON.stringify({ current_sec: currentSec, duration_sec: dur })
                            }).then(function(r) { return r.json(); }).then(function(data) {
                                if (data && data.success) {
                                    var wrapper = document.querySelector('.learn-page');
                                    if (wrapper && data.course_progress != null) wrapper.dataset.courseProgress = data.course_progress;
                                    if (data.total_items != null) wrapper.dataset.totalItems = data.total_items;
                                    if (data.completed_items != null) wrapper.dataset.completedItems = data.completed_items;
                                    if (typeof updateProgressBar === 'function') updateProgressBar();
                                    if (typeof data.progress_percent === 'number') {
                                        window.dispatchEvent(new CustomEvent('learn-lecture-progress', { detail: { progress_percent: data.progress_percent } }));
                                    }
                                    if (!hasOpenedNext && (data.is_completed || (data.progress_percent >= minPercentToUnlock))) {
                                        hasOpenedNext = true;
                                        try {
                                            var nextMap = document.getElementById('learn-next-item-map');
                                            var nextByLecture = nextMap && nextMap.textContent ? JSON.parse(nextMap.textContent) : {};
                                            var nextItem = nextByLecture[lectureId];
                                            if (nextItem && nextItem.type && nextItem.id) {
                                                window.dispatchEvent(new CustomEvent('learn-open-next-item', { detail: { type: nextItem.type, id: nextItem.id } }));
                                            }
                                        } catch (err) { console.warn('learn-open-next', err); }
                                    }
                                }
                            }).catch(function() {});
                        };
                        if (platform === 'youtube') {
                            var d = (player.getDuration && player.getDuration()) || savedDurationSec || fallbackDurationSec;
                            if (d) send(t, d);
                        } else if (platform === 'vimeo' && player.getDuration) {
                            player.getDuration().then(function(d) { send(t, d || savedDurationSec || fallbackDurationSec); });
                        } else if (platform === 'bunny' && player.getDuration) {
                            player.getDuration(function(d) { send(t, d || savedDurationSec || fallbackDurationSec); });
                        } else {
                            send(t, fallbackDurationSec || savedDurationSec);
                        }
                    }
                }
                for (var i = 0; i < questions.length; i++) {
                    var q = questions[i];
                    if (q.show_at_end) continue;
                    if (t >= q.timestamp_seconds && !shownIds.has(q.id)) {
                        if (player && player.pauseVideo) player.pauseVideo();
                        showQuestion(q);
                        break;
                    }
                }
            }, 1000);
        }

        function showEndOfVideoQuestions() {
            for (var i = 0; i < questions.length; i++) {
                var q = questions[i];
                if (q.show_at_end && !shownIds.has(q.id)) {
                    if (player && player.pauseVideo) player.pauseVideo();
                    if (player && player.pause) player.pause();
                    showQuestion(q);
                    return;
                }
            }
        }

        if (platform === 'youtube') {
            var videoId = getYoutubeVideoId(url);
            if (!videoId) { container.innerHTML = '<div class="flex items-center justify-center text-white h-full"><p>رابط يوتيوب غير صالح</p></div>'; return; }
            function createYT() {
                if (player) return;
                player = new YT.Player('lecture-yt-player-box', {
                    videoId: videoId,
                    width: '100%',
                    height: '100%',
                    playerVars: { enablejsapi: 1, origin: window.location.origin, rel: 0 },
                    events: {
                        onReady: function() {
                            startTimeCheck();
                            setTimeout(seekToStartPosition, 300);
                        },
                        onStateChange: function(ev) {
                            if (ev.data === 0) showEndOfVideoQuestions();
                        }
                    }
                });
            }
            if (window.YT && window.YT.Player) {
                createYT();
            } else {
                window.onYouTubeIframeAPIReady = function() {
                    createYT();
                };
                var tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                var first = document.getElementsByTagName('script')[0];
                first.parentNode.insertBefore(tag, first);
            }
        } else if (platform === 'vimeo') {
            var vimeoId = getVimeoVideoId(url);
            if (!vimeoId) { container.innerHTML = '<div class="flex items-center justify-center text-white h-full"><p>رابط فيميوه غير صالح</p></div>'; return; }
            if (!window.Vimeo) {
                var s = document.createElement('script');
                s.src = 'https://player.vimeo.com/api/player.js';
                s.onload = function() {
                    player = new Vimeo.Player(document.getElementById('lecture-yt-player-box'), { id: parseInt(vimeoId, 10), width: '100%', height: '100%' });
                    player.on('ended', showEndOfVideoQuestions);
                    startTimeCheck();
                    setTimeout(seekToStartPosition, 500);
                };
                document.head.appendChild(s);
            } else {
                player = new Vimeo.Player(document.getElementById('lecture-yt-player-box'), { id: parseInt(vimeoId, 10), width: '100%', height: '100%' });
                player.on('ended', showEndOfVideoQuestions);
                startTimeCheck();
                setTimeout(seekToStartPosition, 500);
            }
        } else if (platform === 'bunny') {
            var root = document.getElementById('lecture-yt-player-box');
            if (!root) return;
            var iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.width = '100%';
            iframe.height = '100%';
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allowfullscreen', 'allowfullscreen');
            iframe.allow = 'autoplay; fullscreen; picture-in-picture';
            root.appendChild(iframe);

            function createBunnyPlayer() {
                if (player) return;
                if (!window.playerjs || !window.playerjs.Player) return;
                player = new window.playerjs.Player(iframe);
                player.on('ready', function() {
                    startTimeCheck();
                    setTimeout(seekToStartPosition, 500);
                });
                player.on('ended', showEndOfVideoQuestions);
            }
            window.addEventListener('beforeunload', function() {
                if (!player) return;
                var t = 0;
                if (platform === 'youtube' && player.getCurrentTime) t = player.getCurrentTime();
                var dur = (platform === 'youtube' && player.getDuration) ? player.getDuration() : savedDurationSec;
                if (t > 0 && dur > 0) {
                    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    var payload = JSON.stringify({ current_sec: t, duration_sec: dur, _token: csrf });
                    navigator.sendBeacon('/my-courses/' + courseId + '/lectures/' + lectureId + '/progress', new Blob([payload], { type: 'application/json' }));
                }
            });

            if (window.playerjs && window.playerjs.Player) {
                createBunnyPlayer();
            } else {
                var s = document.createElement('script');
                s.src = '//assets.mediadelivery.net/playerjs/playerjs-latest.min.js';
                s.onload = function() { createBunnyPlayer(); };
                document.head.appendChild(s);
            }
        }
    };
})();
</script>
@endpush
