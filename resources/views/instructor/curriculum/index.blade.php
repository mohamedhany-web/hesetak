@extends('layouts.app')

@section('title', __('instructor.build_curriculum') . ' - ' . $course->title)
@section('page_title', __('instructor.build_curriculum'))

@push('styles')
<style>
    #lectureModal { backdrop-filter: blur(4px); }
    .section-block.collapsed .section-body { display: none; }
    .section-block.collapsed .section-chevron { transform: rotate(-90deg); }
    .sortable-ghost { opacity: 0.35; background: var(--su-card-1) !important; border-color: var(--su-ink) !important; border-style: dashed !important; transform: scale(0.98); }
    .sortable-drag { opacity: 1; box-shadow: 0 10px 40px rgba(0,0,0,0.12); cursor: grabbing !important; z-index: 1000; }
    .sortable-chosen { background: var(--su-bg) !important; border-color: var(--su-ink) !important; }
    .items-container.sortable-dragging { min-height: 52px; background: var(--su-bg); border: 2px dashed var(--su-line); border-radius: 12px; }
    body.curriculum-dragging .curriculum-drag-hint { opacity: 1 !important; }
    .section-block .section-header { touch-action: manipulation; }
    .item-card .drag-handle { cursor: grab; }
    .su-curr-layout { display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1fr); gap:20px; }
    @media (max-width: 1024px) { .su-curr-layout { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="su-page">
    <div class="su-page-head">
        <div class="min-w-0">
            <h1 class="su-page-head__title">
                <i class="fas fa-sitemap su-page-head__ico" aria-hidden="true"></i>
                {{ __('instructor.build_curriculum') }}
            </h1>
            <p class="su-page-head__sub">{{ $course->title }}</p>
        </div>
        <div class="su-page-head__actions">
            <a href="{{ route('instructor.lectures.index') }}" class="su-btn su-btn--primary">
                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                {{ __('instructor.lectures') }}
            </a>
            <a href="{{ route('instructor.courses.index') }}" class="su-btn">
                <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </div>

    <div class="su-curr-layout">
        <div>
            <div id="sections-container">
                @forelse($sections as $section)
                    @include('instructor.curriculum.partials.section', ['section' => $section, 'depth' => 0])
                @empty
                    <div class="su-empty">
                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                        <p>{{ __('instructor.curr_no_sections') }}</p>
                        <button type="button" onclick="showAddSectionModal()" class="su-btn su-btn--primary" style="margin-top:12px">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            {{ __('instructor.curr_add_section') }}
                        </button>
                    </div>
                @endforelse
            </div>

            @if($sections->count() > 0)
                <button type="button" onclick="showAddSectionModal()" class="su-btn" style="width:100%;justify-content:center;margin-top:12px">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    {{ __('instructor.curr_add_section') }}
                </button>
            @endif
        </div>

        <aside class="su-card">
            <h3 class="su-card__title" style="margin-bottom:14px">{{ __('instructor.curr_available_items') }}</h3>

            @if($availableLectures->count() > 0)
                <div style="margin-bottom:16px">
                    <div style="font-size:12px;font-weight:600;color:var(--su-ink-40);margin-bottom:8px">
                        <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                        {{ __('instructor.curr_lecture') }} ({{ $availableLectures->count() }})
                    </div>
                    <div class="su-list">
                        @foreach($availableLectures as $lecture)
                            <div class="su-list-item" style="cursor:pointer"
                                 onclick="showAddItemModal('App\\Models\\Lecture', {{ $lecture->id }}, '{{ addslashes($lecture->title) }}', '{{ __('instructor.curr_lecture') }}')">
                                <span class="su-list-item__ico su-soft-1"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></span>
                                <div class="su-list-item__body">
                                    <div class="su-list-item__title">{{ $lecture->title }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($availableAssignments->count() > 0)
                <div style="margin-bottom:16px">
                    <div style="font-size:12px;font-weight:600;color:var(--su-ink-40);margin-bottom:8px">
                        <i class="fas fa-tasks" aria-hidden="true"></i>
                        {{ __('instructor.curr_assignment') }} ({{ $availableAssignments->count() }})
                    </div>
                    <div class="su-list">
                        @foreach($availableAssignments as $assignment)
                            <div class="su-list-item" style="cursor:pointer"
                                 onclick="showAddItemModal('App\\Models\\Assignment', {{ $assignment->id }}, '{{ addslashes($assignment->title) }}', '{{ __('instructor.curr_assignment') }}')">
                                <span class="su-list-item__ico su-soft-2"><i class="fas fa-tasks" aria-hidden="true"></i></span>
                                <div class="su-list-item__body">
                                    <div class="su-list-item__title">{{ $assignment->title }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(isset($availableExams) && $availableExams->count() > 0)
                <div style="margin-bottom:16px">
                    <div style="font-size:12px;font-weight:600;color:var(--su-ink-40);margin-bottom:8px">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                        {{ __('instructor.curr_exam') }} ({{ $availableExams->count() }})
                    </div>
                    <div class="su-list">
                        @foreach($availableExams as $exam)
                            <div class="su-list-item" style="cursor:pointer"
                                 onclick="showAddItemModal('App\\Models\\AdvancedExam', {{ $exam->id }}, '{{ addslashes($exam->title) }}', '{{ __('instructor.curr_exam') }}')">
                                <span class="su-list-item__ico su-soft-3"><i class="fas fa-clipboard-check" aria-hidden="true"></i></span>
                                <div class="su-list-item__body">
                                    <div class="su-list-item__title">{{ $exam->title }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($availableLectures->count() == 0 && $availableAssignments->count() == 0 && (!isset($availableExams) || $availableExams->count() == 0))
                <div class="su-empty" style="padding:24px">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <p>{{ __('instructor.curr_all_items_added') }}</p>
                </div>
            @endif
        </aside>
    </div>
</div>

<!-- Modal إضافة/تعديل قسم -->
<div id="sectionModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-slate-800/95 rounded-2xl p-6 max-w-md w-full shadow-xl border border-slate-200 dark:border-slate-700">
        <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 mb-4" id="modalTitle">إضافة قسم جديد</h3>
        <form id="sectionForm" onsubmit="saveSection(event)">
            <input type="hidden" id="sectionId">
            <input type="hidden" id="sectionParentId" name="parent_id" value="">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">عنوان القسم</label>
                <input type="text" id="sectionTitle" required 
                       class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
            </div>
            <div class="mb-4" id="sectionDescriptionWrap">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">الوصف (اختياري) — للأقسام الرئيسية فقط</label>
                <textarea id="sectionDescription" rows="3"
                          class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100"></textarea>
            </div>
            <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 rounded-xl">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">فتح هذا القسم للطالب</label>
                <p class="text-xs text-slate-600 dark:text-slate-400 mb-2">متى يُسمح للطالب بفتح هذا القسم؟ (القسم التالي لا يفتح إلا بتحقيق الشرط)</p>
                <select id="sectionUnlockRule" onchange="var w=document.getElementById('sectionUnlockPercentWrap');if(w) w.classList.toggle('hidden', this.value !== 'previous_percent');" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100 mb-2">
                    <option value="always">دائماً مفتوح (لا يشترط إكمال قسم سابق)</option>
                    <option value="previous_percent">عند تحقيق نسبة معينة من القسم السابق</option>
                    <option value="previous_all_items">عند إكمال كل عناصر القسم السابق</option>
                </select>
                <div id="sectionUnlockPercentWrap" class="hidden">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">النسبة المئوية من القسم السابق %</label>
                    <input type="number" id="sectionUnlockPercent" min="0" max="100" value="100" placeholder="مثال: 80"
                           class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-100">
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2.5 bg-sky-500 dark:bg-sky-600 hover:bg-sky-600 text-white rounded-xl font-semibold transition-colors">
                    حفظ
                </button>
                <button type="button" onclick="closeSectionModal()" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-colors">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal إضافة محاضرة (عرض الصفحة) -->
<div id="lectureModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto p-4">
    <div class="bg-white dark:bg-slate-800/95 rounded-2xl p-6 w-full max-w-6xl my-8 max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">إضافة محاضرة جديدة</h3>
            <button onclick="closeLectureModal()" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:bg-slate-700/50 hover:text-slate-700 dark:text-slate-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="lectureForm" onsubmit="saveLecture(event)">
            @csrf
            <input type="hidden" id="lectureSectionId">
            <input type="hidden" name="course_id" value="{{ $course->id }}">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">عنوان المحاضرة <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="lectureTitle" required placeholder="مثال: مقدمة في التخطيط للحصة الرقمية"
                           class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">الوصف</label>
                    <textarea name="description" id="lectureDescription" rows="3" placeholder="وصف مختصر للمحاضرة..."
                              class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100"></textarea>
                </div>
                <input type="hidden" name="course_lesson_id" value="">
                <input type="hidden" name="duration_minutes" id="lectureDuration" value="60">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @include('partials.timezone-select', [
                        'value' => old('timezone', auth()->user()?->timezoneCode()),
                        'class' => 'w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100',
                    ])
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">التاريخ والوقت <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="scheduled_at" id="lectureScheduledAt" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">نسبة المشاهدة لفتح الفيديو التالي %</label>
                        <input type="number" name="min_watch_percent_to_unlock_next" id="lectureMinWatchPercent" min="0" max="100" placeholder="مثال: 80 — اترك فارغاً لعدم اشتراط نسبة"
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">يجب على الطالب مشاهدة هذه النسبة من الفيديو ليتاح له الانتقال للمحاضرة التالية (0–100، اختياري)</p>
                    </div>
                </div>
            </div>
            <div class="space-y-5">
                <div class="space-y-3">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"><i class="fas fa-video text-sky-500 ml-1"></i> رابط تسجيل المحاضرة (اختياري)</label>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2">اختر المشغل</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
                            <button type="button" onclick="selectVideoPlatform('bunny', this)"
                                    class="platform-btn p-3 border-2 border-slate-200 dark:border-slate-700 rounded-lg text-center hover:border-sky-400 transition-colors" data-platform="bunny">
                                <i class="fas fa-cloud text-orange-600 text-xl mb-1 block"></i>
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Bunny</span>
                            </button>
                        </div>
                        <input type="hidden" name="video_platform" id="lectureVideoPlatform" value="">
                    </div>
                    <div>
                        <input type="url" name="recording_url" id="lectureRecordingUrl" placeholder="الصق رابط Bunny هنا..." oninput="previewLectureVideo()"
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" id="lectureVideoPlaceholder"></p>
                    </div>
                    <div id="lectureVideoPreview" class="hidden mt-3 bg-black rounded-lg overflow-hidden" style="aspect-ratio: 16/9; max-height: 200px;">
                        <div id="lectureVideoPreviewContent" class="w-full h-full flex items-center justify-center text-white">
                            <i class="fas fa-spinner fa-spin text-2xl"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">الملاحظات</label>
                    <textarea name="notes" id="lectureNotes" rows="3" placeholder="ملاحظات إضافية..."
                              class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100"></textarea>
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 bg-sky-50 dark:bg-sky-900/30 rounded-xl cursor-pointer border border-sky-100">
                        <input type="checkbox" name="has_attendance_tracking" value="1" checked class="w-4 h-4 text-sky-500 border-slate-300 rounded">
                        <span class="font-semibold text-slate-800 dark:text-slate-100">تتبع الحضور</span>
                    </label>
                </div>
                <!-- مواد المحاضرة -->
                <div class="border-t border-slate-200 dark:border-slate-700 pt-5 mt-5">
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2 flex items-center gap-2">
                        <i class="fas fa-paperclip text-sky-500"></i>
                        مواد المحاضرة (اختياري)
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">يمكنك رفع ملفات (PDF، Word، عروض...) وتحديد ظهورها للطالب.</p>
                    <div id="curriculum-materials-container" class="space-y-3">
                        <div class="curriculum-material-row flex flex-wrap items-end gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700">
                            <div class="flex-1 min-w-[160px]">
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">الملف</label>
                                <input type="file" name="material_files[]" class="w-full text-sm text-slate-700 dark:text-slate-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-100 file:text-sky-700 file:text-sm file:font-semibold" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.png,.jpg,.jpeg">
                            </div>
                            <div class="w-40">
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">عنوان (اختياري)</label>
                                <input type="text" name="material_titles[]" placeholder="مثال: ملخص المحاضرة" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg text-sm">
                            </div>
                            <label class="flex items-center gap-2 pb-2">
                                <input type="hidden" name="material_visible[]" value="0">
                                <input type="checkbox" name="material_visible[]" value="1" checked class="w-4 h-4 text-sky-600 rounded">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">ظاهر للطالب</span>
                            </label>
                            <button type="button" class="curriculum-remove-material px-3 py-2 bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-400 rounded-lg text-sm font-medium hover:bg-rose-200" style="display:none;"><i class="fas fa-times ml-1"></i> حذف</button>
                        </div>
                    </div>
                    <button type="button" id="curriculum-add-material" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-sky-100 text-sky-700 rounded-xl font-semibold text-sm hover:bg-sky-200 transition-colors">
                        <i class="fas fa-plus"></i>
                        إضافة مادة أخرى
                    </button>
                </div>
            </div>
            <div class="flex gap-3 mt-6 col-span-full">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-sky-500 dark:bg-sky-600 hover:bg-sky-600 text-white rounded-xl font-semibold transition-colors">
                    <i class="fas fa-save"></i>
                    <span id="lectureSubmitText">حفظ وإضافة للمنهج</span>
                </button>
                <button type="button" onclick="closeLectureModal()" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-colors">
                    إلغاء
                </button>
            </div>
            </div>
            <input type="hidden" id="lectureEditId" name="lecture_id">
            <input type="hidden" name="status" id="lectureStatus" value="scheduled">
        </form>
    </div>
</div>

<!-- Modal أسئلة الفيديو للمحاضرة -->
<div id="videoQuestionsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800/95 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-3xl my-8 max-h-[90vh] overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-amber-50 dark:bg-amber-900/30 flex items-center justify-between shrink-0">
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100" id="videoQuestionsModalTitle"><i class="fas fa-question-circle text-amber-600 ml-1"></i> أسئلة الفيديو</h3>
            <button type="button" onclick="closeVideoQuestionsModal()" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-200"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-4 overflow-y-auto flex-1">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">تظهر الأسئلة للطالب عند الوصول للدقيقة المحددة ويتوقف الفيديو. يمكن استيراد سؤال من البنك أو كتابة سؤال مخصص.</p>
            <div id="videoQuestionsList" class="space-y-2 mb-6"></div>
            <hr class="border-slate-200 dark:border-slate-700 my-4">
            <h4 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-3">إضافة سؤال جديد</h4>
            <form id="videoQuestionForm" onsubmit="submitVideoQuestion(event)" class="space-y-4">
                @csrf
                <input type="hidden" id="vqLectureId" name="lecture_id" value="">
                <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700">
                    <label class="inline-flex items-center gap-2 cursor-pointer flex-1">
                        <input type="checkbox" id="vqShowAtEnd" name="show_at_end" value="1" class="w-4 h-4 text-amber-500 rounded border-slate-300" onchange="toggleVqTimestampFields()">
                        <span class="font-semibold text-slate-800 dark:text-slate-100">يظهر السؤال في نهاية الفيديو</span>
                    </label>
                    <span class="text-xs text-slate-500 dark:text-slate-400">لا حاجة لتحديد الدقيقة عند التفعيل</span>
                </div>
                <div id="vqTimestampWrap" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الدقيقة في الفيديو <span class="text-red-500">*</span></label>
                        <input type="number" id="vqTimestampMinutes" name="timestamp_minutes" min="0" max="999" value="0" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">ثوانٍ إضافية (0–59)</label>
                        <input type="number" id="vqTimestampSeconds" name="timestamp_seconds_extra" min="0" max="59" value="0" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">مصدر السؤال</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="question_source" value="bank" class="text-sky-500" onchange="toggleVqSource('bank')"> من بنك الأسئلة</label>
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="question_source" value="custom" checked class="text-sky-500" onchange="toggleVqSource('custom')"> سؤال مخصص</label>
                    </div>
                </div>
                <div id="vqBankWrap" class="hidden space-y-2">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">البنك</label>
                    <select id="vqBankId" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100">
                        <option value="">-- اختر البنك --</option>
                    </select>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">السؤال</label>
                    <select name="question_id" id="vqQuestionId" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100">
                        <option value="">-- اختر السؤال --</option>
                    </select>
                </div>
                <div id="vqCustomWrap" class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">نص السؤال <span class="text-red-500">*</span></label>
                    <textarea id="vqCustomText" name="custom_question_text" rows="2" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100" placeholder="اكتب السؤال..."></textarea>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">الخيارات (سطر لكل خيار)</label>
                    <textarea id="vqCustomOptions" name="custom_options_text" rows="3" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 font-mono text-sm" placeholder="الخيار أ&#10;الخيار ب&#10;الخيار ج"></textarea>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">الإجابة الصحيحة <span class="text-red-500">*</span></label>
                    <input type="text" id="vqCustomCorrect" name="custom_correct_answer" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100" placeholder="نفس النص كما في أحد الخيارات">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">عند الإجابة الخاطئة</label>
                        <select id="vqOnWrong" name="on_wrong" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100" onchange="toggleVqRewind()">
                            <option value="continue">متابعة الفيديو</option>
                            <option value="rewind">إعادة جزء من الفيديو</option>
                        </select>
                    </div>
                    <div id="vqRewindWrap" class="hidden">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">كم ثانية للرجوع؟</label>
                        <input type="number" name="rewind_seconds" id="vqRewindSeconds" min="0" max="3600" value="0" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">النقاط</label>
                        <input type="number" name="points" id="vqPoints" value="1" min="1" max="100" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">عدد مرات الظهور</label>
                        <select name="show_count" id="vqShowCount" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100">
                            <option value="0">كل مرة (عند كل مشاهدة)</option>
                            <option value="1" selected>مرة واحدة فقط</option>
                            <option value="2">مرتين</option>
                            <option value="3">3 مرات</option>
                            <option value="5">5 مرات</option>
                            <option value="10">10 مرات</option>
                        </select>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">متى يظهر السؤال للطالب أثناء المشاهدة</p>
                    </div>
                </div>
                <button type="submit" class="w-full py-2.5 bg-amber-500 dark:bg-amber-600 hover:bg-amber-600 text-white rounded-xl font-semibold transition-colors">
                    <i class="fas fa-plus ml-1"></i> إضافة السؤال
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal إضافة امتحان من المنهج (عريض) -->
<div id="examModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800/95 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-4xl my-8">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center text-violet-600"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">إضافة امتحان جديد</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">يُضاف لهذا القسم في الكورس الحالي</p>
                </div>
            </div>
            <button type="button" onclick="closeExamModal()" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-200 transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <form id="examForm" onsubmit="saveExam(event)">
            @csrf
            <input type="hidden" name="section_id" id="examSectionId" value="">
            <input type="hidden" name="course_lesson_id" value="">
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">عنوان الامتحان <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="examTitle" required placeholder="مثال: اختبار الوحدة الأولى"
                           class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الوصف</label>
                    <textarea name="description" id="examDescription" rows="3" placeholder="وصف مختصر..."
                              class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الدرجة الكلية <span class="text-red-500">*</span></label>
                        <input type="number" name="total_marks" id="examTotalMarks" value="100" min="1" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">درجة النجاح <span class="text-red-500">*</span></label>
                        <input type="number" name="passing_marks" id="examPassingMarks" value="60" min="0" step="0.5" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">المدة (دقيقة) <span class="text-red-500">*</span></label>
                        <input type="number" name="duration_minutes" id="examDuration" value="60" min="5" max="480" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">المحاولات <span class="text-red-500">*</span></label>
                        <input type="number" name="attempts_allowed" id="examAttempts" value="1" min="1" max="10" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex gap-3">
                <button type="submit" id="examSubmitBtn" class="flex-1 px-4 py-2.5 bg-violet-600 dark:bg-violet-700 hover:bg-violet-600 text-white rounded-xl font-semibold transition-colors">
                    <i class="fas fa-plus ml-1"></i> إنشاء وإضافة للمنهج
                </button>
                <button type="button" onclick="closeExamModal()" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-colors">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal إنشاء واجب وإضافة للمنهج (عريض) -->
<div id="assignmentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800/95 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-4xl my-8">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-600"><i class="fas fa-tasks"></i></div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">إنشاء واجب جديد</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">يُضاف مباشرة لهذا القسم في الكورس الحالي</p>
                </div>
            </div>
            <button type="button" onclick="closeAssignmentModal()" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-200 transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <form id="assignmentFormCurriculum" onsubmit="saveAssignment(event)">
            <input type="hidden" name="section_id" id="assignmentSectionId" value="">
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">عنوان الواجب <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="assignmentTitle" required placeholder="مثال: واجب الوحدة الأولى"
                           class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الوصف</label>
                    <textarea name="description" id="assignmentDescription" rows="2" placeholder="وصف مختصر..."
                              class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-slate-800 dark:text-slate-100 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">التعليمات</label>
                    <textarea name="instructions" id="assignmentInstructions" rows="2" placeholder="تعليمات للطلاب..."
                              class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-slate-800 dark:text-slate-100 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">تاريخ الاستحقاق</label>
                    <input type="datetime-local" name="due_date" id="assignmentDueDate"
                           class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الدرجة الكلية <span class="text-red-500">*</span></label>
                    <input type="number" name="max_score" id="assignmentMaxScore" value="100" min="1" max="1000" required
                           class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-slate-800 dark:text-slate-100">
                </div>
                <div class="flex items-end gap-4 md:col-span-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="allow_late_submission" id="assignmentAllowLate" value="1"
                               class="w-4 h-4 rounded border-slate-300 text-sky-500 focus:ring-sky-500/20">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">السماح بالتسليم المتأخر</span>
                    </label>
                    <div class="w-40">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">الحالة</label>
                        <select name="status" id="assignmentStatus"
                                class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-slate-800 dark:text-slate-100">
                            <option value="draft">مسودة</option>
                            <option value="published">منشور</option>
                            <option value="archived">مؤرشف</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex gap-3">
                <button type="submit" id="assignmentSubmitBtn" class="flex-1 px-4 py-2.5 bg-emerald-600 dark:bg-emerald-700 hover:bg-emerald-600 text-white rounded-xl font-semibold transition-colors">
                    <i class="fas fa-plus ml-1"></i> إنشاء وإضافة للمنهج
                </button>
                <button type="button" onclick="closeAssignmentModal()" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-colors">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal إضافة عنصر -->
<div id="itemModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-slate-800/95 rounded-2xl p-6 max-w-md w-full shadow-xl border border-slate-200 dark:border-slate-700">
        <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 mb-2">إضافة عنصر للمنهج</h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4" id="itemName"></p>
        <form id="itemForm" onsubmit="addItem(event)">
            <input type="hidden" id="itemType">
            <input type="hidden" id="itemId">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">اختر القسم</label>
                <select id="targetSection" required
                        class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 text-slate-800 dark:text-slate-100">
                    <option value="">اختر القسم</option>
                    @foreach($sectionsFlatForSelect as $entry)
                        <option value="{{ $entry->section->id }}">{{ str_repeat('— ', $entry->depth) }}{{ $entry->section->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2.5 bg-sky-500 dark:bg-sky-600 hover:bg-sky-600 text-white rounded-xl font-semibold transition-colors">
                    إضافة
                </button>
                <button type="button" onclick="closeItemModal()" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-colors">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
let currentSectionId = null;
let currentItemType = null;
let currentItemId = null;
let selectedVideoPlatform = '';

function toggleSection(sectionId) {
    const block = document.querySelector('.section-block[data-section-id="' + sectionId + '"]');
    if (!block) return;
    block.classList.toggle('collapsed');
    const chevron = document.querySelector('.section-chevron[data-section-id="' + sectionId + '"]');
    if (chevron) chevron.style.transform = block.classList.contains('collapsed') ? 'rotate(-90deg)' : '';
}

function showAddExamModal(sectionId) {
    document.getElementById('examSectionId').value = sectionId;
    document.getElementById('examForm').reset();
    document.getElementById('examSectionId').value = sectionId;
    document.getElementById('examTotalMarks').value = 100;
    document.getElementById('examPassingMarks').value = 60;
    document.getElementById('examDuration').value = 60;
    document.getElementById('examAttempts').value = 1;
    document.getElementById('examModal').classList.remove('hidden');
    document.getElementById('examModal').classList.add('flex');
}

function closeExamModal() {
    document.getElementById('examModal').classList.add('hidden');
    document.getElementById('examModal').classList.remove('flex');
}

function showAddAssignmentModal(sectionId) {
    document.getElementById('assignmentSectionId').value = sectionId;
    document.getElementById('assignmentFormCurriculum').reset();
    document.getElementById('assignmentSectionId').value = sectionId;
    document.getElementById('assignmentMaxScore').value = 100;
    document.getElementById('assignmentModal').classList.remove('hidden');
    document.getElementById('assignmentModal').classList.add('flex');
}

function closeAssignmentModal() {
    document.getElementById('assignmentModal').classList.add('hidden');
    document.getElementById('assignmentModal').classList.remove('flex');
}

function saveAssignment(e) {
    e.preventDefault();
    var form = document.getElementById('assignmentFormCurriculum');
    var btn = document.getElementById('assignmentSubmitBtn');
    var formData = new FormData(form);
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> جاري الحفظ...';
    fetch('{{ route("instructor.courses.curriculum.assignments.store", $course) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            closeAssignmentModal();
            location.reload();
        } else {
            alert(data.message || 'حدث خطأ');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus ml-1"></i> إنشاء وإضافة للمنهج';
        }
    })
    .catch(function() {
        alert('حدث خطأ في الاتصال');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus ml-1"></i> إنشاء وإضافة للمنهج';
    });
}

function saveExam(e) {
    e.preventDefault();
    const form = document.getElementById('examForm');
    const btn = document.getElementById('examSubmitBtn');
    const sectionId = document.getElementById('examSectionId').value;
    const totalMarks = parseFloat(document.getElementById('examTotalMarks').value) || 100;
    const passingMarks = parseFloat(document.getElementById('examPassingMarks').value) || 60;
    if (passingMarks > totalMarks) {
        alert('درجة النجاح يجب ألا تتجاوز الدرجة الكلية');
        return;
    }
    const formData = new FormData(form);
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> جاري الحفظ...';
    fetch('{{ route("instructor.courses.curriculum.exams.store", $course) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeExamModal();
            if (data.redirect) {
                if (confirm('تم إنشاء الامتحان بنجاح. هل تريد الانتقال الآن لإضافة الأسئلة؟')) {
                    window.location.href = data.redirect;
                } else {
                    location.reload();
                }
            } else {
                location.reload();
            }
        } else {
            alert(data.message || 'حدث خطأ');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus ml-1"></i> إنشاء وإضافة للمنهج';
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ في الاتصال');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus ml-1"></i> إنشاء وإضافة للمنهج';
    });
}

function showAddSectionModal() {
    document.getElementById('sectionId').value = '';
    document.getElementById('sectionParentId').value = '';
    document.getElementById('sectionTitle').value = '';
    document.getElementById('sectionDescription').value = '';
    var wrap = document.getElementById('sectionDescriptionWrap');
    if (wrap) wrap.style.display = '';
    document.getElementById('modalTitle').textContent = 'إضافة قسم جديد';
    var ruleEl = document.getElementById('sectionUnlockRule');
    var percentWrap = document.getElementById('sectionUnlockPercentWrap');
    if (ruleEl) ruleEl.value = 'previous_all_items';
    if (percentWrap) percentWrap.classList.add('hidden');
    document.getElementById('sectionModal').classList.remove('hidden');
    document.getElementById('sectionModal').classList.add('flex');
}

function showAddSubSectionModal(parentId) {
    document.getElementById('sectionId').value = '';
    document.getElementById('sectionParentId').value = parentId || '';
    document.getElementById('sectionTitle').value = '';
    document.getElementById('sectionDescription').value = '';
    var wrap = document.getElementById('sectionDescriptionWrap');
    if (wrap) wrap.style.display = 'none';
    document.getElementById('modalTitle').textContent = 'إضافة قسم فرعي';
    var ruleEl = document.getElementById('sectionUnlockRule');
    var percentWrap = document.getElementById('sectionUnlockPercentWrap');
    if (ruleEl) ruleEl.value = 'previous_all_items';
    if (percentWrap) percentWrap.classList.add('hidden');
    document.getElementById('sectionModal').classList.remove('hidden');
    document.getElementById('sectionModal').classList.add('flex');
}

function editSection(id, title, description, parentId, unlockRule, unlockPercent) {
    document.getElementById('sectionId').value = id;
    document.getElementById('sectionParentId').value = parentId || '';
    document.getElementById('sectionTitle').value = title;
    document.getElementById('sectionDescription').value = description || '';
    var wrap = document.getElementById('sectionDescriptionWrap');
    if (wrap) wrap.style.display = (parentId ? 'none' : '');
    var ruleEl = document.getElementById('sectionUnlockRule');
    var percentWrap = document.getElementById('sectionUnlockPercentWrap');
    var percentEl = document.getElementById('sectionUnlockPercent');
    if (ruleEl) ruleEl.value = unlockRule || 'previous_all_items';
    if (percentEl) percentEl.value = (unlockPercent != null && unlockPercent !== '') ? unlockPercent : 100;
    if (percentWrap) percentWrap.classList.toggle('hidden', ruleEl && ruleEl.value !== 'previous_percent');
    document.getElementById('modalTitle').textContent = 'تعديل القسم';
    document.getElementById('sectionModal').classList.remove('hidden');
    document.getElementById('sectionModal').classList.add('flex');
}

function closeSectionModal() {
    document.getElementById('sectionModal').classList.add('hidden');
    document.getElementById('sectionModal').classList.remove('flex');
}

function saveSection(e) {
    e.preventDefault();
    const id = document.getElementById('sectionId').value;
    const title = document.getElementById('sectionTitle').value;
    const description = document.getElementById('sectionDescription').value;
    const parentIdEl = document.getElementById('sectionParentId');
    const parentId = parentIdEl && parentIdEl.value ? parentIdEl.value : null;
    const unlockRule = document.getElementById('sectionUnlockRule').value || 'previous_all_items';
    const unlockPercentEl = document.getElementById('sectionUnlockPercent');
    const unlockPercent = (unlockRule === 'previous_percent' && unlockPercentEl) ? parseInt(unlockPercentEl.value, 10) : null;
    
    const url = id 
        ? `/instructor/sections/${id}`
        : `/instructor/courses/{{ $course->id }}/sections`;
    const method = id ? 'PUT' : 'POST';
    const body = id 
        ? { title, description, unlock_rule: unlockRule, unlock_percent: (unlockRule === 'previous_percent' ? unlockPercent : null) } 
        : { title, description, parent_id: parentId };
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(body)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('حدث خطأ: ' + (data.message || 'خطأ غير معروف'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ أثناء الحفظ');
    });
}

function deleteSection(id) {
    if (!confirm('هل أنت متأكد من حذف هذا القسم؟ سيتم حذف جميع العناصر والأقسام الفرعية بداخله.')) return;
    
    fetch(`/instructor/sections/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('حدث خطأ: ' + (data.message || 'خطأ غير معروف'));
        }
    });
}

function showAddItemModal(type, id, name, typeName) {
    currentItemType = type;
    currentItemId = id;
    document.getElementById('itemType').value = type;
    document.getElementById('itemId').value = id;
    document.getElementById('itemName').textContent = `إضافة ${typeName}: ${name}`;
    document.getElementById('targetSection').value = '';
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
}

function closeItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
    document.getElementById('itemModal').classList.remove('flex');
}

function addItem(e) {
    e.preventDefault();
    const sectionId = document.getElementById('targetSection').value;
    
    fetch(`/instructor/sections/${sectionId}/items`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            item_type: currentItemType,
            item_id: currentItemId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('حدث خطأ: ' + (data.message || 'خطأ غير معروف'));
        }
    });
}

function removeItem(id) {
    if (!confirm('هل أنت متأكد من حذف هذا العنصر من المنهج؟')) return;
    
    fetch(`/instructor/curriculum-items/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('حدث خطأ: ' + (data.message || 'خطأ غير معروف'));
        }
    });
}

function showAddLectureModal(sectionId) {
    // Important: this function must never throw, otherwise the modal won't open.
    try {
        currentSectionId = sectionId;
        const sectionInput = document.getElementById('lectureSectionId');
        if (sectionInput) sectionInput.value = sectionId;

        const editIdInput = document.getElementById('lectureEditId');
        if (editIdInput) editIdInput.value = '';

        const form = document.getElementById('lectureForm');
        if (form && form.reset) form.reset();

        const courseIdInput = form ? form.querySelector('input[name="course_id"]') : null;
        if (courseIdInput) courseIdInput.value = {{ $course->id }};

        const statusEl = document.getElementById('lectureStatus');
        if (statusEl) statusEl.value = 'scheduled';

        const hasAttendance = form ? form.querySelector('input[name="has_attendance_tracking"]') : null;
        if (hasAttendance) hasAttendance.checked = true;

        const scheduledAt = document.getElementById('lectureScheduledAt');
        if (scheduledAt) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            scheduledAt.value = now.toISOString().slice(0, 16);
        }

        const durationEl = document.getElementById('lectureDuration');
        if (durationEl) durationEl.value = 60;

        var minWatchEl = document.getElementById('lectureMinWatchPercent');
        if (minWatchEl) minWatchEl.value = '';

        const titleEl = document.querySelector('#lectureModal h3');
        if (titleEl) titleEl.textContent = 'إضافة محاضرة جديدة';
        const submitTextEl = document.getElementById('lectureSubmitText');
        if (submitTextEl) submitTextEl.textContent = 'حفظ وإضافة للمنهج';

        // Reset platform state (if present)
        selectedVideoPlatform = '';
        const platformEl = document.getElementById('lectureVideoPlatform');
        if (platformEl) platformEl.value = '';
        const previewEl = document.getElementById('lectureVideoPreview');
        if (previewEl) previewEl.classList.add('hidden');
        document.querySelectorAll('.platform-btn').forEach(btn => {
            btn.classList.remove('border-sky-500', 'bg-sky-50 dark:bg-sky-900/30');
            btn.classList.add('border-slate-200', 'dark:border-slate-700');
        });
    } catch (e) {
        console.error('showAddLectureModal failed:', e);
    } finally {
        const modal = document.getElementById('lectureModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            alert('تعذر فتح نافذة المحاضرة: lectureModal غير موجود.');
        }
    }
}

// تعديل المحاضرة من باني الدورات
async function editLectureFromCurriculum(lectureId, sectionId) {
    try {
        const response = await fetch(`/instructor/lectures/${lectureId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            // إذا لم يكن JSON، جرب تحميل الصفحة
            window.location.href = `/instructor/lectures/${lectureId}/edit`;
            return;
        }
        
        const lecture = await response.json();
        
        console.log('=== Loaded lecture data ===');
        console.log('Full lecture object:', lecture);
        console.log('recording_url:', lecture.recording_url);
        console.log('video_platform:', lecture.video_platform);
        console.log('recording_url type:', typeof lecture.recording_url);
        console.log('recording_url length:', lecture.recording_url ? lecture.recording_url.length : 0);
        
        // ملء النموذج
        document.getElementById('lectureEditId').value = lectureId;
        document.getElementById('lectureSectionId').value = sectionId;
        document.getElementById('lectureTitle').value = lecture.title || '';
        document.getElementById('lectureDescription').value = lecture.description || '';
        // تحويل التاريخ
        if (lecture.scheduled_at) {
            var tzEl = document.querySelector('[data-timezone-select]');
            var tz = tzEl && tzEl.value ? tzEl.value : 'UTC';
            if (window.hesetakDateTimeLocal) {
                document.getElementById('lectureScheduledAt').value = window.hesetakDateTimeLocal(lecture.scheduled_at, tz);
            } else {
                const scheduledDate = new Date(lecture.scheduled_at);
                scheduledDate.setMinutes(scheduledDate.getMinutes() - scheduledDate.getTimezoneOffset());
                document.getElementById('lectureScheduledAt').value = scheduledDate.toISOString().slice(0, 16);
            }
        }
        
        document.getElementById('lectureDuration').value = lecture.duration_minutes || 60;
        var minWatchEl = document.getElementById('lectureMinWatchPercent');
        if (minWatchEl) minWatchEl.value = (lecture.min_watch_percent_to_unlock_next != null && lecture.min_watch_percent_to_unlock_next !== '') ? lecture.min_watch_percent_to_unlock_next : '';
        
        // تعيين الرابط - مهم جداً - نستخدم setTimeout لضمان أن الحقل موجود
        setTimeout(() => {
            const recordingUrlInput = document.getElementById('lectureRecordingUrl');
            if (recordingUrlInput) {
                const urlValue = lecture.recording_url || '';
                recordingUrlInput.value = urlValue;
                console.log('Set recording_url input to:', urlValue);
                console.log('Input value after setting:', recordingUrlInput.value);
                
                // إطلاق event لتفعيل oninput إذا كان موجوداً
                recordingUrlInput.dispatchEvent(new Event('input', { bubbles: true }));
            } else {
                console.error('lectureRecordingUrl input not found!');
            }
        }, 100);
        
        document.getElementById('lectureNotes').value = lecture.notes || '';
        
        // تعيين حالة المحاضرة (مطلوب عند التحديث)
        const statusInput = document.getElementById('lectureStatus');
        if (statusInput) statusInput.value = lecture.status || 'scheduled';
        
        // تحديد المشغل - تطبيع video_platform لأحرف صغيرة لأن data-platform في HTML كلها lowercase
        setTimeout(() => {
            let platformSet = false;
            const platformNormalized = (lecture.video_platform || '').toString().trim().toLowerCase();
            
            // أولاً: محاولة استخدام video_platform المحفوظ (بعد التطبيع)
            if (platformNormalized) {
                const platformBtn = document.querySelector('[data-platform="' + platformNormalized + '"]');
                if (platformBtn) {
                    const savedUrl = lecture.recording_url || '';
                    selectVideoPlatform(platformNormalized, platformBtn);
                    document.getElementById('lectureVideoPlatform').value = platformNormalized;
                    setTimeout(() => {
                        const recordingUrlInput = document.getElementById('lectureRecordingUrl');
                        if (recordingUrlInput && savedUrl) recordingUrlInput.value = savedUrl;
                    }, 50);
                    platformSet = true;
                }
            }
            
            // ثانياً: إذا لم يتم تعيين المشغل، اكتشفه من الرابط
            if (!platformSet && lecture.recording_url) {
                const url = lecture.recording_url;
                let detectedPlatform = null;
                let platformBtn = null;
                
                if (url.includes('youtube.com') || url.includes('youtu.be')) {
                    detectedPlatform = 'youtube';
                    platformBtn = document.querySelector('[data-platform="youtube"]');
                } else if (url.includes('vimeo.com')) {
                    detectedPlatform = 'vimeo';
                    platformBtn = document.querySelector('[data-platform="vimeo"]');
                } else if (url.includes('drive.google.com')) {
                    detectedPlatform = 'google_drive';
                    platformBtn = document.querySelector('[data-platform="google_drive"]');
                } else if (url.includes('mediadelivery.net')) {
                    detectedPlatform = 'bunny';
                    platformBtn = document.querySelector('[data-platform="bunny"]');
                } else if (url.match(/\.(mp4|webm|ogg|avi|mov)(\?.*)?$/i)) {
                    detectedPlatform = 'direct';
                    platformBtn = document.querySelector('[data-platform="direct"]');
                }
                
                if (platformBtn && detectedPlatform) {
                    const savedUrl = lecture.recording_url || '';
                    selectVideoPlatform(detectedPlatform, platformBtn);
                    document.getElementById('lectureVideoPlatform').value = detectedPlatform;
                    setTimeout(() => {
                        const recordingUrlInput = document.getElementById('lectureRecordingUrl');
                        if (recordingUrlInput && savedUrl) recordingUrlInput.value = savedUrl;
                    }, 50);
                    platformSet = true;
                }
            }
            
            // معاينة الفيديو إذا كان موجوداً - بعد تعيين المشغل والرابط
            if (lecture.recording_url) {
                console.log('Setting up video preview for URL:', lecture.recording_url);
                setTimeout(() => {
                    previewLectureVideo();
                }, 400);
            } else {
                console.warn('No recording_url found in lecture data');
            }
        }, 200);
        
        // تحديث الخيارات
        const hasAttendance = document.querySelector('input[name="has_attendance_tracking"]');
        const hasAssignment = document.querySelector('input[name="has_assignment"]');
        const hasEvaluation = document.querySelector('input[name="has_evaluation"]');
        
        if (hasAttendance) hasAttendance.checked = lecture.has_attendance_tracking || false;
        if (hasAssignment) hasAssignment.checked = lecture.has_assignment || false;
        if (hasEvaluation) hasEvaluation.checked = lecture.has_evaluation || false;
        
        // تحديث العنوان والنص
        document.querySelector('#lectureModal h3').textContent = 'تعديل المحاضرة';
        document.getElementById('lectureSubmitText').textContent = 'حفظ التعديلات';
        
        // فتح Modal
        document.getElementById('lectureModal').classList.remove('hidden');
        document.getElementById('lectureModal').classList.add('flex');
        
    } catch (error) {
        console.error('Error loading lecture:', error);
        // في حالة الخطأ، افتح صفحة التعديل
        window.location.href = `/instructor/lectures/${lectureId}/edit`;
    }
}

// حذف المحاضرة من باني الدورات
async function deleteLectureFromCurriculum(lectureId, curriculumItemId) {
    if (!confirm('هل أنت متأكد من حذف هذه المحاضرة؟ سيتم حذفها من المنهج أيضاً.')) {
        return;
    }
    
    try {
        const response = await fetch(`/instructor/lectures/${lectureId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('فشل حذف المحاضرة');
        }
        
        const data = await response.json();
        
        if (data.success || response.ok) {
            // حذف العنصر من المنهج أيضاً
            if (curriculumItemId) {
                await removeItem(curriculumItemId);
            } else {
                location.reload();
            }
        } else {
            throw new Error(data.message || 'فشل حذف المحاضرة');
        }
    } catch (error) {
        console.error('Error deleting lecture:', error);
        alert('حدث خطأ أثناء حذف المحاضرة: ' + error.message);
    }
}

// --- أسئلة الفيديو للمحاضرة ---
let videoQuestionsLectureId = null;
let videoQuestionsBankData = {};

function openVideoQuestionsModal(lectureId, title) {
    videoQuestionsLectureId = lectureId;
    document.getElementById('videoQuestionsModalTitle').textContent = 'أسئلة الفيديو — ' + (title || 'المحاضرة');
    document.getElementById('vqLectureId').value = lectureId;
    var showAtEndEl = document.getElementById('vqShowAtEnd');
    if (showAtEndEl) { showAtEndEl.checked = false; toggleVqTimestampFields(); }
    document.getElementById('videoQuestionsModal').classList.remove('hidden');
    document.getElementById('videoQuestionsModal').classList.add('flex');
    loadVideoQuestionsData(lectureId);
}

function closeVideoQuestionsModal() {
    document.getElementById('videoQuestionsModal').classList.add('hidden');
    document.getElementById('videoQuestionsModal').classList.remove('flex');
    videoQuestionsLectureId = null;
}

function toggleVqSource(source) {
    document.getElementById('vqBankWrap').classList.toggle('hidden', source !== 'bank');
    document.getElementById('vqCustomWrap').classList.toggle('hidden', source !== 'custom');
}
function toggleVqTimestampFields() {
    var showAtEnd = document.getElementById('vqShowAtEnd').checked;
    var wrap = document.getElementById('vqTimestampWrap');
    var minutesInput = document.getElementById('vqTimestampMinutes');
    if (wrap) wrap.style.display = showAtEnd ? 'none' : 'grid';
    if (minutesInput) minutesInput.required = !showAtEnd;
}
function toggleVqRewind() {
    var onWrong = document.getElementById('vqOnWrong').value;
    document.getElementById('vqRewindWrap').classList.toggle('hidden', onWrong !== 'rewind');
}

async function loadVideoQuestionsData(lectureId) {
    var listEl = document.getElementById('videoQuestionsList');
    listEl.innerHTML = '<p class="text-slate-500 dark:text-slate-400 text-sm"><i class="fas fa-spinner fa-spin ml-1"></i> جاري التحميل...</p>';
    try {
        var res = await fetch('/instructor/lectures/' + lectureId + '/video-questions', { headers: { 'Accept': 'application/json' } });
        var data = await res.json();
        videoQuestionsBankData = data.bank_questions || {};
        var banks = data.question_banks || [];
        var bankSelect = document.getElementById('vqBankId');
        bankSelect.innerHTML = '<option value="">-- اختر البنك --</option>';
        banks.forEach(function(b) { bankSelect.innerHTML += '<option value="' + b.id + '">' + (b.title || '') + '</option>'; });
        listEl.innerHTML = '';
        (data.video_questions || []).forEach(function(q) {
            var row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700';
            row.innerHTML = '<div class="min-w-0 flex-1"><span class="font-semibold text-slate-800 dark:text-slate-100">' + (q.timestamp_label || '0:00') + '</span><span class="text-slate-500 dark:text-slate-400 mx-2">—</span><span class="text-slate-700 dark:text-slate-300 text-sm truncate">' + (q.question_text || '').substring(0, 60) + (q.question_text && q.question_text.length > 60 ? '...' : '') + '</span><span class="text-xs text-slate-400 mr-2">(' + (q.on_wrong === 'rewind' ? 'إعادة' : 'متابعة') + ')</span><span class="text-xs text-amber-600 mr-2">· ' + (q.show_count_label || 'مرة واحدة') + '</span></div><button type="button" onclick="deleteVideoQuestion(' + lectureId + ',' + q.id + ')" class="p-2 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 text-sm"><i class="fas fa-trash"></i></button>';
            listEl.appendChild(row);
        });
        if ((data.video_questions || []).length === 0) listEl.innerHTML = '<p class="text-slate-500 dark:text-slate-400 text-sm">لا توجد أسئلة بعد. أضف سؤالاً أدناه.</p>';
        document.getElementById('vqQuestionId').innerHTML = '<option value="">-- اختر السؤال --</option>';
        document.getElementById('vqQuestionId').dispatchEvent(new Event('change'));
    } catch (e) {
        listEl.innerHTML = '<p class="text-red-600 text-sm">فشل تحميل البيانات.</p>';
    }
}

document.getElementById('vqBankId').addEventListener('change', function() {
    var bankId = this.value;
    var qSelect = document.getElementById('vqQuestionId');
    qSelect.innerHTML = '<option value="">-- اختر السؤال --</option>';
    var questions = videoQuestionsBankData[bankId] || [];
    questions.forEach(function(q) {
        qSelect.innerHTML += '<option value="' + q.id + '">' + (q.text || '').substring(0, 50) + (q.text && q.text.length > 50 ? '...' : '') + '</option>';
    });
});

async function submitVideoQuestion(e) {
    e.preventDefault();
    var form = document.getElementById('videoQuestionForm');
    var source = form.querySelector('input[name="question_source"]:checked').value;
    var showAtEnd = document.getElementById('vqShowAtEnd') && document.getElementById('vqShowAtEnd').checked;
    var payload = {
        _token: '{{ csrf_token() }}',
        show_at_end: showAtEnd,
        timestamp_minutes: document.getElementById('vqTimestampMinutes').value,
        timestamp_seconds_extra: document.getElementById('vqTimestampSeconds').value,
        question_source: source,
        on_wrong: document.getElementById('vqOnWrong').value,
        rewind_seconds: document.getElementById('vqRewindSeconds').value || 0,
        points: document.getElementById('vqPoints').value || 1,
        show_count: document.getElementById('vqShowCount').value !== undefined ? document.getElementById('vqShowCount').value : '1'
    };
    if (source === 'bank') {
        payload.question_id = document.getElementById('vqQuestionId').value;
        if (!payload.question_id) { alert('اختر سؤالاً من البنك'); return; }
    } else {
        payload.custom_question_text = document.getElementById('vqCustomText').value.trim();
        payload.custom_correct_answer = document.getElementById('vqCustomCorrect').value.trim();
        var opts = document.getElementById('vqCustomOptions').value.split(/\n/).map(function(s) { return s.trim(); }).filter(Boolean);
        payload.custom_options = opts;
        if (!payload.custom_question_text || !payload.custom_correct_answer) { alert('أدخل نص السؤال والإجابة الصحيحة'); return; }
    }
    try {
        var res = await fetch('/instructor/lectures/' + videoQuestionsLectureId + '/video-questions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });
        var data = await res.json();
        if (!data.success) { alert(data.message || 'فشل الإضافة'); return; }
        var q = data.question;
        var listEl = document.getElementById('videoQuestionsList');
        if (listEl.querySelector('p')) listEl.innerHTML = '';
        var row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700';
        row.innerHTML = '<div class="min-w-0 flex-1"><span class="font-semibold text-slate-800 dark:text-slate-100">' + (q.timestamp_label || '0:00') + '</span><span class="text-slate-500 dark:text-slate-400 mx-2">—</span><span class="text-slate-700 dark:text-slate-300 text-sm truncate">' + (q.question_text || '').substring(0, 60) + (q.question_text && q.question_text.length > 60 ? '...' : '') + '</span><span class="text-xs text-slate-400 mr-2">(' + (q.on_wrong === 'rewind' ? 'إعادة' : 'متابعة') + ')</span><span class="text-xs text-amber-600 mr-2">· ' + (q.show_count_label || 'مرة واحدة') + '</span></div><button type="button" onclick="deleteVideoQuestion(' + videoQuestionsLectureId + ',' + q.id + ')" class="p-2 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 text-sm"><i class="fas fa-trash"></i></button>';
        listEl.appendChild(row);
        form.reset();
        document.getElementById('vqTimestampMinutes').value = 0;
        document.getElementById('vqTimestampSeconds').value = 0;
        document.getElementById('vqPoints').value = 1;
        document.getElementById('vqShowCount').value = '1';
        var showAtEndEl = document.getElementById('vqShowAtEnd');
        if (showAtEndEl) { showAtEndEl.checked = false; toggleVqTimestampFields(); }
    } catch (err) {
        alert('حدث خطأ: ' + (err.message || 'فشل الإضافة'));
    }
}

async function deleteVideoQuestion(lectureId, vqId) {
    if (!confirm('حذف هذا السؤال؟')) return;
    try {
        var res = await fetch('/instructor/lectures/' + lectureId + '/video-questions/' + vqId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        var data = await res.json();
        if (data.success) loadVideoQuestionsData(lectureId);
        else alert(data.message || 'فشل الحذف');
    } catch (e) {
        alert('فشل الحذف');
    }
}

function closeLectureModal() {
    document.getElementById('lectureModal').classList.add('hidden');
    document.getElementById('lectureModal').classList.remove('flex');
    document.getElementById('lectureForm').reset();
    document.getElementById('lectureEditId').value = '';
    document.getElementById('lectureSectionId').value = '';
    currentSectionId = null;
    // إعادة صفوف المواد إلى صف واحد
    var matContainer = document.getElementById('curriculum-materials-container');
    if (matContainer) {
        var rows = matContainer.querySelectorAll('.curriculum-material-row');
        for (var i = 1; i < rows.length; i++) rows[i].remove();
        var firstRemove = matContainer.querySelector('.curriculum-remove-material');
        if (firstRemove) firstRemove.style.display = 'none';
    }
    // إعادة تعيين المشغل
    selectedVideoPlatform = '';
    document.getElementById('lectureVideoPlatform').value = '';
    document.getElementById('lectureVideoPreview').classList.add('hidden');
    document.querySelectorAll('.platform-btn').forEach(btn => {
        btn.classList.remove('border-sky-500', 'bg-sky-50 dark:bg-sky-900/30');
        btn.classList.add('border-slate-200 dark:border-slate-700');
    });
}

(function initCurriculumMaterials() {
    var container = document.getElementById('curriculum-materials-container');
    var addBtn = document.getElementById('curriculum-add-material');
    if (container && addBtn) {
        addBtn.addEventListener('click', function() {
            var first = container.querySelector('.curriculum-material-row');
            if (!first) return;
            var clone = first.cloneNode(true);
            clone.querySelector('input[type="file"]').value = '';
            clone.querySelector('input[type="text"]').value = '';
            clone.querySelector('input[type="checkbox"]').checked = true;
            var removeBtn = clone.querySelector('.curriculum-remove-material');
            if (removeBtn) removeBtn.style.display = 'inline-flex';
            container.appendChild(clone);
        });
        container.addEventListener('click', function(e) {
            if (e.target.closest('.curriculum-remove-material')) {
                var row = e.target.closest('.curriculum-material-row');
                if (container.querySelectorAll('.curriculum-material-row').length > 1) row.remove();
            }
        });
    }
})();

function saveLecture(e) {
    e.preventDefault();
    const form = document.getElementById('lectureForm');
    const formData = new FormData(form);
    const sectionId = document.getElementById('lectureSectionId').value;
    const lectureId = document.getElementById('lectureEditId').value;
    
    // التأكد من أن video_platform و recording_url موجودان في البيانات
    const videoPlatformInput = document.getElementById('lectureVideoPlatform');
    const recordingUrlInput = document.getElementById('lectureRecordingUrl');
    
    const videoPlatform = selectedVideoPlatform || (videoPlatformInput ? videoPlatformInput.value : '');
    const recordingUrl = recordingUrlInput ? recordingUrlInput.value.trim() : '';
    
    console.log('Form data before save:');
    console.log('- video_platform:', videoPlatform);
    console.log('- recording_url:', recordingUrl);
    console.log('- selectedVideoPlatform:', selectedVideoPlatform);
    console.log('- videoPlatformInput value:', videoPlatformInput ? videoPlatformInput.value : 'N/A');
    console.log('- recordingUrlInput value:', recordingUrlInput ? recordingUrlInput.value : 'N/A');
    
    // التأكد من إضافة recording_url إلى formData
    if (recordingUrl) {
        formData.set('recording_url', recordingUrl);
        console.log('Set recording_url in formData:', recordingUrl);
    } else {
        // إذا كان فارغاً، أرسل string فارغ
        formData.set('recording_url', '');
        console.log('Set recording_url to empty string');
    }
    
    // التأكد من إضافة video_platform إلى formData
    if (videoPlatform) {
        formData.set('video_platform', videoPlatform);
        console.log('Set video_platform in formData:', videoPlatform);
    } else if (recordingUrl) {
        // إذا لم يكن platform محدداً لكن يوجد رابط، حاول اكتشافه
        console.log('No platform selected, trying to auto-detect from URL');
        let detectedPlatform = '';
        if (recordingUrl.includes('youtube.com') || recordingUrl.includes('youtu.be')) {
            detectedPlatform = 'youtube';
        } else if (recordingUrl.includes('vimeo.com')) {
            detectedPlatform = 'vimeo';
        } else if (recordingUrl.includes('drive.google.com')) {
            detectedPlatform = 'google_drive';
        } else if (recordingUrl.includes('mediadelivery.net')) {
            detectedPlatform = 'bunny';
        } else if (recordingUrl.match(/\.(mp4|webm|ogg|avi|mov)(\?.*)?$/i)) {
            detectedPlatform = 'direct';
        }
        
        if (detectedPlatform) {
            formData.set('video_platform', detectedPlatform);
            console.log('Auto-detected and set video_platform:', detectedPlatform);
        }
    } else {
        // إذا لم يكن هناك رابط ولا platform، أرسل string فارغ
        formData.set('video_platform', '');
        console.log('Set video_platform to empty string');
    }
    
    // طباعة جميع البيانات في formData للتحقق
    console.log('All formData entries:');
    for (let pair of formData.entries()) {
        console.log('- ' + pair[0] + ': ' + pair[1]);
    }
    
    // إضافة section_id للبيانات
    formData.append('section_id', sectionId);

    var minWatchVal = document.getElementById('lectureMinWatchPercent') ? document.getElementById('lectureMinWatchPercent').value.trim() : '';
    formData.set('min_watch_percent_to_unlock_next', minWatchVal === '' ? '' : minWatchVal);

    // مواد المحاضرة: إرسال فقط الصفوف التي تحتوي ملفاً حتى تتطابق المؤشرات مع الـ backend
    formData.delete('material_files[]');
    formData.delete('material_titles[]');
    formData.delete('material_visible[]');
    var materialRows = document.querySelectorAll('#curriculum-materials-container .curriculum-material-row');
    materialRows.forEach(function(row) {
        var fileInput = row.querySelector('input[type="file"]');
        var titleInput = row.querySelector('input[type="text"]');
        var visibleCb = row.querySelector('input[type="checkbox"]');
        if (!fileInput || !fileInput.files || !fileInput.files[0]) return;
        formData.append('material_files[]', fileInput.files[0]);
        formData.append('material_titles[]', titleInput ? titleInput.value : '');
        formData.append('material_visible[]', (visibleCb && visibleCb.checked) ? '1' : '0');
    });
    
    // تحديد URL والـ method
    let url = '{{ route("instructor.lectures.store") }}';
    let method = 'POST';
    
    if (lectureId) {
        url = `/instructor/lectures/${lectureId}`;
        formData.append('_method', 'PUT');
        // إرسال كـ POST مع _method=PUT لأن PHP لا يفسر body طلبات PUT multipart/form-data
        method = 'POST';
        console.log('Updating lecture:', lectureId);
    } else {
        console.log('Creating new lecture');
    }
    
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async res => {
        const contentType = res.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return res.json();
        } else {
            const text = await res.text();
            throw new Error('Expected JSON but got HTML');
        }
    })
    .then(data => {
        console.log('Save response:', data);
        if (data.success || (lectureId && !data.error)) {
            if (lectureId) {
                // تم التعديل - إعادة تحميل الصفحة
                console.log('Lecture updated successfully, reloading page...');
                location.reload();
            } else {
                // إذا تم إنشاء المحاضرة بنجاح، أضفها تلقائياً للقسم
                if (data.lecture && data.lecture.id && sectionId) {
                    console.log('Lecture created successfully, adding to section...');
                    addLectureToSection(data.lecture.id, sectionId);
                } else {
                    console.log('Lecture created but no section ID, reloading page...');
                    location.reload();
                }
            }
        } else {
            console.error('Save failed:', data);
            alert('حدث خطأ: ' + (data.message || 'خطأ غير معروف'));
        }
    })
    .catch(err => {
        console.error('Error saving lecture:', err);
        console.error('Error details:', err.message, err.stack);
        
        // إذا كان هناك أخطاء في التحقق من Laravel
        if (err.message && err.message.includes('422')) {
            fetch('{{ route("instructor.lectures.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.errors) {
                    let errorMsg = 'أخطاء في النموذج:\n';
                    Object.values(data.errors).forEach(errors => {
                        errors.forEach(error => errorMsg += error + '\n');
                    });
                    alert(errorMsg);
                }
            });
        } else {
            alert('حدث خطأ أثناء حفظ المحاضرة');
        }
    });
}

