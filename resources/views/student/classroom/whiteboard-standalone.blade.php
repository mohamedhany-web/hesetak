@extends(!empty($useInstructorRoutes) ? 'layouts.app' : 'layouts.student-timeline')

@section('title', 'حصتك Whiteboard')
@section('header', 'حصتك Whiteboard')

@push('styles')
<style>
    .mx-standalone-excalidraw-wrap {
        position: relative;
        width: 100%;
        min-height: min(85vh, 880px);
        height: min(85vh, 880px);
        border-radius: 1rem;
        overflow: hidden;
        border: 1px solid rgb(226 232 240);
    }
    .dark .mx-standalone-excalidraw-wrap {
        border-color: rgb(51 65 85);
    }
    #mx-standalone-excalidraw-root {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
    #mx-standalone-excalidraw-root .excalidraw {
        --color-surface-lowest: #0f172a;
        height: 100% !important;
    }
    /* حصتك Whiteboard: مكتبة + روابط وخدمات خارجية */
    .mx-muallimx-whiteboard .excalidraw .layer-ui__library,
    .mx-muallimx-whiteboard .excalidraw .layer-ui__library-message,
    .mx-muallimx-whiteboard .excalidraw .library-menu,
    .mx-muallimx-whiteboard .excalidraw .library-menu-dropdown-container,
    .mx-muallimx-whiteboard .excalidraw .library-menu-dropdown-container--in-heading,
    .mx-muallimx-whiteboard .excalidraw .library-menu-items-container,
    .mx-muallimx-whiteboard .excalidraw .library-menu-control-buttons,
    .mx-muallimx-whiteboard .excalidraw .library-menu-control-buttons--at-bottom,
    .mx-muallimx-whiteboard .excalidraw .library-menu-browse-button,
    .mx-muallimx-whiteboard .excalidraw .library-menu-items-private-library-container,
    .mx-muallimx-whiteboard .excalidraw .library-actions-counter,
    .mx-muallimx-whiteboard .excalidraw .single-library-item,
    .mx-muallimx-whiteboard .excalidraw .single-library-item-wrapper,
    .mx-muallimx-whiteboard .excalidraw .library-unit,
    .mx-muallimx-whiteboard .excalidraw .selected-library-items,
    .mx-muallimx-whiteboard .excalidraw [class*="publish-library"] {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
    .mx-muallimx-whiteboard .excalidraw .dropdown-menu a.dropdown-menu-item[href^="http://"],
    .mx-muallimx-whiteboard .excalidraw .dropdown-menu a.dropdown-menu-item[href^="https://"] {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
    .mx-muallimx-whiteboard .excalidraw .dropdown-menu .dropdown-menu-group:has(a.dropdown-menu-item[href^="http"]),
    .mx-muallimx-whiteboard .excalidraw .dropdown-menu .dropdown-menu-group:has(a.dropdown-menu-item[href^="https"]) {
        display: none !important;
    }
    .mx-muallimx-whiteboard .excalidraw .HelpDialog__header {
        display: none !important;
    }
    .mx-muallimx-whiteboard .excalidraw [data-testid="collab-button"] {
        display: none !important;
        pointer-events: none !important;
    }
    .mx-muallimx-whiteboard .excalidraw .ExcalidrawLogo,
    .mx-muallimx-whiteboard .excalidraw .welcome-screen-center__logo {
        display: none !important;
        pointer-events: none !important;
    }
    .mx-muallimx-whiteboard .excalidraw a.welcome-screen-menu-item[href^="http://"],
    .mx-muallimx-whiteboard .excalidraw a.welcome-screen-menu-item[href^="https://"] {
        display: none !important;
        pointer-events: none !important;
    }
    .mx-muallimx-whiteboard .excalidraw .ExportDialog a[href^="http://"],
    .mx-muallimx-whiteboard .excalidraw .ExportDialog a[href^="https://"],
    .mx-muallimx-whiteboard .excalidraw .ImageExportModal a[href^="http://"],
    .mx-muallimx-whiteboard .excalidraw .ImageExportModal a[href^="https://"],
    .mx-muallimx-whiteboard .excalidraw .OverwriteConfirm a[href^="http://"],
    .mx-muallimx-whiteboard .excalidraw .OverwriteConfirm a[href^="https://"],
    .mx-muallimx-whiteboard .excalidraw [class*="publish-library"] a[href^="http://"],
    .mx-muallimx-whiteboard .excalidraw [class*="publish-library"] a[href^="https://"],
    .mx-muallimx-whiteboard .excalidraw .HelpDialog a[href^="http://"],
    .mx-muallimx-whiteboard .excalidraw .HelpDialog a[href^="https://"] {
        display: none !important;
        pointer-events: none !important;
        visibility: hidden !important;
    }
    #mx-standalone-loading {
        position: absolute;
        inset: 0;
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.65);
        color: #94a3b8;
        font-size: 14px;
        text-align: center;
        padding: 1rem;
    }
