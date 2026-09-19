@extends('layouts.app')

@section('title', __('instructor.o1a_title'))
@section('page_title', __('instructor.o1a_title'))

@section('content')
@php
    $windowsCount = $rules->count();
    $daysWithSlots = $grouped->filter(fn ($g) => $g['rules']->isNotEmpty())->count();
    $existingSlots = $rules->map(function ($r) {
        return [
            'day_of_week' => (string) $r->day_of_week,
            'start_time' => substr((string) $r->start_time, 0, 5),
            'end_time' => substr((string) $r->end_time, 0, 5),
            'slot_duration_minutes' => (string) ($r->slot_duration_minutes ?: 50),
        ];
    })->values();
    $sessionsHref = Route::has('instructor.one-to-one-sessions.index')
        ? route('instructor.one-to-one-sessions.index')
        : route('dashboard');
@endphp

<div class="id-page" x-data="availabilityForm()">
    @if($errors->any())
        <div class="id-alert id-alert--err">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <ul style="margin:0;padding-inline-start:18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="id-hero" aria-label="{{ __('instructor.o1a_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.o1a_chip') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.o1a_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.o1a_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ $sessionsHref }}" class="id-btn id-btn--gold">{{ __('instructor.o1o_title') }}</a>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.o1a_windows') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-window-maximize"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.o1a_windows') }}</span>
                <span class="id-kpi__value" x-text="slots.length">{{ $windowsCount }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-calendar-day"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.o1a_active_days') }}</span>
                <span class="id-kpi__value">{{ number_format($daysWithSlots) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-save"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.o1a_saved') }}</span>
                <span class="id-kpi__value">{{ number_format($windowsCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default;align-items:flex-start">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-info"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.tws_hint_label') }}</span>
                <span style="font-size:12px;font-weight:700;color:#6B7A93;line-height:1.4;margin-top:4px">{{ __('instructor.o1a_hint') }}</span>
            </span>
        </article>
    </section>

    <div class="id-grid">
        <form method="POST" action="{{ route('instructor.one-to-one-availability.update') }}" class="id-panel id-form">
            @csrf
            <header class="id-panel__head">
                <div>
                    <h2>{{ __('instructor.o1a_edit_windows') }}</h2>
                    <p class="id-panel__hint">{{ __('instructor.o1a_edit_hint') }}</p>
                </div>
                <button type="button" @click="addSlot()" class="id-btn id-btn--navy" style="min-height:36px;padding:0 12px;font-size:12px">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    {{ __('instructor.o1a_add_slot') }}
                </button>
            </header>

            @include('partials.timezone-select', [
                'value' => old('timezone', auth()->user()?->timezoneCode()),
                'class' => 'id-input',
                'labelClass' => 'block text-[12px] font-extrabold text-[#3A4A63] mb-1.5',
                'label' => __('instructor.o1a_timezone'),
            ])

            <div style="display:flex;flex-direction:column;gap:12px">
                <template x-for="(slot, index) in slots" :key="slot._uid">
                    <div class="id-slot-card">
                        <div class="id-slot-card__head">
                            <span class="id-chip">
                                {{ __('instructor.o1a_slot') }} <span x-text="index + 1"></span>
                            </span>
                            <button type="button" @click="removeSlot(index)" x-show="slots.length > 1" class="id-btn id-btn--outline" style="min-height:32px;padding:0 10px;font-size:12px;color:#B91C1C;border-color:#F5C2C2">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                                {{ __('instructor.o1a_remove_slot') }}
                            </button>
                        </div>
                        <div class="id-form-grid">
                            <div class="id-field id-field--span2">
                                <label>{{ __('instructor.o1a_day') }}</label>
                                <select :name="'slots['+index+'][day_of_week]'" x-model="slot.day_of_week" class="id-input" required>
                                    @foreach($dayLabels as $day => $label)
                                        <option value="{{ $day }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="id-field">
                                <label>{{ __('instructor.o1a_from') }}</label>
                                <input type="time" step="60" :name="'slots['+index+'][start_time]'" x-model="slot.start_time" class="id-input" required>
                            </div>
                            <div class="id-field">
                                <label>{{ __('instructor.o1a_to') }}</label>
                                <input type="time" step="60" :name="'slots['+index+'][end_time]'" x-model="slot.end_time" class="id-input" required>
                            </div>
                            <div class="id-field id-field--span2" style="max-width:220px">
                                <label>{{ __('instructor.o1o_minutes') }}</label>
                                <input type="number" :name="'slots['+index+'][slot_duration_minutes]'" x-model="slot.slot_duration_minutes" min="30" max="180" step="15" class="id-input">
                            </div>
                        </div>
                        <p class="id-field__hint" style="margin-top:10px" :style="slotYield(slot) > 0 ? 'color:#152A4A' : 'color:#B91C1C'">
                            <span x-show="slotYield(slot) > 0" x-text="yieldLabel(slotYield(slot))"></span>
                            <span x-show="slotYield(slot) < 1">{{ __('instructor.o1a_yield_zero') }}</span>
                        </p>
                    </div>
                </template>
            </div>

            <div class="id-foot-actions">
                <p class="id-field__hint" style="margin:0">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    {{ __('instructor.o1a_save_hint') }}
                </p>
                <button type="submit" class="id-btn id-btn--gold">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    {{ __('instructor.o1a_save') }}
                </button>
            </div>
        </form>

        <aside class="id-panel">
            <header class="id-panel__head">
                <h2>{{ __('instructor.o1a_current_schedule') }}</h2>
                <span class="id-chip">{{ __('instructor.o1a_saved_chip') }}</span>
            </header>
            <div style="display:flex;flex-direction:column;gap:10px">
                @foreach($grouped as $dayGroup)
                    @php $has = $dayGroup['rules']->isNotEmpty(); @endphp
                    <div class="id-day-card {{ $has ? 'is-on' : '' }}">
                        <div class="id-day-card__head">
                            <span>{{ $dayGroup['label'] }}</span>
                            @if($has)
                                <span class="id-chip tabular-nums">{{ $dayGroup['rules']->count() }}</span>
                            @else
                                <span class="id-field__hint" style="margin:0">{{ __('instructor.o1a_empty_day') }}</span>
                            @endif
                        </div>
                        <div class="id-day-card__body">
                            @forelse($dayGroup['rules'] as $rule)
                                <span class="id-chip">
                                    <i class="far fa-clock" aria-hidden="true"></i>
                                    {{ substr((string) $rule->start_time, 0, 5) }}–{{ substr((string) $rule->end_time, 0, 5) }}
                                    · {{ (int) $rule->slot_duration_minutes }} {{ __('instructor.o1o_minutes') }}
                                </span>
                            @empty
                                <span class="id-chip id-chip--muted" style="width:100%;justify-content:center;padding:12px">{{ __('instructor.o1a_no_windows') }}</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>

<script>
function availabilityForm() {
    const existing = @json($existingSlots);
    const yieldTpl = @json(__('instructor.o1a_yield'));
    let uid = 1;
    const withIds = (existing.length ? existing : [{ day_of_week: '1', start_time: '16:00', end_time: '22:00', slot_duration_minutes: '50' }])
        .map(function (slot) {
            slot._uid = uid++;
            return slot;
        });
    return {
        slots: withIds,
        nextUid: uid,
        addSlot() {
            this.slots.push({
                _uid: this.nextUid++,
                day_of_week: '1',
                start_time: '16:00',
                end_time: '22:00',
                slot_duration_minutes: '50'
            });
        },
        removeSlot(i) {
            this.slots.splice(i, 1);
        },
        minutes(t) {
            if (!t) return 0;
            const p = String(t).split(':');
            return (parseInt(p[0], 10) || 0) * 60 + (parseInt(p[1], 10) || 0);
        },
        slotYield(slot) {
            let start = this.minutes(slot.start_time);
            let end = this.minutes(slot.end_time);
            if (end === 0 && start > 0) end = 24 * 60;
            const duration = parseInt(slot.slot_duration_minutes, 10) || 50;
            if (end <= start || duration < 1) return 0;
            return Math.floor((end - start) / duration);
        },
        yieldLabel(n) {
            return String(yieldTpl).replace(':count', n);
        }
    };
}
</script>
@endsection
