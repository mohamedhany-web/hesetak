{{-- غرفة حصتك Live — $livekitUrl + $livekitToken + $user — اختياري: $lkTheme instructor|student --}}
@php
    $lkRole = $lkRole ?? 'participant';
    $lkLeaveUrl = $lkLeaveUrl ?? url('/');
    $displayName = $user->name ?? ('User #'.($user->id ?? ''));
    $lkStartAudio = $lkStartAudio ?? true;
    $lkStartVideo = $lkStartVideo ?? true;
    $lkAllowScreenShare = $lkAllowScreenShare ?? true;
    $lkTheme = $lkTheme ?? 'default';
    $lkHideLeave = $lkHideLeave ?? false;
    $lkHostEndFormId = $lkHostEndFormId ?? null;
    $lkHostLeaveWarn = $lkHostLeaveWarn ?? (! empty($lkHostEndFormId) && ($lkRole ?? '') === 'host');
    $lkZoomMax = $lkZoomMax ?? (($lkTheme === 'student' || ($lkRole ?? '') === 'participant') ? 5 : 3);
    $lkDefaultScreenZoom = $lkDefaultScreenZoom ?? (($lkTheme === 'student' || ($lkRole ?? '') === 'participant') ? 1.35 : 1);
@endphp
<div id="lk-room-shell" class="lk-room lk-theme-{{ $lkTheme }} relative flex-1 min-h-0 flex flex-col" data-lk-theme="{{ $lkTheme }}" data-lk-role="{{ $lkRole }}">
    <div id="lk-status" class="lk-status hidden" role="status"></div>

    {{-- بوابة دخول قبل الاتصال: ضرورية للموبايل/التابلت (gesture لتفعيل الميك والصوت) --}}
    <div id="lk-prejoin" class="lk-prejoin" role="dialog" aria-modal="true" aria-labelledby="lk-prejoin-title">
        <div class="lk-prejoin__card">
            <div class="lk-prejoin__icon" aria-hidden="true"><i class="fas fa-video"></i></div>
            <h2 id="lk-prejoin-title" class="lk-prejoin__title">جاهز لدخول حصة حصتك؟</h2>
            <p class="lk-prejoin__text">اضغط الزر للسماح بالميكروفون/الكاميرا والانضمام للحصة المباشرة. على التليفون والتابلت لازم تضغط هنا أولاً.</p>
            <p id="lk-prejoin-browser-hint" class="lk-prejoin__hint hidden"></p>
            <button type="button" id="lk-prejoin-enter" class="lk-prejoin__btn">
                <i class="fas fa-sign-in-alt"></i> دخول الحصة الآن
            </button>
            <p class="lk-prejoin__note">لو فتحت الرابط من واتساب/إنستجرام: افتحه في Chrome أو Safari من قائمة ⋮</p>
        </div>
    </div>

    <div class="lk-body flex-1 min-h-0 flex flex-col md:flex-row">
        <div class="lk-main flex-1 min-h-0 flex flex-col relative">
            <div id="lk-focus" class="lk-focus hidden" aria-live="polite">
                <div class="lk-focus__viewport" id="lk-focus-viewport">
                    <div class="lk-focus__scaler" id="lk-focus-scaler">
                        <video id="lk-focus-video" autoplay playsinline></video>
                        <div id="lk-local-share-placeholder" class="lk-local-share-placeholder hidden" role="status">
                            <i class="fas fa-desktop" aria-hidden="true"></i>
                            <strong>أنت تشارك شاشتك الآن</strong>
                            <span>لا نعرض معاينة حية هنا حتى لا تتكرر الشاشة عند مشاركة الشاشة كاملة. الطالب يرى شاشتك مباشرة — بدون سبورة.</span>
                        </div>
                    </div>
                </div>
                <div class="lk-focus__bar">
                    <span class="lk-focus__title" id="lk-focus-title">مشاركة الشاشة</span>
                    <div class="lk-focus__zoom">
                        <button type="button" id="lk-os-pip" class="lk-icon-btn" title="نافذة عائمة فوق التبويبات والتطبيقات"><i class="fas fa-external-link-alt"></i></button>
                        <button type="button" id="lk-zoom-out" class="lk-icon-btn" title="تصغير"><i class="fas fa-search-minus"></i></button>
                        <input type="range" id="lk-zoom-slider" class="lk-zoom-slider" min="75" max="{{ (int) round($lkZoomMax * 100) }}" step="5" value="100" aria-label="مستوى التكبير">
                        <span id="lk-zoom-label" class="lk-zoom-label">100%</span>
                        <button type="button" id="lk-zoom-in" class="lk-icon-btn" title="تكبير"><i class="fas fa-search-plus"></i></button>
                        <button type="button" id="lk-zoom-reset" class="lk-icon-btn" title="إعادة"><i class="fas fa-compress"></i></button>
                        <button type="button" id="lk-zoom-fit" class="lk-icon-btn" title="ملء المساحة"><i class="fas fa-expand"></i></button>
                        <button type="button" id="lk-zoom-fill" class="lk-icon-btn lk-icon-btn--accent" title="أقصى تكبير"><i class="fas fa-up-right-and-down-left-from-center"></i></button>
                    </div>
                </div>
            </div>
            <div id="lk-stage" class="lk-stage flex-1 min-h-0"></div>

            {{-- نافذة عائمة للكاميرات أثناء الشير --}}
            <div id="lk-pip" class="lk-pip lk-pip--cols-2 hidden" data-pip-cols="2" aria-label="كاميرات المشاركين">
                <div class="lk-pip__head">
                    <span class="lk-pip__title"><i class="fas fa-video"></i> <span id="lk-pip-count-label">الكاميرات</span></span>
                    <div class="lk-pip__actions">
                        <div class="lk-pip__grid-switch" role="group" aria-label="تخطيط الشبكة">
                            <button type="button" class="lk-icon-btn lk-pip-cols-btn" data-pip-cols="1" title="عمود واحد"><i class="fas fa-square"></i></button>
                            <button type="button" class="lk-icon-btn lk-pip-cols-btn is-active" data-pip-cols="2" title="شبكتان"><i class="fas fa-th-large"></i></button>
                            <button type="button" class="lk-icon-btn lk-pip-cols-btn" data-pip-cols="3" title="شبكة 3"><i class="fas fa-th"></i></button>
                        </div>
                        <button type="button" id="lk-pip-os" class="lk-icon-btn" title="نافذة عائمة فوق الجهاز كله"><i class="fas fa-external-link-alt"></i></button>
                        <button type="button" id="lk-pip-toggle" class="lk-icon-btn" title="طي/فتح"><i class="fas fa-chevron-down"></i></button>
                    </div>
                </div>
                <div class="lk-pip__body" id="lk-pip-body"></div>
                <div class="lk-pip__empty hidden" id="lk-pip-empty">لا توجد كاميرات مفتوحة حالياً</div>
            </div>
        </div>
    </div>

    <div class="lk-toolbar shrink-0">
        <div id="lk-toolbar-zoom" class="lk-toolbar-zoom hidden" aria-label="تحكم التكبير">
            <button type="button" id="lk-toolbar-zoom-out" class="lk-icon-btn" title="تصغير"><i class="fas fa-search-minus"></i></button>
            <input type="range" id="lk-toolbar-zoom-slider" class="lk-zoom-slider lk-zoom-slider--toolbar" min="75" max="{{ (int) round($lkZoomMax * 100) }}" step="5" value="100" aria-label="مستوى التكبير">
            <span id="lk-toolbar-zoom-label" class="lk-zoom-label">100%</span>
            <button type="button" id="lk-toolbar-zoom-in" class="lk-icon-btn" title="تكبير"><i class="fas fa-search-plus"></i></button>
            <button type="button" id="lk-toolbar-zoom-fit" class="lk-icon-btn" title="ملء المساحة"><i class="fas fa-expand"></i></button>
        </div>
        <button type="button" id="lk-toggle-mic" class="lk-btn{{ $lkStartAudio ? ' is-on-active' : ' is-off' }}" aria-pressed="{{ $lkStartAudio ? 'true' : 'false' }}" title="{{ $lkStartAudio ? 'إيقاف الميكروفون' : 'تشغيل الميكروفون' }}"><i class="fas fa-{{ $lkStartAudio ? 'microphone' : 'microphone-slash' }}"></i><span>{{ $lkStartAudio ? 'ميكروفون' : 'ميكروفون مقفول' }}</span></button>
        <button type="button" id="lk-toggle-cam" class="lk-btn{{ $lkStartVideo ? ' is-on-active' : ' is-off' }}" aria-pressed="{{ $lkStartVideo ? 'true' : 'false' }}" title="{{ $lkStartVideo ? 'إيقاف الكاميرا' : 'تشغيل الكاميرا' }}"><i class="fas fa-{{ $lkStartVideo ? 'video' : 'video-slash' }}"></i><span>{{ $lkStartVideo ? 'كاميرا' : 'كاميرا مقفولة' }}</span></button>
        @if($lkAllowScreenShare)
        <button type="button" id="lk-toggle-screen" class="lk-btn"><i class="fas fa-desktop"></i><span>مشاركة الشاشة</span></button>
        @endif
        <button type="button" id="lk-toggle-os-pip" class="lk-btn" title="نافذة عائمة للكاميرات فوق الجهاز كله"><i class="fas fa-external-link-alt"></i><span>عائمة</span></button>
        @if(!empty($lkHostEndFormId) && ($lkRole ?? '') === 'host')
        <button type="button" id="lk-host-end" class="lk-btn lk-btn--danger" title="إنهاء البث للجميع"><i class="fas fa-stop"></i><span>إنهاء البث</span></button>
        @endif
        @unless($lkHideLeave)
        <a href="{{ $lkLeaveUrl }}" id="lk-leave" class="lk-btn lk-btn--danger"><i class="fas fa-phone-slash"></i><span>مغادرة</span></a>
        @endunless
    </div>
</div>

