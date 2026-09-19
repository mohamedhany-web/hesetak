@extends('layouts.app')

@section('title', $assignment->title . ' - ' . config('app.name'))
@section('page_title', $assignment->title)

@section('content')
@php
    $locale = app()->getLocale();
    $chip = match ($assignment->status) {
        'published' => 'id-chip--ok',
        'draft' => 'id-chip--warn',
        default => 'id-chip--muted',
    };
    $statusLabel = match ($assignment->status) {
        'published' => __('instructor.published'),
        'draft' => __('instructor.draft'),
        default => __('instructor.archived'),
    };
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $assignment->title }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.assignments') }}</p>
            <h2 class="id-hero__title">{{ $assignment->title }}</h2>
            <p class="id-hero__meta" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <span>{{ $assignment->course->title ?? '—' }}</span>
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ $statusLabel }}</span>
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ $assignment->max_score }} {{ __('instructor.score_marks') }}</span>
                @if($assignment->due_date)
                    <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ $assignment->due_date->format('Y/m/d H:i') }}</span>
                @endif
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.assignments.edit', $assignment) }}" class="id-btn id-btn--ghost">
                <i class="fas fa-edit" aria-hidden="true"></i>
                {{ __('common.edit') }}
            </a>
            <a href="{{ route('instructor.assignments.submissions', $assignment) }}" class="id-btn id-btn--gold">
                <i class="fas fa-inbox" aria-hidden="true"></i>
                {{ __('instructor.submissions_title') }} ({{ $submissionStats['total'] ?? 0 }})
            </a>
            <a href="{{ route('instructor.assignments.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    @if($assignment->description)
        <section class="id-panel">
            <header class="id-panel__head">
                <h2><i class="fas fa-align-left" aria-hidden="true"></i> {{ __('instructor.description') }}</h2>
            </header>
            <p class="id-prose">{{ $assignment->description }}</p>
        </section>
    @endif

    @php
        $instrRes = is_array($assignment->resource_attachments) ? $assignment->resource_attachments : [];
    @endphp
    @if(count($instrRes) > 0)
        <section class="id-panel">
            <header class="id-panel__head">
                <h2><i class="fas fa-paperclip" aria-hidden="true"></i> {{ __('instructor.assignment_attachments_students') }}</h2>
            </header>
            <div class="id-meta">
                @foreach($instrRes as $att)
                    @php
                        $p = is_array($att) ? ($att['path'] ?? '') : '';
                        $u = $p ? (\App\Services\AssignmentFileStorage::publicUrl($p) ?? '#') : '#';
                        $lb = is_array($att) ? ($att['original_name'] ?? basename($p)) : '';
                    @endphp
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-file" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.attachments_label') ?? 'ملف' }}</span>
                        <strong><a href="{{ $u }}" target="_blank" rel="noopener" class="id-link">{{ $lb }}</a></strong>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="id-panel id-panel--wide">
        <header class="id-panel__head">
            <h2>{{ __('instructor.last_submissions') }}</h2>
            <span class="id-chip {{ $chip }}">{{ $statusLabel }}</span>
        </header>
        @if($submissions->count() > 0)
            <div class="id-table-wrap">
                <table class="id-table">
                    <thead>
                        <tr>
                            <th>{{ __('instructor.student') }}</th>
                            <th>{{ __('instructor.submission_date') }}</th>
                            <th>{{ __('common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $sub)
                            <tr>
                                <td><strong>{{ $sub->student->name ?? '—' }}</strong></td>
                                <td class="tabular-nums muted">{{ $sub->submitted_at?->format('Y/m/d H:i') }}</td>
                                <td>
                                    <span class="id-chip {{ $sub->status === 'graded' ? 'id-chip--ok' : 'id-chip--warn' }}">{{ $sub->status }}</span>
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
            <div class="id-empty" style="border:0;background:transparent;padding:24px 8px">
                <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-inbox"></i></span>
                <p>{{ __('instructor.no_submissions') }}</p>
            </div>
        @endif
    </section>
</div>
@endsection
