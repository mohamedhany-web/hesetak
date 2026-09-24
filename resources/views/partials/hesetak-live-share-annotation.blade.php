{{--
  رسم فوق منطقة البث: قلم + ممحاة، إحداثيات معيّرة 0..1 لمزامنة فورية.
  mxAnnRole: student_emit | viewer_poll | classroom_guest_emit | host_emit | emit_and_poll
--}}
@php
    $mxAnnRole = $mxAnnRole ?? 'student_emit';
    $mxAnnPostUrl = $mxAnnPostUrl ?? '';
    $mxAnnPollUrl = $mxAnnPollUrl ?? '';
    $mxAnnSelfKey = $mxAnnSelfKey ?? (string) (auth()->id() ?? '');
@endphp
<style>
    #hstk-share-ann-layer {
        pointer-events: none;
        position: absolute;
        inset-inline: 0;
        top: 0;
        /* اترك شريط Hissatak Meeting (ميك/كاميرا/مغادرة) قابلاً للضغط وغير مغطى */
        bottom: var(--mx-ann-above-media, 5.35rem);
        z-index: 25;
    }
    #hstk-share-ann-layer.hstk-share-ann-drawing { pointer-events: auto; }
    #hstk-share-ann-layer.hstk-share-ann-drawing #hstk-share-ann-canvas { touch-action: none; }
    #hstk-share-ann-toolbar {
        pointer-events: auto;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        bottom: 0.45rem;
        z-index: 26;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.94);
        border: 1px solid rgb(71, 85, 105);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.35);
        max-width: min(95vw, 42rem);
    }
    @media (max-width: 640px) {
        #hstk-share-ann-layer {
            bottom: var(--mx-ann-above-media-mobile, 6.75rem);
        }
        #hstk-share-ann-toolbar {
            gap: 0.35rem;
            padding: 0.4rem 0.55rem;
            max-width: 96vw;
        }
    }
    /* ثيم الطالب: شريط الوسائط أوضح وأطول أحياناً */
    .lk-theme-student ~ #hstk-share-ann-layer,
    .st-live-stage #hstk-share-ann-layer {
        --mx-ann-above-media: 5.6rem;
        --mx-ann-above-media-mobile: 7.1rem;
    }
</style>
<div id="hstk-share-ann-layer" class="hidden"
     data-role="{{ $mxAnnRole }}"
     data-post-url="{{ e($mxAnnPostUrl) }}"
     data-poll-url="{{ e($mxAnnPollUrl) }}"
     data-self-key="{{ e($mxAnnSelfKey) }}"
     data-guest-token="">
    <canvas id="hstk-share-ann-canvas" class="absolute inset-0 w-full h-full block"></canvas>
    <div id="hstk-share-ann-toolbar">
        <span class="text-slate-400 text-[11px] px-1 hidden sm:inline">فوق عرض البث</span>
        <button type="button" data-mx-ann-tool="pen" class="mx-ann-tool-btn inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-600/30 text-amber-100 text-xs font-semibold border border-amber-500/50 ring-2 ring-amber-400/60">
            <i class="fas fa-pen"></i> قلم
        </button>
        <button type="button" data-mx-ann-tool="eraser" class="mx-ann-tool-btn inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-600">
            <i class="fas fa-eraser"></i> ممحاة
        </button>
        <button type="button" data-mx-ann-action="clear" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-700 text-slate-200 text-xs font-medium border border-slate-600">
            <i class="fas fa-trash-alt"></i> مسح كتابتي
        </button>
        <button type="button" data-mx-ann-action="close" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-slate-400 text-xs border border-slate-600" title="إيقاف الرسم والتفاعل مع الاجتماع">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<script>
