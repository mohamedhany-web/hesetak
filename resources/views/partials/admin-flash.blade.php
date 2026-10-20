{{-- تنبيهات موحّدة: تُسحب من الجلسة هنا حتى لا تتكرر داخل صفحات المحتوى --}}
@php
    $flashSuccess = session()->pull('success');
    $flashError = session()->pull('error');
    $flashWarning = session()->pull('warning');
    $flashInfo = session()->pull('info');
    $flashStatus = session()->pull('status');
@endphp

@if($flashSuccess)
    <div class="flex items-center gap-3 rounded-2xl border border-line bg-success/10 px-4 py-3 text-success sm:px-5" role="status" data-admin-flash="success">
        <i class="fas fa-check-circle shrink-0 text-sm" aria-hidden="true"></i>
        <span class="min-w-0 flex-1 text-sm font-medium">{{ $flashSuccess }}</span>
        <button type="button" onclick="this.closest('[data-admin-flash]')?.remove()" class="shrink-0 text-muted hover:text-ink" aria-label="إغلاق">×</button>
    </div>
@endif
@if($flashError)
    <div class="flex items-center gap-3 rounded-2xl border border-line bg-danger/10 px-4 py-3 text-danger sm:px-5" role="alert" data-admin-flash="error">
        <i class="fas fa-exclamation-circle shrink-0 text-sm" aria-hidden="true"></i>
        <span class="min-w-0 flex-1 text-sm font-medium">{{ $flashError }}</span>
        <button type="button" onclick="this.closest('[data-admin-flash]')?.remove()" class="shrink-0 text-muted hover:text-ink" aria-label="إغلاق">×</button>
    </div>
@endif
@if($flashWarning)
    <div class="flex items-center gap-3 rounded-2xl border border-line bg-metal/15 px-4 py-3 text-[#7a5c2e] sm:px-5" role="status" data-admin-flash="warning">
        <i class="fas fa-exclamation-triangle shrink-0 text-sm" aria-hidden="true"></i>
        <span class="min-w-0 flex-1 text-sm font-medium">{{ $flashWarning }}</span>
        <button type="button" onclick="this.closest('[data-admin-flash]')?.remove()" class="shrink-0 text-muted hover:text-ink" aria-label="إغلاق">×</button>
    </div>
@endif
@if($flashInfo || $flashStatus)
    <div class="flex items-center gap-3 rounded-2xl border border-line bg-accent/10 px-4 py-3 text-accent sm:px-5" role="status" data-admin-flash="info">
        <i class="fas fa-info-circle shrink-0 text-sm" aria-hidden="true"></i>
        <span class="min-w-0 flex-1 text-sm font-medium">{{ $flashInfo ?: $flashStatus }}</span>
        <button type="button" onclick="this.closest('[data-admin-flash]')?.remove()" class="shrink-0 text-muted hover:text-ink" aria-label="إغلاق">×</button>
    </div>
@endif
