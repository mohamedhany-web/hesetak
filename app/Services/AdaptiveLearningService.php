<?php

namespace App\Services;

use App\Models\AdaptiveLearningSuggestion;
use App\Models\AdvancedCourse;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdaptiveLearningService
{
    public const TRACK_RECORDED = 'recorded';

    public const TRACK_BOOK = 'book';

    /**
     * حلّل أخطاء الاختبارات واقترح مراجعات/حصص/مسارات.
     *
     * @return Collection<int, AdaptiveLearningSuggestion>
     */
    public function refreshSuggestionsFor(User $student, int $limit = 8): Collection
    {
        if (! Schema::hasTable('adaptive_learning_suggestions')) {
            return collect();
        }

        AdaptiveLearningSuggestion::query()
            ->where('user_id', $student->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $suggestions = collect();
        $suggestions = $suggestions->merge($this->fromExamErrors($student));
        $suggestions = $suggestions->merge($this->fromProductTracks($student));

        $suggestions = $suggestions->unique(fn ($s) => $s['kind'].'|'.$s['title'])->take($limit);

        $saved = collect();
        foreach ($suggestions as $row) {
            $saved->push(AdaptiveLearningSuggestion::create([
                'user_id' => $student->id,
                'kind' => $row['kind'],
                'title' => $row['title'],
                'reason' => $row['reason'] ?? null,
                'priority' => $row['priority'] ?? 'medium',
                'source' => $row['source'] ?? 'rules',
                'suggestable_type' => $row['suggestable_type'] ?? null,
                'suggestable_id' => $row['suggestable_id'] ?? null,
                'action_url' => $row['action_url'] ?? null,
                'meta' => $row['meta'] ?? null,
                'is_active' => true,
                'expires_at' => now()->addDays(14),
            ]));
        }

        return $saved;
    }

    /**
     * @return Collection<int, AdaptiveLearningSuggestion>
     */
    public function activeFor(User $student): Collection
    {
        if (! Schema::hasTable('adaptive_learning_suggestions')) {
            return collect();
        }

        $active = AdaptiveLearningSuggestion::query()
            ->where('user_id', $student->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->sortBy(fn (AdaptiveLearningSuggestion $s) => match ($s->priority) {
                'high' => 0,
                'medium' => 1,
                default => 2,
            })
            ->values();

        if ($active->isEmpty()) {
            return $this->refreshSuggestionsFor($student);
        }

        return $active;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fromExamErrors(User $student): array
    {
        if (! Schema::hasTable('exam_attempts')) {
            return [];
        }

        $attempts = ExamAttempt::query()
            ->with(['exam:id,title,advanced_course_id'])
            ->where('user_id', $student->id)
            ->whereNotNull('score')
            ->latest('id')
            ->limit(12)
            ->get();

        $out = [];
        $weakExams = $attempts->filter(fn (ExamAttempt $a) => (float) $a->score < 70);

        foreach ($weakExams->take(4) as $attempt) {
            $wrongTopics = $this->wrongQuestionHints($attempt);
            $title = app()->getLocale() === 'ar'
                ? 'مراجعة: '.($attempt->exam?->title ?: 'اختبار')
                : 'Review: '.($attempt->exam?->title ?: 'Exam');

            $reason = app()->getLocale() === 'ar'
                ? 'درجتك '.round((float) $attempt->score).'%'.($wrongTopics !== [] ? ' — نقاط ضعف: '.implode('، ', array_slice($wrongTopics, 0, 3)) : '')
                : 'Score '.round((float) $attempt->score).'%'.($wrongTopics !== [] ? ' — weak areas: '.implode(', ', array_slice($wrongTopics, 0, 3)) : '');

            $out[] = [
                'kind' => AdaptiveLearningSuggestion::KIND_REVIEW_LESSON,
                'title' => $title,
                'reason' => $reason,
                'priority' => (float) $attempt->score < 50 ? 'high' : 'medium',
                'source' => 'exam_errors',
                'suggestable_type' => $attempt->exam ? get_class($attempt->exam) : null,
                'suggestable_id' => $attempt->exam_id,
                'action_url' => route('student.exams.index'),
                'meta' => [
                    'exam_attempt_id' => $attempt->id,
                    'score' => (float) $attempt->score,
                    'topics' => $wrongTopics,
                ],
            ];
        }

        if ($weakExams->isNotEmpty()) {
            $out[] = [
                'kind' => AdaptiveLearningSuggestion::KIND_BOOK_SESSION,
                'title' => app()->getLocale() === 'ar'
                    ? 'احجز حصة مراجعة 1:1 لسد الفجوة'
                    : 'Book a 1:1 review session to close the gap',
                'reason' => app()->getLocale() === 'ar'
                    ? 'ظهرت نتائج ضعيفة في '. $weakExams->count().' اختبارات أخيرة.'
                    : 'Weak results in '.$weakExams->count().' recent exams.',
                'priority' => 'high',
                'source' => 'exam_errors',
                'action_url' => route('student.learn.index'),
                'meta' => ['weak_exams' => $weakExams->count()],
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    protected function wrongQuestionHints(ExamAttempt $attempt): array
    {
        $answers = is_array($attempt->answers) ? $attempt->answers : [];
        if ($answers === [] || ! Schema::hasTable('exam_questions')) {
            return [];
        }

        $questionIds = collect(array_keys($answers))->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->all();
        if ($questionIds === []) {
            return [];
        }

        $questions = ExamQuestion::query()->whereIn('id', $questionIds)->get()->keyBy('id');
        $hints = [];

        foreach ($answers as $qid => $userAnswer) {
            $q = $questions->get((int) $qid);
            if (! $q) {
                continue;
            }
            $correct = $q->correct_answer ?? $q->answer ?? null;
            if ($correct === null) {
                continue;
            }
            if ((string) $userAnswer === (string) $correct) {
                continue;
            }
            $label = trim((string) ($q->topic ?? $q->tag ?? $q->category ?? ''));
            if ($label === '') {
                $label = Str::limit(strip_tags((string) ($q->question_text ?? $q->question ?? 'سؤال')), 40);
            }
            $hints[] = $label;
        }

        return array_values(array_unique($hints));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fromProductTracks(User $student): array
    {
        if (! Schema::hasTable('advanced_courses') || ! Schema::hasColumn('advanced_courses', 'product_track')) {
            return [];
        }

        $out = [];
        $recorded = AdvancedCourse::query()
            ->where('is_active', true)
            ->where('product_track', self::TRACK_RECORDED)
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->first();

        if ($recorded) {
            $out[] = [
                'kind' => AdaptiveLearningSuggestion::KIND_RECORDED_COURSE,
                'title' => app()->getLocale() === 'ar'
                    ? 'كورس مسجّل: '.$recorded->title
                    : 'Recorded course: '.$recorded->title,
                'reason' => app()->getLocale() === 'ar'
                    ? 'مسار فيديو/شرح بالوتيرة المناسبة — مناسب للمراجعة الذاتية.'
                    : 'Self-paced video track — great for independent review.',
                'priority' => 'medium',
                'source' => 'product_track',
                'suggestable_type' => AdvancedCourse::class,
                'suggestable_id' => $recorded->id,
                'action_url' => route('public.recorded-courses'),
                'meta' => ['product_track' => self::TRACK_RECORDED],
            ];
        }

        $book = AdvancedCourse::query()
            ->where('is_active', true)
            ->where('product_track', self::TRACK_BOOK)
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->first();

        if ($book) {
            $out[] = [
                'kind' => AdaptiveLearningSuggestion::KIND_BOOK_MATERIAL,
                'title' => app()->getLocale() === 'ar'
                    ? 'كتاب للقراءة: '.$book->title
                    : 'Reading book: '.$book->title,
                'reason' => app()->getLocale() === 'ar'
                    ? 'مسار الكتب للقراءة على الموقع — upsell للمراجعة والتمهيد.'
                    : 'On-site reading track — upsell for prep and review.',
                'priority' => 'low',
                'source' => 'product_track',
                'suggestable_type' => AdvancedCourse::class,
                'suggestable_id' => $book->id,
                'action_url' => route('public.books'),
                'meta' => ['product_track' => self::TRACK_BOOK],
            ];
        } else {
            $out[] = [
                'kind' => AdaptiveLearningSuggestion::KIND_BOOK_MATERIAL,
                'title' => app()->getLocale() === 'ar'
                    ? 'مكتبة المناهج والمواد'
                    : 'Curriculum library & materials',
                'reason' => app()->getLocale() === 'ar'
                    ? 'اطّلع على المواد/الكتب — ثم فعّل باقة للوصول الكامل.'
                    : 'Browse materials/books — unlock full access with a package.',
                'priority' => 'low',
                'source' => 'product_track',
                'action_url' => route('student.library.curriculum'),
                'meta' => ['upsell' => true],
            ];
        }

        return $out;
    }
}
