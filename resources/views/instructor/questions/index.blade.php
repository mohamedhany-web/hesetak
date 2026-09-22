@extends('layouts.app')

@section('title', __('instructor.questions') ?? 'الأسئلة')
@section('page_title', __('instructor.questions') ?? 'الأسئلة')

@section('content')
<div class="su-page">
    <div class="su-page-head">
        <div class="min-w-0">
            <h1 class="su-page-head__title">
                <i class="fas fa-question-circle su-page-head__ico" aria-hidden="true"></i>
                {{ __('instructor.questions') ?? 'أسئلتي' }}
            </h1>
            <p class="su-page-head__sub">{{ app()->getLocale() === 'ar' ? 'أسئلة بنوكك المرتبطة بالكورسات المسجّلة' : 'Questions from your course banks' }}</p>
        </div>
        <div class="su-page-head__actions">
            @if(Route::has('instructor.question-banks.index'))
                <a href="{{ route('instructor.question-banks.index') }}" class="su-btn su-btn--primary">
                    <i class="fas fa-database" aria-hidden="true"></i>
                    {{ __('instructor.question_banks') }}
                </a>
            @endif
        </div>
    </div>

    @if($questions->count() > 0)
        <div class="su-table-wrap">
            <table class="su-table">
                <thead>
                    <tr>
                        <th>{{ app()->getLocale() === 'ar' ? 'السؤال' : 'Question' }}</th>
                        <th>{{ __('instructor.question_banks') }}</th>
                        <th>{{ app()->getLocale() === 'ar' ? 'النوع' : 'Type' }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($questions as $question)
                        <tr>
                            <td>{{ Str::limit(strip_tags((string) $question->question), 80) }}</td>
                            <td>{{ $question->questionBank?->title ?? '—' }}</td>
                            <td>{{ $question->type }}</td>
                            <td class="text-end">
                                @if(Route::has('instructor.questions.edit'))
                                    <a href="{{ route('instructor.questions.edit', $question) }}" class="su-btn su-btn--sm">{{ __('instructor.edit') ?? 'تعديل' }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $questions->links() }}</div>
    @else
        <div class="su-empty">
            <p>{{ app()->getLocale() === 'ar' ? 'لا توجد أسئلة بعد. أنشئ بنكاً ثم أضف أسئلة.' : 'No questions yet.' }}</p>
        </div>
    @endif
</div>
@endsection