function addLectureToSection(lectureId, sectionId) {
    fetch(`/instructor/sections/${sectionId}/items`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            item_type: 'App\\Models\\Lecture',
            item_id: lectureId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('تم إنشاء المحاضرة لكن حدث خطأ في إضافتها للمنهج: ' + (data.message || ''));
            location.reload();
        }
    })
    .catch(err => {
        console.error(err);
        alert('تم إنشاء المحاضرة لكن حدث خطأ في إضافتها للمنهج');
        location.reload();
    });
}

// اختيار المشغل للفيديو
function selectVideoPlatform(platform, button) {
    selectedVideoPlatform = platform;
    const platformInput = document.getElementById('lectureVideoPlatform');
    if (platformInput) {
        platformInput.value = platform;
        console.log('Platform selected:', platform, 'Input value set to:', platformInput.value);
    } else {
        console.error('lectureVideoPlatform input not found!');
    }
    
    // تحديث الأزرار
    document.querySelectorAll('.platform-btn').forEach(btn => {
        btn.classList.remove('border-sky-500', 'bg-sky-50 dark:bg-sky-900/30');
        btn.classList.add('border-slate-200 dark:border-slate-700');
    });
    if (button) {
        button.classList.remove('border-slate-200 dark:border-slate-700');
        button.classList.add('border-sky-500', 'bg-sky-50 dark:bg-sky-900/30');
    }
    
    // تحديث placeholder
    const placeholder = document.getElementById('lectureVideoPlaceholder');
    const input = document.getElementById('lectureRecordingUrl');
    
    if (input && placeholder) {
        // حفظ القيمة الحالية قبل التحديث
        const currentValue = input.value;
        
        if (platform === 'bunny') {
            placeholder.textContent = 'مثال: https://player.mediadelivery.net/play/LIBRARY_ID/VIDEO_ID أو https://iframe.mediadelivery.net/embed/LIBRARY_ID/VIDEO_ID';
            input.placeholder = 'الصق رابط Bunny هنا...';
        } else {
            placeholder.textContent = '';
            input.placeholder = 'الصق رابط Bunny هنا...';
        }
        
        // استعادة القيمة إذا كانت موجودة (للتعديل)
        if (currentValue) {
            input.value = currentValue;
            console.log('Preserved recording_url value:', currentValue);
        } else {
            // مسح المعاينة السابقة فقط إذا لم تكن هناك قيمة
            document.getElementById('lectureVideoPreview').classList.add('hidden');
        }
    }
}