(function () {
    var layer = document.getElementById('hstk-share-ann-layer');
    var canvas = document.getElementById('hstk-share-ann-canvas');
    if (!layer || !canvas) return;

    var role = layer.getAttribute('data-role') || '';
    var postUrl = layer.getAttribute('data-post-url') || '';
    var pollUrl = layer.getAttribute('data-poll-url') || '';
    var selfKey = String(layer.getAttribute('data-self-key') || '');
    var ctx = canvas.getContext('2d');

    var polylines = [];
    var tool = 'pen';
    var drawing = false;
    var drawEnabled = false;
    var currentPts = null;
    var postTimer = null;
    var pollTimer = null;
    var allowed = false;
    var lastRemotePayload = null;
    var lastPollSig = '';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    function isEmitter() {
        return role === 'student_emit' || role === 'classroom_guest_emit' || role === 'host_emit' || role === 'emit_and_poll';
    }
    function isViewer() {
        return role === 'viewer_poll' || role === 'host_emit' || role === 'emit_and_poll' || role === 'student_emit';
    }
    function shouldPoll() {
        return !!pollUrl && isViewer();
    }

    function resizeCanvas() {
        var rect = layer.getBoundingClientRect();
        var dpr = window.devicePixelRatio || 1;
        var w = Math.max(1, Math.floor(rect.width));
        var h = Math.max(1, Math.floor(rect.height));
        canvas.width = Math.floor(w * dpr);
        canvas.height = Math.floor(h * dpr);
        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        paintAll();
    }

    function normToPx(nx, ny) {
        var rect = layer.getBoundingClientRect();
        return [nx * rect.width, ny * rect.height];
    }

    function pxToNorm(x, y) {
        var rect = layer.getBoundingClientRect();
        if (rect.width < 1 || rect.height < 1) return [0, 0];
        return [x / rect.width, y / rect.height];
    }

    function hueFromKey(key) {
        var s = String(key);
        var h = 0;
        for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) % 360;
        return h;
    }

    function strokeLine(line, color, width) {
        if (!line || line.length < 2) return;
        ctx.beginPath();
        var p0 = normToPx(line[0][0], line[0][1]);
        ctx.moveTo(p0[0], p0[1]);
        for (var i = 1; i < line.length; i++) {
            var pi = normToPx(line[i][0], line[i][1]);
            ctx.lineTo(pi[0], pi[1]);
        }
        ctx.strokeStyle = color;
        ctx.lineWidth = width || 3;
        ctx.globalAlpha = 0.92;
        ctx.stroke();
        ctx.globalAlpha = 1;
    }

    function paintAll() {
        var rect = layer.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        if (lastRemotePayload && lastRemotePayload.layers) {
            Object.keys(lastRemotePayload.layers).forEach(function (k) {
                if (selfKey && String(k) === selfKey) return;
                var L = lastRemotePayload.layers[k];
                if (!L || !L.polylines) return;
                var col = L.is_host
                    ? 'rgba(56, 189, 248, 0.95)'
                    : ('hsl(' + hueFromKey(k) + ', 82%, 62%)');
                L.polylines.forEach(function (line) { strokeLine(line, col, 3); });
            });
        }

        if (isEmitter()) {
            polylines.forEach(function (line) {
                strokeLine(line, 'rgba(250, 204, 21, 0.95)', 3);
            });
            if (currentPts && currentPts.length > 1) {
                strokeLine(currentPts, 'rgba(250, 204, 21, 0.95)', 3);
            }
        }
    }

    function distPointSeg(px, py, x1, y1, x2, y2) {
        var dx = x2 - x1, dy = y2 - y1;
        if (dx === 0 && dy === 0) return Math.hypot(px - x1, py - y1);
        var t = ((px - x1) * dx + (py - y1) * dy) / (dx * dx + dy * dy);
        t = Math.max(0, Math.min(1, t));
        var qx = x1 + t * dx, qy = y1 + t * dy;
        return Math.hypot(px - qx, py - qy);
    }

    function distPointPolyline(px, py, line) {
        var m = Infinity;
        for (var i = 1; i < line.length; i++) {
            var a = normToPx(line[i - 1][0], line[i - 1][1]);
            var b = normToPx(line[i][0], line[i][1]);
            var d = distPointSeg(px, py, a[0], a[1], b[0], b[1]);
            if (d < m) m = d;
        }
        return m;
    }

    function eraseAt(px, py, radius) {
        polylines = polylines.filter(function (line) {
            return distPointPolyline(px, py, line) > radius;
        });
    }

    function schedulePost(immediate) {
        if (!isEmitter() || !postUrl || !allowed) return;
        if (postTimer) clearTimeout(postTimer);
        var delay = immediate ? 40 : 120;
        postTimer = setTimeout(function () {
            postTimer = null;
            var body = { polylines: polylines };
            var headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (role === 'classroom_guest_emit') {
                body.token = layer.getAttribute('data-guest-token') || '';
            }
            fetch(postUrl, { method: 'POST', headers: headers, body: JSON.stringify(body), keepalive: true }).catch(function () {});
        }, delay);
    }

    function setDrawActive(on) {
        drawEnabled = !!on;
        if (!isEmitter()) return;
        if (drawEnabled) {
            layer.classList.add('hstk-share-ann-drawing');
            layer.classList.remove('hidden');
        } else {
            layer.classList.remove('hstk-share-ann-drawing');
            drawing = false;
            currentPts = null;
            if (!shouldPoll()) layer.classList.add('hidden');
        }
    }

    function updateToolbarTools() {
        var btns = layer.querySelectorAll('.mx-ann-tool-btn');
        btns.forEach(function (b) {
            var t = b.getAttribute('data-mx-ann-tool');
            var on = t === tool;
            b.classList.toggle('ring-2', on);
            b.classList.toggle('ring-amber-400/60', on);
            b.classList.toggle('bg-amber-600/30', on && t === 'pen');
            b.classList.toggle('border-amber-500/50', on && t === 'pen');
            b.classList.toggle('bg-slate-700', !on || t === 'eraser');
        });
    }

    function bindEmitter() {
        layer.querySelectorAll('[data-mx-ann-tool]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                tool = btn.getAttribute('data-mx-ann-tool') || 'pen';
                updateToolbarTools();
            });
        });
        layer.querySelector('[data-mx-ann-action="clear"]').addEventListener('click', function () {
            polylines = [];
            paintAll();
            schedulePost(true);
        });
        layer.querySelector('[data-mx-ann-action="close"]').addEventListener('click', function () {
            setDrawActive(false);
        });

        function pos(ev) {
            var rect = layer.getBoundingClientRect();
            var cx = ev.clientX, cy = ev.clientY;
            if (ev.touches && ev.touches[0]) {
                cx = ev.touches[0].clientX;
                cy = ev.touches[0].clientY;
            }
            return [cx - rect.left, cy - rect.top];
        }

        canvas.addEventListener('pointerdown', function (ev) {
            if (!drawEnabled || !allowed) return;
            ev.preventDefault();
            canvas.setPointerCapture(ev.pointerId);
            drawing = true;
            var p = pos(ev);
            if (tool === 'pen') {
                currentPts = [pxToNorm(p[0], p[1])];
            } else {
                eraseAt(p[0], p[1], 14);
                paintAll();
                schedulePost();
            }
        });
        canvas.addEventListener('pointermove', function (ev) {
            if (!drawEnabled || !allowed || !drawing) return;
            ev.preventDefault();
            var p = pos(ev);
            if (tool === 'pen' && currentPts) {
                currentPts.push(pxToNorm(p[0], p[1]));
                paintAll();
                if (currentPts.length % 4 === 0) schedulePost();
            } else if (tool === 'eraser') {
                eraseAt(p[0], p[1], 14);
                paintAll();
                schedulePost();
            }
        });
        canvas.addEventListener('pointerup', function (ev) {
            if (!drawing) return;
            drawing = false;
            try { canvas.releasePointerCapture(ev.pointerId); } catch (e) {}
            if (tool === 'pen' && currentPts && currentPts.length > 1) {
                polylines.push(currentPts);
                if (polylines.length > 120) polylines.shift();
            }
            currentPts = null;
            paintAll();
            schedulePost(true);
        });
        updateToolbarTools();
    }

    function startPolling() {
        if (!shouldPoll()) return;
        function poll() {
            fetch(pollUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data || !data.layers) return;
                    var sig = JSON.stringify(data.layers);
                    if (sig === lastPollSig) return;
                    lastPollSig = sig;
                    lastRemotePayload = data;
                    paintAll();
                })
                .catch(function () {});
        }
        poll();
        pollTimer = setInterval(poll, 450);
        window.addEventListener('beforeunload', function () {
            if (pollTimer) clearInterval(pollTimer);
        });
    }

    window.__mxShareAnnSetAllowed = function (on) {
        allowed = !!on;
        if (!allowed) {
            setDrawActive(false);
            if (isEmitter() && !shouldPoll()) layer.classList.add('hidden');
            if (isEmitter()) {
                polylines = [];
                paintAll();
                schedulePost(true);
            }
            return;
        }
        if (isEmitter()) {
            layer.classList.add('hidden');
            setDrawActive(false);
        }
        if (shouldPoll()) layer.classList.remove('hidden');
    };

    window.__mxShareAnnOpenToolbar = function () {
        if (!allowed || !isEmitter()) return;
        layer.classList.remove('hidden');
        var toolbar = layer.querySelector('#hstk-share-ann-toolbar');
        if (toolbar) toolbar.style.display = '';
        setDrawActive(true);
        resizeCanvas();
    };

    window.__mxShareAnnSetGuestToken = function (tok) {
        layer.setAttribute('data-guest-token', tok || '');
    };

    if (isEmitter()) {
        bindEmitter();
        if (role === 'host_emit') {
            // المعلم: أدوات الرسم جاهزة فوق منطقة الفيديو فقط (فوق شريط الوسائط)
            allowed = true;
            layer.classList.remove('hidden');
            var hostTb = layer.querySelector('#hstk-share-ann-toolbar');
            if (hostTb) hostTb.style.display = '';
            setDrawActive(true);
        } else if (role === 'emit_and_poll') {
            // الطالب: يرى رسوم المعلم؛ شريط القلم يظهر فقط عند ضغط «رسم فوق العرض»
            allowed = true;
            layer.classList.remove('hidden');
            var studentTb = layer.querySelector('#hstk-share-ann-toolbar');
            if (studentTb) studentTb.style.display = 'none';
            setDrawActive(false);
        }
    } else if (role === 'viewer_poll') {
        var tb = layer.querySelector('#hstk-share-ann-toolbar');
        if (tb) tb.style.display = 'none';
        layer.classList.remove('hidden');
    }

    if (shouldPoll()) {
        if (role === 'viewer_poll' || role === 'host_emit' || role === 'emit_and_poll' || role === 'student_emit') {
            layer.classList.remove('hidden');
        }
        startPolling();
    }

    var ro = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(function () { resizeCanvas(); }) : null;
    if (ro) ro.observe(layer);
    window.addEventListener('resize', function () { resizeCanvas(); });
    requestAnimationFrame(function () { resizeCanvas(); });
})();
</script>
