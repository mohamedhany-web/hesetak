@php $ad = $ad ?? null; @endphp
@if($ad)
<div id="popup-ad-overlay" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/55 backdrop-blur-sm" style="animation: popupOverlayIn 0.35s ease-out;">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true" style="background:radial-gradient(circle at 18% 20%,rgba(255,229,247,.23),transparent 28%),radial-gradient(circle at 86% 82%,rgba(255,229,105,.17),transparent 30%),radial-gradient(circle at 50% 35%,rgba(40,53,147,.22),transparent 42%);"></div>

    <div id="popup-ad-box" class="relative z-10 w-full max-w-xl rounded-[24px] overflow-hidden border border-[#e6e9f7] dark:border-slate-600 bg-white dark:bg-slate-800 shadow-2xl transition-all duration-300 popup-card-glow" style="animation: popupCardIn 0.45s cubic-bezier(0.22, 1, 0.36, 1);">
        <div class="h-1.5 w-full bg-gradient-to-l from-[#FB5607] via-[#FFE569] to-[#283593] popup-top-bar"></div>

        {{-- زر الإغلاق --}}
        <button type="button" id="popup-ad-close" class="absolute top-4 right-4 z-20 w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 flex items-center justify-center text-slate-500 hover:text-[#1F2A7A] dark:text-slate-300 dark:hover:text-white transition-all duration-200 hover:scale-105" aria-label="إغلاق">
            <i class="fas fa-times text-sm"></i>
        </button>

        <div class="p-7 sm:p-8">
            <div class="mb-4 flex justify-center">
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold" style="background:#FFE5F7;color:#283593;border:1px solid #f5c7e8">
                    <i class="fas fa-bullhorn text-[11px]"></i>
                    إعلان حصتك
                </span>
            </div>
            <h3 class="text-2xl sm:text-[1.7rem] font-extrabold text-[#1F2A7A] dark:text-slate-100 mb-4 leading-tight text-center">{{ $ad->title }}</h3>

            {{-- نص الإعلان في المنصف --}}
            <div class="text-slate-600 dark:text-slate-300 leading-8 text-[15px] mb-7 whitespace-pre-wrap text-center">{{ nl2br(e($ad->body ?? '')) }}</div>

            {{-- زر الدعوة في المنصف --}}
            @if($ad->cta_text && $ad->link_url)
            <div class="flex justify-center">
                <a href="{{ $ad->link_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-6 py-3 text-white font-bold rounded-xl transition-all duration-300 hover:scale-[1.02]" style="background:linear-gradient(135deg,#FB5607,#e84d00);box-shadow:0 14px 28px -14px rgba(251,86,7,.6);">
                    {{ $ad->cta_text }}
                    <i class="fas fa-arrow-left text-sm opacity-90"></i>
                </a>
            </div>
            @elseif($ad->cta_text)
            <div class="flex justify-center">
                <span class="inline-flex items-center gap-2 px-6 py-3 bg-[#f4f6ff] dark:bg-slate-700/80 text-[#1F2A7A] dark:text-slate-100 font-semibold rounded-xl border border-[#e6e9f7] dark:border-slate-600">{{ $ad->cta_text }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    @keyframes popupOverlayIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes popupCardIn {
        0% { opacity: 0; transform: translateY(14px) scale(.98); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    .popup-top-bar {
        animation: barShine 2.8s ease-in-out infinite;
    }
    @keyframes barShine {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.85; }
    }
    @keyframes cardGlow {
        0%, 100% { box-shadow: 0 0 0 1px rgba(255,255,255,.9), 0 24px 54px -26px rgba(31,42,122,.36), 0 0 46px -30px rgba(251,86,7,.44); }
        50% { box-shadow: 0 0 0 1px rgba(255,255,255,.9), 0 26px 56px -24px rgba(31,42,122,.38), 0 0 62px -24px rgba(251,86,7,.5); }
    }
    .popup-card-glow { animation: cardGlow 3.6s ease-in-out infinite; }
</style>
<script>
(function() {
    var overlay = document.getElementById('popup-ad-overlay');
    var closeBtn = document.getElementById('popup-ad-close');
    if (!overlay) return;
    function hide() {
        overlay.style.opacity = '0';
        overlay.style.pointerEvents = 'none';
        setTimeout(function() { overlay.style.display = 'none'; }, 350);
    }
    if (closeBtn) closeBtn.addEventListener('click', hide);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) hide(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') hide(); });
})();
</script>
@endif
