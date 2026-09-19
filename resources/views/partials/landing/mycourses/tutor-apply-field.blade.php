@php
  $fid = $field->id;
  $savedVal = $saved[(string) $fid]['value'] ?? null;
  if ($savedVal === null && $field->system_key) {
      $savedVal = $application->{$field->system_key} ?? null;
      if ($field->system_key === 'photo') {
          $savedVal = null;
      }
  }
  $value = $oldAnswers[$fid] ?? $savedVal;
  $wideTypes = ['long_text', 'file', 'checkbox', 'radio', 'url'];
  $isWide = in_array($field->type, $wideTypes, true);
@endphp
<div class="mc-ta-field {{ $isWide ? 'is-wide' : '' }}">
  <label for="field_{{ $fid }}">
    {{ $field->label }}
    @if($field->is_required)<span class="mc-ta-req" aria-hidden="true">*</span>@endif
  </label>
  @if($field->help_text)
    <p class="mc-ta-help">{{ $field->help_text }}</p>
  @endif

  @switch($field->type)
    @case('long_text')
      <textarea class="mc-input mc-textarea" id="field_{{ $fid }}" name="answers[{{ $fid }}]" @if($field->is_required) required @endif placeholder="{{ $field->placeholder }}">{{ $value }}</textarea>
      @break
    @case('email')
      <input class="mc-input" id="field_{{ $fid }}" type="email" name="answers[{{ $fid }}]" value="{{ $value }}" @if($field->is_required) required @endif dir="ltr" autocomplete="email" placeholder="{{ $field->placeholder }}">
      @break
    @case('phone')
      <input class="mc-input" id="field_{{ $fid }}" type="tel" name="answers[{{ $fid }}]" value="{{ $value ?? $application->phone }}" @if($field->is_required) required @endif dir="ltr" autocomplete="tel" placeholder="{{ $field->placeholder ?: '+9665xxxxxxxx' }}">
      @break
    @case('number')
      <input class="mc-input" id="field_{{ $fid }}" type="number" name="answers[{{ $fid }}]" value="{{ $value }}" @if($field->is_required) required @endif placeholder="{{ $field->placeholder }}">
      @break
    @case('date')
      <input class="mc-input" id="field_{{ $fid }}" type="date" name="answers[{{ $fid }}]" value="{{ $value }}" @if($field->is_required) required @endif>
      @break
    @case('url')
      <input class="mc-input" id="field_{{ $fid }}" type="url" name="answers[{{ $fid }}]" value="{{ $value }}" @if($field->is_required) required @endif dir="ltr" placeholder="{{ $field->placeholder ?: 'https://' }}">
      @break
    @case('select')
      <select class="mc-select" id="field_{{ $fid }}" name="answers[{{ $fid }}]" @if($field->is_required) required @endif>
        <option value="">{{ $isRtl ? 'اختر…' : 'Choose…' }}</option>
        @foreach($field->options ?? [] as $opt)
          @php $ov = is_array($opt) ? ($opt['value'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; @endphp
          <option value="{{ $ov }}" @selected((string) $value === (string) $ov)>{{ $ol }}</option>
        @endforeach
      </select>
      @break
    @case('radio')
      <div class="mc-ta-choices" role="radiogroup" aria-labelledby="field_{{ $fid }}_label">
        <span id="field_{{ $fid }}_label" class="visually-hidden">{{ $field->label }}</span>
        @foreach($field->options ?? [] as $opt)
          @php $ov = is_array($opt) ? ($opt['value'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; @endphp
          <label class="mc-ta-choice">
            <input type="radio" name="answers[{{ $fid }}]" value="{{ $ov }}" @checked((string) $value === (string) $ov) @if($field->is_required) required @endif>
            <span>{{ $ol }}</span>
          </label>
        @endforeach
      </div>
      @break
    @case('checkbox')
      @php $arr = is_array($value) ? $value : (filled($value) ? [$value] : []); @endphp
      <div class="mc-ta-choices">
        @foreach($field->options ?? [] as $opt)
          @php $ov = is_array($opt) ? ($opt['value'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; @endphp
          <label class="mc-ta-choice">
            <input type="checkbox" name="answers[{{ $fid }}][]" value="{{ $ov }}" @checked(in_array((string) $ov, array_map('strval', $arr), true))>
            <span>{{ $ol }}</span>
          </label>
        @endforeach
      </div>
      @break
    @case('file')
      @php
        $existingPath = $saved[(string) $fid]['path'] ?? null;
        if (! $existingPath && $field->system_key === 'photo') {
            $existingPath = $application->photo_path;
        }
        if (! $existingPath && $field->system_key === 'id_document') {
            $existingPath = $application->id_document_path;
        }
        if (! $existingPath && $field->system_key === 'certificate') {
            $existingPath = $application->certificate_path;
        }
        if (! $existingPath && $field->system_key === 'intro_video') {
            $existingPath = $application->intro_video_path;
        }
      @endphp
      <div class="mc-ta-file">
        @if($existingPath)
          <p class="mc-ta-file__ok">{{ $isRtl ? 'تم الرفع مسبقاً. يمكنك استبداله بملف جديد.' : 'Already uploaded. You can replace it with a new file.' }}</p>
        @endif
        <input class="mc-ta-file__input" id="field_{{ $fid }}" type="file" name="hiring_upload[{{ $fid }}]" accept="{{ $field->fileAccept() }}" @if($field->is_required && ! $existingPath) required @endif>
      </div>
      @break
    @default
      <input class="mc-input" id="field_{{ $fid }}" type="text" name="answers[{{ $fid }}]" value="{{ $value }}" @if($field->is_required) required @endif placeholder="{{ $field->placeholder }}" autocomplete="off">
  @endswitch

  @error('answers.'.$fid)<p class="mc-ta-err">{{ $message }}</p>@enderror
  @error('hiring_upload.'.$fid)<p class="mc-ta-err">{{ $message }}</p>@enderror
</div>
