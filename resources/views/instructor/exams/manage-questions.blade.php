@extends('layouts.app')

@section('title', __('instructor.manage_questions'))
@section('page_title', __('instructor.manage_questions'))

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="id-page" x-data="{ activeTab: 'current', showAddModal: false, showCreateModal: false }">
    <section class="id-hero" aria-label="{{ __('instructor.manage_questions') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.exams') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.manage_questions') }}</h2>
            <p class="id-hero__meta">{{ $exam->title }}</p>
        </div>
        <div class="id-hero__actions">
            <button type="button" @click="showAddModal = true" class="id-btn id-btn--gold">
                <i class="fas fa-database" aria-hidden="true"></i>
                {{ __('instructor.add_from_bank') }}
            </button>
            <button type="button" @click="showCreateModal = true" class="id-btn id-btn--ghost">
                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                {{ __('instructor.new_question') }}
            </button>
            <a href="{{ route('instructor.exams.show', $exam) }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="id-alert id-alert--ok">
            <i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="id-alert id-alert--err">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="id-alert id-alert--err">
            <p style="margin:0 0 8px;font-weight:800">{{ __('instructor.form_fix_errors') }}</p>
            <ul style="margin:0;padding-inline-start:1.25rem;font-size:13px">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="id-panel id-panel--wide">
        <div class="id-tabs" role="tablist">
            <button type="button" class="id-tab" :class="{ 'is-on': activeTab === 'current' }" @click="activeTab = 'current'">
                <i class="fas fa-list" aria-hidden="true"></i>
                {{ __('instructor.current_questions') }} ({{ $exam->questions->count() }})
            </button>
            <button type="button" class="id-tab" :class="{ 'is-on': activeTab === 'bank' }" @click="activeTab = 'bank'">
                <i class="fas fa-database" aria-hidden="true"></i>
                {{ __('instructor.question_bank') }} ({{ $availableQuestions->count() }})
            </button>
        </div>

        <div x-show="activeTab === 'current'" x-cloak>
            @if($exam->questions->count() > 0)
                <div class="id-list" id="questions-list">
                    @foreach($exam->questions as $index => $question)
                        <article class="id-list__row">
                            <span class="id-list__ico" aria-hidden="true" style="font-weight:800">{{ $index + 1 }}</span>
                            <div class="id-list__body">
                                <div class="id-list__title" style="font-size:14px">{{ $question->question }}</div>
                                <div class="id-list__meta">
                                    {{ $question->getTypeLabel() }} ·
                                    {{ $question->pivot->marks ?? 1 }} {{ __('instructor.point_unit') }} ·
                                    {{ $question->getDifficultyLabel() }}
                                </div>
                            </div>
                            <div class="id-list__actions">
                                <form action="{{ route('instructor.exams.questions.remove', [$exam, $question->id]) }}" method="POST"
                                      onsubmit="return confirm(@json(__('instructor.confirm_remove_question')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="id-icon-btn" title="{{ __('common.delete') }}">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-question-circle"></i></span>
                    <p>{{ __('instructor.no_questions') }}</p>
                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.add_questions_hint') }}</p>
                    <div class="id-empty__actions">
                        <button type="button" @click="showAddModal = true" class="id-btn id-btn--navy">{{ __('instructor.add_from_bank') }}</button>
                        <button type="button" @click="showCreateModal = true" class="id-btn id-btn--outline">{{ __('instructor.new_question') }}</button>
                    </div>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'bank'" x-cloak>
            @if($availableQuestions->count() > 0)
                <div class="id-cards">
                    @foreach($availableQuestions as $question)
                        <article class="id-card">
                            <p class="id-card__title">{{ Str::limit($question->question, 100) }}</p>
                            <div class="id-card__meta">
                                <span class="id-chip id-chip--muted">{{ $question->getTypeLabel() }}</span>
                                <span class="id-chip id-chip--muted">{{ $question->getDifficultyLabel() }}</span>
                                @if(!$question->is_active)
                                    <span class="id-chip id-chip--warn">{{ __('instructor.inactive') }}</span>
                                @endif
                                @if($question->questionBank)
                                    <span class="id-chip">{{ $question->questionBank->title }}</span>
                                @endif
                            </div>
                            <form action="{{ route('instructor.exams.questions.add-from-bank', $exam) }}" method="POST"
                                  style="display:flex;gap:8px;align-items:center;margin-top:auto">
                                @csrf
                                <input type="hidden" name="question_id" value="{{ $question->id }}">
                                <input type="number" name="marks" value="{{ $question->points ?? 1 }}" min="0.5" step="0.5" required
                                       class="id-input" style="width:5rem;min-height:36px">
                                <button type="submit" class="id-btn id-btn--navy id-btn--sm" style="flex:1">
                                    <i class="fas fa-plus" aria-hidden="true"></i> {{ __('instructor.add_short') }}
                                </button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-database"></i></span>
                    <p>{{ __('instructor.no_bank_questions') }}</p>
                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.create_bank_first') }}</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Create question modal --}}
    <div class="id-modal" x-show="showCreateModal" x-cloak @click.self="showCreateModal = false" style="display:none" :style="showCreateModal && { display: 'flex' }">
        <div class="id-modal__panel" @click.stop>
            <div class="id-modal__head">
                <h3>{{ __('instructor.create_new_question') }}</h3>
                <button type="button" @click="showCreateModal = false" class="id-icon-btn" style="background:#F1F4F8;color:#6B7A93" aria-label="{{ __('common.cancel') }}">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            @if($questionBanks->isEmpty())
                <div class="id-modal__body">
                    <div class="id-empty" style="border:0;background:transparent;padding:16px">
                        <p>{{ __('instructor.need_question_bank_first') }}</p>
                        <div class="id-empty__actions">
                            <a href="{{ route('instructor.question-banks.index') }}" class="id-btn id-btn--navy">
                                <i class="fas fa-database" aria-hidden="true"></i> {{ __('instructor.question_banks') }}
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <form action="{{ route('instructor.exams.questions.create-new', $exam) }}" method="POST" class="id-modal__body id-form">
                    @csrf
                    <div class="id-form-grid">
                        <div class="id-field id-field--span2">
                            <label>{{ __('instructor.question_bank') }} <span style="color:#B91C1C">*</span></label>
                            <select name="question_bank_id" required class="id-select">
                                <option value="">{{ __('instructor.choose_question_bank') }}</option>
                                @foreach($questionBanks as $bank)
                                    <option value="{{ $bank->id }}" @selected(old('question_bank_id') == $bank->id)>{{ $bank->title }}</option>
                                @endforeach
                            </select>
                            @error('question_bank_id')<p class="id-field__err">{{ $message }}</p>@enderror
                        </div>
                        <div class="id-field id-field--span2">
                            <label>{{ __('instructor.question_type') }} <span style="color:#B91C1C">*</span></label>
                            <select name="type" id="question_type" required onchange="updateQuestionForm()" class="id-select">
                                <option value="">{{ __('instructor.choose_type') }}</option>
                                <option value="multiple_choice">{{ __('instructor.type_multiple_choice') }}</option>
                                <option value="true_false">{{ __('instructor.type_true_false') }}</option>
                            </select>
                        </div>
                        <div class="id-field id-field--span2">
                            <label>{{ __('instructor.question_text') }} <span style="color:#B91C1C">*</span></label>
                            <textarea name="question" rows="3" required class="id-input"
                                      style="min-height:88px;padding-top:10px;padding-bottom:10px;resize:vertical"
                                      placeholder="{{ __('instructor.question_text_ph') }}"></textarea>
                        </div>
                        <div class="id-field id-field--span2" id="options_field" style="display:none">
                            <label>{{ __('instructor.options_one_per_line') }}</label>
                            <textarea name="options_text" rows="3" class="id-input"
                                      style="min-height:88px;padding-top:10px;padding-bottom:10px;resize:vertical"
                                      placeholder="{{ __('instructor.options_ph') }}"></textarea>
                        </div>
                        <div class="id-field id-field--span2">
                            <label>{{ __('instructor.correct_answer') }} <span style="color:#B91C1C">*</span></label>
                            <input type="text" name="correct_answer" required class="id-input" placeholder="{{ __('instructor.correct_answer') }}">
                        </div>
                        <div class="id-field id-field--span2">
                            <label>{{ __('instructor.explanation') }}</label>
                            <textarea name="explanation" rows="2" class="id-input"
                                      style="min-height:72px;padding-top:10px;padding-bottom:10px;resize:vertical"></textarea>
                        </div>
                        <div class="id-field">
                            <label>{{ __('instructor.points') }} <span style="color:#B91C1C">*</span></label>
                            <input type="number" name="points" value="1" min="0.5" step="0.5" required class="id-input">
                        </div>
                        <div class="id-field">
                            <label>{{ __('instructor.difficulty') }} <span style="color:#B91C1C">*</span></label>
                            <select name="difficulty_level" required class="id-select">
                                <option value="easy">{{ __('instructor.easy') }}</option>
                                <option value="medium" selected>{{ __('instructor.medium') }}</option>
                                <option value="hard">{{ __('instructor.hard') }}</option>
                            </select>
                        </div>
                        <div class="id-field">
                            <label>{{ __('instructor.exam_marks') }} <span style="color:#B91C1C">*</span></label>
                            <input type="number" name="marks" value="1" min="0.5" step="0.5" required class="id-input">
                        </div>
                    </div>
                    <div class="id-foot-actions" style="margin-top:16px">
                        <button type="button" @click="showCreateModal = false" class="id-btn id-btn--outline">{{ __('common.cancel') }}</button>
                        <button type="submit" class="id-btn id-btn--navy">
                            <i class="fas fa-save" aria-hidden="true"></i> {{ __('instructor.create_and_add') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- Add from bank modal --}}
    <div class="id-modal" x-show="showAddModal" x-cloak @click.self="showAddModal = false" style="display:none" :style="showAddModal && { display: 'flex' }">
        <div class="id-modal__panel" @click.stop>
            <div class="id-modal__head">
                <h3>{{ __('instructor.add_from_bank') }}</h3>
                <button type="button" @click="showAddModal = false" class="id-icon-btn" style="background:#F1F4F8;color:#6B7A93" aria-label="{{ __('common.cancel') }}">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="id-modal__body">
                <p class="id-modal__sub">{{ __('instructor.add_from_bank_hint') }}</p>
                @if($availableQuestions->isEmpty())
                    <div class="id-empty" style="border:0;background:transparent;padding:16px">
                        <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-database"></i></span>
                        <p>{{ __('instructor.no_bank_questions') }}</p>
                        <div class="id-empty__actions">
                            <a href="{{ route('instructor.question-banks.index') }}" class="id-btn id-btn--outline">{{ __('instructor.question_banks') }}</a>
                        </div>
                    </div>
                @else
                    <div class="id-list">
                        @foreach($availableQuestions as $question)
                            <form action="{{ route('instructor.exams.questions.add-from-bank', $exam) }}" method="POST" class="id-list__row" style="margin:0">
                                @csrf
                                <input type="hidden" name="question_id" value="{{ $question->id }}">
                                <div class="id-list__body">
                                    <div class="id-list__title" style="font-size:13px">{{ Str::limit($question->question, 70) }}</div>
                                    <div class="id-list__meta">
                                        {{ $question->getTypeLabel() }} · {{ $question->getDifficultyLabel() }}
                                        @if(!$question->is_active) · {{ __('instructor.inactive') }} @endif
                                    </div>
                                </div>
                                <div class="id-list__actions">
                                    <input type="number" name="marks" value="{{ $question->points ?? 1 }}" min="0.5" step="0.5" required
                                           class="id-input" style="width:4.5rem;min-height:32px">
                                    <button type="submit" class="id-btn id-btn--navy id-btn--sm">
                                        <i class="fas fa-plus" aria-hidden="true"></i> {{ __('instructor.add_short') }}
                                    </button>
                                </div>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateQuestionForm() {
    var type = document.getElementById('question_type').value;
    var el = document.getElementById('options_field');
    el.style.display = type === 'multiple_choice' ? 'block' : 'none';
}
document.querySelectorAll('form[action*="create-new"]').forEach(function(form) {
    form.addEventListener('submit', function() {
        var optionsText = form.querySelector('textarea[name="options_text"]');
        if (optionsText && optionsText.value) {
            var options = optionsText.value.split('\n').filter(function(opt) { return opt.trim(); });
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'options';
            input.value = JSON.stringify(options);
            form.appendChild(input);
        }
    });
});
</script>
@endpush
@endsection
