<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Support\AttachesCourseCurriculumItems;
use App\Models\LectureMaterial;
use App\Models\LectureVideoQuestion;
use App\Models\LectureVideoQuestionAnswer;
use App\Models\LectureWatchProgress;
use App\Models\StudentCourseEnrollment;
use App\Services\LectureMaterialStorage;
use App\Services\PlatformCourseCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MyCourseController extends Controller
{
    /**
     * عرض الكورسات المفعلة للطالب
     */
    public function index()
    {
        $user = Auth::user();

        // الكورسات المفعلة للطالب
        $activeCourses = $user->activeCourses()
            ->with(['academicYear', 'academicSubject', 'teacher', 'lessons'])
            ->paginate(12);

        // إضافة نقاط الطالب من أسئلة الفيديو لكل كورس
        $activeCourses->getCollection()->transform(function ($course) use ($user) {
            $course->student_points = LectureVideoQuestionAnswer::totalScoreForUserInCourse($user->id, $course->id);

            return $course;
        });

        // إحصائيات
        $stats = [
            'total_active' => $user->activeCourses()->count(),
            'total_completed' => $user->courseEnrollments()->where('status', 'completed')->count(),
            'total_hours' => $user->activeCourses()->sum('duration_hours'),
            'avg_progress' => $this->calculateAverageProgress($user),
        ];

        return view('student.my-courses.index', compact('activeCourses', 'stats'));
    }

    /**
     * عرض تفاصيل الكورس المفعل
     */
    public function show($courseId)
    {
        $user = Auth::user();

        // التحقق من أن الطالب مسجل في الكورس
        $course = $user->activeCourses()
            ->with([
                'academicYear',
                'academicSubject',
                'teacher',
                'lessons.progress' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'sections.items.item' => function ($query) use ($user) {
                    // جلب تقدم الدروس والأنماط في العناصر
                    if ($query->getModel() instanceof \App\Models\CourseLesson) {
                        $query->with(['progress' => function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        }]);
                    }
                },
            ])
            ->findOrFail($courseId);

        AttachesCourseCurriculumItems::syncCourseContent((int) $course->id);

        // جلب الأقسام مع العناصر مرتبة
        $sections = $course->activeSections()
            ->with(['activeItems' => function ($query) {
                $query->orderBy('order')->with('item');
            }])
            ->orderBy('order')
            ->get();

        // تحميل تقدم الدروس والأنماط والامتحانات والواجبات في العناصر
        foreach ($sections as $section) {
            foreach ($section->activeItems as $curriculumItem) {
                $entity = $curriculumItem->item;
                if ($entity instanceof \App\Models\CourseLesson) {
                    $entity->load(['progress' => function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }]);
                } elseif ($entity instanceof \App\Models\AdvancedExam) {
                    $entity->load(['attempts' => function ($q) use ($user) {
                        $q->where('user_id', $user->id)->whereNotNull('submitted_at');
                    }]);
                } elseif ($entity instanceof \App\Models\Assignment) {
                    $entity->load(['submissions' => function ($q) use ($user) {
                        $q->where('student_id', $user->id);
                    }]);
                } elseif ($entity instanceof \App\Models\Lecture) {
                    $entity->load(['watchProgress' => function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }]);
                }
            }
        }

        [$progress, $totalLessons, $completedLessons] = $this->calculateProgressFromSections($user, $course, $sections);

        $coursePoints = LectureVideoQuestionAnswer::totalScoreForUserInCourse($user->id, $course->id);

        return view('student.my-courses.show', compact(
            'course',
            'progress',
            'totalLessons',
            'completedLessons',
            'coursePoints',
            'sections'
        ));
    }

    /**
     * صفحة عرض المحتوى (Focus Mode)
     */
    public function learn($courseId, Request $request)
    {
        $user = Auth::user();

        // التحقق من أن الطالب مسجل في الكورس
        $course = $user->activeCourses()
            ->with([
                'academicYear',
                'academicSubject',
                'teacher',
                'lessons.progress' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'lectures' => function ($query) {
                    $query->orderBy('scheduled_at', 'desc');
                },
                'lectures.lesson',
                'lectures.instructor',
                'sections.items.item' => function ($query) use ($user) {
                    // جلب تقدم الدروس والأنماط في العناصر
                    if ($query->getModel() instanceof \App\Models\CourseLesson) {
                        $query->with(['progress' => function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        }]);
                    }
                },
            ])
            ->findOrFail($courseId);

        AttachesCourseCurriculumItems::syncCourseContent((int) $course->id);

        // جلب الدرس إذا تم تمرير lesson_id
        $lesson = null;
        if ($request->has('lesson')) {
            $lessonId = $request->input('lesson');
            $lesson = $course->lessons()->find($lessonId);

            // التحقق من أن الدرس نشط
            if ($lesson && ! $lesson->is_active) {
                $lesson = null;
            }

            // التحقق من ترتيب الدروس (لا يمكن مشاهدة درس قبل إكمال السابق)
            if ($lesson) {
                $previousLessons = $course->lessons()
                    ->where('order', '<', $lesson->order)
                    ->where('is_active', true)
                    ->get();

                foreach ($previousLessons as $prevLesson) {
                    $prevProgress = \App\Models\LessonProgress::where('user_id', $user->id)
                        ->where('course_lesson_id', $prevLesson->id)
                        ->first();

                    if (! $prevProgress || ! $prevProgress->is_completed) {
                        $lesson = null;
                        break;
                    }
                }
            }
        }

        // جلب كل الأقسام (مسطحة) مع العناصر لاحتساب التقدم وبناء الشجرة
        $allSections = $course->activeSections()
            ->with(['activeItems' => function ($query) {
                $query->orderBy('order')->with('item');
            }])
            ->orderBy('order')
            ->get();

        // تحميل تقدم الدروس والأنماط والامتحانات في العناصر
        foreach ($allSections as $section) {
            foreach ($section->activeItems as $curriculumItem) {
                if ($curriculumItem->item instanceof \App\Models\CourseLesson) {
                    $curriculumItem->item->load(['progress' => function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }]);
                } elseif ($curriculumItem->item instanceof \App\Models\AdvancedExam) {
                    $curriculumItem->item->load(['attempts' => function ($q) use ($user) {
                        $q->where('user_id', $user->id)->whereNotNull('submitted_at');
                    }]);
                } elseif ($curriculumItem->item instanceof \App\Models\Assignment) {
                    $curriculumItem->item->load(['submissions' => function ($q) use ($user) {
                        $q->where('student_id', $user->id);
                    }]);
                } elseif ($curriculumItem->item instanceof \App\Models\Lecture) {
                    $curriculumItem->item->load(['watchProgress' => function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }]);
                }
            }
        }

        [$progress, $totalLessons, $completedLessons] = $this->calculateProgressFromSections($user, $course, $allSections);

        // بناء شجرة الأقسام (جذور + أطفال) للعرض في السايدبار
        foreach ($allSections as $section) {
            $section->setRelation('children', $allSections->where('parent_id', $section->id)->values());
        }
        $sections = $allSections->whereNull('parent_id')->values();
        $this->computeSectionLockState($user, $allSections);
        $sectionDescriptions = $allSections->pluck('description', 'id')->map(fn ($d) => $d ?? '')->toArray();

        // بناء خريطة "العنصر التالي" لكل محاضرة (للفتح التلقائي عند بلوغ النسبة)
        $nextItemByLectureId = $this->buildNextItemMapByLectureId($sections);

        // تجميع المحاضرات حسب الدرس (للتوافق مع الكود القديم)
        $lecturesByLesson = $course->lectures->groupBy('course_lesson_id');

        // جلب الاختبارات المرتبة حسب الموضع في السايدبار
        $sidebarExams = \App\Models\AdvancedExam::where('advanced_course_id', $course->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('show_in_sidebar', true)
                    ->orWhereNull('show_in_sidebar'); // للتوافق مع البيانات القديمة
            })
            ->orderByRaw('CASE WHEN sidebar_position IS NULL THEN 999 ELSE sidebar_position END ASC')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('student.my-courses.learn', compact(
            'course',
            'progress',
            'totalLessons',
            'completedLessons',
            'lecturesByLesson',
            'sections',
            'sectionDescriptions',
            'sidebarExams',
            'lesson',
            'nextItemByLectureId'
        ));
    }

    /**
     * بناء خريطة: لكل محاضرة، العنصر التالي في المنهج (للفتح التلقائي عند بلوغ نسبة المشاهدة).
     */
    private function buildNextItemMapByLectureId($sections): array
    {
        $flat = $this->flattenCurriculumItems($sections);
        $nextMap = [];
        for ($i = 0; $i < count($flat); $i++) {
            if (($flat[$i]['type'] ?? '') === 'lecture' && isset($flat[$i]['id'])) {
                $next = $flat[$i + 1] ?? null;
                $nextMap[$flat[$i]['id']] = $next;
            }
        }

        return $nextMap;
    }

    /**
     * تسطيح عناصر المنهج حسب ترتيب العرض (أقسام ثم أطفال).
     */
    private function flattenCurriculumItems($sections): array
    {
        $flat = [];
        foreach ($sections as $section) {
            $items = $section->activeItems->sortBy('order')->values();
            foreach ($items as $curriculumItem) {
                $item = $curriculumItem->item;
                if (! $item || $item instanceof \App\Models\CourseLesson) {
                    continue;
                }
                if ($item instanceof \App\Models\Lecture) {
                    $flat[] = ['type' => 'lecture', 'id' => $item->id];
                } elseif ($item instanceof \App\Models\Assignment) {
                    $flat[] = ['type' => 'assignment', 'id' => $item->id];
                } elseif ($item instanceof \App\Models\AdvancedExam || $item instanceof \App\Models\Exam) {
                    $flat[] = ['type' => 'exam', 'id' => $item->id];
                }
            }
            if ($section->children && $section->children->isNotEmpty()) {
                $flat = array_merge($flat, $this->flattenCurriculumItems($section->children));
            }
        }

        return $flat;
    }

    /**
     * إرجاع بيانات محاضرة واحدة كـ JSON (لصفحة التعلم - جلب الفيديو عند الحاجة)
     */
    public function getLectureData($courseId, $lectureId)
    {
        $user = Auth::user();
        $course = $user->activeCourses()->findOrFail($courseId);
        $lecture = $course->lectures()->findOrFail($lectureId);

        $recordingUrl = $lecture->recording_url ? trim($lecture->recording_url) : null;
        $videoPlatform = $lecture->video_platform ? trim(strtolower($lecture->video_platform)) : null;

        if ((! $recordingUrl) && $lecture->recording_file_path) {
            $recordingUrl = route('my-courses.lectures.recording-stream', [$courseId, $lectureId]);
            $videoPlatform = 'direct';
        }

        $materials = $lecture->materials()
            ->where('is_visible_to_student', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($m) use ($courseId, $lectureId) {
                return [
                    'id' => $m->id,
                    'title' => $m->title ?: $m->file_name,
                    'file_name' => $m->file_name,
                    'download_url' => route('my-courses.lectures.material.download', [$courseId, $lectureId, $m->id]),
                ];
            });

        $videoQuestions = $lecture->videoQuestions()->orderBy('timestamp_seconds')->get()->filter(function ($vq) use ($user) {
            $showCount = $vq->show_count;
            if ($showCount === null || $showCount == 0) {
                return true;
            }
            $answered = LectureVideoQuestionAnswer::where('lecture_video_question_id', $vq->id)->where('user_id', $user->id)->count();

            return $answered < $showCount;
        })->map(function ($vq) {
            $payload = $vq->getPayloadForStudent();
            $showEveryTime = $vq->show_count === null || $vq->show_count == 0;

            return [
                'id' => $vq->id,
                'timestamp_seconds' => $vq->timestamp_seconds,
                'show_at_end' => (bool) $vq->show_at_end,
                'text' => $payload['text'] ?? '',
                'options' => $payload['options'] ?? [],
                'type' => $payload['type'] ?? 'multiple_choice',
                'points' => $vq->points,
                'on_wrong' => $vq->on_wrong,
                'rewind_seconds' => $vq->rewind_seconds,
                'show_every_time' => $showEveryTime,
            ];
        })->values()->all();

        $watchProgress = LectureWatchProgress::where('lecture_id', $lecture->id)
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'id' => $lecture->id,
            'title' => $lecture->title,
            'description' => $lecture->description,
            'scheduled_at' => $lecture->scheduled_at ? $lecture->scheduled_at->toIso8601String() : null,
            'scheduled_at_formatted' => $lecture->scheduled_at ? $lecture->scheduled_at->format('Y/m/d H:i') : null,
            'duration_minutes' => $lecture->duration_minutes ?? 60,
            'min_watch_percent_to_unlock_next' => $lecture->min_watch_percent_to_unlock_next,
            'recording_url' => $recordingUrl,
            'recording_file_url' => $lecture->recording_file_path
                ? route('my-courses.lectures.recording-stream', [$courseId, $lectureId])
                : null,
            'video_platform' => $videoPlatform,
            'teams_meeting_link' => $lecture->teams_meeting_link ?? null,
            'teams_registration_link' => $lecture->teams_registration_link ?? null,
            'notes' => $lecture->notes ?? null,
            'materials' => $materials,
            'video_questions' => $videoQuestions,
            'progress' => $watchProgress ? [
                'progress_percent' => (int) $watchProgress->progress_percent,
                'is_completed' => (bool) $watchProgress->is_completed,
                'watch_time_seconds' => (int) $watchProgress->watch_time_seconds,
                'video_duration_seconds' => (int) $watchProgress->video_duration_seconds,
            ] : null,
        ]);
    }

    /**
     * بث ملف تسجيل المحاضرة للطالب المسجّل (بدون رابط عام مباشر).
     */
    public function streamLectureRecording($courseId, $lectureId)
    {
        $user = Auth::user();
        $course = $user->activeCourses()->findOrFail($courseId);
        $lecture = $course->lectures()->findOrFail($lectureId);

        abort_unless($lecture->recording_file_path, 404);

        $diskName = $lecture->storage_disk ?: LectureMaterialStorage::resolvedDisk();
        $path = str_replace('\\', '/', ltrim((string) $lecture->recording_file_path, '/'));

        $disk = Storage::disk($diskName);
        if (! $disk->exists($path) && $diskName !== 'public') {
            $disk = Storage::disk('public');
            abort_unless($disk->exists($path), 404);
        } else {
            abort_unless($disk->exists($path), 404);
        }

        $mime = 'video/mp4';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'mp4' => 'video/mp4',
            default => $mime,
        };

        return $disk->response($path, null, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="lecture-'.$lecture->id.'.'.$ext.'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * تحميل مادة محاضرة (للطالب - المواد الظاهرة فقط)
     */
    public function downloadLectureMaterial($courseId, $lectureId, $materialId)
    {
        $user = Auth::user();
        $course = $user->activeCourses()->findOrFail($courseId);
        $lecture = $course->lectures()->findOrFail($lectureId);
        $material = LectureMaterial::where('lecture_id', $lecture->id)
            ->where('id', $materialId)
            ->where('is_visible_to_student', true)
            ->firstOrFail();

        return LectureMaterialStorage::download($material);
    }

    /**
     * تحديث تقدم مشاهدة محاضرة (نسبة المشاهدة من الفيديو).
     */
    public function updateLectureProgress(Request $request, $courseId, $lectureId): JsonResponse
    {
        $user = Auth::user();
        $course = $user->activeCourses()->findOrFail($courseId);
        $lecture = $course->lectures()->findOrFail($lectureId);

        $data = $request->validate([
            'current_sec' => 'required|numeric|min:0',
            'duration_sec' => 'required|numeric|min:1',
        ]);

        $currentSec = (int) round($data['current_sec']);
        $durationSec = (int) round($data['duration_sec']);

        /** @var LectureWatchProgress $progress */
        $progress = LectureWatchProgress::firstOrNew([
            'lecture_id' => $lecture->id,
            'user_id' => $user->id,
        ]);
        $minPercent = $lecture->min_watch_percent_to_unlock_next;
        $progress->updateFromSample($currentSec, $durationSec, $minPercent);

        $this->updateCourseProgress($user->id, (int) $courseId);

        $sectionsForProgress = $course->activeSections()
            ->with(['activeItems' => fn ($q) => $q->with('item')])
            ->orderBy('order')
            ->get();
        foreach ($sectionsForProgress as $section) {
            foreach ($section->activeItems as $curriculumItem) {
                if ($curriculumItem->item instanceof \App\Models\Lecture) {
                    $curriculumItem->item->load(['watchProgress' => fn ($q) => $q->where('user_id', $user->id)]);
                }
            }
        }
        [$courseProgressPct, $totalItems, $completedItems] = $this->calculateProgressFromSections($user, $course, $sectionsForProgress);

        return response()->json([
            'success' => true,
            'progress_percent' => $progress->progress_percent,
            'is_completed' => $progress->is_completed,
            'course_progress' => $courseProgressPct,
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
        ]);
    }

    /**
     * تسجيل إجابة الطالب على سؤال فيديو في المحاضرة (يُحسب كدرجة للتخرج).
     */
    public function submitLectureVideoQuestionAnswer(Request $request, $courseId, $lectureId, $videoQuestionId): JsonResponse
    {
        $user = Auth::user();
        $course = $user->activeCourses()->findOrFail($courseId);
        $lecture = $course->lectures()->findOrFail($lectureId);
        $videoQuestion = LectureVideoQuestion::where('id', $videoQuestionId)
            ->where('lecture_id', $lecture->id)
            ->firstOrFail();

        $answer = $request->input('answer', '');
        $isCorrect = $videoQuestion->checkAnswer($answer);
        $scoreEarned = $isCorrect ? (float) $videoQuestion->points : 0.0;

        LectureVideoQuestionAnswer::create([
            'user_id' => $user->id,
            'lecture_video_question_id' => $videoQuestion->id,
            'answer' => $answer,
            'is_correct' => $isCorrect,
            'score_earned' => $scoreEarned,
            'answered_at' => now(),
        ]);

        return response()->json([
            'correct' => $isCorrect,
            'score_earned' => $scoreEarned,
            'on_wrong' => $videoQuestion->on_wrong,
            'rewind_seconds' => $videoQuestion->rewind_seconds,
        ]);
    }

    /**
     * عرض الدرس في واجهة محمية
     */
    public function watchLesson($courseId, $lessonId)
    {
        $user = Auth::user();

        // التحقق من أن الطالب مسجل في الكورس
        $course = $user->activeCourses()->findOrFail($courseId);
        $lesson = $course->lessons()->findOrFail($lessonId);

        // التحقق من أن الدرس نشط
        if (! $lesson->is_active) {
            return redirect()->route('my-courses.show', $course)
                ->with('error', 'هذا الدرس غير متاح حالياً');
        }

        // التحقق من ترتيب الدروس (لا يمكن مشاهدة درس قبل إكمال السابق)
        $previousLessons = $course->lessons()
            ->where('order', '<', $lesson->order)
            ->where('is_active', true)
            ->get();

        foreach ($previousLessons as $prevLesson) {
            $prevProgress = \App\Models\LessonProgress::where('user_id', $user->id)
                ->where('course_lesson_id', $prevLesson->id)
                ->first();

            if (! $prevProgress || ! $prevProgress->is_completed) {
                return redirect()->route('my-courses.show', $course)
                    ->with('error', 'يجب إكمال الدروس السابقة أولاً');
            }
        }

        return view('student.my-courses.lesson-viewer', compact('course', 'lesson'));
    }

    /**
     * حساب متوسط التقدم
     */
    private function calculateAverageProgress($user)
    {
        $enrollments = $user->courseEnrollments()->where('status', 'active')->get();
        if ($enrollments->isEmpty()) {
            return 0;
        }

        $totalProgress = $enrollments->sum('progress');

        return round($totalProgress / $enrollments->count(), 1);
    }

    /**
     * تحديث تقدم الدرس
     */
    public function updateLessonProgress(Request $request, $courseId, $lessonId)
    {
        $user = Auth::user();

        // التحقق من أن الطالب مسجل في الكورس
        $course = $user->activeCourses()->findOrFail($courseId);
        $lesson = $course->lessons()->findOrFail($lessonId);

        $watchTime = (int) $request->input('watch_time', 0);
        $clientPercent = (int) min(100, max(0, $request->input('progress_percent', 0)));

        // احتساب النسبة من الثواني المشاهدة فعلياً (منع الغش: لا نعتمد على currentTime فقط)
        $totalSeconds = $lesson->duration_minutes ? (int) ($lesson->duration_minutes * 60) : 0;
        if ($totalSeconds > 0) {
            $progressPercent = (int) min(100, round(($watchTime / $totalSeconds) * 100));
        } else {
            $progressPercent = $clientPercent;
        }
        $isCompleted = $request->boolean('completed') || $progressPercent >= 90;

        // تحديث أو إنشاء تقدم الدرس
        $progress = \App\Models\LessonProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_lesson_id' => $lessonId,
            ],
            [
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
                'watch_time' => $watchTime,
                'progress_percent' => $progressPercent,
            ]
        );

        // تحديث التقدم الإجمالي للكورس
        $this->updateCourseProgress($user->id, $courseId);

        $course = $user->activeCourses()->findOrFail($courseId);
        $sections = $course->activeSections()->with(['activeItems' => fn ($q) => $q->with('item')])->orderBy('order')->get();
        foreach ($sections as $section) {
            foreach ($section->activeItems as $curriculumItem) {
                $entity = $curriculumItem->item;
                if ($entity instanceof \App\Models\CourseLesson) {
                    $entity->load(['progress' => fn ($q) => $q->where('user_id', $user->id)]);
                } elseif ($entity instanceof \App\Models\AdvancedExam) {
                    $entity->load(['attempts' => fn ($q) => $q->where('user_id', $user->id)->whereNotNull('submitted_at')]);
                } elseif ($entity instanceof \App\Models\Assignment) {
                    $entity->load(['submissions' => fn ($q) => $q->where('student_id', $user->id)]);
                }
            }
        }
        [$progressPct, $totalItems, $completedItems] = $this->calculateProgressFromSections($user, $course, $sections);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التقدم بنجاح',
            'progress' => $progress,
            'course_progress' => $progressPct,
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
        ]);
    }

    /**
     * حساب قفل كل قسم بناءً على إعدادات فتح القسم (unlock_rule) وتقدم القسم السابق
     */
    private function computeSectionLockState($user, $allSections)
    {
        foreach ($allSections as $section) {
            $total = 0;
            $completed = 0;
            foreach ($section->activeItems as $item) {
                $entity = $item->item;
                if (! $entity) {
                    continue;
                }
                $total++;
                if ($entity instanceof \App\Models\CourseLesson) {
                    $p = $entity->progress->first();
                    if ($p && $p->is_completed) {
                        $completed++;
                    }
                } elseif ($entity instanceof \App\Models\Lecture) {
                    $wp = $entity->watchProgress->first();
                    $minPercent = $entity->min_watch_percent_to_unlock_next;
                    $threshold = $minPercent !== null ? (int) $minPercent : 90;
                    if ($wp && $wp->progress_percent >= $threshold) {
                        $completed++;
                    } elseif ($entity->videoQuestions()->count() > 0) {
                        $answered = \App\Models\LectureVideoQuestionAnswer::where('user_id', $user->id)
                            ->whereIn('lecture_video_question_id', $entity->videoQuestions()->pluck('id'))
                            ->distinct('lecture_video_question_id')->count('lecture_video_question_id');
                        if ($answered >= $entity->videoQuestions()->count()) {
                            $completed++;
                        }
                    }
                } elseif ($entity instanceof \App\Models\Assignment) {
                    if ($entity->submissions->where('student_id', $user->id)->isNotEmpty()) {
                        $completed++;
                    }
                } elseif ($entity instanceof \App\Models\AdvancedExam) {
                    $passing = (float) ($entity->passing_marks ?? 0);
                    if ($entity->attempts->contains(fn ($a) => $a->score !== null && (float) $a->score >= $passing)) {
                        $completed++;
                    }
                }
            }
            $section->progress_percent = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
            $section->all_items_completed = $total > 0 && $completed >= $total;
        }
        foreach ($allSections as $section) {
            $prev = $allSections->where('parent_id', $section->parent_id)->where('order', '<', $section->order)->sortByDesc('order')->first();
            if (! $prev) {
                $section->is_locked = false;

                continue;
            }
            $rule = $section->unlock_rule ?? 'previous_all_items';
            if ($rule === 'always') {
                $section->is_locked = false;

                continue;
            }
            if ($rule === 'previous_all_items') {
                $section->is_locked = ! ($prev->all_items_completed ?? false);

                continue;
            }
            if ($rule === 'previous_percent') {
                $required = (int) ($section->unlock_percent ?? 100);
                $section->is_locked = ($prev->progress_percent ?? 0) < $required;

                continue;
            }
            $section->is_locked = false;
        }
    }

    /**
     * حساب التقدم من المنهج (أقسام + عناصر) أو من الدروس فقط
     * يُرجع: [نسبة التقدم، إجمالي العناصر، العناصر المكتملة]
     */
    private function calculateProgressFromSections($user, $course, $sections)
    {
        if ($sections->isEmpty()) {
            $total = $course->lessons()->where('is_active', true)->count();
            $completed = \App\Models\LessonProgress::where('user_id', $user->id)
                ->whereIn('course_lesson_id', $course->lessons()->where('is_active', true)->pluck('id'))
                ->where('is_completed', true)
                ->count();
            $progress = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

            return [$progress, $total, $completed];
        }

        $totalItems = 0;
        $completedItems = 0;

        foreach ($sections as $section) {
            foreach ($section->activeItems as $item) {
                $entity = $item->item;
                if (! $entity) {
                    continue;
                }

                $totalItems++;
                if ($entity instanceof \App\Models\CourseLesson) {
                    $p = $entity->progress->first();
                    if ($p && $p->is_completed) {
                        $completedItems++;
                    }
                } elseif ($entity instanceof \App\Models\Lecture) {
                    $wp = $entity->watchProgress->first();
                    $minPercent = $entity->min_watch_percent_to_unlock_next;
                    $threshold = $minPercent !== null ? (int) $minPercent : 90;
                    if ($wp && $wp->progress_percent >= $threshold) {
                        $completedItems++;
                    } else {
                        $vqCount = $entity->videoQuestions()->count();
                        if ($vqCount > 0) {
                            $answered = \App\Models\LectureVideoQuestionAnswer::where('user_id', $user->id)
                                ->whereIn('lecture_video_question_id', $entity->videoQuestions()->pluck('id'))
                                ->distinct('lecture_video_question_id')
                                ->count('lecture_video_question_id');
                            if ($answered >= $vqCount) {
                                $completedItems++;
                            }
                        }
                    }
                } elseif ($entity instanceof \App\Models\Assignment) {
                    if ($entity->submissions->where('student_id', $user->id)->isNotEmpty()) {
                        $completedItems++;
                    }
                } elseif ($entity instanceof \App\Models\AdvancedExam) {
                    $passing = (float) ($entity->passing_marks ?? 0);
                    $passed = $entity->attempts->contains(fn ($a) => $a->score !== null && (float) $a->score >= $passing);
                    if ($passed) {
                        $completedItems++;
                    }
                }
            }
        }

        $progress = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0;

        return [$progress, $totalItems, $completedItems];
    }

    /**
     * الحصول على تقدم الكورس (نسبة مئوية)
     */
    private function getCourseProgress($userId, $courseId)
    {
        $course = \App\Models\AdvancedCourse::findOrFail($courseId);
        $user = \App\Models\User::findOrFail($userId);
        $sections = $course->activeSections()->with(['activeItems' => fn ($q) => $q->with('item')])->orderBy('order')->get();

        foreach ($sections as $section) {
            foreach ($section->activeItems as $curriculumItem) {
                $entity = $curriculumItem->item;
                if ($entity instanceof \App\Models\CourseLesson) {
                    $entity->load(['progress' => fn ($q) => $q->where('user_id', $userId)]);
                } elseif ($entity instanceof \App\Models\AdvancedExam) {
                    $entity->load(['attempts' => fn ($q) => $q->where('user_id', $userId)->whereNotNull('submitted_at')]);
                } elseif ($entity instanceof \App\Models\Assignment) {
                    $entity->load(['submissions' => fn ($q) => $q->where('student_id', $userId)]);
                }
            }
        }

        [$progress] = $this->calculateProgressFromSections($user, $course, $sections);

        return $progress;
    }

    /**
     * تحديث التقدم الإجمالي للكورس في جدول التسجيلات (يُستدعى أيضاً بعد تسليم الامتحان)
     */
    public function updateCourseProgress($userId, $courseId)
    {
        $progress = $this->getCourseProgress($userId, $courseId);

        $enrollment = StudentCourseEnrollment::query()
            ->where('user_id', $userId)
            ->where('advanced_course_id', $courseId)
            ->first();

        if (! $enrollment) {
            return;
        }

        $wasCompleted = $enrollment->status === 'completed';
        $enrollment->progress = $progress;

        if ((float) $progress >= 99.5
            && $enrollment->status === 'active'
        ) {
            $enrollment->status = 'completed';
        }

        $enrollment->save();

        if (! $wasCompleted
            && $enrollment->status === 'completed'
            && (float) $progress >= 99.5
        ) {
            try {
                app(PlatformCourseCertificateService::class)->issueIfEligible($enrollment->fresh());
            } catch (\Throwable $e) {
                Log::warning('auto_platform_certificate', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