</style>
@endpush

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-4">
    <p class="text-sm text-slate-600 dark:text-slate-400">لوحة مستقلة خارج الاجتماع — يمكنك التصدير من قائمة حصتك Whiteboard (PNG/SVG).</p>
    <div class="mx-standalone-excalidraw-wrap bg-slate-900">
        <div id="mx-standalone-excalidraw-root" class="mx-muallimx-whiteboard" data-lang="ar"></div>
        <div id="mx-standalone-loading">جاري تحميل حصتك Whiteboard…</div>
    </div>
</div>
@endsection

@php
    $mxBp = rtrim((string) request()->getBasePath(), '/');
    $mxP = $mxBp !== '' ? $mxBp : '';
    $mxExBases = array_values(array_unique(array_filter([
        $mxP . '/mx-vendor/excalidraw/',
        '/mx-vendor/excalidraw/',
        $mxP . '/vendor/excalidraw/',
        '/vendor/excalidraw/',
    ])));
@endphp

@push('scripts')
<script>
(function() {
    var excRoot = document.getElementById('mx-standalone-excalidraw-root');
    var excLoading = document.getElementById('mx-standalone-loading');
    var mxExcalidrawBases = {!! json_encode($mxExBases) !!};
    var excVendorPromise = null;
    var mounted = false;

    function mxAbsAssetUrl(basePath) {
        var b = String(basePath || '').replace(/\/?$/, '/');
        if (b.indexOf('http') === 0) return b;
        if (b.charAt(0) !== '/') b = '/' + b;
        return window.location.origin + b;
    }

    function loadScriptSequential(url) {
        return new Promise(function(resolve, reject) {
            var s = document.createElement('script');
            s.src = url;
            s.async = false;
            s.onerror = function() {
                s.onerror = s.onload = null;
                reject(new Error('فشل تحميل: ' + url));
            };
            s.onload = function() {
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
                .then(function() { return loadScriptSequential(prefix + 'react-dom.production.min.js'); })
                .then(function() { return loadScriptSequential(prefix + 'dist/excalidraw.production.min.js'); })
                .then(function() {
                    if (!window.React || !window.ReactDOM || !getExcalidrawLib()) {
                        throw new Error('تعذّر تعريف المكتبات بعد التحميل');
                    }
                });
        }

        function tryNext(i) {
            if (i >= bases.length) {
                return Promise.reject(new Error('فشل تحميل ملفات اللوحة من كل المسارات'));
            }
            return loadFromBase(bases[i]).catch(function() { return tryNext(i + 1); });
        }

        excVendorPromise = tryNext(0).catch(function(e) {
            excVendorPromise = null;
            throw e;
        });
        return excVendorPromise;
    }

    function nudgeLayout() {
        window.dispatchEvent(new Event('resize'));
        if (window.requestAnimationFrame) {
            requestAnimationFrame(function() { window.dispatchEvent(new Event('resize')); });
        }
    }

    function fail(msg) {
        if (excLoading) {
            excLoading.textContent = msg;
            excLoading.style.display = 'flex';
        }
        console.error('[Whiteboard standalone]', msg);
    }

    function mountWhenSized() {
        var deadline = Date.now() + 5000;
        function tick() {
            if (!excRoot) return;
            var rect = excRoot.getBoundingClientRect();
            if (rect.width < 8 || rect.height < 8) {
                if (Date.now() > deadline) {
                    fail('الحاوية بلا أبعاد كافية.');
                    return;
                }
                requestAnimationFrame(tick);
                return;
            }
            try {
                var Lib = getExcalidrawLib();
                var R = window.React;
                var RD = window.ReactDOM;
                var Excalidraw = Lib.Excalidraw;
                if (Excalidraw == null || (typeof Excalidraw !== 'function' && typeof Excalidraw !== 'object')) {
                    throw new Error('مكوّن حصتك Whiteboard غير متاح');
                }
                if (typeof RD.createRoot !== 'function') {
                    throw new Error('ReactDOM.createRoot غير متاح');
                }
                RD.createRoot(excRoot).render(R.createElement(Excalidraw, {
                    langCode: 'ar-SA',
                    excalidrawAPI: function(api) {
                        window.__mxStandaloneExcalidrawAPI = api;
                    }
                }));
                mounted = true;
                if (excLoading) excLoading.style.display = 'none';
                nudgeLayout();
                setTimeout(nudgeLayout, 120);
            } catch (e) {
                fail('تعذّر تهيئة اللوحة: ' + (e && e.message ? e.message : String(e)));
            }
        }
        requestAnimationFrame(tick);
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (!excRoot) return;
        ensureExcalidrawVendorLoaded()
            .then(function() { mountWhenSized(); })
            .catch(function(e) {
                fail('تعذّر تحميل المكتبات: ' + (e && e.message ? e.message : ''));
            });
    });
})();
</script>
@endpush