// معاينة الفيديو
function previewLectureVideo() {
    const url = document.getElementById('lectureRecordingUrl').value.trim();
    let platform = 'bunny';
    selectedVideoPlatform = 'bunny';
    const platformInput = document.getElementById('lectureVideoPlatform');
    if (platformInput) platformInput.value = 'bunny';
    const bunnyBtn = document.querySelector('[data-platform="bunny"]');
    if (bunnyBtn) selectVideoPlatform('bunny', bunnyBtn);
    
    const previewDiv = document.getElementById('lectureVideoPreview');
    const previewContent = document.getElementById('lectureVideoPreviewContent');
    
    if (!url || !platform) {
        previewDiv.classList.add('hidden');
        return;
    }
    if (!url.includes('mediadelivery.net')) {
        previewDiv.classList.remove('hidden');
        previewContent.innerHTML = '<div class="text-center p-4"><i class="fas fa-exclamation-triangle text-yellow-400 text-2xl mb-2"></i><p class="text-sm">مسموح فقط بروابط Bunny Stream (mediadelivery.net)</p></div>';
        return;
    }
    
    previewDiv.classList.remove('hidden');
    previewContent.innerHTML = '<i class="fas fa-spinner fa-spin text-2xl"></i>';
    
    let html = '';
    let isValid = false;
    
    try {
        // Bunny Stream (embed or play)
        const bunnyMatch = url.match(/(?:iframe|player)\.mediadelivery\.net\/(embed|play)\/(\d+)\/([a-zA-Z0-9_-]+)/);
        if (bunnyMatch && bunnyMatch[2] && bunnyMatch[3]) {
            isValid = true;
            const clean = url.split('?')[0];
            html = '<iframe src="' + clean.replace(/"/g, '&quot;') + '" width="100%" height="100%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen style="border-radius: 0.75rem;"></iframe>';
        }
        
        if (isValid && html) {
            previewContent.innerHTML = html;
        } else {
            previewContent.innerHTML = '<div class="text-center p-4"><i class="fas fa-exclamation-triangle text-yellow-400 text-2xl mb-2"></i><p class="text-sm">رابط Bunny غير صحيح. استخدم play أو embed من mediadelivery.net</p></div>';
        }
    } catch (error) {
        console.error('Error generating preview:', error);
        previewContent.innerHTML = '<div class="text-center p-4"><i class="fas fa-exclamation-circle text-red-400 text-2xl mb-2"></i><p class="text-sm">حدث خطأ في عرض المعاينة</p></div>';
    }
}

// إعادة تعيين عند إغلاق modal
function closeLectureModal() {
    document.getElementById('lectureModal').classList.add('hidden');
    document.getElementById('lectureForm').reset();
    selectedVideoPlatform = '';
    document.getElementById('lectureVideoPlatform').value = '';
    document.getElementById('lectureVideoPreview').classList.add('hidden');
    document.querySelectorAll('.platform-btn').forEach(btn => {
        btn.classList.remove('border-sky-500', 'bg-sky-50 dark:bg-sky-900/30');
        btn.classList.add('border-slate-200 dark:border-slate-700');
    });
}

// ——— سحب وإفلات المنهج (أقسام + عناصر) ———
(function curriculumSortable() {
    if (typeof Sortable === 'undefined') return;
    var courseId = {{ $course->id }};
    var sectionsOrderUrl = '{{ route("instructor.courses.sections.order", $course) }}';
    var moveItemUrlBase = '{{ url("instructor/curriculum-items") }}';
    var token = '{{ csrf_token() }}';

    function showToast(msg, isError) {
        var t = document.createElement('div');
        t.className = 'fixed bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 rounded-xl text-white text-sm font-semibold shadow-lg z-[9999] ' + (isError ? 'bg-red-600 dark:bg-red-700' : 'bg-emerald-600 dark:bg-emerald-700');
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function() { t.remove(); }, 2500);
    }

    function buildSectionsPayload() {
        var sections = [];
        var root = document.getElementById('sections-container');
        if (!root) return sections;
        var rootBlocks = root.querySelectorAll(':scope > .section-block');
        rootBlocks.forEach(function(el, i) {
            sections.push({ id: parseInt(el.dataset.sectionId, 10), order: i, parent_id: null });
        });
        document.querySelectorAll('.sections-children').forEach(function(container) {
            var parentId = parseInt(container.dataset.parentId, 10);
            var blocks = container.querySelectorAll(':scope > .section-block');
            blocks.forEach(function(el, i) {
                sections.push({ id: parseInt(el.dataset.sectionId, 10), order: i, parent_id: parentId });
            });
        });
        return sections;
    }

    function saveSectionsOrder() {
        var payload = buildSectionsPayload();
        if (payload.length === 0) return;
        fetch(sectionsOrderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({ sections: payload })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) showToast('تم تحديث ترتيب الأقسام');
            else showToast(data.message || 'حدث خطأ', true);
        })
        .catch(function() { showToast('حدث خطأ في الاتصال', true); });
    }

    function saveItemsOrder(sectionId, itemIds) {
        var items = itemIds.map(function(id, i) { return { id: id, order: i }; });
        var url = '/instructor/sections/' + sectionId + '/items/order';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({ items: items })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) showToast('تم تحديث الترتيب');
            else showToast(data.message || 'حدث خطأ', true);
        })
        .catch(function() { showToast('حدث خطأ في الاتصال', true); });
    }

    function moveCurriculumItem(itemId, sectionId, order) {
        fetch(moveItemUrlBase + '/' + itemId + '/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({ section_id: sectionId, order: order })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) showToast('تم نقل العنصر');
            else showToast(data.message || 'حدث خطأ', true);
        })
        .catch(function() { showToast('حدث خطأ في الاتصال', true); });
    }

    var sectionOpts = {
        group: 'sections',
        animation: 200,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        draggable: '.section-block',
        onStart: function() { document.body.classList.add('curriculum-dragging'); },
        onEnd: function() {
            document.body.classList.remove('curriculum-dragging');
            saveSectionsOrder();
        }
    };

    var itemOpts = {
        group: 'curriculum-items',
        animation: 200,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        draggable: '.item-card',
        handle: '.drag-handle',
        filter: 'button, a, input, textarea, select, label',
        preventOnFilter: true,
        onStart: function() { document.body.classList.add('curriculum-dragging'); },
        onEnd: function(evt) {
            document.body.classList.remove('curriculum-dragging');
            var from = evt.from;
            var to = evt.to;
            var sectionId = to.dataset.sectionId;
            if (!sectionId) return;
            if (from === to) {
                var ids = [];
                to.querySelectorAll('.item-card').forEach(function(card) {
                    var id = card.dataset.itemId;
                    if (id) ids.push(parseInt(id, 10));
                });
                if (ids.length) saveItemsOrder(sectionId, ids);
            } else {
                var itemId = evt.item.dataset.itemId;
                if (itemId) moveCurriculumItem(parseInt(itemId, 10), parseInt(sectionId, 10), evt.newIndex);
                var fromIds = [];
                from.querySelectorAll('.item-card').forEach(function(card) {
                    var id = card.dataset.itemId;
                    if (id) fromIds.push(parseInt(id, 10));
                });
                if (fromIds.length && from.dataset.sectionId) saveItemsOrder(from.dataset.sectionId, fromIds);
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        var root = document.getElementById('sections-container');
        if (root) Sortable.create(root, sectionOpts);
        document.querySelectorAll('.sections-children').forEach(function(el) {
            Sortable.create(el, sectionOpts);
        });
        document.querySelectorAll('.items-container').forEach(function(el) {
            Sortable.create(el, itemOpts);
        });
    });
})();
</script>

