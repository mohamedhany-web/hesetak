@extends('layouts.app')

@section('title', __('instructor.submissions_of') . ': ' . $assignment->title . ' - ' . config('app.name'))
@section('page_title', __('instructor.submissions_title'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.submissions_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.assignments') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.submissions_of') }}: {{ $assignment->title }}</h2>
            <p class="id-hero__meta">{{ __('instructor.max_score_points') }}: {{ $assignment->max_score }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.assignments.show', $assignment) }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back_to_assignment') }}
            </a>
        </div>
    </section>

    <section class="id-panel id-panel--wide">
        <header class="id-panel__head">
            <h2>{{ __('instructor.submissions_list') }}</h2>
            @if($submissions->count() > 0)
                <span class="id-panel__badge">{{ number_format($submissions->total() ?? $submissions->count()) }}</span>
            @endif
        </header>

        @if($submissions->count() > 0)
            <div class="id-table-wrap">
                <table class="id-table">
                    <thead>
                        <tr>
                            <th>{{ __('instructor.student') }}</th>
                            <th>{{ __('instructor.submission_date') }}</th>
                            <th>{{ __('instructor.score_label') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('instructor.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $sub)
                            <tr>
                                <td><strong>{{ $sub->student->name ?? '—' }}</strong></td>
                                <td class="tabular-nums muted">{{ $sub->submitted_at?->format('Y/m/d H:i') }}</td>
                                <td class="tabular-nums">
                                    @if($sub->score !== null)
                                        <strong>{{ $sub->score }}/{{ $assignment->max_score }}</strong>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub->status === 'graded')
                                        <span class="id-chip id-chip--ok">{{ __('instructor.graded_status') }}</span>
                                    @elseif($sub->status === 'returned')
                                        <span class="id-chip id-chip--muted">{{ __('instructor.returned_status') }}</span>
                                    @else
                                        <span class="id-chip id-chip--warn">{{ __('instructor.pending_review') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" onclick="toggleDetail({{ $sub->id }})" class="id-btn id-btn--outline id-btn--sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                        {{ __('instructor.view_grade') }}
                                    </button>
                                </td>
                            </tr>
                            <tr id="detail-{{ $sub->id }}" class="hidden">
                                <td colspan="5" style="background:#F8FAFC;padding:0">
                                    <div class="id-sub-detail" style="margin:8px;max-width:48rem">
                                        @if($sub->content)
                                            <div>
                                                <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.content_label') }}</p>
                                                <p class="id-prose">{{ $sub->content }}</p>
                                            </div>
                                        @endif
                                        @if($sub->attachments && count($sub->attachments) > 0)
                                            <div>
                                                <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.attachments_label') }}</p>
                                                <div class="id-meta">
                                                    @foreach($sub->attachments as $att)
                                                        @php
                                                            $path = is_string($att) ? $att : ($att['path'] ?? $att['url'] ?? null);
                                                            $url = $path ? (\App\Services\AssignmentFileStorage::publicUrl($path) ?? (str_starts_with((string) $path, 'http') ? $path : url('storage/'.$path))) : '#';
                                                            $label = is_array($att) ? ($att['original_name'] ?? $att['name'] ?? basename($path ?? __('instructor.attachment_fallback'))) : basename($att);
                                                        @endphp
                                                        <div class="id-meta__row">
                                                            <span class="id-meta__ico"><i class="fas fa-file" aria-hidden="true"></i></span>
                                                            <span>{{ __('instructor.attachments_label') }}</span>
                                                            <strong><a href="{{ $url }}" target="_blank" rel="noopener" class="id-link">{{ $label }}</a></strong>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        @if($sub->feedback)
                                            <div>
                                                <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.feedback_label') }}</p>
                                                <p style="margin:0;font-size:13px;font-weight:600;color:#6B7A93">{{ $sub->feedback }}</p>
                                            </div>
                                        @endif
                                        <form action="{{ route('instructor.assignments.grade', [$assignment, $sub]) }}" method="POST" class="id-form"
                                              style="border-top:1px solid #E6EEF8;padding-top:12px">
                                            @csrf
                                            <div class="id-form-grid" style="align-items:end">
                                                <div class="id-field">
                                                    <label>{{ __('instructor.score_label') }} (0–{{ $assignment->max_score }})</label>
                                                    <input type="number" name="score" min="0" max="{{ $assignment->max_score }}"
                                                           value="{{ old('score', $sub->score) }}" class="id-input" style="max-width:8rem">
                                                </div>
                                                <div class="id-field id-field--span2">
                                                    <label>{{ __('instructor.feedback_label') }}</label>
                                                    <input type="text" name="feedback" value="{{ old('feedback', $sub->feedback) }}"
                                                           placeholder="{{ __('instructor.optional_comment') }}" class="id-input">
                                                </div>
                                                <div class="id-field">
                                                    <label>{{ __('common.status') }}</label>
                                                    <select name="status" class="id-select">
                                                        <option value="submitted" @selected($sub->status === 'submitted')>{{ __('instructor.pending_review') }}</option>
                                                        <option value="graded" @selected($sub->status === 'graded')>{{ __('instructor.graded_status') }}</option>
                                                        <option value="returned" @selected($sub->status === 'returned')>{{ __('instructor.returned_status') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div>
                                                <button type="submit" class="id-btn id-btn--navy">
                                                    <i class="fas fa-check" aria-hidden="true"></i>
                                                    {{ __('instructor.save_grade') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(method_exists($submissions, 'hasPages') && $submissions->hasPages())
                <div class="id-pager">{{ $submissions->links() }}</div>
            @endif
        @else
            <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-inbox"></i></span>
                <p>{{ __('instructor.no_submissions_yet') }}</p>
            </div>
        @endif
    </section>
</div>

@if($submissions->count() > 0)
<script>
function toggleDetail(id) {
    const row = document.getElementById('detail-' + id);
    if (!row) return;
    row.classList.toggle('hidden');
}
</script>
@endif
@endsection