<style>
/* ─── Base room (self-contained; admin room has no Tailwind) ─── */
.hidden{display:none!important}
.lk-room{--lk-bg:#0f172a;--lk-surface:#152A4A;--lk-panel:#1a3358;--lk-line:rgba(255,255,255,.12);--lk-text:#f8fafc;--lk-muted:#a8b3c7;--lk-accent:#1E4E8C;--lk-gold:#C9952A;--lk-danger:#ef4444;--lk-ok:#22c55e;background:var(--lk-bg);color:var(--lk-text);font-family:"IBM Plex Sans Arabic","Lato","Rubik",system-ui,sans-serif}
.lk-theme-instructor{--lk-bg:#0f172a;--lk-surface:#152A4A;--lk-panel:#1a3358;--lk-line:rgba(255,255,255,.12);--lk-text:#f8fafc;--lk-muted:#a8b3c7;--lk-accent:#1E4E8C;--lk-gold:#C9952A;--lk-danger:#ef4444;font-family:"IBM Plex Sans Arabic","Lato","Rubik",system-ui,sans-serif}
.lk-theme-student{--lk-bg:#0f172a;--lk-surface:#152A4A;--lk-panel:#1a3358;--lk-line:rgba(255,255,255,.12);--lk-text:#f8fafc;--lk-muted:#a8b3c7;--lk-accent:#1E4E8C;--lk-gold:#C9952A;font-family:"IBM Plex Sans Arabic","Lato","Rubik",system-ui,sans-serif}
.lk-theme-admin{--lk-bg:#0a1020;--lk-surface:#152A4A;--lk-panel:#1a3358;--lk-line:rgba(201,149,42,.22);--lk-text:#f8fafc;--lk-muted:#a8b3c7;--lk-accent:#1E4E8C;--lk-gold:#C9952A;--lk-danger:#ef4444;font-family:"IBM Plex Sans Arabic","Lato","Rubik",system-ui,sans-serif}
.lk-status{position:absolute;top:.75rem;left:50%;transform:translateX(-50%);z-index:30;padding:.4rem 1rem;border-radius:999px;background:rgba(15,23,42,.92);border:1px solid var(--lk-line);font-size:.75rem;font-weight:700}
.lk-status.is-error{background:rgba(127,29,29,.92);border-color:#991b1b;color:#fecaca}
.lk-prejoin{position:absolute;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(2,6,23,.88);backdrop-filter:blur(8px)}
.lk-prejoin.hidden{display:none!important}
.lk-prejoin__card{width:min(22rem,100%);border-radius:1.15rem;border:1px solid var(--lk-line);background:var(--lk-panel);padding:1.35rem 1.2rem;text-align:center;box-shadow:0 18px 50px rgba(0,0,0,.35)}
.lk-prejoin__icon{width:3.2rem;height:3.2rem;margin:0 auto .85rem;border-radius:1rem;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, var(--lk-accent) 28%, transparent);color:var(--lk-gold);font-size:1.25rem}
.lk-prejoin__title{margin:0;font-size:1.15rem;font-weight:900;color:var(--lk-text)}
.lk-prejoin__text{margin:.55rem 0 0;font-size:.82rem;line-height:1.55;font-weight:600;color:var(--lk-muted)}
.lk-prejoin__hint{margin:.7rem 0 0;font-size:.75rem;line-height:1.45;font-weight:700;color:#fecaca;background:rgba(127,29,29,.35);border:1px solid #991b1b;border-radius:.75rem;padding:.55rem .7rem}
.lk-prejoin__hint.hidden{display:none!important}
.lk-prejoin__btn{margin-top:1rem;width:100%;display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border:0;border-radius:999px;padding:.85rem 1rem;font-size:.95rem;font-weight:900;cursor:pointer;background:linear-gradient(135deg,#1E4E8C,#C9952A);color:#fff}
.lk-prejoin__btn:disabled{opacity:.65;cursor:wait}
.lk-prejoin__note{margin:.75rem 0 0;font-size:.68rem;line-height:1.45;font-weight:600;color:var(--lk-muted)}
.lk-body{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;width:100%}
@media (min-width:768px){.lk-body{flex-direction:row}}
.lk-main{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;width:100%;background:var(--lk-surface)}
.lk-room,#lk-room-shell.lk-room{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;height:100%;width:100%;position:relative}
.lk-stage{
  display:grid;
  flex:1;
  min-height:0;
  gap:.45rem;
  padding:.45rem;
  overflow:hidden;
  align-content:stretch;
  height:100%;
}
.lk-stage.layout-solo{grid-template-columns:1fr;grid-template-rows:1fr}
.lk-stage.layout-duo{grid-template-columns:1fr 1fr;grid-template-rows:1fr}
.lk-stage.layout-trio{grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr}
.lk-stage.layout-class{grid-template-columns:1fr 1fr;grid-auto-rows:1fr}
.lk-stage.layout-grid{grid-template-columns:repeat(auto-fit,minmax(220px,1fr));grid-auto-rows:minmax(160px,1fr);overflow:auto;align-content:start}
.lk-room.is-screen-focus .lk-stage{display:none}
.lk-room.is-screen-focus #lk-focus{display:flex}
.lk-focus{display:none;flex-direction:column;flex:1;min-height:0;background:#020617}
.lk-focus__viewport{flex:1;min-height:0;overflow:auto;position:relative;cursor:grab;background:
  radial-gradient(circle at 20% 20%, rgba(11,61,145,.18), transparent 45%),
  radial-gradient(circle at 80% 0%, rgba(201,149,42,.12), transparent 40%),
  #020617}
.lk-focus__viewport.is-dragging{cursor:grabbing}
.lk-focus__scaler{transform-origin:center center;transition:transform .12s ease;min-width:100%;min-height:100%;display:flex;align-items:center;justify-content:center;padding:.75rem;position:relative}
.lk-focus__scaler video{max-width:100%;max-height:calc(100vh - 220px);width:auto;height:auto;object-fit:contain;background:#000;border-radius:12px;box-shadow:0 18px 50px rgba(0,0,0,.45);border:1px solid var(--lk-line);transform:none}
.lk-focus__scaler video.lk-local-share-hidden{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;overflow:hidden}
.lk-local-share-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.75rem;text-align:center;width:min(28rem,92%);padding:1.5rem 1.25rem;border-radius:16px;border:1px dashed rgba(201,149,42,.45);background:rgba(15,23,42,.92);color:#e2e8f0;box-shadow:0 18px 50px rgba(0,0,0,.35)}
.lk-local-share-placeholder.hidden{display:none!important}
.lk-local-share-placeholder i{font-size:1.75rem;color:var(--lk-gold)}
.lk-local-share-placeholder strong{font-size:.95rem;font-weight:800}
.lk-local-share-placeholder span{font-size:.78rem;line-height:1.55;color:var(--lk-muted);font-weight:600}
.lk-theme-student .lk-focus__viewport{display:flex;flex-direction:column}
.lk-theme-student .lk-focus__scaler{flex:1;width:100%;height:100%;min-height:0;padding:.2rem;box-sizing:border-box}
.lk-theme-student .lk-focus__scaler video{width:100%;height:100%;max-width:100%;max-height:100%;min-width:0;min-height:0;border-radius:10px;transform:none}
.lk-focus__bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;padding:.55rem .85rem;border-top:1px solid var(--lk-line);background:rgba(15,23,42,.92)}
.lk-focus__title{font-size:.8rem;font-weight:800;color:var(--lk-text)}
.lk-focus__zoom{display:inline-flex;align-items:center;gap:.35rem;flex-wrap:wrap}
.lk-zoom-label{font-size:.72rem;font-weight:800;min-width:3.2rem;text-align:center;color:var(--lk-muted)}
.lk-zoom-slider{width:min(120px,28vw);height:4px;accent-color:var(--lk-gold);cursor:pointer}
.lk-zoom-slider--toolbar{width:min(96px,22vw)}
.lk-toolbar-zoom{display:none;align-items:center;gap:.35rem;padding:.15rem .45rem;border-radius:999px;border:1px solid var(--lk-line);background:color-mix(in srgb, var(--lk-panel) 88%, #000)}
.lk-toolbar-zoom:not(.hidden){display:inline-flex}
.lk-room.is-screen-focus .lk-toolbar{flex-wrap:wrap;gap:.55rem}
.lk-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:8px;border:1px solid var(--lk-line);background:var(--lk-panel);color:var(--lk-text);cursor:pointer}
.lk-icon-btn:hover{border-color:var(--lk-accent);color:var(--lk-gold)}
.lk-icon-btn--accent{border-color:var(--lk-gold);color:var(--lk-gold);background:color-mix(in srgb, var(--lk-gold) 16%, var(--lk-panel))}

/* tiles */
.lk-tile{position:relative;background:var(--lk-panel);border:1px solid var(--lk-line);border-radius:14px;overflow:hidden;min-height:0;width:100%;height:100%}
.lk-stage.layout-solo .lk-tile,
.lk-stage.layout-duo .lk-tile,
.lk-stage.layout-trio .lk-tile,
.lk-stage.layout-class .lk-tile{aspect-ratio:unset;min-height:0}
    .lk-tile video{width:100%;height:100%;object-fit:cover;background:#020617;transform:none}
/* مرآة للكاميرا المحلية فقط (معاينة طبيعية مثل زوم) — الشير والبعيد بدون مرآة */
.lk-tile.is-local:not(.is-screen) video,
.lk-pip-tile.is-local video{transform:scaleX(-1)}
.lk-tile.is-screen{grid-column:1/-1;min-height:280px;aspect-ratio:auto}
.lk-theme-student .lk-tile.is-screen{min-height:min(78vh,640px)}
.lk-tile.is-screen video{object-fit:contain;background:#000;transform:none!important}
.lk-theme-student .lk-stage.layout-solo .lk-tile video{object-fit:contain}
.lk-tile-label{position:absolute;inset-inline-start:.65rem;bottom:.65rem;background:rgba(2,6,23,.82);color:var(--lk-text);font-size:.68rem;font-weight:800;padding:.2rem .5rem;border-radius:.45rem;z-index:2}
.lk-tile.is-local{outline:2px solid color-mix(in srgb, var(--lk-accent) 55%, transparent)}
.lk-tile.is-host{outline:2px solid color-mix(in srgb, var(--lk-gold) 70%, transparent)}
.lk-tile.is-host .lk-tile-label::after{content:' · مضيف';color:var(--lk-gold)}
.lk-tile.is-presence video{opacity:0}
.lk-tile-avatar{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.45rem;background:linear-gradient(160deg,#0f172a,#152A4A 55%,#1E4E8C);color:#e2e8f0;z-index:1;pointer-events:none}
.lk-tile-avatar.hidden{display:none!important}
.lk-tile-avatar__circle{width:4.2rem;height:4.2rem;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;font-weight:900;background:color-mix(in srgb, var(--lk-accent) 55%, #0f172a);border:2px solid color-mix(in srgb, var(--lk-gold) 55%, transparent);color:#fff}
.lk-tile-avatar__name{font-size:.78rem;font-weight:800;max-width:88%;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lk-tile-avatar__hint{font-size:.65rem;font-weight:700;color:var(--lk-muted)}
.lk-pip-tile.is-presence{display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,#0f172a,#1e293b)}
.lk-pip-tile.is-presence video{display:none}
.lk-pip-tile__avatar{font-size:.85rem;font-weight:900;color:#fff;width:2.4rem;height:2.4rem;border-radius:999px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, var(--lk-accent) 55%, #0f172a);border:1px solid color-mix(in srgb, var(--lk-gold) 45%, transparent)}

/* floating pip — fixed داخل الصفحة + Document PiP للتبويبات/الجهاز */
.lk-pip{position:fixed;z-index:99990;inset-inline-end:12px;bottom:calc(72px + env(safe-area-inset-bottom,0px));width:min(320px,52vw);border-radius:16px;border:1px solid var(--lk-line);background:rgba(15,23,42,.96);backdrop-filter:blur(12px);box-shadow:0 16px 40px rgba(0,0,0,.4);overflow:hidden}
.lk-theme-instructor .lk-pip{width:min(360px,56vw);border-color:rgba(255,255,255,.14);background:rgba(20,20,20,.96)}
.lk-pip.hidden{display:none!important}
.lk-pip.is-collapsed .lk-pip__body,.lk-pip.is-collapsed .lk-pip__empty{display:none}
.lk-pip__head{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.5rem .65rem;font-size:.72rem;font-weight:800;border-bottom:1px solid var(--lk-line);cursor:move;user-select:none}
.lk-pip__title{display:inline-flex;align-items:center;gap:.4rem;min-width:0}
.lk-pip__actions{display:inline-flex;align-items:center;gap:.25rem;flex-shrink:0}
.lk-pip__grid-switch{display:inline-flex;align-items:center;gap:2px;padding:2px;border-radius:8px;background:rgba(0,0,0,.25);border:1px solid var(--lk-line)}
.lk-pip__grid-switch .lk-icon-btn{width:1.65rem;height:1.65rem;border:0;background:transparent;opacity:.65}
.lk-pip__grid-switch .lk-icon-btn.is-active{opacity:1;background:color-mix(in srgb, var(--lk-accent) 35%, transparent);color:var(--lk-gold)}
.lk-pip__body{display:grid;gap:.4rem;padding:.5rem;max-height:min(42vh,320px);overflow:auto;align-content:start}
.lk-pip--cols-1 .lk-pip__body{grid-template-columns:1fr}
.lk-pip--cols-2 .lk-pip__body{grid-template-columns:1fr 1fr}
.lk-pip--cols-3 .lk-pip__body{grid-template-columns:1fr 1fr 1fr}
.lk-pip__empty{padding:.75rem .65rem;font-size:.7rem;font-weight:700;color:var(--lk-muted);text-align:center}
.lk-pip__empty.hidden{display:none!important}
.lk-pip-tile{position:relative;border-radius:10px;overflow:hidden;background:#000;border:1px solid var(--lk-line);aspect-ratio:4/3;min-height:72px}
.lk-pip--cols-1 .lk-pip-tile{aspect-ratio:16/10;min-height:110px}
.lk-pip-tile.is-local{outline:1px solid color-mix(in srgb, var(--lk-accent) 60%, transparent)}
.lk-pip-tile.is-host{outline:1px solid color-mix(in srgb, var(--lk-gold) 70%, transparent)}
.lk-pip-tile video{width:100%;height:100%;object-fit:cover;display:block;background:#020617;transform:none}
.lk-pip-tile.is-local video{transform:scaleX(-1)}
.lk-pip-tile span{position:absolute;inset-inline-start:.3rem;bottom:.3rem;font-size:.58rem;font-weight:800;background:rgba(0,0,0,.72);padding:.12rem .35rem;border-radius:.3rem;max-width:92%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lk-btn.is-os-pip{background:color-mix(in srgb, var(--lk-gold) 28%, var(--lk-surface));border-color:var(--lk-gold);color:#fff}
.lk-os-pip-shell{position:fixed;inset:0;background:#152A4A;color:var(--lk-text);display:flex;flex-direction:column;overflow:hidden;font-family:inherit}
.lk-os-pip-shell.is-cameras-only{background:#111}
.lk-os-pip-shell .lk-focus{flex:1;min-height:0;display:flex!important}
.lk-os-pip-shell .lk-focus__viewport{flex:1}
.lk-os-pip-shell .lk-pip{position:relative;inset:auto;bottom:auto;right:auto;width:100%;max-height:none;flex:1;border-radius:0;border:0;box-shadow:none;display:flex!important;flex-direction:column}
.lk-os-pip-shell.is-cameras-only .lk-pip{max-height:none}
.lk-os-pip-shell .lk-pip__head{cursor:default;flex-shrink:0}
.lk-os-pip-shell .lk-pip__body{flex:1;max-height:none;overflow:auto}
.lk-os-pip-compact{flex:1;display:flex;align-items:center;justify-content:center;padding:.5rem;background:#020617}
.lk-os-pip-compact video{max-width:100%;max-height:100%;object-fit:contain;border-radius:12px;background:#000}
@media(max-width:640px){
  .lk-pip{width:min(240px,68vw)}
  .lk-pip__grid-switch .lk-icon-btn[data-pip-cols="3"]{display:none}
}

/* toolbar */
.lk-toolbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.45rem;padding:.7rem .85rem;padding-bottom:calc(.7rem + env(safe-area-inset-bottom,0px));border-top:1px solid var(--lk-line);background:color-mix(in srgb, var(--lk-panel) 92%, #000);flex-shrink:0;z-index:25;position:relative}
.lk-btn{display:inline-flex;align-items:center;gap:.45rem;border-radius:999px;background:var(--lk-surface);color:var(--lk-text);padding:.55rem 1rem;font-size:.78rem;font-weight:800;border:1px solid var(--lk-line);cursor:pointer;text-decoration:none}
.lk-btn:hover{border-color:var(--lk-accent)}
.lk-btn.is-off{background:color-mix(in srgb, var(--lk-danger) 24%, var(--lk-surface));border-color:color-mix(in srgb, var(--lk-danger) 72%, var(--lk-line));color:#fecaca;box-shadow:inset 0 0 0 1px color-mix(in srgb, var(--lk-danger) 35%, transparent)}
.lk-btn.is-off i{color:#fca5a5}
.lk-btn.is-on-active{background:color-mix(in srgb, var(--lk-ok) 16%, var(--lk-surface));border-color:color-mix(in srgb, var(--lk-ok) 55%, var(--lk-line));color:#ecfdf5}
.lk-btn.is-on-active i{color:#86efac}
.lk-btn.is-sharing{background:color-mix(in srgb, var(--lk-accent) 35%, var(--lk-surface));border-color:var(--lk-accent);color:#fff}
.lk-btn--danger{background:var(--lk-danger);border-color:var(--lk-danger);color:#fff}
.lk-btn--accent{background:var(--lk-accent);border-color:var(--lk-accent);color:#fff}
.lk-theme-student .lk-btn--accent,.lk-theme-student .lk-btn.is-sharing{background:linear-gradient(135deg,#1E4E8C,#C9952A);border:0}
.lk-theme-instructor .lk-btn{border-radius:12px}
@media(max-width:640px){
  .lk-btn span{display:none}
  .lk-pip{width:min(240px,68vw)}
  .lk-pip__grid-switch .lk-icon-btn[data-pip-cols="3"]{display:none}
  .lk-zoom-slider{width:min(88px,24vw)}
  .lk-focus__zoom .lk-icon-btn:nth-child(n+6){display:none}
  .lk-stage.layout-duo,
  .lk-stage.layout-trio,
  .lk-stage.layout-class{grid-template-columns:1fr;grid-template-rows:none;grid-auto-rows:minmax(160px,1fr);overflow:auto}
  .lk-toolbar{padding:.55rem .55rem calc(.55rem + env(safe-area-inset-bottom,0px));gap:.35rem}
}
.lk-room.is-screen-focus .lk-main{min-height:0}
.lk-theme-student.is-screen-focus .lk-main{min-height:min(78vh,780px)}
</style>

<script>
(function () {
    const url = @json($livekitUrl);
    const token = @json($livekitToken);
    const displayName = @json($displayName);
    const role = @json($lkRole);
    const startAudio = @json($lkStartAudio);
    const startVideo = @json($lkStartVideo);
    const allowScreenShare = @json($lkAllowScreenShare);
    const lkTheme = @json($lkTheme);
    const lkDefaultScreenZoom = @json($lkDefaultScreenZoom);
    const ZOOM_MAX = @json($lkZoomMax);
    const lkHostEndFormId = @json($lkHostEndFormId);
    const lkHostLeaveWarn = @json($lkHostLeaveWarn);
    const lkLeaveUrl = @json($lkLeaveUrl);
    let lkHostSessionEnded = false;
    window.__mxLkHostSessionEnded = false;
    const shell = document.getElementById('lk-room-shell');
    const stage = document.getElementById('lk-stage');
    const focusBox = document.getElementById('lk-focus');
    const focusVideo = document.getElementById('lk-focus-video');
    const focusScaler = document.getElementById('lk-focus-scaler');
    const focusViewport = document.getElementById('lk-focus-viewport');
    const focusTitle = document.getElementById('lk-focus-title');
    const pip = document.getElementById('lk-pip');
    const pipBody = document.getElementById('lk-pip-body');
    const pipEmpty = document.getElementById('lk-pip-empty');
    const pipCountLabel = document.getElementById('lk-pip-count-label');
    let pipCols = Math.min(3, Math.max(1, parseInt(pip?.dataset?.pipCols || '2', 10) || 2));
    const statusEl = document.getElementById('lk-status');
    const micBtn = document.getElementById('lk-toggle-mic');
    const camBtn = document.getElementById('lk-toggle-cam');
    const screenBtn = document.getElementById('lk-toggle-screen');
    const zoomLabel = document.getElementById('lk-zoom-label');
    const zoomSlider = document.getElementById('lk-zoom-slider');
    const toolbarZoom = document.getElementById('lk-toolbar-zoom');
    const toolbarZoomSlider = document.getElementById('lk-toolbar-zoom-slider');
    const toolbarZoomLabel = document.getElementById('lk-toolbar-zoom-label');
    const prejoinEl = document.getElementById('lk-prejoin');
    const prejoinBtn = document.getElementById('lk-prejoin-enter');
    const prejoinHint = document.getElementById('lk-prejoin-browser-hint');

    function setStatus(msg, isError) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.classList.remove('hidden');
        statusEl.classList.toggle('is-error', !!isError);
    }
    function hideStatusSoon() {
        setTimeout(() => statusEl?.classList.add('hidden'), 2200);
    }

    function isInAppBrowser() {
        const ua = navigator.userAgent || '';
        if (/FBAN|FBAV|Instagram|Line\/|WhatsApp|Snapchat|Twitter|TikTok|MicroMessenger/i.test(ua)) return true;
        // iOS WebView داخل تطبيقات بدون Safari الكامل
        if (/iPhone|iPod|iPad/i.test(ua) && /AppleWebKit/i.test(ua) && !/Safari/i.test(ua)) return true;
        if (/\bwv\b|; wv\)/i.test(ua)) return true;
        return false;
    }

    function loadLivekitClient() {
        if (window.LivekitClient) return Promise.resolve();
        const urls = [
            'https://cdn.jsdelivr.net/npm/livekit-client@2.9.1/dist/livekit-client.umd.min.js',
            'https://unpkg.com/livekit-client@2.9.1/dist/livekit-client.umd.min.js',
        ];
        return urls.reduce(function (chain, src) {
            return chain.catch(function () {
                return new Promise(function (resolve, reject) {
                    const s = document.createElement('script');
                    s.src = src;
                    s.async = true;
                    s.onload = function () {
                        if (window.LivekitClient) resolve();
                        else reject(new Error('LivekitClient missing after ' + src));
                    };
                    s.onerror = function () { reject(new Error('failed ' + src)); };
                    document.head.appendChild(s);
                });
            });
        }, Promise.reject());
    }

    // ننتظر تحميل SDK ثم نكمل التهيئة — لا نصل أوتوماتيك
    loadLivekitClient().then(bootLivekitRoom).catch(function () {
        setStatus('تعذر تحميل مكتبة LiveKit — تحقق من الإنترنت أو افتح من Chrome/Safari', true);
        if (prejoinBtn) {
            prejoinBtn.disabled = true;
            prejoinBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> تعذر التحميل';
        }
    });

    function bootLivekitRoom() {
    if (!window.LivekitClient) { setStatus('تعذر تحميل مكتبة LiveKit', true); return; }
    if (!url || !token) { setStatus('إعدادات LiveKit غير مكتملة (رابط أو توكن)', true); return; }

    const { Room, RoomEvent, Track, createLocalTracks, createLocalScreenTracks, LocalVideoTrack, LocalAudioTrack, VideoQuality, VideoPresets, AudioPresets, ConnectionQuality } = window.LivekitClient;
    const mxLkAudioCapture = {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
    };
    const room = new Room({
        // إيقاف adaptiveStream/pause يمنع LiveKit من تقليل أو إيقاف الصوت بعد دقائق
        adaptiveStream: false,
        dynacast: true,
        subscriberAllowPause: false,
        reconnectPolicy: {
            nextRetryDelayInMs: function (context) {
                return Math.min(1000 * Math.pow(2, context.retryCount || 0), 10000);
            },
        },
        audioCaptureDefaults: mxLkAudioCapture,
        videoCaptureDefaults: {
            resolution: (VideoPresets && VideoPresets.h360)
                ? VideoPresets.h360.resolution
                : { width: 640, height: 360, frameRate: 24 },
            facingMode: 'user',
        },
        publishDefaults: {
            dtx: false,
            red: true,
            forceStereo: false,
            audioPreset: (AudioPresets && AudioPresets.music) ? AudioPresets.music : { maxBitrate: 48_000 },
            videoSimulcastLayers: (VideoPresets)
                ? [VideoPresets.h180].filter(Boolean)
                : undefined,
            videoEncoding: (VideoPresets && VideoPresets.h360)
                ? VideoPresets.h360.encoding
                : { maxBitrate: 600_000, maxFramerate: 24 },
            screenShareEncoding: { maxBitrate: 2_500_000, maxFramerate: 15 },
            screenShareSimulcastLayers: [],
            videoCodec: 'vp8',
        },
    });

    const tiles = new Map();
    const pipTiles = new Map();
    let micOn = !!startAudio;
    let camOn = !!startVideo;
    let screenOn = false;
    let screenTrack = null;
    let screenAudioTrack = null;
    let focusTrack = null;
    let localSharePlaceholderMode = false;
    let connected = false;
    let zoom = 1;
    let osPipShell = null;
    let osPipCompact = null;
    let osPipRestore = null;
    let osPipActive = false;
    let osPipAutoTried = false;
    let osPipOpening = false;
    // شير + قلم مثل زوم: الرسم يُدمَج في فيديو الشاشة ويظهر للطالب، والأدوات في نافذة عائمة فوق النظام
    let annDisplayStream = null;
    let annDisplayVideo = null;
    let annOutCanvas = null;
    let annOutCtx = null;
    let annLoopId = null;
    let annLoopInterval = null;
    let annStrokes = [];
    let annTool = 'pen';
    let annDrawing = false;
    let annCurrent = null;
    let annPipWindow = null;
    let annPipCanvas = null;
    let annPipVideo = null;
    let annotateActive = false;
    const ZOOM_MIN = 0.75;
    const ZOOM_STEP = lkTheme === 'student' ? 0.15 : 0.25;
    const isStudentView = lkTheme === 'student' || role === 'participant';

    function syncZoomUi() {
        const pct = Math.round(zoom * 100);
        if (zoomLabel) zoomLabel.textContent = pct + '%';
        if (toolbarZoomLabel) toolbarZoomLabel.textContent = pct + '%';
        if (zoomSlider) zoomSlider.value = String(pct);
        if (toolbarZoomSlider) toolbarZoomSlider.value = String(pct);
    }

    function setZoom(next, opts) {
        opts = opts || {};
        zoom = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, next));
        applyZoom();
        if (opts.scrollReset && focusViewport) {
            focusViewport.scrollTo({ left: 0, top: 0 });
        }
    }

    function computeFitZoom() {
        if (!focusVideo || !focusViewport) return lkDefaultScreenZoom || 1;
        const vw = focusVideo.videoWidth || focusVideo.clientWidth;
        const vh = focusVideo.videoHeight || focusVideo.clientHeight;
        const cw = focusViewport.clientWidth;
        const ch = focusViewport.clientHeight;
        if (!vw || !vh || !cw || !ch) return lkDefaultScreenZoom || 1;
        const contained = Math.min(cw / vw, ch / vh);
        const boost = isStudentView ? 1.08 : 1.02;
        return Math.min(ZOOM_MAX, Math.max(lkDefaultScreenZoom || 1, contained * boost));
    }

    function autoFitZoom() {
        setZoom(computeFitZoom());
    }

    function maxFillZoom() {
        setZoom(ZOOM_MAX);
    }

    function syncMicButton() {
        if (!micBtn) return;
        micBtn.classList.toggle('is-off', !micOn);
        micBtn.classList.toggle('is-on-active', !!micOn);
        micBtn.setAttribute('aria-pressed', micOn ? 'true' : 'false');
        micBtn.setAttribute('title', micOn ? 'إيقاف الميكروفون' : 'تشغيل الميكروفون');
        const icon = micBtn.querySelector('i');
        const label = micBtn.querySelector('span');
        if (icon) icon.className = micOn ? 'fas fa-microphone' : 'fas fa-microphone-slash';
        if (label) label.textContent = micOn ? 'ميكروفون' : 'ميكروفون مقفول';
    }

    function syncCamButton() {
        if (!camBtn) return;
        camBtn.classList.toggle('is-off', !camOn);
        camBtn.classList.toggle('is-on-active', !!camOn);
        camBtn.setAttribute('aria-pressed', camOn ? 'true' : 'false');
        camBtn.setAttribute('title', camOn ? 'إيقاف الكاميرا' : 'تشغيل الكاميرا');
        const icon = camBtn.querySelector('i');
        const label = camBtn.querySelector('span');
        if (icon) icon.className = camOn ? 'fas fa-video' : 'fas fa-video-slash';
        if (label) label.textContent = camOn ? 'كاميرا' : 'كاميرا مقفولة';
    }

    function syncLocalMediaStateFromRoom() {
        if (!room?.localParticipant) return;
        try {
            micOn = room.localParticipant.isMicrophoneEnabled;
            camOn = room.localParticipant.isCameraEnabled;
        } catch (e) {}
        syncMicButton();
        syncCamButton();
    }

    function errMsg(err, fallback) {
        if (!err) return fallback;
        const name = err.name || '';
        const message = err.message || String(err);
        if (name === 'NotAllowedError' || /Permission|NotAllowed|denied/i.test(message)) {
            return 'تم رفض الإذن من المتصفح — اسمح بالوصول ثم أعد المحاولة';
        }
        if (/AbortError|cancelled|canceled/i.test(name + message)) return 'تم إلغاء مشاركة الشاشة';
        return fallback + (message ? ': ' + message : '');
    }
    function tileKey(participant, source) {
        return participant.identity + ':' + source;
    }

    function parseParticipantMeta(participant) {
        if (!participant?.metadata) return {};
        try { return JSON.parse(participant.metadata); } catch (e) { return {}; }
    }

    function isHostParticipant(participant) {
        if (!participant) return false;
        if (participant.isLocal && role === 'host') return true;
        const meta = parseParticipantMeta(participant);
        const r = String(meta.role || '').toLowerCase();
        if (meta.is_host === true || meta.is_host === 'true') return true;
        return r === 'instructor' || r === 'admin' || r === 'teacher';
    }

    function cameraTiles() {
        return [...tiles.values()].filter((ref) => {
            return ref.source !== Track.Source.ScreenShare
                && ref.el.style.display !== 'none'
                && ref.source !== Track.Source.ScreenShareAudio;
        });
    }

    function findHostTile(list) {
        const host = list.find((t) => isHostParticipant(t.participant));
        if (host) return host;
        if (role === 'host') {
            const local = list.find((t) => t.participant.isLocal);
            if (local) return local;
        }
        return list[0];
    }

    function resetTilePlacement(ref) {
        ref.el.classList.remove('is-host');
        ref.el.style.gridColumn = '';
        ref.el.style.gridRow = '';
    }

    function updateStageLayout() {
        if (!stage || shell?.classList.contains('is-screen-focus')) return;

        const list = cameraTiles();
        const layouts = ['layout-solo', 'layout-duo', 'layout-trio', 'layout-class', 'layout-grid'];
        stage.classList.remove(...layouts);
        list.forEach(resetTilePlacement);

        const n = list.length;
        if (n === 0) return;

        if (n === 1) {
            stage.classList.add('layout-solo');
            return;
        }

        if (n === 2) {
            stage.classList.add('layout-duo');
            return;
        }

        if (n === 3) {
            stage.classList.add('layout-trio');
            const hostTile = findHostTile(list);
            const others = list.filter((t) => t !== hostTile);
            hostTile.el.classList.add('is-host');
            hostTile.el.style.gridColumn = '1';
            hostTile.el.style.gridRow = '1 / -1';
            if (others[0]) {
                others[0].el.style.gridColumn = '2';
                others[0].el.style.gridRow = '1';
            }
            if (others[1]) {
                others[1].el.style.gridColumn = '2';
                others[1].el.style.gridRow = '2';
            }
            return;
        }

        // 4+ — المعلم/المضيف نصف الشاشة والباقي عمودياً في النصف الآخر
        stage.classList.add('layout-class');
        const hostTile = findHostTile(list);
        const others = list.filter((t) => t !== hostTile);
        hostTile.el.classList.add('is-host');
        hostTile.el.style.gridColumn = '1';
        hostTile.el.style.gridRow = '1 / -1';
        others.forEach((t, i) => {
            t.el.style.gridColumn = '2';
            t.el.style.gridRow = String(i + 1);
        });
    }

    function applyZoom() {
        if (!focusScaler) return;
        focusScaler.style.transform = 'scale(' + zoom + ')';
        syncZoomUi();
    }
    function setLocalSharePlaceholder(on) {
        localSharePlaceholderMode = !!on;
        const ph = document.getElementById('lk-local-share-placeholder');
        if (ph) ph.classList.toggle('hidden', !on);
        if (focusVideo) {
            focusVideo.classList.toggle('lk-local-share-hidden', !!on);
            if (on) {
                try {
                    focusVideo.pause?.();
                    focusVideo.srcObject = null;
                    focusVideo.removeAttribute('src');
                } catch (e) {}
            }
        }
    }

    function preferScreenShareQuality(publication) {
        if (!publication) return;
        try {
            if (typeof publication.setVideoQuality === 'function' && VideoQuality) {
                publication.setVideoQuality(VideoQuality.HIGH);
            }
        } catch (e) {}
        try {
            // إبقاء الاشتراك نشطاً حتى لو العنصر صغيراً لحظياً
            if (typeof publication.setSubscribed === 'function') {
                publication.setSubscribed(true);
            }
        } catch (e2) {}
    }

    function setScreenFocus(on, title) {
        shell?.classList.toggle('is-screen-focus', !!on);
        if (focusBox) focusBox.classList.toggle('hidden', !on);
        if (toolbarZoom) toolbarZoom.classList.toggle('hidden', !on);
        if (title && focusTitle) focusTitle.textContent = title;
        if (!on) {
            setLocalSharePlaceholder(false);
            setZoom(1);
            if (focusVideo) {
                focusVideo.srcObject = null;
                focusVideo.removeAttribute('src');
                focusVideo.classList.remove('lk-local-share-hidden');
            }
            focusTrack = null;
            if (documentPictureInPicture?.window || osPipActive || document.pictureInPictureElement) {
                closeOsFloatingWindow().catch(() => restoreOsPipDom());
            }
            osPipAutoTried = false;
            syncFloatingPipExclusive();
            updateStageLayout();
        } else {
            // نافذة كاميرات واحدة فقط داخل الصفحة أثناء الشير — النافذة فوق النظام بضغطة يدوية
            rebuildPip();
            syncFloatingPipExclusive();
        }
    }
    function attachToFocus(track, label, opts) {
        opts = opts || {};
        const isLocalShare = !!opts.localSharePlaceholder;
        focusTrack = track;
        if (isLocalShare) {
            // لا نعرض معاينة الشير الحي للمضيف — يمنع تكرار الشاشة عند مشاركة الشاشة كاملة
            setLocalSharePlaceholder(true);
            setScreenFocus(true, label || 'مشاركة الشاشة');
            setZoom(1);
            try { window.__mxLkNotifyRecordingCaptureChanged?.(); } catch (e) {}
            return;
        }
        setLocalSharePlaceholder(false);
        if (focusVideo) {
            track.attach(focusVideo);
            focusVideo.muted = true;
            focusVideo.playsInline = true;
            focusVideo.style.transform = 'none';
            focusVideo.play?.().catch(() => {});
            if (!focusVideo.__mxZoomBound) {
                focusVideo.__mxZoomBound = true;
                focusVideo.addEventListener('loadedmetadata', function onMeta() {
                    if (!isStudentView || !shell?.classList.contains('is-screen-focus')) return;
                    setTimeout(autoFitZoom, 80);
                });
            }
        }
        setScreenFocus(true, label || 'مشاركة الشاشة');
        try { window.__mxLkNotifyRecordingCaptureChanged?.(); } catch (e) {}
        if (isStudentView) {
            setZoom(lkDefaultScreenZoom || 1.35);
            setTimeout(autoFitZoom, 120);
        } else {
            setZoom(1);
        }
    }

    function participantDisplayName(participant) {
        return (participant?.name || participant?.identity || 'مشارك').trim() || 'مشارك';
    }

    function participantInitials(participant) {
        const name = participantDisplayName(participant);
        const parts = name.split(/\s+/).filter(Boolean);
        if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
        return name.slice(0, 2).toUpperCase();
    }

    function syncTilePresence(ref) {
        if (!ref?.el || ref.source === Track.Source.ScreenShare) return;
        const hasLiveVideo = cameraTrackIsLive(ref);
        ref.el.classList.toggle('is-presence', !hasLiveVideo);
        if (ref.avatar) {
            ref.avatar.classList.toggle('hidden', !!hasLiveVideo);
            if (ref.avatarName) ref.avatarName.textContent = participantDisplayName(ref.participant);
            if (ref.avatarCircle) ref.avatarCircle.textContent = participantInitials(ref.participant);
            if (ref.avatarHint) {
                ref.avatarHint.textContent = hasLiveVideo ? '' : 'متصل · بدون كاميرا';
            }
        }
        if (ref.label) {
            ref.label.textContent = participantDisplayName(ref.participant)
                + (ref.source === Track.Source.ScreenShare ? ' · شاشة' : '');
        }
    }

    function ensureTile(participant, source) {
        const key = tileKey(participant, source);
        if (tiles.has(key)) {
            const existing = tiles.get(key);
            existing.participant = participant;
            syncTilePresence(existing);
            return existing;
        }
        const el = document.createElement('div');
        el.className = 'lk-tile' + (source === Track.Source.ScreenShare ? ' is-screen' : '') + (participant.isLocal ? ' is-local' : '');
        el.dataset.key = key;
        const video = document.createElement('video');
        video.autoplay = true;
        video.playsInline = true;
        video.muted = !!participant.isLocal;
        const avatar = document.createElement('div');
        avatar.className = 'lk-tile-avatar';
        const avatarCircle = document.createElement('div');
        avatarCircle.className = 'lk-tile-avatar__circle';
        avatarCircle.textContent = participantInitials(participant);
        const avatarName = document.createElement('div');
        avatarName.className = 'lk-tile-avatar__name';
        avatarName.textContent = participantDisplayName(participant);
        const avatarHint = document.createElement('div');
        avatarHint.className = 'lk-tile-avatar__hint';
        avatarHint.textContent = 'متصل · بدون كاميرا';
        avatar.appendChild(avatarCircle);
        avatar.appendChild(avatarName);
        avatar.appendChild(avatarHint);
        const label = document.createElement('div');
        label.className = 'lk-tile-label';
        label.textContent = participantDisplayName(participant) + (source === Track.Source.ScreenShare ? ' · شاشة' : '');
        el.appendChild(video);
        el.appendChild(avatar);
        el.appendChild(label);
        stage.appendChild(el);
        const ref = {
            el, video, participant, source, track: null,
            avatar, avatarCircle, avatarName, avatarHint, label,
        };
        tiles.set(key, ref);
        syncTilePresence(ref);
        updateStageLayout();
        return ref;
    }

    function ensureParticipantPresence(participant) {
        if (!participant || !stage) return null;
        const source = Track.Source.Camera;
        const ref = ensureTile(participant, source);
        if (isHostParticipant(participant)) ref.el.classList.add('is-host');
        syncTilePresence(ref);
        updateStageLayout();
        if (shell?.classList.contains('is-screen-focus') || osPipActive) rebuildPip();
        return ref;
    }
    function removeTile(participant, source) {
        const key = tileKey(participant, source);
        const ref = tiles.get(key);
        if (!ref) return;
        if (ref.track && ref.pipVideo) {
            try { ref.track.detach(ref.pipVideo); } catch (e) {}
        }
        ref.el.remove();
        tiles.delete(key);
        updateStageLayout();
    }

    function applyPipCols(cols) {
        pipCols = Math.min(3, Math.max(1, parseInt(cols, 10) || 2));
        if (!pip) return;
        pip.dataset.pipCols = String(pipCols);
        pip.classList.remove('lk-pip--cols-1', 'lk-pip--cols-2', 'lk-pip--cols-3');
        pip.classList.add('lk-pip--cols-' + pipCols);
        pip.querySelectorAll('.lk-pip-cols-btn').forEach((btn) => {
            btn.classList.toggle('is-active', String(btn.getAttribute('data-pip-cols')) === String(pipCols));
        });
    }

    function cameraTrackIsLive(ref) {
        if (!ref || ref.source === Track.Source.ScreenShare) return false;
        if (ref.source === Track.Source.ScreenShareAudio) return false;
        const mediaTrack = ref.track?.mediaStreamTrack;
        if (mediaTrack) {
            return mediaTrack.readyState === 'live' && mediaTrack.enabled !== false && !ref.track.isMuted;
        }
        const videoEl = ref.video;
        if (!videoEl || !videoEl.srcObject) return false;
        const stream = videoEl.srcObject;
        if (!(stream instanceof MediaStream)) return false;
        const tracks = stream.getVideoTracks();
        if (!tracks.length) return false;
        return tracks.some((t) => t.readyState === 'live' && t.enabled !== false);
    }

    function playPipVideo(v) {
        if (!v) return;
        const p = v.play?.();
        if (p && typeof p.catch === 'function') p.catch(() => {});
    }

    function isPipHostedInOsWindow() {
        return !!(pip && osPipShell && pip.parentElement === osPipShell);
    }

    function isOsDocumentPipOpen() {
        try {
            return !!(typeof documentPictureInPicture !== 'undefined' && documentPictureInPicture.window);
        } catch (e) {
            return false;
        }
    }

    /**
     * ضمان نافذة عائمة واحدة فقط:
     * - إما شريط الكاميرات داخل الصفحة
     * - أو نفس الشريط داخل Document PiP
     * ولا نفتح Video PiP للشاشة بالتوازي مع شريط الكاميرات.
     */
    function syncFloatingPipExclusive() {
        if (!pip) return;
        const screenFocus = !!shell?.classList.contains('is-screen-focus');
        const inOs = isPipHostedInOsWindow();

        if (inOs) {
            pip.classList.remove('hidden');
            return;
        }

        // نافذة نظام مفتوحة لكن العنصر رُدّ للصفحة بالخطأ → أخفِ النسخة داخل الصفحة لتفادي التكرار
        if (isOsDocumentPipOpen() && !inOs) {
            pip.classList.add('hidden');
            return;
        }

        // لا نسمح بـ Video PiP للشاشة + شريط كاميرات داخل الصفحة معاً
        if (screenFocus && document.pictureInPictureElement && focusVideo
            && document.pictureInPictureElement === focusVideo) {
            document.exitPictureInPicture().catch(() => {});
        }

        pip.classList.toggle('hidden', !screenFocus && !osPipActive);
    }

    function rebuildPip() {
        if (!pipBody) return;
        // detach previous pip attaches
        tiles.forEach((ref) => {
            if (ref.track && ref.pipVideo) {
                try { ref.track.detach(ref.pipVideo); } catch (e) {}
            }
            ref.pipVideo = null;
        });
        pipBody.innerHTML = '';
        pipTiles.clear();

        // مشارك واحد لكل هوية — حتى بدون كاميرا (presence) حتى يظهر للطالب/المعلم
        const byIdentity = new Map();
        tiles.forEach((ref, key) => {
            if (ref.source === Track.Source.ScreenShare || ref.source === Track.Source.ScreenShareAudio) return;
            if (ref.el?.style?.display === 'none') return;
            const id = String(ref.participant?.identity || key);
            const prev = byIdentity.get(id);
            const live = cameraTrackIsLive(ref);
            if (!prev) {
                byIdentity.set(id, [key, ref, live]);
                return;
            }
            const preferNew = (live && !prev[2])
                || (ref.source === Track.Source.Camera && prev[1].source !== Track.Source.Camera);
            if (preferNew) byIdentity.set(id, [key, ref, live]);
        });

        const liveCams = [...byIdentity.values()].sort((a, b) => {
            const aHost = isHostParticipant(a[1].participant) ? 0 : 1;
            const bHost = isHostParticipant(b[1].participant) ? 0 : 1;
            if (aHost !== bHost) return aHost - bHost;
            const aLocal = a[1].participant.isLocal ? 0 : 1;
            const bLocal = b[1].participant.isLocal ? 0 : 1;
            return aLocal - bLocal;
        });

        liveCams.forEach(([key, ref, live]) => {
            const wrap = document.createElement('div');
            wrap.className = 'lk-pip-tile'
                + (ref.participant.isLocal ? ' is-local' : '')
                + (isHostParticipant(ref.participant) ? ' is-host' : '')
                + (live ? '' : ' is-presence');
            wrap.dataset.pipIdentity = String(ref.participant?.identity || key);
            if (live) {
                const v = document.createElement('video');
                v.autoplay = true;
                v.playsInline = true;
                v.muted = true;
                v.setAttribute('playsinline', '');
                v.setAttribute('autoplay', '');
                if (ref.track && typeof ref.track.attach === 'function') {
                    try {
                        ref.track.attach(v);
                        ref.pipVideo = v;
                    } catch (attachErr) {
                        if (ref.video?.srcObject) v.srcObject = ref.video.srcObject;
                    }
                } else if (ref.video?.srcObject) {
                    v.srcObject = ref.video.srcObject;
                }
                playPipVideo(v);
                wrap.appendChild(v);
                requestAnimationFrame(function () { playPipVideo(v); });
                setTimeout(function () { playPipVideo(v); }, 120);
                setTimeout(function () { playPipVideo(v); }, 400);
            } else {
                const av = document.createElement('div');
                av.className = 'lk-pip-tile__avatar';
                av.textContent = participantInitials(ref.participant);
                wrap.appendChild(av);
            }
            const name = document.createElement('span');
            let label = participantDisplayName(ref.participant).slice(0, 18);
            if (isHostParticipant(ref.participant)) label += ' · مضيف';
            if (!live) label += ' · متصل';
            name.textContent = label;
            wrap.appendChild(name);
            pipBody.appendChild(wrap);
            pipTiles.set(key, wrap);
        });

        const count = liveCams.length;
        if (pipCountLabel) {
            pipCountLabel.textContent = count ? ('الكاميرات · ' + count) : 'الكاميرات';
        }
        if (pipEmpty) pipEmpty.classList.toggle('hidden', count > 0);
        if (pipBody) pipBody.classList.toggle('hidden', count === 0);

        syncFloatingPipExclusive();
        if (pip && shell?.classList.contains('is-screen-focus') && !isPipHostedInOsWindow() && !isOsDocumentPipOpen()) {
            if (count === 0 && !osPipActive) {
                pip.classList.toggle('hidden', lkTheme !== 'instructor' && role !== 'host');
            }
        }
    }

    function preferRemoteAudioSubscription(publication) {
        if (!publication) return;
        const isAudio = publication.kind === 'audio'
            || publication.kind === Track.Kind.Audio
            || publication.source === Track.Source.Microphone
            || publication.source === Track.Source.ScreenShareAudio;
        if (!isAudio) return;
        try {
            if (typeof publication.setSubscribed === 'function') {
                publication.setSubscribed(true);
            }
        } catch (eSub) {}
    }

    function resubscribeAllRemoteAudio() {
        if (!room) return;
        room.remoteParticipants.forEach(function (participant) {
            participant.trackPublications.forEach(function (pub) {
                preferRemoteAudioSubscription(pub);
            });
        });
    }

    async function forceRefreshRemoteAudio(participant) {
        if (!participant || participant.isLocal) return;
        const pubs = [];
        participant.trackPublications.forEach(function (pub) {
            const isAudio = pub.kind === 'audio'
                || pub.kind === Track.Kind.Audio
                || pub.source === Track.Source.Microphone;
            if (isAudio && pub.track) pubs.push(pub);
        });
        for (let i = 0; i < pubs.length; i++) {
            const pub = pubs[i];
            try {
                if (typeof pub.setSubscribed === 'function') {
                    await pub.setSubscribed(false);
                    await pub.setSubscribed(true);
                }
                if (pub.track) attachRemoteAudio(pub.track, participant);
            } catch (eRef) {}
        }
    }

    async function recoverAudioPlayback(reason) {
        await ensureAudioPlayback();
        resubscribeAllRemoteAudio();
        document.querySelectorAll('audio[data-lk-audio]').forEach(function (audio) {
            if (audio.paused) {
                audio.play().catch(function () {});
            }
        });
        if (reason) console.info('[LiveKit] audio recovery:', reason);
    }

    let audioHealthTimer = null;
    let remoteQualityPoorSince = null;

    function startAudioHealthMonitor() {
        if (audioHealthTimer) return;
        audioHealthTimer = setInterval(function () {
            if (!connected || !room) return;
            recoverAudioPlayback('health-check');
        }, 20000);
    }

    function stopAudioHealthMonitor() {
        if (!audioHealthTimer) return;
        clearInterval(audioHealthTimer);
        audioHealthTimer = null;
    }

    function attachRemoteAudio(track, participant) {
        if (!track || participant?.isLocal) return;
        const key = tileKey(participant, track.source || 'mic');
        document.querySelectorAll('audio[data-lk-audio="' + key + '"]').forEach(function (el) {
            try { el.remove(); } catch (eRm) {}
        });
        const audio = track.attach();
        audio.autoplay = true;
        audio.playsInline = true;
        audio.dataset.lkAudio = key;
        document.body.appendChild(audio);
        const playRemote = function () {
            const p = audio.play();
            if (p && typeof p.catch === 'function') {
                p.catch(function () { setTimeout(playRemote, 300); });
            }
        };
        playRemote();
    }

    function attachTrack(track, participant, publication) {
        if (!track) return;
        if (track.kind === Track.Kind.Audio && !participant.isLocal) {
            attachRemoteAudio(track, participant);
            return;
        }
        if (track.kind !== Track.Kind.Video) return;

        if (track.source === Track.Source.ScreenShare) {
            const label = (participant.name || participant.identity) + ' · شاشة';
            if (!participant.isLocal) {
                preferScreenShareQuality(publication);
            }
            // المضيف: placeholder بدل معاينة حية (يمنع تكرار الشاشة عند شير الشاشة كاملة)
            // الطالب: يشوف الشاشة مباشرة — لا علاقة بالوايت بورد
            attachToFocus(track, label, { localSharePlaceholder: !!participant.isLocal });
            const tile = ensureTile(participant, track.source);
            tile.track = track;
            tile.publication = publication || null;
            if (!participant.isLocal) {
                try {
                    track.attach(tile.video);
                    tile.video.style.transform = 'none';
                } catch (eAtt) {}
            }
            tile.el.style.display = 'none';
            return;
        }

        const tile = ensureTile(participant, track.source);
        tile.track = track;
        tile.publication = publication || null;
        track.attach(tile.video);
        // مرآة للكاميرا المحلية فقط عبر CSS؛ البعيد بدون قلب
        if (!participant.isLocal) {
            tile.video.style.transform = 'none';
        }
        syncTilePresence(tile);
        updateStageLayout();
        if (shell?.classList.contains('is-screen-focus') || osPipActive) rebuildPip();
        try { window.__mxLkNotifyRecordingCaptureChanged?.(); } catch (eN) {}
    }

    function detachTrack(track, participant) {
        if (!track) return;
        track.detach().forEach((el) => el.remove());
        if (track.kind === Track.Kind.Video) {
            if (track.source === Track.Source.ScreenShare) {
                if (focusTrack === track || (participant && focusTitle)) {
                    setScreenFocus(false);
                }
                removeTile(participant, track.source);
            } else if (participant) {
                // أبقِ بلاطة الحضور حتى لو أُغلقت الكاميرا — لا نخفي المشارك
                const ref = tiles.get(tileKey(participant, track.source || Track.Source.Camera));
                if (ref) {
                    ref.track = null;
                    syncTilePresence(ref);
                }
            }
            updateStageLayout();
            if (shell?.classList.contains('is-screen-focus')) rebuildPip();
            try { window.__mxLkNotifyRecordingCaptureChanged?.(); } catch (eN2) {}
        }
    }

    function attachExistingRemoteTracks() {
        room.remoteParticipants.forEach((participant) => {
            ensureParticipantPresence(participant);
            participant.trackPublications.forEach((pub) => {
                preferRemoteAudioSubscription(pub);
                if (pub.track) attachTrack(pub.track, participant, pub);
                if (pub.source === Track.Source.ScreenShare) preferScreenShareQuality(pub);
            });
        });
        if (room.localParticipant) {
            ensureParticipantPresence(room.localParticipant);
        }
        room.localParticipant.trackPublications.forEach((pub) => {
            if (pub.track) attachTrack(pub.track, room.localParticipant, pub);
        });
    }

    function clearParticipantTiles(participant) {
        [...tiles.keys()].forEach((key) => {
            if (key.startsWith(participant.identity + ':')) {
                const ref = tiles.get(key);
                if (ref) ref.el.remove();
                tiles.delete(key);
            }
        });
        document.querySelectorAll('audio[data-lk-audio^="' + participant.identity + ':"]').forEach((el) => el.remove());
        updateStageLayout();
        if (shell?.classList.contains('is-screen-focus')) rebuildPip();
    }

    room
        .on(RoomEvent.TrackSubscribed, (track, publication, participant) => {
            if (publication?.source === Track.Source.ScreenShare) preferScreenShareQuality(publication);
            preferRemoteAudioSubscription(publication);
            attachTrack(track, participant, publication);
        })
        .on(RoomEvent.TrackSubscriptionStatusChanged, (publication, status, participant) => {
            if (!publication || !participant || participant.isLocal) return;
            preferRemoteAudioSubscription(publication);
            if (status === 'subscribed' && publication.track) {
                attachTrack(publication.track, participant, publication);
            }
        })
        .on(RoomEvent.TrackUnsubscribed, (track, publication, participant) => detachTrack(track, participant))
        .on(RoomEvent.ParticipantConnected, (participant) => {
            ensureParticipantPresence(participant);
            const who = participantDisplayName(participant);
            setStatus('انضم ' + who + ' إلى الحصة');
            hideStatusSoon();
            updateStageLayout();
            if (shell?.classList.contains('is-screen-focus') || osPipActive) rebuildPip();
        })
        .on(RoomEvent.LocalTrackPublished, (publication, participant) => {
            if (publication.track) attachTrack(publication.track, participant, publication);
            if (participant) {
                const camRef = tiles.get(tileKey(participant, Track.Source.Camera));
                if (camRef) syncTilePresence(camRef);
            }
        })
        .on(RoomEvent.LocalTrackUnpublished, (publication, participant) => {
            if (publication.track) detachTrack(publication.track, participant);
            if (publication.source === Track.Source.ScreenShare) {
                screenOn = false;
                screenTrack = null;
                screenBtn?.classList.remove('is-sharing');
                setScreenFocus(false);
            }
        })
        .on(RoomEvent.TrackPublished, (publication, participant) => {
            // تأكد أن شير الطالب يبقى على أعلى جودة ممكنة عند توفره
            if (!participant?.isLocal && publication?.source === Track.Source.ScreenShare) {
                preferScreenShareQuality(publication);
            }
        })
        .on(RoomEvent.ParticipantDisconnected, (participant) => clearParticipantTiles(participant))
        .on(RoomEvent.Disconnected, () => {
            connected = false;
            stopAudioHealthMonitor();
            setStatus('تم قطع الاتصال بالغرفة', true);
        })
        .on(RoomEvent.Reconnecting, () => setStatus('إعادة الاتصال بسبب ضعف الشبكة…', true))
        .on(RoomEvent.Reconnected, async () => {
            setStatus('تم استعادة الاتصال — جاري إصلاح الصوت…');
            await recoverAudioPlayback('reconnected');
            attachExistingRemoteTracks();
            hideStatusSoon();
        })
        .on(RoomEvent.ConnectionQualityChanged, function (quality, participant) {
            if (!participant || participant.isLocal || !ConnectionQuality) return;
            const isPoor = quality === ConnectionQuality.Poor || quality === ConnectionQuality.Lost;
            if (isPoor) {
                if (!remoteQualityPoorSince) remoteQualityPoorSince = Date.now();
                if (Date.now() - remoteQualityPoorSince >= 8000) {
                    const who = participant.name || participant.identity || 'المشارك';
                    setStatus('صوت ' + who + ' ضعيف — جاري إعادة الاتصال بالصوت…', true);
                    forceRefreshRemoteAudio(participant).then(function () {
                        return recoverAudioPlayback('quality-' + who);
                    }).finally(function () {
                        hideStatusSoon();
                    });
                    remoteQualityPoorSince = Date.now();
                }
                return;
            }
            remoteQualityPoorSince = null;
        })
        .on(RoomEvent.DataReceived, (payload, participant, kind, topic) => {
            try {
                let data = null;
                if (payload != null) {
                    const text = (typeof TextDecoder !== 'undefined')
                        ? new TextDecoder().decode(payload)
                        : String.fromCharCode.apply(null, payload instanceof Uint8Array ? payload : new Uint8Array(payload));
                    try { data = JSON.parse(text); } catch (eParse) { data = text; }
                }
                window.dispatchEvent(new CustomEvent('mx-lk-data', {
                    detail: {
                        data,
                        payload,
                        topic: topic || ((data && (data.t === 'wb' || data.t === 'wb_chunk' || data.t === 'wb_req')) ? 'mx-wb' : ''),
                        participantIdentity: participant?.identity || null,
                        kind,
                    },
                }));
            } catch (eData) {}
        })
        .on(RoomEvent.TrackMuted, (publication, participant) => {
            if (participant?.isLocal) {
                if (publication?.source === Track.Source.Microphone) {
                    micOn = false;
                    syncMicButton();
                }
                if (publication?.source === Track.Source.Camera) {
                    camOn = false;
                    syncCamButton();
                }
            }
            if (publication?.source === Track.Source.Camera || publication?.kind === Track.Kind.Video) {
                const camRef = participant
                    ? tiles.get(tileKey(participant, Track.Source.Camera))
                    : null;
                if (camRef) syncTilePresence(camRef);
                if (shell?.classList.contains('is-screen-focus') || osPipActive) rebuildPip();
            }
        })
        .on(RoomEvent.TrackUnmuted, (publication, participant) => {
            if (participant?.isLocal) {
                if (publication?.source === Track.Source.Microphone) {
                    micOn = true;
                    syncMicButton();
                }
                if (publication?.source === Track.Source.Camera) {
                    camOn = true;
                    syncCamButton();
                }
            }
            if (publication?.source === Track.Source.Camera || publication?.kind === Track.Kind.Video) {
                const camRef = participant
                    ? tiles.get(tileKey(participant, Track.Source.Camera))
                    : null;
                if (camRef) syncTilePresence(camRef);
                if (shell?.classList.contains('is-screen-focus') || osPipActive) rebuildPip();
            }
        })
        .on(RoomEvent.LocalTrackPublished, (publication, participant) => {
            if (!participant?.isLocal || !publication) return;
            if (publication.source === Track.Source.Microphone) {
                micOn = true;
                syncMicButton();
            }
            if (publication.source === Track.Source.Camera) {
                camOn = true;
                syncCamButton();
            }
        })
        .on(RoomEvent.LocalTrackUnpublished, (publication, participant) => {
            if (!participant?.isLocal || !publication) return;
            if (publication.source === Track.Source.Microphone) {
                micOn = false;
                syncMicButton();
            }
            if (publication.source === Track.Source.Camera) {
                camOn = false;
                syncCamButton();
            }
        })
        .on(RoomEvent.AudioPlaybackStatusChanged, function () {
            if (room.canPlaybackAudio) return;
            setStatus('اضغط أي مكان في الغرفة لتفعيل الصوت', true);
        });

    async function ensureAudioPlayback() {
        if (!room || typeof room.startAudio !== 'function') return;
        try {
            await room.startAudio();
        } catch (eAudio) {}
    }

    shell?.addEventListener('click', function () { ensureAudioPlayback(); });
    shell?.querySelector('.lk-toolbar')?.addEventListener('click', function () { ensureAudioPlayback(); });

    async function createSoftLocalTracks(wantAudio, wantVideo) {
        if (!wantAudio && !wantVideo) return [];
        const videoIdeal = wantVideo ? {
            resolution: (VideoPresets && VideoPresets.h360)
                ? VideoPresets.h360.resolution
                : { width: 640, height: 360, frameRate: 24 },
            facingMode: 'user',
        } : false;
        try {
            return await createLocalTracks({
                audio: wantAudio ? mxLkAudioCapture : false,
                video: videoIdeal,
            });
        } catch (e1) {
            try {
                return await createLocalTracks({
                    audio: wantAudio ? true : false,
                    video: wantVideo ? true : false,
                });
            } catch (e2) {
                if (wantAudio) {
                    return await createLocalTracks({ audio: true, video: false });
                }
                throw e2;
            }
        }
    }

    async function connect(preacquiredTracks) {
        let localTracks = Array.isArray(preacquiredTracks) ? preacquiredTracks : [];
        try {
            setStatus('جارٍ الاتصال…');
            const wantAudio = !!startAudio;
            const wantVideo = !!startVideo;
            // إن لم تُحضَّر المسارات من ضغطة الدخول، نحاول هنا (قد تفشل على iOS بدون gesture)
            if (!localTracks.length && (wantAudio || wantVideo)) {
                try {
                    localTracks = await createSoftLocalTracks(wantAudio, wantVideo);
                } catch (mediaWarmErr) {
                    console.warn(mediaWarmErr);
                    localTracks = [];
                }
            }
            await room.connect(url, token);
            connected = true;
            await ensureAudioPlayback();
            resubscribeAllRemoteAudio();
            attachExistingRemoteTracks();
            startAudioHealthMonitor();
            try {
                if (localTracks.length) {
                    await Promise.all(localTracks.map(function (t) {
                        return room.localParticipant.publishTrack(t);
                    }));
                    localTracks.forEach(function (t) { attachTrack(t, room.localParticipant); });
                    micOn = localTracks.some(function (t) {
                        return t.kind === Track.Kind.Audio || t.kind === 'audio';
                    });
                    camOn = localTracks.some(function (t) {
                        return t.kind === Track.Kind.Video || t.kind === 'video';
                    });
                } else {
                    micOn = false;
                    camOn = false;
                }
                syncMicButton();
                syncCamButton();
                if (!micOn && !camOn && (wantAudio || wantVideo)) {
                    setStatus('متصل بدون ميكروفون/كاميرا — فعّل الأذونات من الأزرار', true);
                } else {
                    setStatus('متصل · ' + (role === 'host' ? 'مضيف' : 'مشارك') + ' · ' + displayName);
                    hideStatusSoon();
                }
                updateStageLayout();
            } catch (mediaErr) {
                console.warn(mediaErr);
                localTracks.forEach(function (t) { try { t.stop(); } catch (eStop) {} });
                micOn = false; camOn = false;
                syncMicButton();
                syncCamButton();
                setStatus('متصل بدون ميكروفون/كاميرا — فعّل الأذونات من الأزرار', true);
            }
            syncLocalMediaStateFromRoom();
        } catch (err) {
            console.error(err);
            localTracks.forEach(function (t) { try { t.stop(); } catch (eStop) {} });
            setStatus(errMsg(err, 'فشل الاتصال بـ LiveKit'), true);
            if (prejoinEl) prejoinEl.classList.remove('hidden');
            if (prejoinBtn) {
                prejoinBtn.disabled = false;
                prejoinBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> إعادة المحاولة';
            }
        }
    }

    let joining = false;
    if (isInAppBrowser() && prejoinHint) {
        prejoinHint.textContent = 'المتصفح داخل واتساب/إنستجرام غالباً يمنع البث. اضغط ⋮ ثم «فتح في المتصفح» (Chrome أو Safari).';
        prejoinHint.classList.remove('hidden');
    }
    prejoinBtn?.addEventListener('click', async function () {
        if (joining || connected) return;
        joining = true;
        prejoinBtn.disabled = true;
        prejoinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الدخول…';
        let warmed = [];
        try {
            // طلب الأذونات داخل نفس اللمسة — ضروري للموبايل/التابلت
            if (startAudio || startVideo) {
                warmed = await createSoftLocalTracks(!!startAudio, !!startVideo);
            }
        } catch (warmErr) {
            console.warn(warmErr);
            warmed = [];
        }
        prejoinEl?.classList.add('hidden');
        await connect(warmed);
        joining = false;
        if (!connected && prejoinBtn) {
            prejoinBtn.disabled = false;
            prejoinBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> إعادة المحاولة';
        }
    });

    micBtn?.addEventListener('click', async () => {
        if (!connected) return;
        try {
            const next = !micOn;
            await room.localParticipant.setMicrophoneEnabled(next, mxLkAudioCapture);
            micOn = next;
            syncMicButton();
        } catch (e) { setStatus(errMsg(e, 'تعذر تفعيل الميكروفون'), true); }
    });
    camBtn?.addEventListener('click', async () => {
        if (!connected) return;
        try {
            const next = !camOn;
            await room.localParticipant.setCameraEnabled(next);
            camOn = next;
            syncCamButton();
            updateStageLayout();
            if (shell?.classList.contains('is-screen-focus')) setTimeout(rebuildPip, 200);
        } catch (e) { setStatus(errMsg(e, 'تعذر تفعيل الكاميرا'), true); }
    });

    function isScreenAnnotateHost() {
        return lkTheme === 'instructor' || role === 'host';
    }

    function stopAnnCompositeLoop() {
        if (annLoopId != null) {
            cancelAnimationFrame(annLoopId);
            annLoopId = null;
        }
        if (annLoopInterval != null) {
            clearInterval(annLoopInterval);
            annLoopInterval = null;
        }
    }

    function paintAnnStrokes(ctx, w, h) {
        if (!ctx || !w || !h) return;
        annStrokes.forEach(function (stroke) {
            if (!stroke || !stroke.points || stroke.points.length < 2) return;
            ctx.beginPath();
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = stroke.color || '#C9952A';
            ctx.lineWidth = Math.max(2, (stroke.width || 4) * (Math.min(w, h) / 720));
            stroke.points.forEach(function (pt, i) {
                var x = pt[0] * w;
                var y = pt[1] * h;
                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.stroke();
        });
        if (annCurrent && annCurrent.points && annCurrent.points.length > 1) {
            ctx.beginPath();
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = annCurrent.color || '#C9952A';
            ctx.lineWidth = Math.max(2, (annCurrent.width || 4) * (Math.min(w, h) / 720));
            annCurrent.points.forEach(function (pt, i) {
                var x = pt[0] * w;
                var y = pt[1] * h;
                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.stroke();
        }
    }

    function eraseAnnNear(nx, ny, radius) {
        var r2 = radius * radius;
        annStrokes = annStrokes.filter(function (stroke) {
            if (!stroke.points) return false;
            return !stroke.points.some(function (pt) {
                var dx = pt[0] - nx;
                var dy = pt[1] - ny;
                return (dx * dx + dy * dy) < r2;
            });
        });
    }

    function renderAnnCompositeFrame() {
        if (!annOutCanvas || !annOutCtx || !annDisplayVideo) return;
        var vw = annDisplayVideo.videoWidth || 1280;
        var vh = annDisplayVideo.videoHeight || 720;
        if (annOutCanvas.width !== vw || annOutCanvas.height !== vh) {
            annOutCanvas.width = vw;
            annOutCanvas.height = vh;
        }
        annOutCtx.fillStyle = '#0f172a';
        annOutCtx.fillRect(0, 0, vw, vh);
        try {
            if (annDisplayVideo.readyState >= 2) {
                annOutCtx.drawImage(annDisplayVideo, 0, 0, vw, vh);
            }
        } catch (e) {}
        paintAnnStrokes(annOutCtx, vw, vh);
        // نبضة خفيفة حتى لا يوقف كروم إرسال الإطارات عند ثبات الصورة
        annOutCtx.fillStyle = 'rgba(0,0,0,0.01)';
        annOutCtx.fillRect((Date.now() / 40) % Math.max(1, vw - 1), 0, 1, 1);

        if (annPipCanvas && annPipWindow && !annPipWindow.closed) {
            var pw = annPipCanvas.clientWidth || annPipCanvas.width;
            var ph = annPipCanvas.clientHeight || annPipCanvas.height;
            if (pw > 0 && ph > 0) {
                if (annPipCanvas.width !== pw || annPipCanvas.height !== ph) {
                    annPipCanvas.width = pw;
                    annPipCanvas.height = ph;
                }
                var pctx = annPipCanvas.getContext('2d');
                if (pctx) {
                    pctx.fillStyle = '#020617';
                    pctx.fillRect(0, 0, pw, ph);
                    var scale = Math.min(pw / vw, ph / vh);
                    var dw = Math.floor(vw * scale);
                    var dh = Math.floor(vh * scale);
                    var ox = Math.floor((pw - dw) / 2);
                    var oy = Math.floor((ph - dh) / 2);
                    try { pctx.drawImage(annOutCanvas, ox, oy, dw, dh); } catch (e2) {}
                }
            }
        }
    }

    function startAnnCompositeLoop() {
        stopAnnCompositeLoop();
        function tick() {
            renderAnnCompositeFrame();
            annLoopId = requestAnimationFrame(tick);
        }
        tick();
        // احتياطي عندما يكون التبويب في الخلفية (rAF يتباطأ)
        annLoopInterval = setInterval(renderAnnCompositeFrame, 66);
    }

    function closeScreenAnnotatePip() {
        try {
            if (annPipWindow && !annPipWindow.closed) annPipWindow.close();
        } catch (e) {}
        annPipWindow = null;
        annPipCanvas = null;
        annPipVideo = null;
        annotateActive = false;
    }

    function bindAnnPipDrawing(canvasEl, doc) {
        if (!canvasEl) return;
        function normFromEvent(ev) {
            var rect = canvasEl.getBoundingClientRect();
            var cx = ev.clientX;
            var cy = ev.clientY;
            if (ev.touches && ev.touches[0]) {
                cx = ev.touches[0].clientX;
                cy = ev.touches[0].clientY;
            }
            var vw = annOutCanvas?.width || rect.width || 1;
            var vh = annOutCanvas?.height || rect.height || 1;
            var scale = Math.min(rect.width / vw, rect.height / vh);
            var dw = vw * scale;
            var dh = vh * scale;
            var ox = (rect.width - dw) / 2;
            var oy = (rect.height - dh) / 2;
            var x = (cx - rect.left - ox) / dw;
            var y = (cy - rect.top - oy) / dh;
            return [
                Math.min(1, Math.max(0, x)),
                Math.min(1, Math.max(0, y)),
            ];
        }
        function onDown(ev) {
            ev.preventDefault();
            annDrawing = true;
            var p = normFromEvent(ev);
            if (annTool === 'eraser') {
                eraseAnnNear(p[0], p[1], 0.03);
                renderAnnCompositeFrame();
                return;
            }
            annCurrent = { color: '#C9952A', width: 5, points: [p] };
            try { canvasEl.setPointerCapture(ev.pointerId); } catch (e) {}
        }
        function onMove(ev) {
            if (!annDrawing) return;
            ev.preventDefault();
            var p = normFromEvent(ev);
            if (annTool === 'eraser') {
                eraseAnnNear(p[0], p[1], 0.03);
                renderAnnCompositeFrame();
                return;
            }
            if (annCurrent) {
                annCurrent.points.push(p);
                renderAnnCompositeFrame();
            }
        }
        function onUp(ev) {
            if (!annDrawing) return;
            annDrawing = false;
            try { canvasEl.releasePointerCapture(ev.pointerId); } catch (e) {}
            if (annTool === 'pen' && annCurrent && annCurrent.points.length > 1) {
                annStrokes.push(annCurrent);
                if (annStrokes.length > 80) annStrokes.shift();
            }
            annCurrent = null;
            renderAnnCompositeFrame();
        }
        canvasEl.addEventListener('pointerdown', onDown);
        canvasEl.addEventListener('pointermove', onMove);
        canvasEl.addEventListener('pointerup', onUp);
        canvasEl.addEventListener('pointercancel', onUp);
        doc?.defaultView?.addEventListener('mouseup', onUp);
    }

    async function openScreenAnnotatePip() {
        if (!supportsDocumentPiP()) {
            setStatus('المتصفح لا يدعم نافذة القلم فوق النظام — جرّب Chrome أو Edge', true);
            return false;
        }
        if (documentPictureInPicture.window) {
            // نافذة واحدة فقط: نغلق الكاميرات العائمة ونفتح قلم الشاشة
            try { documentPictureInPicture.window.close(); } catch (e) {}
            await new Promise(function (r) { setTimeout(r, 120); });
        }
        if (document.pictureInPictureElement) {
            try { await document.exitPictureInPicture(); } catch (e) {}
        }

        const pipWindow = await documentPictureInPicture.requestWindow({ width: 720, height: 520 });
        annPipWindow = pipWindow;
        const doc = pipWindow.document;
        doc.head.innerHTML = '';
        const style = doc.createElement('style');
        style.textContent = `
            html,body{margin:0;height:100%;background:#0f172a;color:#f8fafc;font-family:"IBM Plex Sans Arabic","Lato","Rubik",system-ui,sans-serif;overflow:hidden}
            .shell{display:flex;flex-direction:column;height:100%}
            .bar{display:flex;flex-wrap:wrap;gap:6px;align-items:center;padding:8px 10px;background:#111827;border-bottom:1px solid rgba(255,255,255,.12);flex-shrink:0}
            .bar button{border:1px solid rgba(255,255,255,.14);background:#1e293b;color:#f8fafc;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:800;cursor:pointer}
            .bar button.is-on{background:rgba(201,149,42,.22);border-color:#C9952A;color:#ffe08a}
            .bar .hint{font-size:11px;font-weight:700;color:rgba(248,250,252,.55);margin-inline-start:auto}
            .stage{position:relative;flex:1;min-height:0;background:#020617;cursor:crosshair}
            canvas{position:absolute;inset:0;width:100%;height:100%;touch-action:none}
        `;
        doc.head.appendChild(style);
        doc.body.innerHTML = `
            <div class="shell">
              <div class="bar">
                <button type="button" data-ann="pen" class="is-on">قلم</button>
                <button type="button" data-ann="eraser">ممحاة</button>
                <button type="button" data-ann="clear">مسح الكل</button>
                <span class="hint">ارسم هنا — يظهر مباشرة للطالب على الشير</span>
              </div>
              <div class="stage"><canvas id="ann-draw"></canvas></div>
            </div>
        `;
        annPipCanvas = doc.getElementById('ann-draw');
        bindAnnPipDrawing(annPipCanvas, doc);
        doc.querySelectorAll('[data-ann]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var act = btn.getAttribute('data-ann');
                if (act === 'clear') {
                    annStrokes = [];
                    annCurrent = null;
                    renderAnnCompositeFrame();
                    return;
                }
                annTool = act;
                doc.querySelectorAll('[data-ann="pen"],[data-ann="eraser"]').forEach(function (b) {
                    b.classList.toggle('is-on', b.getAttribute('data-ann') === annTool);
                });
            });
        });
        pipWindow.addEventListener('pagehide', function () {
            annPipWindow = null;
            annPipCanvas = null;
            annotateActive = false;
        }, { once: true });
        annotateActive = true;
        renderAnnCompositeFrame();
        setStatus('نافذة القلم فوق النظام نشطة — تنقّل بحرية وارسم عليها ليظهر للطالب');
        hideStatusSoon();
        return true;
    }

    async function cleanupAnnotatedShareMedia() {
        stopAnnCompositeLoop();
        closeScreenAnnotatePip();
        if (annDisplayStream) {
            try { annDisplayStream.getTracks().forEach(function (t) { t.stop(); }); } catch (e) {}
        }
        annDisplayStream = null;
        if (annDisplayVideo) {
            try { annDisplayVideo.pause(); annDisplayVideo.srcObject = null; } catch (e) {}
        }
        annDisplayVideo = null;
        annOutCanvas = null;
        annOutCtx = null;
        annStrokes = [];
        annCurrent = null;
    }

    async function stopScreenShare() {
        try {
            if (typeof room.localParticipant.setScreenShareEnabled === 'function' && !annOutCanvas) {
                await room.localParticipant.setScreenShareEnabled(false);
            }
        } catch (e) {}
        if (screenTrack) {
            try { await room.localParticipant.unpublishTrack(screenTrack); } catch (e) {}
            try { screenTrack.stop(); } catch (e) {}
            screenTrack = null;
        }
        if (screenAudioTrack) {
            try { await room.localParticipant.unpublishTrack(screenAudioTrack); } catch (e) {}
            try { screenAudioTrack.stop(); } catch (e) {}
            screenAudioTrack = null;
        }
        await cleanupAnnotatedShareMedia();
        screenOn = false;
        screenBtn?.classList.remove('is-sharing');
        setScreenFocus(false);
        try { window.__mxLkNotifyRecordingCaptureChanged?.(); } catch (e) {}
    }

    async function publishMediaTrackAsScreen(mediaTrack, isAudio) {
        const source = isAudio ? Track.Source.ScreenShareAudio : Track.Source.ScreenShare;
        try {
            if (!isAudio && mediaTrack && typeof mediaTrack.contentHint !== 'undefined') {
                try { mediaTrack.contentHint = 'detail'; } catch (eHint) {}
            }
            const publishOpts = {
                source: source,
                name: isAudio ? 'screen-audio' : 'screen',
                simulcast: false,
            };
            if (!isAudio) {
                publishOpts.screenShareEncoding = { maxBitrate: 2_500_000, maxFramerate: 15 };
                publishOpts.videoCodec = 'vp8';
            }
            if (!isAudio && typeof LocalVideoTrack === 'function') {
                const local = new LocalVideoTrack(mediaTrack);
                try { local.source = source; } catch (eSrc) {}
                await room.localParticipant.publishTrack(local, publishOpts);
                return local;
            }
            if (isAudio && typeof LocalAudioTrack === 'function') {
                const local = new LocalAudioTrack(mediaTrack);
                try { local.source = source; } catch (eSrc2) {}
                await room.localParticipant.publishTrack(local, publishOpts);
                return local;
            }
        } catch (wrapErr) {
            console.warn('LocalTrack wrap failed, publishing raw track', wrapErr);
        }
        const pub = await room.localParticipant.publishTrack(mediaTrack, {
            source: source,
            name: isAudio ? 'screen-audio' : 'screen',
            simulcast: false,
            screenShareEncoding: isAudio ? undefined : { maxBitrate: 2_500_000, maxFramerate: 15 },
        });
        return pub?.track || mediaTrack;
    }

    async function startAnnotatedScreenShare() {
        if (!navigator.mediaDevices?.getDisplayMedia) {
            throw new Error('getDisplayMedia unsupported');
        }
        await stopScreenShare();

        // يفضّل نافذة/تبويب على الشاشة كاملة؛ استبعاد تبويب المتصفح الحالي يقلل التكرار
        const displayStream = await navigator.mediaDevices.getDisplayMedia({
            video: {
                frameRate: 15,
                width: { ideal: 1920, max: 1920 },
                height: { ideal: 1080, max: 1080 },
                displaySurface: 'window',
            },
            audio: true,
            // Chromium: لا تلتقط نافذة هذا التبويب ضمن الشير
            preferCurrentTab: false,
            selfBrowserSurface: 'exclude',
            surfaceSwitching: 'include',
            systemAudio: 'include',
        });
        annDisplayStream = displayStream;
        const rawVideo = displayStream.getVideoTracks()[0];
        if (!rawVideo) throw new Error('no display video');
        try { rawVideo.contentHint = 'detail'; } catch (eHint) {}

        rawVideo.addEventListener('ended', function () {
            stopScreenShare().catch(function () {});
        });

        annDisplayVideo = document.createElement('video');
        annDisplayVideo.playsInline = true;
        annDisplayVideo.muted = true;
        annDisplayVideo.srcObject = new MediaStream([rawVideo]);
        await annDisplayVideo.play().catch(function () {});

        // انتظر أبعاد الشاشة
        for (var i = 0; i < 40 && (!annDisplayVideo.videoWidth); i++) {
            await new Promise(function (r) { setTimeout(r, 50); });
        }

        annOutCanvas = document.createElement('canvas');
        annOutCanvas.width = Math.min(1920, annDisplayVideo.videoWidth || 1280);
        annOutCanvas.height = Math.min(1080, annDisplayVideo.videoHeight || 720);
        annOutCtx = annOutCanvas.getContext('2d', { alpha: false, desynchronized: true });
        startAnnCompositeLoop();

        const outStream = annOutCanvas.captureStream(15);
        const outVideoTrack = outStream.getVideoTracks()[0];
        if (!outVideoTrack) throw new Error('canvas capture failed');
        try { outVideoTrack.contentHint = 'detail'; } catch (eHint2) {}

        screenTrack = await publishMediaTrackAsScreen(outVideoTrack, false);
        if (screenTrack && typeof attachTrack === 'function') {
            try {
                if (!screenTrack.source) screenTrack.source = Track.Source.ScreenShare;
                attachTrack(screenTrack, room.localParticipant);
            } catch (attachErr) {
                console.warn(attachErr);
                // حتى لو فشل الربط: placeholder للمضيف (لا نعرض الشير الحي لتجنب التكرار)
                setLocalSharePlaceholder(true);
                setScreenFocus(true, 'مشاركة الشاشة + قلم');
            }
        }

        const rawAudio = displayStream.getAudioTracks()[0];
        if (rawAudio) {
            try {
                screenAudioTrack = await publishMediaTrackAsScreen(rawAudio, true);
            } catch (audioErr) {
                console.warn(audioErr);
            }
        }

        screenOn = true;
        screenBtn?.classList.add('is-sharing');
        annStrokes = [];
        annTool = 'pen';
        try { window.__mxLkNotifyRecordingCaptureChanged?.(); } catch (e) {}

        try {
            await openScreenAnnotatePip();
        } catch (pipErr) {
            console.warn(pipErr);
            setStatus('الشير يعمل للطالب — اضغط «قلم الشاشة» لفتح أدوات الرسم فوق النظام', true);
        }
    }

    async function startScreenShare() {
        if (isScreenAnnotateHost()) {
            await startAnnotatedScreenShare();
            return;
        }
        if (typeof room.localParticipant.setScreenShareEnabled === 'function') {
            await room.localParticipant.setScreenShareEnabled(true, {
                audio: true,
                resolution: { width: 1920, height: 1080, frameRate: 15 },
                contentHint: 'detail',
            });
            screenOn = true;
            screenBtn?.classList.add('is-sharing');
            return;
        }
        let tracks;
        try {
            tracks = await createLocalScreenTracks({
                audio: true,
                resolution: { width: 1920, height: 1080, frameRate: 15 },
                contentHint: 'detail',
            });
        } catch (e) {
            tracks = await createLocalScreenTracks({ audio: false });
        }
        screenTrack = tracks[0];
        await room.localParticipant.publishTrack(screenTrack, {
            source: Track.Source.ScreenShare,
            name: 'screen',
            simulcast: false,
            screenShareEncoding: { maxBitrate: 2_500_000, maxFramerate: 15 },
        });
        if (tracks[1]) {
            try {
                await room.localParticipant.publishTrack(tracks[1], {
                    source: Track.Source.ScreenShareAudio,
                    name: 'screen-audio',
                });
                screenAudioTrack = tracks[1];
            } catch (e) {}
        }
        attachTrack(screenTrack, room.localParticipant);
        screenOn = true;
        screenBtn?.classList.add('is-sharing');
    }

    screenBtn?.addEventListener('click', async () => {
        if (!connected || !allowScreenShare) return;
        try {
            if (screenOn) { await stopScreenShare(); return; }
            await startScreenShare();
            setStatus(isScreenAnnotateHost()
                ? 'مشاركة الشاشة للطالب مفعّلة — بدون تكرار هنا. اختر «نافذة» بدل الشاشة كاملة إن أمكن'
                : 'مشاركة الشاشة مفعّلة — استخدم الزووم والنافذة العائمة');
            hideStatusSoon();
        } catch (err) {
            console.error(err);
            screenOn = false;
            screenBtn?.classList.remove('is-sharing');
            await cleanupAnnotatedShareMedia().catch(function () {});
            setStatus(errMsg(err, 'تعذر مشاركة الشاشة'), true);
        }
    });

    window.__mxLkToggleScreenAnnotate = async function () {
        if (!connected || !allowScreenShare || !isScreenAnnotateHost()) {
            setStatus('قلم الشاشة متاح للمضيف أثناء الشير', true);
            return;
        }
        try {
            if (!screenOn) {
                await startAnnotatedScreenShare();
                setStatus('شير + قلم فوق النظام — ارسم لتظهر الكتابة للطالب');
                hideStatusSoon();
                return;
            }
            if (annotateActive && annPipWindow && !annPipWindow.closed) {
                annPipWindow.focus();
                return;
            }
            await openScreenAnnotatePip();
        } catch (err) {
            console.warn(err);
            setStatus(errMsg(err, 'تعذر فتح قلم الشاشة'), true);
        }
    };

    document.getElementById('lk-zoom-in')?.addEventListener('click', () => {
        setZoom(zoom + ZOOM_STEP);
    });
    document.getElementById('lk-zoom-out')?.addEventListener('click', () => {
        setZoom(zoom - ZOOM_STEP);
    });
    document.getElementById('lk-zoom-reset')?.addEventListener('click', () => {
        setZoom(1, { scrollReset: true });
    });
    document.getElementById('lk-zoom-fit')?.addEventListener('click', () => {
        autoFitZoom();
    });
    document.getElementById('lk-zoom-fill')?.addEventListener('click', () => {
        maxFillZoom();
    });
    function bindZoomSlider(el) {
        if (!el) return;
        el.addEventListener('input', function () {
            setZoom(parseInt(el.value, 10) / 100);
        });
    }
    bindZoomSlider(zoomSlider);
    bindZoomSlider(toolbarZoomSlider);
    document.getElementById('lk-toolbar-zoom-in')?.addEventListener('click', () => setZoom(zoom + ZOOM_STEP));
    document.getElementById('lk-toolbar-zoom-out')?.addEventListener('click', () => setZoom(zoom - ZOOM_STEP));
    document.getElementById('lk-toolbar-zoom-fit')?.addEventListener('click', () => autoFitZoom());
    document.getElementById('lk-pip-toggle')?.addEventListener('click', () => {
        pip?.classList.toggle('is-collapsed');
    });
    pip?.querySelectorAll('.lk-pip-cols-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
            applyPipCols(btn.getAttribute('data-pip-cols'));
        });
    });
    applyPipCols(pipCols);

    const osPipBtn = document.getElementById('lk-toggle-os-pip');
    const osPipFocusBtn = document.getElementById('lk-os-pip');
    const osPipCamBtn = document.getElementById('lk-pip-os');

    function supportsDocumentPiP() {
        return typeof window.documentPictureInPicture !== 'undefined'
            && typeof window.documentPictureInPicture.requestWindow === 'function';
    }
    function supportsVideoPiP() {
        return typeof HTMLVideoElement !== 'undefined'
            && HTMLVideoElement.prototype.requestPictureInPicture;
    }
    function updateOsPipButtons(active) {
        osPipActive = !!active;
        [osPipBtn, osPipFocusBtn, osPipCamBtn].forEach((btn) => {
            if (!btn) return;
            btn.classList.toggle('is-os-pip', osPipActive);
            btn.title = osPipActive ? 'إغلاق النافذة العائمة' : (btn.title || 'نافذة عائمة فوق التبويبات والتطبيقات');
        });
    }
    function lkMainHost() {
        return shell?.querySelector('.lk-main') || shell;
    }
    function primaryStageVideo() {
        const hostTile = stage?.querySelector('.lk-tile.is-host video');
        if (hostTile?.srcObject) return hostTile;
        const any = stage?.querySelector('.lk-tile video');
        return any?.srcObject ? any : null;
    }
    function injectOsPipStyles(doc) {
        if (!doc) return;
        const style = doc.createElement('style');
        style.textContent = `
            html,body{margin:0;padding:0;width:100%;height:100%;overflow:hidden;background:#152A4A}
            .lk-os-pip-shell{position:fixed;inset:0;background:#152A4A;color:#f5f5f5;display:flex;flex-direction:column;font-family:"IBM Plex Sans Arabic","Lato","Rubik",system-ui,sans-serif}
            .lk-os-pip-shell.is-cameras-only{background:#111}
            .lk-os-pip-shell .lk-focus{flex:1;min-height:0;display:flex!important;flex-direction:column;background:#020617}
            .lk-os-pip-shell .lk-focus.hidden{display:none!important}
            .lk-os-pip-shell .lk-focus__viewport{flex:1;min-height:0;overflow:auto;background:#020617}
            .lk-os-pip-shell .lk-focus__scaler{display:flex;align-items:center;justify-content:center;min-height:100%;padding:.35rem}
            .lk-os-pip-shell .lk-focus__scaler video{max-width:100%;max-height:100%;object-fit:contain;background:#000;border-radius:8px}
            .lk-os-pip-shell .lk-focus__bar{display:flex;align-items:center;justify-content:space-between;padding:.35rem .55rem;border-top:1px solid rgba(255,255,255,.1);background:rgba(20,20,20,.95);font-size:.68rem;font-weight:800}
            .lk-os-pip-shell .lk-pip{position:relative!important;inset:auto!important;width:100%;max-height:42vh;border-radius:0;border:0;border-top:1px solid rgba(255,255,255,.1);box-shadow:none;background:rgba(20,20,20,.98);display:flex!important;flex-direction:column}
            .lk-os-pip-shell.is-cameras-only .lk-pip{max-height:none;flex:1;border-top:0}
            .lk-os-pip-shell .lk-pip.hidden{display:none!important}
            .lk-os-pip-shell .lk-pip__head{cursor:default;flex-shrink:0;padding:.55rem .7rem;border-bottom:1px solid rgba(255,255,255,.1)}
            .lk-os-pip-shell .lk-pip__body{flex:1;max-height:none;overflow:auto;padding:.55rem;gap:.45rem;display:grid;align-content:start}
            .lk-os-pip-shell .lk-pip--cols-1 .lk-pip__body{grid-template-columns:1fr}
            .lk-os-pip-shell .lk-pip--cols-2 .lk-pip__body{grid-template-columns:1fr 1fr}
            .lk-os-pip-shell .lk-pip--cols-3 .lk-pip__body{grid-template-columns:1fr 1fr 1fr}
            .lk-os-pip-shell .lk-pip-tile{position:relative;border-radius:10px;overflow:hidden;background:#000;border:1px solid rgba(255,255,255,.12);aspect-ratio:4/3;min-height:78px}
            .lk-os-pip-shell .lk-pip--cols-1 .lk-pip-tile{aspect-ratio:16/10;min-height:120px}
            .lk-os-pip-shell .lk-pip-tile video{width:100%;height:100%;object-fit:cover;display:block}
            .lk-os-pip-shell .lk-pip-tile span{position:absolute;inset-inline-start:.35rem;bottom:.35rem;font-size:.62rem;font-weight:800;background:rgba(0,0,0,.72);padding:.12rem .35rem;border-radius:.3rem}
            .lk-os-pip-shell .lk-pip__empty{padding:1rem;text-align:center;color:rgba(245,245,245,.55);font-size:.75rem;font-weight:700}
            .lk-os-pip-shell .lk-pip__empty.hidden{display:none!important}
            .lk-os-pip-shell .lk-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:1.75rem;height:1.75rem;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:#181818;color:#f5f5f5;cursor:pointer}
            .lk-os-pip-shell .lk-pip__grid-switch{display:inline-flex;gap:2px;padding:2px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:rgba(0,0,0,.3)}
            .lk-os-pip-shell .lk-pip__grid-switch .lk-icon-btn{width:1.55rem;height:1.55rem;border:0;background:transparent;opacity:.65}
            .lk-os-pip-shell .lk-pip__grid-switch .lk-icon-btn.is-active{opacity:1;background:rgba(149,164,252,.35);color:#ffcb9a}
            .lk-os-pip-compact{flex:1;display:flex;align-items:center;justify-content:center;padding:.35rem;background:#020617}
            .lk-os-pip-compact video{width:100%;height:100%;max-height:100%;object-fit:contain;background:#000;border-radius:8px}
        `;
        doc.head.appendChild(style);
    }
    function restoreOsPipDom() {
        const host = lkMainHost();
        if (!host) return;
        if (osPipRestore) {
            const { focusParent, focusNext, pipParent, pipNext, compactParent } = osPipRestore;
            if (focusBox && focusParent) {
                if (focusNext) focusParent.insertBefore(focusBox, focusNext);
                else focusParent.appendChild(focusBox);
            } else if (focusBox && host && focusBox.parentElement !== host) {
                host.insertBefore(focusBox, stage);
            }
            if (pip && pipParent) {
                if (pipNext) pipParent.insertBefore(pip, pipNext);
                else pipParent.appendChild(pip);
            } else if (pip && host && pip.parentElement !== host) {
                host.appendChild(pip);
            }
            if (osPipCompact && compactParent) compactParent.removeChild(osPipCompact);
            osPipRestore = null;
        }
        osPipShell = null;
        osPipCompact = null;
        updateOsPipButtons(false);
        syncFloatingPipExclusive();
    }
    function buildOsPipCompact() {
        const wrap = document.createElement('div');
        wrap.className = 'lk-os-pip-compact';
        const video = document.createElement('video');
        video.autoplay = true;
        video.playsInline = true;
        video.muted = true;
        const src = (shell?.classList.contains('is-screen-focus') && focusVideo?.srcObject)
            ? focusVideo
            : primaryStageVideo();
        if (src?.srcObject) video.srcObject = src.srcObject;
        wrap.appendChild(video);
        return wrap;
    }
    async function closeOsFloatingWindow() {
        if (documentPictureInPicture?.window) {
            documentPictureInPicture.window.close();
            return;
        }
        if (document.pictureInPictureElement) {
            await document.exitPictureInPicture();
        }
        restoreOsPipDom();
    }
    async function openVideoPiP() {
        const video = (shell?.classList.contains('is-screen-focus') && focusVideo?.srcObject)
            ? focusVideo
            : primaryStageVideo();
        if (!video || !supportsVideoPiP()) {
            setStatus('المتصفح لا يدعم النافذة العائمة — جرّب Chrome أو Edge', true);
            return false;
        }
        if (document.pictureInPictureElement === video) {
            await document.exitPictureInPicture();
            updateOsPipButtons(false);
            return true;
        }
        await video.requestPictureInPicture();
        updateOsPipButtons(true);
        setStatus('النافذة العائمة نشطة — تبقى فوق التبويبات والتطبيقات');
        hideStatusSoon();
        return true;
    }
    async function openDocumentPiP(opts) {
        opts = opts || {};
        if (osPipOpening) return false;
        const camerasOnly = !!opts.camerasOnly || (lkTheme === 'instructor' || role === 'host');
        // للكاميرات فقط: لا نفتح Video PiP للشاشة (يتسبب بنافذتين)
        if (!supportsDocumentPiP()) {
            if (camerasOnly) {
                syncFloatingPipExclusive();
                setStatus('استخدم شريط الكاميرات العائم داخل الصفحة — المتصفح لا يدعم نافذة النظام للكاميرات', true);
                return false;
            }
            return openVideoPiP();
        }
        if (documentPictureInPicture.window) {
            documentPictureInPicture.window.focus();
            updateOsPipButtons(true);
            syncFloatingPipExclusive();
            return true;
        }
        if (document.pictureInPictureElement) {
            try { await document.exitPictureInPicture(); } catch (e) {}
        }

        osPipOpening = true;
        try {
        rebuildPip();
        const camCount = pipBody ? pipBody.children.length : 0;
        const inScreenFocus = shell?.classList.contains('is-screen-focus');
        const wantCamerasOnly = camerasOnly || !inScreenFocus;
        const width = wantCamerasOnly
            ? (pipCols >= 3 ? 540 : (pipCols === 1 ? 280 : 420))
            : 560;
        const height = wantCamerasOnly
            ? Math.min(520, Math.max(220, 120 + camCount * (pipCols === 1 ? 140 : 110)))
            : 380;

        const pipWindow = await documentPictureInPicture.requestWindow({ width, height });
        injectOsPipStyles(pipWindow.document);
        osPipShell = pipWindow.document.createElement('div');
        osPipShell.className = 'lk-os-pip-shell' + (wantCamerasOnly ? ' is-cameras-only' : '');
        pipWindow.document.body.appendChild(osPipShell);

        osPipRestore = {
            focusParent: focusBox?.parentElement || null,
            focusNext: focusBox?.nextSibling || null,
            pipParent: pip?.parentElement || null,
            pipNext: pip?.nextSibling || null,
            compactParent: null,
        };

        if (wantCamerasOnly) {
            if (pip) {
                pip.classList.remove('hidden');
                osPipShell.appendChild(pip);
            }
        } else if (inScreenFocus && focusBox) {
            focusBox.classList.remove('hidden');
            osPipShell.appendChild(focusBox);
            if (pip) {
                pip.classList.remove('hidden');
                osPipShell.appendChild(pip);
            }
        } else {
            osPipCompact = buildOsPipCompact();
            osPipRestore.compactParent = osPipShell;
            osPipShell.appendChild(osPipCompact);
            if (pip && pipBody?.children.length) {
                pip.classList.remove('hidden');
                osPipShell.appendChild(pip);
            }
        }

        pipWindow.addEventListener('pagehide', restoreOsPipDom, { once: true });
        // إعادة ربط فيديوهات الكاميرا بعد نقل الـ DOM لنافذة PiP (حتى لا تتجمد كصورة)
        requestAnimationFrame(function () {
            rebuildPip();
            pipBody?.querySelectorAll('video').forEach(playPipVideo);
            syncFloatingPipExclusive();
        });
        setTimeout(function () {
            rebuildPip();
            pipBody?.querySelectorAll('video').forEach(playPipVideo);
            syncFloatingPipExclusive();
        }, 250);
        updateOsPipButtons(true);
        syncFloatingPipExclusive();
        setStatus(wantCamerasOnly
            ? 'نافذة الكاميرات العائمة نشطة — تتنقل معك فوق كل التطبيقات'
            : 'النافذة العائمة نشطة — تبقى فوق التبويبات والتطبيقات');
        hideStatusSoon();
        return true;
        } finally {
            osPipOpening = false;
        }
    }
    async function toggleOsFloatingWindow(forceCamerasOnly) {
        try {
            if (osPipOpening) return;
            if (osPipActive || documentPictureInPicture?.window || document.pictureInPictureElement) {
                await closeOsFloatingWindow();
                syncFloatingPipExclusive();
                return;
            }
            await openDocumentPiP({
                camerasOnly: forceCamerasOnly === true || lkTheme === 'instructor' || role === 'host',
            });
        } catch (err) {
            if (err?.name === 'NotAllowedError') {
                setStatus('اسمح للموقع بفتح النافذة العائمة من إعدادات المتصفح', true);
                syncFloatingPipExclusive();
                return;
            }
            console.warn(err);
            // لا نفتح Video PiP كبديل للمدرب حتى لا تتكرر النوافذ مع شريط الكاميرات
            if (lkTheme === 'instructor' || role === 'host' || forceCamerasOnly === true) {
                syncFloatingPipExclusive();
                setStatus(errMsg(err, 'تعذر فتح نافذة النظام — شريط الكاميرات يبقى داخل الصفحة'), true);
                return;
            }
            try { await openVideoPiP(); } catch (e2) {
                setStatus(errMsg(e2, 'تعذر فتح النافذة العائمة'), true);
            }
        }
    }
    function maybeAutoOpenOsPip() {
        // معطّل عمداً: الفتح التلقائي يسبب نافذة نظام + شريط داخل الصفحة أو فشلاً صامتاً
        return;
    }
    osPipBtn?.addEventListener('click', () => toggleOsFloatingWindow(true));
    osPipFocusBtn?.addEventListener('click', () => toggleOsFloatingWindow(true));
    osPipCamBtn?.addEventListener('click', () => toggleOsFloatingWindow(true));
    document.addEventListener('leavepictureinpicture', () => {
        updateOsPipButtons(false);
        syncFloatingPipExclusive();
    });
    if (window.documentPictureInPicture) {
        window.documentPictureInPicture.addEventListener('enter', () => {
            updateOsPipButtons(true);
            syncFloatingPipExclusive();
        });
    }

    // drag focus viewport while zoomed
    if (focusViewport) {
        let dragging = false, sx = 0, sy = 0, sl = 0, st = 0;
        focusViewport.addEventListener('mousedown', (e) => {
            if (zoom <= 1) return;
            dragging = true;
            focusViewport.classList.add('is-dragging');
            sx = e.clientX; sy = e.clientY;
            sl = focusViewport.scrollLeft; st = focusViewport.scrollTop;
        });
        window.addEventListener('mousemove', (e) => {
            if (!dragging) return;
            focusViewport.scrollLeft = sl - (e.clientX - sx);
            focusViewport.scrollTop = st - (e.clientY - sy);
        });
        window.addEventListener('mouseup', () => {
            dragging = false;
            focusViewport.classList.remove('is-dragging');
        });
        focusViewport.addEventListener('wheel', (e) => {
            if (!e.ctrlKey && !e.metaKey) return;
            e.preventDefault();
            setZoom(zoom + (e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP));
        }, { passive: false });
        focusViewport.addEventListener('dblclick', () => {
            if (zoom >= ZOOM_MAX * 0.95) {
                autoFitZoom();
            } else {
                maxFillZoom();
            }
        });
    }

    // draggable pip (fixed — يتحرك داخل نافذة المتصفح)
    if (pip) {
        const head = pip.querySelector('.lk-pip__head');
        let drag = false, ox = 0, oy = 0;
        head?.addEventListener('mousedown', (e) => {
            if (e.target.closest('button')) return;
            drag = true;
            const r = pip.getBoundingClientRect();
            ox = e.clientX - r.left;
            oy = e.clientY - r.top;
            e.preventDefault();
        });
        window.addEventListener('mousemove', (e) => {
            if (!drag) return;
            let left = e.clientX - ox;
            let top = e.clientY - oy;
            left = Math.max(8, Math.min(window.innerWidth - pip.offsetWidth - 8, left));
            top = Math.max(8, Math.min(window.innerHeight - pip.offsetHeight - 8, top));
            pip.style.left = left + 'px';
            pip.style.top = top + 'px';
            pip.style.right = 'auto';
            pip.style.bottom = 'auto';
            pip.style.insetInlineEnd = 'auto';
        });
        window.addEventListener('mouseup', () => { drag = false; });
    }

    window.addEventListener('beforeunload', () => {
        try { if (documentPictureInPicture?.window) documentPictureInPicture.window.close(); } catch (e) {}
        try { room.disconnect(); } catch (e) {}
    });
    window.__mxLkLeaveRoom = function () {
        try { room.disconnect(); } catch (e) {}
    };

    function submitHostEndSession(alreadyConfirmed) {
        if (!lkHostEndFormId) return false;
        if (!alreadyConfirmed && !confirm('إنهاء البث للجميع؟ سيتم إغلاق الغرفة أمام المشاركين.')) {
            return false;
        }
        window.__mxLkHostSessionEnded = true;
        lkHostSessionEnded = true;
        if (typeof window.__mxLiveHostEndSession === 'function') {
            window.__mxLiveHostEndSession(alreadyConfirmed);
            return true;
        }
        const form = document.getElementById(lkHostEndFormId);
        if (!form) return false;
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
        return true;
    }
    window.__mxLkSubmitHostEndSession = submitHostEndSession;

    function confirmHostLeave(e) {
        if (e) e.preventDefault();
        if (confirm('⚠️ لإنهاء المحاضرة للجميع استخدم «إنهاء البث» — المغادرة وحدها تترك الغرفة مفتوحة.\n\nهل تريد إنهاء البث الآن؟')) {
            submitHostEndSession(true);
            return false;
        }
        if (confirm('المغادرة دون إنهاء البث؟ ستبقى الغرفة نشطة للطلاب.')) {
            window.__mxLkHostSessionEnded = true;
            lkHostSessionEnded = true;
            window.__mxLkLeaveRoom();
            window.location.href = lkLeaveUrl;
        }
        return false;
    }

    document.getElementById('lk-host-end')?.addEventListener('click', function (e) {
        e.preventDefault();
        submitHostEndSession(false);
    });

    const leaveBtn = document.getElementById('lk-leave');
    if (leaveBtn && lkHostLeaveWarn && lkHostEndFormId && role === 'host') {
        leaveBtn.addEventListener('click', confirmHostLeave);
    }

    if (lkHostEndFormId && role === 'host') {
        window.addEventListener('beforeunload', function (e) {
            if (window.__mxLkHostSessionEnded || lkHostSessionEnded) return;
            e.preventDefault();
            e.returnValue = '';
        });
    }

    /** مصادر التسجيل الصامت: شير إن وُجد، وإلا كاميرات الغرفة + الصوت */
    window.__mxLkGetRecordCapture = function () {
        const audioTracks = [];
        const pushTrack = function (t) {
            if (!t || t.readyState !== 'live' || t.enabled === false) return;
            if (audioTracks.indexOf(t) >= 0) return;
            audioTracks.push(t);
        };
        try {
            room.localParticipant?.audioTrackPublications?.forEach(function (pub) {
                if (pub?.isMuted) return;
                pushTrack(pub.track?.mediaStreamTrack);
            });
            room.localParticipant?.trackPublications?.forEach(function (pub) {
                if (pub?.source === Track.Source.ScreenShareAudio) {
                    pushTrack(pub.track?.mediaStreamTrack);
                }
            });
        } catch (e) {}
        try {
            room.remoteParticipants?.forEach(function (participant) {
                participant.audioTrackPublications?.forEach(function (pub) {
                    if (pub?.isMuted) return;
                    pushTrack(pub.track?.mediaStreamTrack);
                });
                participant.trackPublications?.forEach(function (pub) {
                    if (pub?.source === Track.Source.ScreenShareAudio) {
                        pushTrack(pub.track?.mediaStreamTrack);
                    }
                });
            });
        } catch (eRemote) {}
        try {
            annDisplayStream?.getAudioTracks()?.forEach(pushTrack);
        } catch (e2) {}

        const cameraVideos = [];
        try {
            tiles.forEach(function (ref) {
                if (!ref || !ref.video) return;
                if (ref.source === Track.Source.ScreenShare) return;
                const v = ref.video;
                if (v.readyState < 2 || !(v.videoWidth > 0)) return;
                cameraVideos.push({
                    video: v,
                    label: (ref.participant && (ref.participant.name || ref.participant.identity)) || '',
                    isLocal: !!(ref.participant && ref.participant.isLocal),
                });
            });
        } catch (eCam) {}

        // فيديوهات ظاهرة في الواجهة كاحتياط (إن لم تُسجَّل في tiles)
        try {
            if (shell) {
                shell.querySelectorAll('video').forEach(function (v) {
                    if (!v || v.id === 'lk-focus-video') return;
                    if (v.readyState < 2 || !(v.videoWidth > 0)) return;
                    const already = cameraVideos.some(function (c) { return c.video === v; });
                    if (!already) cameraVideos.push({ video: v, label: '', isLocal: false });
                });
            }
        } catch (eDom) {}

        const sharing = !!screenOn;
        return {
            screenSharing: sharing,
            canvas: (sharing && annOutCanvas && annOutCanvas.width > 0) ? annOutCanvas : null,
            videoElement: (sharing && focusVideo && (focusVideo.srcObject || focusVideo.readyState >= 2))
                ? focusVideo
                : null,
            cameraVideos: cameraVideos,
            audioTracks: audioTracks,
            roomShell: shell || null,
        };
    };
    window.__mxLkNotifyRecordingCaptureChanged = function () {
        try {
            window.dispatchEvent(new CustomEvent('mx-lk-record-capture-changed'));
        } catch (e) {}
    };

    /** نشر بيانات خفيفة للغرفة (سبورة / إشارات) عبر LiveKit Data Channel */
    window.__mxLkPublishData = function (data, opts) {
        opts = opts || {};
        if (!connected || !room?.localParticipant) return false;
        try {
            const topic = opts.topic || '';
            let bytes;
            if (data instanceof Uint8Array) {
                bytes = data;
            } else if (typeof data === 'string') {
                bytes = new TextEncoder().encode(data);
            } else {
                bytes = new TextEncoder().encode(JSON.stringify(data));
            }
            const reliable = opts.reliable !== false;
            const dest = Array.isArray(opts.destinationIdentities) ? opts.destinationIdentities : undefined;
            let pub;
            try {
                pub = room.localParticipant.publishData(bytes, {
                    reliable,
                    topic: topic || undefined,
                    destinationIdentities: dest,
                });
            } catch (eOpt) {
                // توافق مع واجهات أقدم من LiveKit
                const kind = (window.LivekitClient && window.LivekitClient.DataPacket_Kind)
                    ? (reliable ? window.LivekitClient.DataPacket_Kind.RELIABLE : window.LivekitClient.DataPacket_Kind.LOSSY)
                    : undefined;
                pub = kind != null
                    ? room.localParticipant.publishData(bytes, kind)
                    : room.localParticipant.publishData(bytes, reliable);
            }
            if (pub && typeof pub.then === 'function') {
                pub.catch(function () {});
            }
            return true;
        } catch (ePub) {
            return false;
        }
    };

    window.__mxLkIsConnected = function () {
        return !!connected;
    };

    // لا تتصل تلقائياً — الانتظار لضغطة «دخول الحصة» (موبايل/تابلت/سطح مكتب)
    } // end bootLivekitRoom
})();
</script>
