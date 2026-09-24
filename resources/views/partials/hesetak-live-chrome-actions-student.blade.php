@if(!empty($showWhiteboard))
<button type="button" id="btn-wb-popup-open" class="hstk-live-btn hstk-live-btn--gold" title="السبورة التفاعلية">
    <i class="fas fa-chalkboard"></i>
    <span class="lbl">السبورة</span>
</button>
@endif
<div id="mx-student-wb-wrap" class="{{ !empty($allowStudentWhiteboard) ? '' : 'hidden' }}">
    <button type="button" id="btn-mx-share-draw" class="hstk-live-btn" title="رسم فوق البث">
        <i class="fas fa-pen-fancy"></i>
        <span class="lbl">رسم فوق البث</span>
    </button>
</div>
@if(!empty($leaveFormId))
<form method="POST" action="{{ $leaveAction }}" class="inline m-0" id="{{ $leaveFormId }}">
    @csrf
    <button type="submit" class="hstk-live-btn hstk-live-btn--ghost">
        <i class="fas fa-sign-out-alt"></i>
        <span class="lbl">مغادرة</span>
    </button>
</form>
@elseif(!empty($leaveUrl))
<a href="{{ $leaveUrl }}" class="hstk-live-btn hstk-live-btn--ghost" id="{{ $leaveLinkId ?? 'student-live-leave' }}">
    <i class="fas fa-sign-out-alt"></i>
    <span class="lbl">مغادرة</span>
</a>
@endif
