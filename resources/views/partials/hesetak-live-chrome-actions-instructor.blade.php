<button type="button" id="btn-wb-popup-open" class="hstk-live-btn hstk-live-btn--gold" title="السبورة التفاعلية">
    <i class="fas fa-chalkboard"></i>
    <span class="lbl">السبورة</span>
</button>
<label class="hstk-live-check" title="الطلاب يرسمون فوق البث">
    <input type="checkbox" id="mx-toggle-student-wb" {{ $liveSession->allowsStudentWhiteboard() ? 'checked' : '' }}>
    <span class="lbl">رسم الطلاب</span>
</label>
<span id="mx-auto-rec-badge" class="hstk-live-btn hstk-live-btn--danger hidden" style="pointer-events:none;opacity:.85" title="تسجيل تلقائي للجلسة">
    <span style="width:8px;height:8px;background:#fecaca;border-radius:50%;display:inline-block"></span>
    <span class="lbl">REC</span>
</span>
<form method="POST" action="{{ route('instructor.live-sessions.end', $liveSession) }}" class="inline" style="margin:0" id="end-session-form" onsubmit="return handleEndSession(event);">
    @csrf
    <button type="submit" class="hstk-live-btn hstk-live-btn--danger">
        <i class="fas fa-stop"></i>
        <span class="lbl">إنهاء البث</span>
    </button>
</form>