<script>
// Safety net: ensure lecture modal can open even if earlier script fails.
(function () {
    function qs(id) { return document.getElementById(id); }

    window.showAddLectureModal = window.showAddLectureModal || function (sectionId) {
        try {
            window.currentSectionId = sectionId;

            var modal = qs('lectureModal');
            var form = qs('lectureForm');
            var sectionInput = qs('lectureSectionId');
            var editId = qs('lectureEditId');
            var scheduledAt = qs('lectureScheduledAt');
            var duration = qs('lectureDuration');
            var statusEl = qs('lectureStatus');
            var platform = qs('lectureVideoPlatform');
            var preview = qs('lectureVideoPreview');

            if (!modal || !form || !sectionInput) {
                alert('تعذر فتح نافذة المحاضرة: عناصر الصفحة غير مكتملة. جرّب تحديث الصفحة (Ctrl+F5).');
                return;
            }

            sectionInput.value = sectionId || '';
            if (editId) editId.value = '';

            if (form && form.reset) form.reset();

            if (statusEl) statusEl.value = 'scheduled';
            if (duration) duration.value = 60;

            if (scheduledAt) {
                var now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                scheduledAt.value = now.toISOString().slice(0, 16);
            }

            window.selectedVideoPlatform = '';
            if (platform) platform.value = '';
            if (preview) preview.classList.add('hidden');

            var titleEl = modal.querySelector('h3');
            if (titleEl) titleEl.textContent = 'إضافة محاضرة جديدة';
            var submitText = qs('lectureSubmitText');
            if (submitText) submitText.textContent = 'حفظ وإضافة للمنهج';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } catch (e) {
            console.error(e);
            alert('حدث خطأ عند فتح نافذة المحاضرة. افتح Console لمعرفة التفاصيل.');
        }
    };
})();
</script>
@endpush
@endsection
