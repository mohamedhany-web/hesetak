{{-- نفس سبورة فصول حصتك (Excalidraw) — نافذة منبثقة كبيرة --}}
@php
    $hstkWbUiMode = $hstkWbUiMode ?? 'full';
@endphp
<style>
    /* فوق كاميرات Hissatak Meeting العائمة (z-index: 99990) وأشرطة التحكم */
    #wb-popup { z-index: 100120 !important; }
    #wb-popup.is-open {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }
    body.hstk-wb-open .lk-pip,
    body.hstk-wb-open #lk-pip {
        visibility: hidden !important;
        pointer-events: none !important;
    }
    #pkg-features-dd-panel { z-index: 130; }
    .pkg-features-dd-panel-inner { box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(34, 211, 238, 0.06); }
    #pkg-features-dd-btn:focus-visible {
        outline: none;
        box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.9), 0 0 0 4px rgba(34, 211, 238, 0.35);
    }
    #wb-popup-stage { min-height: 50vh; }
    .mx-excalidraw-host {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        min-height: 240px;
    }
    .mx-excalidraw-host .excalidraw {
        --color-surface-lowest: #0f172a;
    }
    .hstk-wb .excalidraw .layer-ui__library,
    .hstk-wb .excalidraw .layer-ui__library-message,
    .hstk-wb .excalidraw .library-menu,
    .hstk-wb .excalidraw .library-menu-dropdown-container,
    .hstk-wb .excalidraw .library-menu-dropdown-container--in-heading,
    .hstk-wb .excalidraw .library-menu-items-container,
    .hstk-wb .excalidraw .library-menu-control-buttons,
    .hstk-wb .excalidraw .library-menu-control-buttons--at-bottom,
    .hstk-wb .excalidraw .library-menu-browse-button,
    .hstk-wb .excalidraw .library-menu-items-private-library-container,
    .hstk-wb .excalidraw .library-actions-counter,
    .hstk-wb .excalidraw .single-library-item,
    .hstk-wb .excalidraw .single-library-item-wrapper,
    .hstk-wb .excalidraw .library-unit,
    .hstk-wb .excalidraw .selected-library-items,
    .hstk-wb .excalidraw [class*="publish-library"] {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
    .hstk-wb .excalidraw .dropdown-menu a.dropdown-menu-item[href^="http://"],
    .hstk-wb .excalidraw .dropdown-menu a.dropdown-menu-item[href^="https://"] {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
    .hstk-wb .excalidraw .dropdown-menu .dropdown-menu-group:has(a.dropdown-menu-item[href^="http"]) {
        display: none !important;
    }
    .hstk-wb .excalidraw .dropdown-menu .dropdown-menu-group:has(a.dropdown-menu-item[href^="https"]) {
        display: none !important;
    }
    .hstk-wb .excalidraw .HelpDialog__header {
        display: none !important;
    }
    .hstk-wb .excalidraw [data-testid="collab-button"] {
        display: none !important;
        pointer-events: none !important;
    }
    .hstk-wb .excalidraw .ExcalidrawLogo,
    .hstk-wb .excalidraw .welcome-screen-center__logo {
        display: none !important;
        pointer-events: none !important;
    }
    .hstk-wb .excalidraw a.welcome-screen-menu-item[href^="http://"],
    .hstk-wb .excalidraw a.welcome-screen-menu-item[href^="https://"] {
        display: none !important;
        pointer-events: none !important;
    }
    .hstk-wb .excalidraw .ExportDialog a[href^="http://"],
    .hstk-wb .excalidraw .ExportDialog a[href^="https://"],
    .hstk-wb .excalidraw .ImageExportModal a[href^="http://"],
    .hstk-wb .excalidraw .ImageExportModal a[href^="https://"],
    .hstk-wb .excalidraw .OverwriteConfirm a[href^="http://"],
    .hstk-wb .excalidraw .OverwriteConfirm a[href^="https://"],
    .hstk-wb .excalidraw [class*="publish-library"] a[href^="http://"],
    .hstk-wb .excalidraw [class*="publish-library"] a[href^="https://"],
    .hstk-wb .excalidraw .HelpDialog a[href^="http://"],
    .hstk-wb .excalidraw .HelpDialog a[href^="https://"] {
        display: none !important;
        pointer-events: none !important;
        visibility: hidden !important;
    }
    /* وضع المشارك: قلم + ممحاة + يد (تحريك اللوحة) فقط */
    .hstk-wb-student-draw-lite .excalidraw button[data-testid^="toolbar-"]:not([data-testid="toolbar-freedraw"]):not([data-testid="toolbar-eraser"]):not([data-testid="toolbar-hand"]) {
        display: none !important;
        pointer-events: none !important;
    }
    .mx-excalidraw-loading {
        position: absolute;
        inset: 0;
        z-index: 5;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15,23,42,0.75);
        color: #94a3b8;
        font-size: 14px;
    }
</style>

<div id="wb-popup" class="hidden fixed inset-0 p-2 sm:p-4" inert aria-hidden="true" role="dialog" aria-labelledby="wb-popup-title" aria-modal="true">
    <div id="wb-popup-backdrop" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer" aria-hidden="true"></div>
    <div id="wb-popup-panel" class="relative z-[141] flex flex-col w-full max-w-[min(1680px,99vw)] h-[min(92vh,calc(100dvh-1rem))] rounded-2xl border border-slate-600 bg-slate-900 shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-700 bg-slate-800/95 shrink-0">
            <h2 id="wb-popup-title" class="text-base font-bold text-white m-0 flex items-center gap-2">
                <i class="fas fa-chalkboard text-amber-400"></i>
                السبورة التفاعلية — حصتك
            </h2>
            <div class="flex items-center gap-2">
                <button type="button" id="btn-wb-popup-fullscreen" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-medium border border-slate-600" title="ملء الشاشة (اخرج بـ Esc)">
                    <i class="fas fa-expand"></i>
                    <span class="hidden sm:inline">ملء الشاشة</span>
                </button>
                <button type="button" id="wb-popup-close" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-700 hover:bg-rose-600/80 text-white text-lg leading-none border border-slate-600" aria-label="إغلاق السبورة">&times;</button>
            </div>
        </div>
        <div id="wb-popup-stage" class="relative flex-1 min-h-0 bg-[#121212]">
            <div id="mx-excalidraw-root" class="mx-excalidraw-host hstk-wb{{ $hstkWbUiMode === 'student_lite' ? ' hstk-wb-student-draw-lite' : '' }}" data-view-only="0" data-lang="ar" data-wb-ui-mode="{{ $hstkWbUiMode }}"></div>
            <div id="mx-excalidraw-loading" class="mx-excalidraw-loading">جاري تحميل السبورة التفاعلية…</div>
        </div>
        <div id="wb-popup-toolbar" class="flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 border-t border-slate-700 bg-slate-800/95 shrink-0">
            <span class="text-slate-400 text-[11px] leading-relaxed text-center max-w-3xl">
                @if($hstkWbUiMode === 'student_lite')
                <strong class="text-slate-200">سبورة متزامنة</strong> — ترى رسم المعلّم مباشرة، ويمكنك الرسم عند السماح.
                @else
                <strong class="text-slate-200">سبورة تفاعلية مباشرة</strong> — المزامنة فورية مع الطرف الآخر عبر البث.
                @endif
            </span>
        </div>
    </div>
</div>

@php
    $mxBp = rtrim((string) request()->getBasePath(), '/');
    $mxP = $mxBp !== '' ? $mxBp : '';
    $mxExBasesLive = array_values(array_unique(array_filter([
        $mxP . '/mx-vendor/excalidraw/',
        '/mx-vendor/excalidraw/',
        $mxP . '/vendor/excalidraw/',
        '/vendor/excalidraw/',
    ])));
@endphp
<script>
(function () {
    var mxExcalidrawBases = {!! json_encode($mxExBasesLive) !!};
    var wbPopup = document.getElementById('wb-popup');
    var wbPopupStage = document.getElementById('wb-popup-stage');
    var wbPopupPanel = document.getElementById('wb-popup-panel');
    var excRoot = document.getElementById('mx-excalidraw-root');
    var excLoading = document.getElementById('mx-excalidraw-loading');
    var excReactRoot = null;
    var excMounted = false;
    var excMountPromise = null;
    var wbPopupClosing = false;
    var excVendorPromise = null;
    var syncOpts = window.__mxWbSyncOptions || null;

    if (excRoot && syncOpts && syncOpts.viewOnly) {
        excRoot.setAttribute('data-view-only', '1');
    }

    function excShowLoading(on) {
        if (excLoading) excLoading.style.display = on ? 'flex' : 'none';
    }

    function nudgeExcalidrawLayout() {
        window.dispatchEvent(new Event('resize'));
        if (window.requestAnimationFrame) {
            requestAnimationFrame(function () { window.dispatchEvent(new Event('resize')); });
        }
    }

    function mxAbsAssetUrl(basePath) {
        var b = String(basePath || '').replace(/\/?$/, '/');
        if (b.indexOf('http') === 0) return b;
        if (b.charAt(0) !== '/') b = '/' + b;
        return window.location.origin + b;
    }

    function loadScriptSequential(url) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = url;
            s.async = false;
            s.onerror = function () {
                s.onerror = s.onload = null;
                reject(new Error('فشل تحميل: ' + url));
            };
            s.onload = function () {
                s.onerror = s.onload = null;
                resolve();
            };
            (document.head || document.documentElement).appendChild(s);
        });
    }

    function getExcalidrawLib() {
        if (typeof ExcalidrawLib !== 'undefined') return ExcalidrawLib;
        if (typeof window.ExcalidrawLib !== 'undefined') return window.ExcalidrawLib;
        return null;
    }

    function ensureExcalidrawVendorLoaded() {
        if (window.React && window.ReactDOM && getExcalidrawLib()) {
            return Promise.resolve();
        }
        if (excVendorPromise) return excVendorPromise;
        var bases = Array.isArray(mxExcalidrawBases) ? mxExcalidrawBases : [];
        if (!bases.length) bases = ['/mx-vendor/excalidraw/', '/vendor/excalidraw/'];

        function loadFromBase(basePath) {
            var root = String(basePath || '').replace(/\/?$/, '/');
            window.EXCALIDRAW_ASSET_PATH = root + 'dist/';
            var prefix = mxAbsAssetUrl(root);
            return loadScriptSequential(prefix + 'react.production.min.js')
                .then(function () { return loadScriptSequential(prefix + 'react-dom.production.min.js'); })
                .then(function () { return loadScriptSequential(prefix + 'dist/excalidraw.production.min.js'); })
                .then(function () {
                    if (!window.React || !window.ReactDOM || !getExcalidrawLib()) {
                        throw new Error('تعذّر تعريف مكوّنات السبورة بعد التحميل');
                    }
                });
        }

        function tryNext(i) {
            if (i >= bases.length) {
                return Promise.reject(new Error('فشل كل مسارات التحميل. تأكد من وجود public/vendor/excalidraw'));
            }
            return loadFromBase(bases[i]).catch(function () { return tryNext(i + 1); });
        }

        excVendorPromise = tryNext(0).catch(function (e) {
            excVendorPromise = null;
            throw e;
        });
        return excVendorPromise;
    }

    function mountحصتكExcalidrawOnce() {
        if (excMounted) return Promise.resolve();
        if (excMountPromise) return excMountPromise;
        if (!excRoot) return Promise.reject(new Error('no excalidraw root'));
        excShowLoading(true);

        function failMount(err) {
            console.error('[السبورة التفاعلية]', err);
            excMountPromise = null;
            excShowLoading(false);
            if (excLoading) {
                var detail = (err && err.message) ? String(err.message) : '';
                if (detail.length > 240) detail = detail.slice(0, 237) + '…';
                excLoading.textContent = 'تعذّر تهيئة السبورة.' + (detail ? (' ' + detail) : '');
                excLoading.style.display = 'flex';
            }
        }

        excMountPromise = ensureExcalidrawVendorLoaded()
            .then(function () {
                return new Promise(function (resolve, reject) {
                    var deadline = Date.now() + 12000;
                    function tryMount() {
                        var Lib = getExcalidrawLib();
                        var ReactMod = window.React;
                        var ReactDOM = window.ReactDOM;
                        if (!Lib || !ReactMod || !ReactDOM) {
                            failMount(new Error('المكتبات غير متاحة'));
                            reject(new Error('missing after load'));
                            return;
                        }
                        var rect = excRoot.getBoundingClientRect();
                        if (rect.width < 8 || rect.height < 8) {
                            if (Date.now() > deadline) {
                                // فرض أبعاد دنيا ثم إعادة المحاولة مرة أخيرة
                                try {
                                    excRoot.style.minHeight = '60vh';
                                    if (wbPopupStage) wbPopupStage.style.minHeight = '60vh';
                                } catch (eDim) {}
                                rect = excRoot.getBoundingClientRect();
                                if (rect.width < 8 || rect.height < 8) {
                                    failMount(new Error('الحاوية بلا أبعاد كافية.'));
                                    reject(new Error('container size'));
                                    return;
                                }
                            } else {
                                requestAnimationFrame(tryMount);
                                return;
                            }
                        }
                        try {
                            var Excalidraw = Lib.Excalidraw;
                            var createRoot = ReactDOM.createRoot;
                            if (Excalidraw == null || (typeof Excalidraw !== 'function' && typeof Excalidraw !== 'object')) {
                                throw new Error('حزمة السبورة غير صالحة');
                            }
                            if (typeof createRoot !== 'function') {
                                throw new Error('ReactDOM.createRoot غير متاح');
                            }
                            var viewOnly = excRoot.getAttribute('data-view-only') === '1';
                            var lang = excRoot.getAttribute('data-lang') || '';
                            var uiMode = excRoot.getAttribute('data-wb-ui-mode') || 'full';
                            var props = {
                                viewModeEnabled: viewOnly,
                                onChange: function () {
                                    if (window.__mxحصتكWbSync && typeof window.__mxحصتكWbSync.onLocalChange === 'function') {
                                        window.__mxحصتكWbSync.onLocalChange();
                                    }
                                },
                                excalidrawAPI: function (api) {
                                    window.__mxحصتكExcalidrawAPI = api;
                                    (function bindحصتكWbSync(tries) {
                                        try {
                                            var opt = window.__mxWbSyncOptions || null;
                                            if (!opt) return;
                                            if (window.__mxحصتكWbSync) {
                                                if (typeof window.__mxحصتكWbSync.requestRemote === 'function') {
                                                    window.__mxحصتكWbSync.requestRemote();
                                                }
                                                return;
                                            }
                                            if (!window.MxClassroomWhiteboardSync) {
                                                if (tries < 40) setTimeout(function () { bindحصتكWbSync(tries + 1); }, 100);
                                                return;
                                            }
                                            window.__mxحصتكWbSync = window.MxClassroomWhiteboardSync.attach({
                                                getApi: function () { return window.__mxحصتكExcalidrawAPI; },
                                                role: opt.role || 'participant',
                                                canEmit: !!opt.canEmit,
                                                canReceive: opt.canReceive !== false,
                                                mergeRemote: !!opt.mergeRemote,
                                                stateUrl: opt.stateUrl || '',
                                                pushUrl: opt.pushUrl || '',
                                                csrf: opt.csrf || '',
                                            });
                                        } catch (eSync) {}
                                    })(0);
                                }
                            };
                            if (lang.indexOf('ar') === 0) props.langCode = 'ar-SA';
                            if (uiMode === 'student_lite') {
                                props.UIOptions = {
                                    tools: { image: false },
                                    canvasActions: {
                                        loadScene: false,
                                        saveToActiveFile: false,
                                        export: false,
                                        saveAsImage: false,
                                        toggleTheme: false,
                                        clearCanvas: false,
                                        changeViewBackgroundColor: false
                                    }
                                };
                            }
                            excReactRoot = createRoot(excRoot);
                            excReactRoot.render(ReactMod.createElement(Excalidraw, props));
                            excMounted = true;
                            excShowLoading(false);
                            nudgeExcalidrawLayout();
                            resolve();
                        } catch (err) {
                            failMount(err);
                            reject(err);
                        }
                    }
                    requestAnimationFrame(tryMount);
                });
            })
            .catch(function (err) {
                failMount(err);
                excMountPromise = null;
                return Promise.reject(err);
            });

        return excMountPromise;
    }

    var wbCanvas = null;
    var wbCtx = null;

    function mergeExcalidrawToMain(done) {
        var api = window.__mxحصتكExcalidrawAPI;
        if (!api || !wbCanvas || !wbCtx) {
            if (done) done();
            return;
        }
        if (done) done();
    }

    function openWbPopup() {
        if (!wbPopup) return;
        wbPopup.removeAttribute('inert');
        wbPopup.classList.remove('hidden');
        wbPopup.classList.add('is-open');
        wbPopup.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        document.body.classList.add('hstk-wb-open');
        // امنح المتصفح إطار تخطيط قبل القياس/التركيب
        requestAnimationFrame(function () {
            mountحصتكExcalidrawOnce().then(function () {
                setTimeout(nudgeExcalidrawLayout, 80);
                setTimeout(nudgeExcalidrawLayout, 400);
                setTimeout(nudgeExcalidrawLayout, 900);
                try {
                    if (window.__mxحصتكWbSync && typeof window.__mxحصتكWbSync.requestRemote === 'function') {
                        window.__mxحصتكWbSync.requestRemote();
                    }
                } catch (eReq) {}
            }).catch(function (err) {
                console.error('[السبورة]', err);
            });
        });
    }

    function closeWbPopup() {
        if (wbPopupClosing) return;
        if (!wbPopup || wbPopup.classList.contains('hidden')) return;
        wbPopupClosing = true;

        function detachWhiteboardUi() {
            var ae = document.activeElement;
            if (ae && typeof ae.blur === 'function' && wbPopup.contains(ae)) {
                ae.blur();
            }
            try {
                var sel = window.getSelection && window.getSelection();
                if (sel && typeof sel.removeAllRanges === 'function') sel.removeAllRanges();
            } catch (eSel) {}

            wbPopup.classList.add('hidden');
            wbPopup.classList.remove('is-open');
            wbPopup.setAttribute('aria-hidden', 'true');
            wbPopup.setAttribute('inert', '');
            document.body.style.overflow = '';
            document.body.classList.remove('hstk-wb-open');

            var reopenBtn = document.getElementById('btn-wb-popup-open');
            if (reopenBtn && typeof reopenBtn.focus === 'function') {
                try {
                    reopenBtn.focus({ preventScroll: true });
                } catch (eF) {
                    try { reopenBtn.focus(); } catch (eF2) {}
                }
            }
            wbPopupClosing = false;
        }

        function runClosePipeline() {
            mergeExcalidrawToMain(function () {
                detachWhiteboardUi();
            });
        }

        var fsEl = document.fullscreenElement;
        if (fsEl && wbPopup.contains(fsEl)) {
            var p = document.exitFullscreen && document.exitFullscreen();
            if (p && typeof p.then === 'function') {
                p.then(runClosePipeline).catch(runClosePipeline);
            } else {
                runClosePipeline();
            }
        } else {
            runClosePipeline();
        }
    }

    window.__mxحصتكCloseWhiteboardPopup = closeWbPopup;

    if (wbPopup) {
        var wbOpenPopupBtn = document.getElementById('btn-wb-popup-open');
        if (wbOpenPopupBtn) wbOpenPopupBtn.addEventListener('click', openWbPopup);
        var wbClosePopupBtn = document.getElementById('wb-popup-close');
        if (wbClosePopupBtn) wbClosePopupBtn.addEventListener('click', closeWbPopup);
        var wbBackdropEl = document.getElementById('wb-popup-backdrop');
        if (wbBackdropEl) wbBackdropEl.addEventListener('click', closeWbPopup);
        var wbFsBtn = document.getElementById('btn-wb-popup-fullscreen');
        if (wbFsBtn && wbPopupPanel) {
            wbFsBtn.addEventListener('click', function () {
                if (!document.fullscreenElement) {
                    wbPopupPanel.requestFullscreen().catch(function () {});
                } else {
                    try { document.exitFullscreen(); } catch (ex) {}
                }
            });
        }

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && wbPopup && !wbPopup.classList.contains('hidden')) {
                closeWbPopup();
            }
        });

        if (wbPopupStage && typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(function () {
                if (wbPopup && !wbPopup.classList.contains('hidden')) {
                    nudgeExcalidrawLayout();
                }
            }).observe(wbPopupStage);
        }

        // تحميل مسبق لأصول السبورة
        setTimeout(function () {
            try { ensureExcalidrawVendorLoaded().catch(function () {}); } catch (ePre) {}
        }, 1200);
    }
})();
</script>
