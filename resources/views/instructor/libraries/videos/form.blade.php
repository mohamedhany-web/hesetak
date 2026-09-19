@extends('layouts.app')

@section('title', $mode === 'create' ? __('instructor.lib_videos_form_create') : __('instructor.lib_videos_form_edit'))
@section('page_title', $mode === 'create' ? __('instructor.lib_videos_form_create') : __('instructor.lib_videos_form_edit'))

@section('content')
@php
    $locale = app()->getLocale();
    $themeLocale = $locale === 'ar' ? 'ar' : 'en';
    $heading = $mode === 'create'
        ? __('instructor.lib_videos_form_create_heading')
        : __('instructor.lib_videos_form_edit');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $heading }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.lib_videos_title') }}</p>
            <h2 class="id-hero__title">{{ $heading }}</h2>
            <p class="id-hero__meta">{{ __('instructor.lib_videos_form_sub') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ $indexRoute }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    @if($errors->any())
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form method="POST" id="library-video-form" action="{{ $storeRoute }}" class="id-form">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <input type="hidden" name="file_path" id="file_path" value="{{ old('file_path', $video->file_path) }}">
        <input type="hidden" name="storage_disk" id="storage_disk" value="{{ old('storage_disk', $video->storage_disk ?: ($uploadDisk ?? 'r2')) }}">
        <input type="hidden" name="file_size" id="file_size" value="{{ old('file_size', $video->file_size ?? 0) }}">
        <input type="hidden" name="mime_type" id="mime_type" value="{{ old('mime_type', $video->mime_type) }}">

        <section class="id-panel">
            <header class="id-panel__head">
                <h2>{{ $heading }}</h2>
            </header>

            <div class="id-form" style="gap:14px">
                <div class="id-field">
                    <label for="title">{{ __('instructor.lib_videos_title_label') }}</label>
                    <input type="text" name="title" id="title" required
                           value="{{ old('title', $video->title) }}"
                           class="id-input" placeholder="{{ __('instructor.lib_videos_title_ph') }}">
                </div>
                <div class="id-field">
                    <label for="description">{{ __('instructor.description') }}</label>
                    <textarea name="description" id="description" rows="3" class="id-input"
                              style="min-height:88px;padding-top:10px;padding-bottom:10px"
                              placeholder="{{ __('instructor.lessons_desc_ph') }}">{{ old('description', $video->description) }}</textarea>
                </div>

                <div class="id-form-grid">
                    <div class="id-field">
                        <label for="content_theme">{{ __('instructor.lib_videos_theme') }}</label>
                        <select name="content_theme" id="content_theme" class="id-select">
                            @foreach(\App\Support\FamilyLibraryThemes::labels($themeLocale) as $key => $themeLabel)
                                <option value="{{ $key }}" @selected(old('content_theme', $video->content_theme ?: 'kids') === $key)>{{ $themeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="id-field">
                        <label for="series_title">{{ __('instructor.lib_videos_series') }}</label>
                        <input type="text" name="series_title" id="series_title"
                               value="{{ old('series_title', $video->series_title) }}" class="id-input">
                    </div>
                    <div class="id-field">
                        <label for="age_label">{{ __('instructor.lib_videos_age') }}</label>
                        <input type="text" name="age_label" id="age_label"
                               value="{{ old('age_label', $video->age_label) }}"
                               class="id-input" placeholder="{{ __('instructor.lib_videos_age_ph') }}">
                    </div>
                    <div class="id-field">
                        <label for="library_folder_id">{{ __('instructor.lib_videos_your_folder') }}</label>
                        <select name="library_folder_id" id="library_folder_id" class="id-select">
                            <option value="">{{ __('instructor.lib_videos_no_folder') }}</option>
                            @foreach(($folders ?? []) as $folder)
                                <option value="{{ $folder->id }}" @selected((string) old('library_folder_id', $video->library_folder_id) === (string) $folder->id)>
                                    {{ $folder->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="id-field">
                        <label for="sort_order">{{ __('instructor.lib_videos_sort') }}</label>
                        <input type="number" name="sort_order" id="sort_order" min="0"
                               value="{{ old('sort_order', $video->sort_order ?? 0) }}" class="id-input">
                    </div>
                    <div class="id-field">
                        <label for="duration_seconds">{{ __('instructor.lib_videos_duration') }}</label>
                        <input type="number" name="duration_seconds" id="duration_seconds" min="0"
                               value="{{ old('duration_seconds', $video->duration_seconds ?? 0) }}" class="id-input">
                    </div>
                </div>

                <div class="id-slot-card">
                    <div class="id-slot-card__head">
                        <strong style="font-size:13px;font-weight:800;color:#152A4A">{{ __('instructor.lib_videos_external') }}</strong>
                    </div>
                    <div class="id-field" style="margin:0">
                        <input type="url" name="external_url" id="external_url"
                               value="{{ old('external_url', $video->external_url) }}"
                               class="id-input" placeholder="https://youtube.com/…">
                    </div>
                </div>

                <div class="id-slot-card">
                    <div class="id-slot-card__head">
                        <strong style="font-size:13px;font-weight:800;color:#152A4A">{{ __('instructor.lib_videos_upload_cf') }}</strong>
                    </div>
                    <p class="id-field__hint" style="margin:0 0 12px">
                        {{ __('instructor.lib_videos_upload_hint', ['disk' => $uploadDisk ?? 'r2']) }}
                    </p>
                    <input type="file" id="video_file" accept="video/*,.mp4,.webm,.mov,.mkv,.m4v,.avi"
                           class="id-input" style="padding-top:10px;padding-bottom:10px">
                    <div id="upload-progress-wrap" class="hidden" style="margin-top:12px">
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:#6B7A93;margin-bottom:6px">
                            <span id="upload-status">…</span>
                            <span id="upload-percent">0%</span>
                        </div>
                        <div style="height:8px;border-radius:999px;background:#E6EEF8;overflow:hidden">
                            <div id="upload-bar" style="height:100%;width:0;background:#1E4E8C;transition:width .15s"></div>
                        </div>
                    </div>
                    <div id="upload-result" class="hidden" style="margin-top:8px;font-size:12px;font-weight:700;color:#047857"></div>
                    @if($mode === 'edit' && $video->file_path)
                        <label style="display:inline-flex;align-items:center;gap:8px;margin-top:12px;font-size:13px;font-weight:700;color:#3A4A63;cursor:pointer">
                            <input type="checkbox" name="clear_file" value="1">
                            {{ __('instructor.lib_videos_clear_file') }}
                        </label>
                    @endif
                </div>

                <div class="id-field">
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#3A4A63;cursor:pointer">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $video->is_published ?? true))>
                        {{ __('instructor.lib_videos_publish_check') }}
                    </label>
                </div>
            </div>
        </section>

        <div class="id-foot-actions">
            <a href="{{ $indexRoute }}" class="id-btn id-btn--outline">{{ __('instructor.back') }}</a>
            <button type="submit" id="save-btn" class="id-btn id-btn--navy">
                <i class="fas fa-save" aria-hidden="true"></i>
                {{ __('common.save') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var fileInput = document.getElementById('video_file');
    var progressWrap = document.getElementById('upload-progress-wrap');
    var progressBar = document.getElementById('upload-bar');
    var progressPct = document.getElementById('upload-percent');
    var progressStatus = document.getElementById('upload-status');
    var uploadResult = document.getElementById('upload-result');
    var saveBtn = document.getElementById('save-btn');
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value;

    function setProgress(pct, status) {
        progressWrap.classList.remove('hidden');
        progressBar.style.width = pct + '%';
        progressPct.textContent = Math.round(pct) + '%';
        if (status) progressStatus.textContent = status;
    }

    function putFile(url, file, contentType, extraHeaders, onPercent) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('PUT', url, true);
            xhr.setRequestHeader('Content-Type', contentType);
            if (extraHeaders) {
                Object.keys(extraHeaders).forEach(function (k) {
                    if (String(k).toLowerCase() === 'content-type') return;
                    var val = extraHeaders[k];
                    if (Array.isArray(val)) val = val[0];
                    try { xhr.setRequestHeader(k, val); } catch (e) {}
                });
            }
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable && onPercent) onPercent((e.loaded / e.total) * 100);
            };
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) resolve();
                else reject(new Error('فشل رفع الملف (HTTP ' + xhr.status + ')'));
            };
            xhr.onerror = function () { reject(new Error('خطأ شبكة أثناء الرفع')); };
            xhr.send(file);
        });
    }

    function putFileViaServer(token, file, onPercent) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', @json($proxyRoute ?? ''), true);
            xhr.timeout = 10 * 60 * 1000;
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable && onPercent) onPercent((e.loaded / e.total) * 100);
            };
            xhr.onload = function () {
                var data = {};
                try { data = JSON.parse(xhr.responseText || '{}'); } catch (e) { data = {}; }
                if (xhr.status >= 200 && xhr.status < 300 && data.ok) resolve(data);
                else reject(new Error(data.message || 'فشل الرفع عبر الخادم (HTTP ' + xhr.status + ')'));
            };
            xhr.onerror = function () { reject(new Error('تعذّر الاتصال بالخادم أثناء الرفع الاحتياطي.')); };
            xhr.ontimeout = function () { reject(new Error('انتهت مهلة الرفع عبر الخادم.')); };
            var fd = new FormData();
            fd.append('upload_token', token);
            fd.append('file', file);
            xhr.send(fd);
        });
    }

    if (!fileInput) return;

    fileInput.addEventListener('change', async function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) return;

        uploadResult.classList.add('hidden');
        saveBtn.disabled = true;
        setProgress(0, 'تجهيز رابط Cloudflare…');

        try {
            var presignRes = await fetch(@json($presignRoute), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    content_type: file.type || 'video/mp4',
                    filename: file.name
                })
            });
            var presignData = await presignRes.json();
            if (!presignRes.ok || !presignData.direct_upload || !presignData.upload_url) {
                throw new Error(presignData.message || 'تعذر تجهيز الرفع المباشر');
            }

            setProgress(1, 'جاري الرفع إلى Cloudflare…');
            try {
                await putFile(
                    presignData.upload_url,
                    file,
                    presignData.content_type || file.type || 'video/mp4',
                    presignData.headers || {},
                    function (pct) { setProgress(pct, 'جاري الرفع إلى Cloudflare…'); }
                );
            } catch (directErr) {
                if (file.size > 200 * 1024 * 1024) {
                    throw new Error((directErr && directErr.message) ? directErr.message : 'فشل الرفع المباشر. الملف أكبر من حد الرفع عبر الخادم.');
                }
                setProgress(1, 'الرفع المباشر حُجب — التحويل عبر الخادم…');
                await putFileViaServer(
                    presignData.upload_token,
                    file,
                    function (pct) { setProgress(pct, 'جاري الرفع عبر الخادم…'); }
                );
            }

            setProgress(99, 'تأكيد الملف…');
            var completeRes = await fetch(@json($completeRoute), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ upload_token: presignData.upload_token })
            });
            var completeData = await completeRes.json();
            if (!completeRes.ok || !completeData.ok) {
                throw new Error(completeData.message || 'فشل تأكيد الرفع');
            }

            document.getElementById('file_path').value = completeData.file_path;
            document.getElementById('storage_disk').value = completeData.storage_disk;
            document.getElementById('file_size').value = completeData.file_size;
            document.getElementById('mime_type').value = completeData.mime_type || '';
            setProgress(100, 'اكتمل الرفع');
            uploadResult.textContent = 'تم الرفع بنجاح (' + (completeData.file_size_human || '') + '). احفظ النموذج.';
            uploadResult.classList.remove('hidden');
            uploadResult.style.color = '#047857';
        } catch (err) {
            setProgress(0, 'فشل الرفع');
            uploadResult.textContent = err.message || String(err);
            uploadResult.classList.remove('hidden');
            uploadResult.style.color = '#B91C1C';
            document.getElementById('file_path').value = @json(old('file_path', $video->file_path));
        } finally {
            saveBtn.disabled = false;
        }
    });
})();
</script>
@endpush
