@php $ad = $ad ?? null; @endphp
@if($ad)
{{-- CSS مستقل: الصفحة الرئيسية (mc-*) لا تحمّل Tailwind، لذلك لا نعتمد على fixed/inset utilities --}}
<div id="popup-ad-overlay" class="mc-popup-ad" role="dialog" aria-modal="true" aria-labelledby="popup-ad-title">
    <div class="mc-popup-ad__backdrop" data-popup-ad-dismiss aria-hidden="true"></div>
    <div id="popup-ad-box" class="mc-popup-ad__card">
        <div class="mc-popup-ad__accent" aria-hidden="true"></div>
        <button type="button" id="popup-ad-close" class="mc-popup-ad__close" aria-label="إغلاق">
            <span aria-hidden="true">&times;</span>
        </button>
        <div class="mc-popup-ad__body">
            <span class="mc-popup-ad__badge">
                <i class="fas fa-bullhorn" aria-hidden="true"></i>
                إعلان حصتك
            </span>
            <h3 id="popup-ad-title" class="mc-popup-ad__title">{{ $ad->title }}</h3>
            @if(filled($ad->body))
                <div class="mc-popup-ad__text">{!! nl2br(e($ad->body)) !!}</div>
            @endif
            @if($ad->cta_text && $ad->link_url)
                <a href="{{ $ad->link_url }}" target="_blank" rel="noopener" class="mc-popup-ad__cta">
                    {{ $ad->cta_text }}
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                </a>
            @elseif($ad->cta_text)
                <span class="mc-popup-ad__cta mc-popup-ad__cta--soft">{{ $ad->cta_text }}</span>
            @endif
        </div>
    </div>
</div>

<style>
.mc-popup-ad {
    position: fixed !important;
    inset: 0 !important;
    z-index: 100050 !important;
    display: flex !important;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    box-sizing: border-box;
    animation: mcPopupOverlayIn 0.28s ease-out;
}
.mc-popup-ad__backdrop {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 18% 20%, rgba(201, 149, 42, 0.16), transparent 32%),
        radial-gradient(circle at 82% 78%, rgba(30, 78, 140, 0.28), transparent 36%),
        rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    cursor: pointer;
}
.mc-popup-ad__card {
    position: relative;
    z-index: 1;
    width: min(100%, 34rem);
    max-height: min(88vh, 40rem);
    overflow: auto;
    border-radius: 1.35rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 28px 60px -24px rgba(15, 23, 42, 0.45);
    animation: mcPopupCardIn 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}
.mc-popup-ad__accent {
    height: 0.35rem;
    width: 100%;
    background: linear-gradient(90deg, #152A4A, #1E4E8C, #C9952A);
}
.mc-popup-ad__close {
    position: absolute;
    top: 0.85rem;
    inset-inline-end: 0.85rem;
    width: 2.35rem;
    height: 2.35rem;
    border: 0;
    border-radius: 999px;
    background: #f1f5f9;
    color: #334155;
    font-size: 1.35rem;
    line-height: 1;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.mc-popup-ad__close:hover {
    background: #e2e8f0;
    color: #152A4A;
}
.mc-popup-ad__body {
    padding: 1.6rem 1.5rem 1.75rem;
    text-align: center;
}
.mc-popup-ad__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0 auto 0.9rem;
    padding: 0.35rem 0.8rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 800;
    color: #1E4E8C;
    background: color-mix(in srgb, #C9952A 18%, #fff);
    border: 1px solid color-mix(in srgb, #C9952A 35%, #fff);
}
.mc-popup-ad__title {
    margin: 0 0 0.85rem;
    font-size: clamp(1.2rem, 2.4vw, 1.55rem);
    font-weight: 800;
    line-height: 1.35;
    color: #152A4A;
}
.mc-popup-ad__text {
    margin: 0 0 1.25rem;
    font-size: 0.95rem;
    line-height: 1.75;
    color: #475569;
    white-space: pre-wrap;
}
.mc-popup-ad__cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    min-height: 2.85rem;
    padding: 0.7rem 1.35rem;
    border-radius: 0.85rem;
    font-weight: 800;
    text-decoration: none !important;
    color: #152A4A !important;
    background: #C9952A;
    border: 1px solid #C9952A;
    box-shadow: 0 12px 24px -14px rgba(201, 149, 42, 0.8);
}
.mc-popup-ad__cta:hover {
    filter: brightness(1.05);
}
.mc-popup-ad__cta--soft {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #1E4E8C !important;
    box-shadow: none;
    cursor: default;
}
@keyframes mcPopupOverlayIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes mcPopupCardIn {
    0% { opacity: 0; transform: translateY(16px) scale(0.98); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
@media (max-width: 640px) {
    .mc-popup-ad { padding: 0.75rem; align-items: flex-end; }
    .mc-popup-ad__card { width: 100%; border-radius: 1.15rem 1.15rem 0.65rem 0.65rem; }
    .mc-popup-ad__body { padding: 1.25rem 1.1rem 1.35rem; }
}
</style>
<script>
(function () {
    var overlay = document.getElementById('popup-ad-overlay');
    if (!overlay) return;
    var closeBtn = document.getElementById('popup-ad-close');
    function hide() {
        overlay.style.opacity = '0';
        overlay.style.pointerEvents = 'none';
        setTimeout(function () {
            overlay.style.display = 'none';
            document.documentElement.style.removeProperty('overflow');
        }, 280);
    }
    document.documentElement.style.overflow = 'hidden';
    if (closeBtn) closeBtn.addEventListener('click', hide);
    overlay.querySelectorAll('[data-popup-ad-dismiss]').forEach(function (el) {
        el.addEventListener('click', hide);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') hide();
    });
})();
</script>
@endif
