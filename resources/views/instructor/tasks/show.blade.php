@extends('layouts.app')

@section('title', $task->title . ' - ' . __('instructor.tasks_from_management'))
@section('page_title', __('instructor.task_details'))

@section('content')
@php
    $locale = app()->getLocale();
    $prioChip = match ($task->priority) {
        'urgent' => 'id-chip--rose',
        'high' => 'id-chip--warn',
        'medium' => 'id-chip--muted',
        default => 'id-chip--muted',
    };
    $prioLabel = match ($task->priority) {
        'urgent' => __('instructor.urgent'),
        'high' => __('instructor.high'),
        'medium' => __('instructor.medium'),
        default => __('instructor.low'),
    };
    $stChip = match ($task->status) {
        'completed' => 'id-chip--ok',
        'in_progress' => 'id-chip--muted',
        default => 'id-chip--warn',
    };
    $stLabel = match ($task->status) {
        'completed' => __('instructor.completed'),
        'in_progress' => __('instructor.in_progress'),
        default => __('instructor.pending'),
    };
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $task->title }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.tasks_from_management') }}</p>
            <h2 class="id-hero__title">{{ $task->title }}</h2>
            <p class="id-hero__meta" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                @if($task->assigner)
                    <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ __('instructor.from_management') }}</span>
                @endif
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ $prioLabel }}</span>
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ $stLabel }}</span>
            </p>
        </div>
        <div class="id-hero__actions">
            @if(! $task->assigned_by)
                <a href="{{ route('instructor.tasks.edit', $task) }}" class="id-btn id-btn--gold">
                    <i class="fas fa-edit" aria-hidden="true"></i>
                    {{ __('common.edit') }}
                </a>
            @endif
            <a href="{{ route('instructor.tasks.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    @if($task->description)
        <section class="id-panel">
            <header class="id-panel__head">
                <h2>{{ __('instructor.description') }}</h2>
            </header>
            <p style="margin:0;font-size:14px;font-weight:600;line-height:1.8;color:#3A4A63;white-space:pre-wrap">{{ $task->description }}</p>
        </section>
    @endif

    <section class="id-panel">
        <header class="id-panel__head">
            <h2>{{ __('instructor.additional_details') }}</h2>
        </header>
        <div class="id-meta">
            @if($task->relatedCourse)
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-book" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.course') }}</span>
                    <strong>{{ $task->relatedCourse->title }}</strong>
                </div>
            @endif
            @if($task->relatedLecture)
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.lecture') }}</span>
                    <strong>{{ $task->relatedLecture->title }}</strong>
                </div>
            @endif
            @if($task->due_date)
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.due_date') }}</span>
                    <strong>
                        {{ $task->due_date->format('Y-m-d H:i') }}
                        @if($task->due_date->isPast() && $task->status != 'completed')
                            <span class="id-chip id-chip--rose" style="margin-inline-start:8px">{{ __('instructor.late') }}</span>
                        @endif
                    </strong>
                </div>
            @endif
            @if($task->completed_at)
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-check-double" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.completed') }}</span>
                    <strong>{{ $task->completed_at->format('Y-m-d H:i') }}</strong>
                </div>
            @endif
            @if($task->assigned_by && isset($task->progress))
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.progress_label') }}</span>
                    <strong class="tabular-nums">{{ (int) ($task->progress ?? 0) }}%</strong>
                </div>
            @endif
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-flag" aria-hidden="true"></i></span>
                <span>{{ __('instructor.priority') }}</span>
                <strong><span class="id-chip {{ $prioChip }}">{{ $prioLabel }}</span></strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-info-circle" aria-hidden="true"></i></span>
                <span>{{ __('common.status') }}</span>
                <strong><span class="id-chip {{ $stChip }}">{{ $stLabel }}</span></strong>
            </div>
        </div>
    </section>

    @if($task->assigned_by)
        <section class="id-panel">
            <header class="id-panel__head">
                <h2>{{ __('instructor.update_progress') }}</h2>
            </header>
            <form action="{{ route('instructor.tasks.update-progress', $task) }}" method="POST" class="id-form">
                @csrf
                @method('PUT')
                <div class="id-form-grid" style="align-items:end">
                    <div class="id-field">
                        <label for="task-prog-status">{{ __('common.status') }}</label>
                        <select name="status" id="task-prog-status" class="id-select">
                            <option value="pending" @selected($task->status === 'pending')>{{ __('instructor.pending') }}</option>
                            <option value="in_progress" @selected($task->status === 'in_progress')>{{ __('instructor.in_progress') }}</option>
                            <option value="completed" @selected($task->status === 'completed')>{{ __('instructor.completed') }}</option>
                        </select>
                    </div>
                    <div class="id-field">
                        <label for="task-prog">{{ __('instructor.progress_percent') }}</label>
                        <input type="number" name="progress" id="task-prog" min="0" max="100"
                               value="{{ (int) ($task->progress ?? 0) }}" class="id-input">
                    </div>
                </div>
                <div>
                    <button type="submit" class="id-btn id-btn--navy">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        {{ __('instructor.save_progress') }}
                    </button>
                </div>
            </form>
        </section>

        <section class="id-panel id-panel--wide">
            <header class="id-panel__head">
                <h2>{{ __('instructor.my_submissions') }}</h2>
            </header>

            @if($task->deliverables->count() > 0)
                <div class="id-list" style="margin-bottom:16px">
                    @foreach($task->deliverables as $d)
                        @php
                            $dChip = match ($d->status) {
                                'approved' => 'id-chip--ok',
                                'rejected', 'needs_revision' => 'id-chip--rose',
                                default => 'id-chip--muted',
                            };
                            $dLabel = match ($d->status) {
                                'approved' => __('instructor.approved'),
                                'rejected' => __('instructor.rejected'),
                                'needs_revision' => __('instructor.needs_revision'),
                                default => __('instructor.submitted_status'),
                            };
                        @endphp
                        <article class="id-list__row">
                            <span class="id-list__ico id-list__ico--gold" aria-hidden="true"><i class="fas fa-paper-plane"></i></span>
                            <div class="id-list__body">
                                <div class="id-list__title">{{ $d->title }}</div>
                                @if($d->description)
                                    <div class="id-list__meta">{{ $d->description }}</div>
                                @endif
                                <div class="id-list__meta" style="margin-top:4px">
                                    {{ $d->submitted_at?->format('Y-m-d H:i') }}
                                    @if($d->delivery_type === 'link' && $d->link_url)
                                        · <a href="{{ $d->link_url }}" target="_blank" rel="noopener">{{ __('instructor.open_link') }}</a>
                                    @endif
                                    @if($d->file_path)
                                        · <a href="{{ Storage::url($d->file_path) }}" target="_blank" rel="noopener">{{ __('instructor.download_file') }}</a>
                                    @endif
                                </div>
                                @if($d->feedback)
                                    <div class="id-alert id-alert--info" style="margin-top:8px;padding:8px 10px">
                                        <strong>{{ __('instructor.admin_notes_label') }}:</strong> {{ $d->feedback }}
                                    </div>
                                @endif
                            </div>
                            <span class="id-chip {{ $dChip }}">{{ $dLabel }}</span>
                        </article>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('instructor.tasks.submit-deliverable', $task) }}" method="POST" enctype="multipart/form-data" class="id-form">
                @csrf
                <div class="id-form-grid">
                    <div class="id-field">
                        <label for="deliv-title">{{ __('instructor.submission_title_label') }} <span style="color:#B91C1C">*</span></label>
                        <input type="text" name="title" id="deliv-title" required maxlength="255" value="{{ old('title') }}"
                               class="id-input" placeholder="{{ __('instructor.submission_title_placeholder') }}">
                    </div>
                    <div class="id-field">
                        <label for="delivery_type">{{ __('instructor.submission_type_label') }}</label>
                        <select name="delivery_type" id="delivery_type" class="id-select">
                            <option value="file">{{ __('instructor.file_type') }}</option>
                            <option value="image">{{ __('instructor.image_type') }}</option>
                            <option value="link">{{ __('instructor.link_type') }}</option>
                        </select>
                    </div>
                    <div class="id-field id-field--span2">
                        <label for="deliv-desc">{{ __('instructor.description_optional') }}</label>
                        <textarea name="description" id="deliv-desc" rows="2" class="id-input"
                                  style="min-height:64px;padding-top:10px;padding-bottom:10px;resize:vertical"
                                  placeholder="{{ __('instructor.submission_description_placeholder') }}">{{ old('description') }}</textarea>
                    </div>
                    <div class="id-field id-field--span2" id="file_input">
                        <label for="deliv-file">{{ __('instructor.file_label') }}</label>
                        <input type="file" name="file" id="deliv-file" accept=".pdf,.doc,.docx,.xls,.xlsx,image/*"
                               class="id-input" style="padding-top:10px;padding-bottom:10px">
                        <p class="id-field__hint">{{ __('instructor.max_10mb') }}</p>
                    </div>
                    <div class="id-field id-field--span2" id="link_input" style="display:none">
                        <label for="deliv-link">{{ __('instructor.submission_link_label') }}</label>
                        <input type="url" name="link_url" id="deliv-link" value="{{ old('link_url') }}"
                               placeholder="https://..." class="id-input">
                    </div>
                </div>
                <div>
                    <button type="submit" class="id-btn id-btn--navy">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        {{ __('instructor.submit_work') }}
                    </button>
                </div>
            </form>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('delivery_type')?.addEventListener('change', function () {
    var type = this.value;
    var fileInput = document.getElementById('file_input');
    var linkInput = document.getElementById('link_input');
    if (fileInput) fileInput.style.display = type === 'link' ? 'none' : '';
    if (linkInput) linkInput.style.display = type === 'link' ? '' : 'none';
});
</script>
@endpush
