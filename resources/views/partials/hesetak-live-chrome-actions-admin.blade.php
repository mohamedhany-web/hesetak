<a href="{{ route('admin.live-sessions.show', $liveSession) }}" class="hstk-live-btn hstk-live-btn--ghost" title="تفاصيل الجلسة في لوحة الإدارة">
    <i class="fas fa-info-circle"></i>
    <span class="lbl">تفاصيل الجلسة</span>
</a>
@if(\Illuminate\Support\Facades\Route::has('admin.live-sessions.index'))
<a href="{{ route('admin.live-sessions.index') }}" class="hstk-live-btn hstk-live-btn--ghost" title="كل البثوث">
    <i class="fas fa-list"></i>
    <span class="lbl">كل البثوث</span>
</a>
@endif
<button type="button" id="admin-copy-room" class="hstk-live-btn" title="نسخ اسم الغرفة" data-room="{{ $liveSession->room_name }}">
    <i class="fas fa-copy"></i>
    <span class="lbl">نسخ الغرفة</span>
</button>
<button type="button" id="admin-end-session-btn" class="hstk-live-btn hstk-live-btn--danger">
    <i class="fas fa-stop"></i>
    <span class="lbl">إنهاء البث</span>
</button>
