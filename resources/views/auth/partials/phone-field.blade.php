@php
  $field = $field ?? 'phone';
  $dialField = $dialField ?? 'country_code';
  $isoField = $isoField ?? 'country_iso';
  $required = $required ?? true;
  $autocomplete = $autocomplete ?? 'tel-national';
  $oldDial = old($dialField, $selectedCountry['dial_code'] ?? '+966');
  $oldIso = old($isoField, $selectedCountry['code'] ?? 'SA');
  $picked = collect($phoneCountries)->firstWhere('code', $oldIso)
      ?? collect($phoneCountries)->firstWhere('dial_code', $oldDial)
      ?? $selectedCountry;
@endphp
<div class="lasles-auth-field">
  <label>{{ $label }}</label>
  <div
    class="lasles-auth-phone @error($field) has-error @enderror"
    x-data="{
      open: false,
      q: '',
      dial: @js($picked['dial_code'] ?? '+966'),
      iso: @js($picked['code'] ?? 'SA'),
      name: @js($isRtl ? ($picked['name_ar'] ?? '') : ($picked['name_en'] ?? $picked['name_ar'] ?? '')),
      placeholder: @js($picked['placeholder'] ?? '5xxxxxxxx'),
      countries: @js($phoneCountriesUi),
      localeRtl: @js($isRtl),
      get filtered() {
        const term = (this.q || '').trim().toLowerCase();
        if (!term) return this.countries;
        return this.countries.filter((c) => {
          const hay = [c.dial_code, c.code, c.name_ar, c.name_en].join(' ').toLowerCase();
          return hay.includes(term);
        });
      },
      labelOf(c) {
        return this.localeRtl ? (c.name_ar || c.name_en) : (c.name_en || c.name_ar);
      },
      select(c) {
        this.dial = c.dial_code;
        this.iso = c.code;
        this.name = this.labelOf(c);
        this.placeholder = c.placeholder || '';
        this.open = false;
        this.q = '';
      },
      toggle() {
        this.open = !this.open;
        if (this.open) {
          this.$nextTick(() => this.$refs.ccSearch && this.$refs.ccSearch.focus());
        }
      }
    }"
    @keydown.escape.window="open = false"
  >
    <input type="hidden" name="{{ $dialField }}" :value="dial" @if($required) required @endif>
    <input type="hidden" name="{{ $isoField }}" :value="iso">
    <div class="lasles-auth-cc" @click.outside="open = false">
      <button
        type="button"
        class="lasles-auth-cc-btn"
        dir="ltr"
        @click="toggle()"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
        aria-label="{{ __('auth.country_code_aria') }}"
      >
        <span class="lasles-auth-cc-dial" x-text="dial"></span>
        <span class="lasles-auth-cc-name" x-text="name"></span>
        <i class="fas fa-chevron-down" aria-hidden="true"></i>
      </button>
      <div class="lasles-auth-cc-panel" x-show="open" x-cloak x-transition.opacity.duration.150ms role="listbox">
        <div class="lasles-auth-cc-search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input
            type="search"
            x-model="q"
            x-ref="ccSearch"
            @keydown.escape.stop="open = false"
            placeholder="{{ __('auth.search_country') }}"
            autocomplete="off"
            dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
          >
        </div>
        <ul class="lasles-auth-cc-list">
          <template x-for="c in filtered" :key="c.code">
            <li>
              <button
                type="button"
                class="lasles-auth-cc-option"
                :class="{ 'is-active': c.code === iso }"
                @click="select(c)"
                dir="ltr"
              >
                <span class="lasles-auth-cc-option-dial" x-text="c.dial_code"></span>
                <span class="lasles-auth-cc-option-name" x-text="labelOf(c)" :dir="localeRtl ? 'rtl' : 'ltr'"></span>
              </button>
            </li>
          </template>
          <li x-show="filtered.length === 0" class="lasles-auth-cc-empty" x-cloak>
            {{ __('auth.no_country_found') }}
          </li>
        </ul>
      </div>
    </div>
    <input
      type="tel"
      name="{{ $field }}"
      value="{{ old($field) }}"
      @if($required) required @endif
      :placeholder="placeholder || '5xxxxxxxx'"
      dir="ltr"
      autocomplete="{{ $autocomplete }}"
      aria-label="{{ $label }}"
    >
  </div>
  @if(!empty($hint))
    <p class="lasles-auth-hint">{{ $hint }}</p>
  @endif
  @error($field)<p class="lasles-auth-error">{{ $message }}</p>@enderror
</div>
