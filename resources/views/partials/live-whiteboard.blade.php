@php $isInstructor = ($whiteboardRole ?? 'student') === 'instructor'; @endphp
<style>
    /* ─── Floating Tools Dropdown ─────────────────────────── */
    #mx-tools-fab {
        position: fixed;
        left: 16px;
        bottom: 16px;
        z-index: 200;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    #mx-tools-menu {
        display: none;
        flex-direction: column;
        gap: 4px;
        background: rgba(15,23,42,0.97);
        border: 1px solid rgba(148,163,184,0.2);
        border-radius: 14px;
        padding: 8px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        backdrop-filter: blur(8px);
        min-width: 190px;
    }
    #mx-tools-menu.is-open { display: flex; }
    .mx-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 14px;
        border-radius: 10px;
        background: rgba(30,41,59,0.7);
        color: #e2e8f0;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid rgba(148,163,184,0.12);
        transition: background 0.15s, border-color 0.15s;
        white-space: nowrap;
    }
    .mx-menu-item:hover { background: rgba(51,65,85,0.9); border-color: rgba(148,163,184,0.3); }
    .mx-menu-item .mx-menu-icon {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px;
    }
    #mx-fab-main {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0ea5e9, #6366f1);
        color: white;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        box-shadow: 0 4px 20px rgba(99,102,241,0.4);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    #mx-fab-main:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(99,102,241,0.5); }
    #mx-fab-chevron { transition: transform 0.2s; font-size: 10px; }
    #mx-fab-main.is-open #mx-fab-chevron { transform: rotate(180deg); }

    /* ─── Annotation Overlay (رسم فوق الفيديو) ────────────── */
    #mx-annotation-overlay {
        position: fixed;
        inset: 72px 0 0 0; /* below header */
        z-index: 190;
        display: none;
    }
    #mx-annotation-overlay.is-open { display: block; }
    #mx-annotation-canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        cursor: crosshair;
    }
    #mx-annotation-toolbar {
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        background: rgba(15,23,42,0.95);
        border: 1px solid rgba(148,163,184,0.25);
        border-radius: 50px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.5);
        backdrop-filter: blur(8px);
        z-index: 1;
        flex-wrap: nowrap;
    }
    .mx-ann-btn {
        width: 34px; height: 34px;
        border-radius: 8px;
        border: 1px solid rgba(148,163,184,0.2);
        background: rgba(30,41,59,0.9);
        color: #cbd5e1;
        font-size: 14px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.15s, color 0.15s;
    }
    .mx-ann-btn:hover, .mx-ann-btn.active {
        background: rgba(14,165,233,0.25);
        border-color: rgba(14,165,233,0.5);
        color: #38bdf8;
    }
    .mx-ann-sep { width: 1px; height: 22px; background: rgba(148,163,184,0.2); margin: 0 2px; }
    .mx-ann-color { width: 28px; height: 28px; border-radius: 6px; cursor: pointer; border: 2px solid rgba(148,163,184,0.3); }
    #mx-ann-size { width: 80px; accent-color: #0ea5e9; }
    #mx-ann-close-btn {
        padding: 0 14px;
        height: 34px;
        background: rgba(220,38,38,0.2);
        border: 1px solid rgba(220,38,38,0.4);
        color: #f87171;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }
    #mx-ann-close-btn:hover { background: rgba(220,38,38,0.4); }

    /* ─── Full Whiteboard Panel (السبورة) ─────────────────── */
    #mx-whiteboard-panel {
        position: fixed;
        left: 12px;
        bottom: 74px;
        width: min(96vw, 1060px);
        height: min(78vh, 700px);
        background: rgba(10,17,32,0.98);
        border: 1px solid rgba(148,163,184,0.18);
        border-radius: 18px;
        box-shadow: 0 32px 80px rgba(0,0,0,0.7);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 195;
        backdrop-filter: blur(10px);
    }
    #mx-whiteboard-panel.is-open { display: flex; }

    /* Panel header */
    .mx-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-bottom: 1px solid rgba(148,163,184,0.15);
        background: rgba(15,23,42,0.95);
        flex-shrink: 0;
    }
    .mx-panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #e2e8f0;
        font-size: 13px;
        font-weight: 600;
    }
    .mx-panel-title i { color: #38bdf8; }

    /* Toolbar */
    .mx-wb-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        padding: 8px 12px;
        border-bottom: 1px solid rgba(148,163,184,0.12);
        background: rgba(15,23,42,0.9);
        flex-shrink: 0;
        align-items: center;
    }
    .mx-tool-group {
        display: flex;
        align-items: center;
        gap: 3px;
        padding: 0 6px;
        border-right: 1px solid rgba(148,163,184,0.15);
    }
    .mx-tool-group:last-child { border-right: none; }
    .mx-tool-btn {
        width: 34px; height: 34px;
        border-radius: 8px;
        border: 1px solid rgba(148,163,184,0.15);
        background: rgba(30,41,59,0.8);
        color: #94a3b8;
        font-size: 13px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.15s;
        position: relative;
    }
    .mx-tool-btn:hover { background: rgba(51,65,85,0.9); color: #e2e8f0; border-color: rgba(148,163,184,0.3); }
    .mx-tool-btn.is-active { background: rgba(14,165,233,0.25); border-color: rgba(56,189,248,0.5); color: #38bdf8; }
    .mx-tool-btn[title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: -28px;
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: #e2e8f0;
        font-size: 10px;
        padding: 3px 7px;
        border-radius: 5px;
        white-space: nowrap;
        z-index: 10;
        pointer-events: none;
    }
    .mx-color-input {
        width: 30px; height: 30px;
        border-radius: 7px;
        border: 2px solid rgba(148,163,184,0.25);
        cursor: pointer;
        padding: 2px;
        background: transparent;
    }
    .mx-size-wrap {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #94a3b8;
        font-size: 11px;
    }
    .mx-size-wrap input[type="range"] {
        width: 75px;
        accent-color: #0ea5e9;
    }
    .mx-bg-switcher {
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .mx-bg-btn {
        width: 28px; height: 28px;
        border-radius: 6px;
        border: 2px solid rgba(148,163,184,0.2);
        cursor: pointer;
        transition: border-color 0.15s;
    }
    .mx-bg-btn:hover { border-color: rgba(148,163,184,0.5); }
    .mx-bg-btn.is-active { border-color: #38bdf8; }

    /* Canvas area */
    #mx-wb-canvas-wrap {
        flex: 1;
        min-height: 200px;
        position: relative;
        background: #ffffff;
        overflow: hidden;
    }
    #mx-wb-canvas-wrap canvas { position: absolute; inset: 0; width: 100%; height: 100%; }

    /* Resize handle */
    #mx-wb-resize {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        cursor: ns-resize;
        color: rgba(148,163,184,0.4);
        font-size: 12px;
        user-select: none;
    }

    /* Text input popup */
    #mx-text-popup {
        display: none;
        position: absolute;
        background: rgba(15,23,42,0.97);
        border: 1px solid rgba(56,189,248,0.4);
        border-radius: 10px;
        padding: 10px;
        z-index: 300;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        min-width: 220px;
    }
    #mx-text-popup input {
        width: 100%;
        background: rgba(30,41,59,0.9);
        border: 1px solid rgba(148,163,184,0.25);
        border-radius: 7px;
        color: #e2e8f0;
        padding: 7px 10px;
        font-size: 14px;
        outline: none;
    }
    #mx-text-popup input:focus { border-color: rgba(56,189,248,0.5); }
    #mx-text-popup .mx-text-btns {
        display: flex;
        gap: 6px;
        margin-top: 7px;
    }
    #mx-text-popup .mx-text-btns button {
        flex: 1;
        padding: 5px;
        border-radius: 7px;
        font-size: 12px;
        cursor: pointer;
        border: none;
    }
    #mx-text-add { background: rgba(14,165,233,0.3); color: #38bdf8; }
    #mx-text-cancel { background: rgba(71,85,105,0.4); color: #94a3b8; }

    /* Grid / Lined background */
    .mx-bg-grid { background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Cpath d='M 40 0 L 0 0 0 40' fill='none' stroke='%23e2e8f0' stroke-width='0.7'/%3E%3C/svg%3E"); }
    .mx-bg-lined { background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100%25' height='40'%3E%3Cline x1='0' y1='39' x2='100%25' y2='39' stroke='%23bfdbfe' stroke-width='1'/%3E%3C/svg%3E"); }
    .mx-bg-dark { background: #1e293b; }
    .mx-bg-green { background: #166534; }
    .mx-bg-cream { background: #fffbeb; }
    .mx-bg-black { background: #0f172a; }

    .mx-wb-toolbar-wrap {
        max-height: 168px;
        overflow-y: auto;
        overflow-x: hidden;
        flex-shrink: 0;
        border-bottom: 1px solid rgba(148,163,184,0.12);
        background: rgba(15,23,42,0.92);
    }
    .mx-wb-toolbar-wrap::-webkit-scrollbar { height: 6px; width: 6px; }
    .mx-wb-toolbar-wrap::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 4px; }

    .mx-tool-btn-sm { width: 30px !important; height: 30px !important; font-size: 11px !important; }
    .mx-wb-select {
        background: rgba(30,41,59,0.95);
        border: 1px solid rgba(148,163,184,0.2);
        border-radius: 8px;
        color: #cbd5e1;
        font-size: 11px;
        padding: 5px 8px;
        max-width: 120px;
        cursor: pointer;
    }
    .mx-color-presets {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        max-width: 140px;
    }
    .mx-cp {
        width: 18px; height: 18px;
        border-radius: 4px;
        border: 2px solid rgba(148,163,184,0.25);
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
    }
    .mx-cp:hover { transform: scale(1.08); border-color: #38bdf8; }
    .mx-sym-btn {
        font-size: 13px;
        font-weight: 700;
        min-width: 28px;
    }
    .mx-row-label {
        font-size: 9px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        width: 100%;
        padding: 2px 6px 0;
    }
</style>

{{-- ═══════════════ FLOATING FAB BUTTON ═══════════════ --}}
<div id="mx-tools-fab">
    <div id="mx-tools-menu">
        <button class="mx-menu-item" id="mx-btn-annotate">
            <span class="mx-menu-icon" style="background:rgba(234,179,8,0.15); color:#fbbf24;">
                <i class="fas fa-pen-nib"></i>
            </span>
            <span>رسم على الفيديو</span>
        </button>
        <button class="mx-menu-item" id="mx-btn-board">
            <span class="mx-menu-icon" style="background:rgba(14,165,233,0.15); color:#38bdf8;">
                <i class="fas fa-chalkboard"></i>
            </span>
            <span>السبورة التفاعلية</span>
        </button>
    </div>
    <button id="mx-fab-main" type="button">
        <i class="fas fa-paintbrush"></i>
        <span>سبورة ورسم</span>
        <i class="fas fa-chevron-up" id="mx-fab-chevron"></i>
    </button>
</div>

{{-- ═══════════════ ANNOTATION OVERLAY (رسم فوق الفيديو) ═══════════════ --}}
<div id="mx-annotation-overlay">
    <canvas id="mx-annotation-canvas"></canvas>
    <div id="mx-annotation-toolbar">
        <button class="mx-ann-btn active" id="mx-ann-pen" title="قلم"><i class="fas fa-pen"></i></button>
        <button class="mx-ann-btn" id="mx-ann-highlighter" title="تمييز شفاف"><i class="fas fa-highlighter"></i></button>
        <button class="mx-ann-btn" id="mx-ann-line" title="خط"><i class="fas fa-minus"></i></button>
        <button class="mx-ann-btn" id="mx-ann-line-dash" title="خط متقطع"><i class="fas fa-grip-lines"></i></button>
        <button class="mx-ann-btn" id="mx-ann-arrow" title="سهم"><i class="fas fa-arrow-right"></i></button>
        <button class="mx-ann-btn" id="mx-ann-rect" title="مستطيل"><i class="far fa-square"></i></button>
        <button class="mx-ann-btn" id="mx-ann-circle" title="دائرة"><i class="far fa-circle"></i></button>
        <button class="mx-ann-btn" id="mx-ann-eraser" title="ممحاة"><i class="fas fa-eraser"></i></button>
        <div class="mx-ann-sep"></div>
        <span class="mx-ann-presets" style="display:flex;gap:3px;flex-wrap:wrap;max-width:120px;">
            <button type="button" class="mx-ann-btn" style="width:22px;height:22px;padding:0;background:#ef4444" data-ann-c="#ef4444" title="أحمر"></button>
            <button type="button" class="mx-ann-btn" style="width:22px;height:22px;padding:0;background:#22c55e" data-ann-c="#22c55e" title="أخضر"></button>
            <button type="button" class="mx-ann-btn" style="width:22px;height:22px;padding:0;background:#3b82f6" data-ann-c="#3b82f6" title="أزرق"></button>
            <button type="button" class="mx-ann-btn" style="width:22px;height:22px;padding:0;background:#eab308" data-ann-c="#eab308" title="أصفر"></button>
            <button type="button" class="mx-ann-btn" style="width:22px;height:22px;padding:0;background:#fff;border:1px solid #64748b" data-ann-c="#ffffff" title="أبيض"></button>
            <button type="button" class="mx-ann-btn" style="width:22px;height:22px;padding:0;background:#a855f7" data-ann-c="#a855f7" title="بنفسجي"></button>
        </span>
        <input type="color" class="mx-ann-color" id="mx-ann-color" value="#ef4444" title="اللون">
        <input type="range" id="mx-ann-size" min="1" max="28" value="4" title="الحجم">
        <div class="mx-ann-sep"></div>
        <button class="mx-ann-btn" id="mx-ann-undo" title="تراجع"><i class="fas fa-rotate-left"></i></button>
        <button class="mx-ann-btn" id="mx-ann-clear" title="مسح الكل"><i class="fas fa-trash"></i></button>
        <div class="mx-ann-sep"></div>
        <button id="mx-ann-close-btn"><i class="fas fa-xmark"></i> إغلاق</button>
    </div>
</div>

{{-- ═══════════════ FULL WHITEBOARD PANEL ═══════════════ --}}
<div id="mx-whiteboard-panel">
    <div class="mx-panel-header">
        <div class="mx-panel-title">
            <i class="fas fa-chalkboard"></i>
            <span>السبورة التفاعلية</span>
        </div>
        <div style="display:flex; gap:6px; align-items:center;">
            <div id="mx-wb-resize" title="تغيير الحجم"><i class="fas fa-arrows-up-down"></i></div>
            <button id="mx-wb-minimize" class="mx-tool-btn" title="تصغير" style="width:28px;height:28px;font-size:11px;"><i class="fas fa-minus"></i></button>
            <button id="mx-wb-close" class="mx-tool-btn" title="إغلاق" style="width:28px;height:28px;font-size:11px;color:#f87171;"><i class="fas fa-xmark"></i></button>
        </div>
    </div>

    <div class="mx-wb-toolbar-wrap">
        <div class="mx-row-label">الرسم والتحديد</div>
        <div class="mx-wb-toolbar">
            <div class="mx-tool-group">
                <button class="mx-tool-btn is-active" data-tool="select" title="تحديد ونقل"><i class="fas fa-mouse-pointer"></i></button>
                <button class="mx-tool-btn" data-tool="draw" title="قلم حر"><i class="fas fa-pen"></i></button>
                <button class="mx-tool-btn" data-tool="highlight" title="تمييز شفاف (ماركر)"><i class="fas fa-highlighter"></i></button>
                <button class="mx-tool-btn" data-tool="eraser" title="ممحاة"><i class="fas fa-eraser"></i></button>
            </div>
            <div class="mx-tool-group">
                <button class="mx-tool-btn" data-tool="line" title="خط"><i class="fas fa-minus"></i></button>
                <button class="mx-tool-btn" data-tool="arrow" title="سهم"><i class="fas fa-arrow-right"></i></button>
                <button class="mx-tool-btn" data-tool="darrow" title="سهم مزدوج"><i class="fas fa-arrows-left-right"></i></button>
                <button class="mx-tool-btn" data-tool="rect" title="مستطيل"><i class="far fa-square"></i></button>
                <button class="mx-tool-btn" data-tool="roundrect" title="مستطيل مستدير"><i class="fas fa-square"></i></button>
                <button class="mx-tool-btn" data-tool="circle" title="بيضاوي"><i class="far fa-circle"></i></button>
                <button class="mx-tool-btn" data-tool="triangle" title="مثلث"><i class="fas fa-play fa-rotate-270"></i></button>
                <button class="mx-tool-btn" data-tool="diamond" title="معين"><i class="fas fa-gem"></i></button>
                <button class="mx-tool-btn" data-tool="hex" title="سداسي"><span style="font-size:14px;font-weight:800;line-height:1">⬡</span></button>
                <button class="mx-tool-btn" data-tool="star" title="نجمة"><i class="fas fa-star"></i></button>
            </div>
            <div class="mx-tool-group">
                <button class="mx-tool-btn" data-tool="text" title="نص حر"><i class="fas fa-font"></i></button>
                <button class="mx-tool-btn mx-sym-btn" data-tool="stamp-check" title="صح ✓">✓</button>
                <button class="mx-tool-btn mx-sym-btn" data-tool="stamp-x" title="خطأ ✗">✗</button>
                <button class="mx-tool-btn mx-sym-btn" data-tool="stamp-q" title="سؤال ؟">?</button>
                <button class="mx-tool-btn mx-sym-btn" data-tool="stamp-bang" title="تنبيه !">!</button>
            </div>
        </div>
        <div class="mx-row-label">المظهر — الخط — الطبقات</div>
        <div class="mx-wb-toolbar">
            <div class="mx-tool-group">
                <span style="font-size:10px;color:#64748b;white-space:nowrap;">ألوان جاهزة</span>
                <div class="mx-color-presets" id="mx-stroke-presets"></div>
            </div>
            <div class="mx-tool-group">
                <input type="color" class="mx-color-input" id="mx-stroke-color" value="#0f172a" title="لون الحد / النص">
                <input type="color" class="mx-color-input" id="mx-fill-color" value="#ffffff" title="تعبئة">
                <label class="mx-size-wrap" title="بدون تعبئة" style="cursor:pointer;white-space:nowrap;">
                    <input type="checkbox" id="mx-fill-transparent" style="accent-color:#0ea5e9;">
                    <span>شفاف</span>
                </label>
            </div>
            <div class="mx-tool-group">
                <div class="mx-size-wrap">
                    <span>سمك</span>
                    <input type="range" id="mx-stroke-width" min="1" max="32" value="3">
                </div>
                <div class="mx-size-wrap">
                    <span>شفافية</span>
                    <input type="range" id="mx-opacity" min="15" max="100" value="100" title="شفافية العنصر المحدد أو الرسم القادم">
                </div>
                <div class="mx-size-wrap">
                    <span>خط النص</span>
                    <input type="range" id="mx-text-size" min="14" max="72" value="22" title="حجم خط النص الجديد">
                </div>
            </div>
            <div class="mx-tool-group">
                <label style="font-size:10px;color:#64748b;">نمط الخط</label>
                <select id="mx-line-dash" class="mx-wb-select" title="متقطع للأشكال الجديدة">
                    <option value="">متصل</option>
                    <option value="8,6">متقطع</option>
                    <option value="2,4">نقاط</option>
                    <option value="12,6,2,6">شرطات طويلة</option>
                </select>
            </div>
            <div class="mx-tool-group">
                <span style="font-size:10px;color:#64748b;">رموز</span>
                <select id="mx-math-insert" class="mx-wb-select" title="إدراج رمز عند النقر على اللوحة">
                    <option value="">+ رياضيات</option>
                    <option value="×">×</option>
                    <option value="÷">÷</option>
                    <option value="±">±</option>
                    <option value="√">√</option>
                    <option value="π">π</option>
                    <option value="θ">θ</option>
                    <option value="Δ">Δ</option>
                    <option value="°">°</option>
                    <option value="²">²</option>
                    <option value="³">³</option>
                    <option value="→">→</option>
                    <option value="←">←</option>
                    <option value="↑">↑</option>
                    <option value="↓">↓</option>
                    <option value="∞">∞</option>
                    <option value="≠">≠</option>
                    <option value="≤">≤</option>
                    <option value="≥">≥</option>
                    <option value="≈">≈</option>
                    <option value="∑">∑</option>
                    <option value="∫">∫</option>
                </select>
            </div>
            <div class="mx-tool-group">
                <button class="mx-tool-btn" id="mx-dup" title="نسخ العنصر المحدد"><i class="fas fa-copy"></i></button>
                <button class="mx-tool-btn" id="mx-front" title="إحضار للأمام"><i class="fas fa-arrow-up"></i></button>
                <button class="mx-tool-btn" id="mx-back" title="إرسال للخلف"><i class="fas fa-arrow-down"></i></button>
            </div>
            <div class="mx-tool-group mx-bg-switcher">
                <span style="font-size:10px;color:#64748b;">خلفية</span>
                <button class="mx-bg-btn is-active" data-bg="white" style="background:#fff;" title="أبيض"></button>
                <button class="mx-bg-btn" data-bg="cream" style="background:#fffbeb;" title="كريمي"></button>
                <button class="mx-bg-btn" data-bg="grid" style="background:linear-gradient(#e2e8f0 1px,transparent 1px),linear-gradient(90deg,#e2e8f0 1px,transparent 1px),#fff;background-size:20px 20px;" title="شبكة"></button>
                <button class="mx-bg-btn" data-bg="lined" style="background:repeating-linear-gradient(#fff,#fff 34px,#bfdbfe 35px,#bfdbfe 35px);" title="مسطرة"></button>
                <button class="mx-bg-btn" data-bg="dark" style="background:#1e293b;" title="داكن"></button>
                <button class="mx-bg-btn" data-bg="black" style="background:#0f172a;" title="أسود"></button>
                <button class="mx-bg-btn" data-bg="green" style="background:#166534;" title="سبورة خضراء"></button>
            </div>
            <div class="mx-tool-group">
                <button class="mx-tool-btn" id="mx-undo" title="تراجع"><i class="fas fa-rotate-left"></i></button>
                <button class="mx-tool-btn" id="mx-redo" title="إعادة"><i class="fas fa-rotate-right"></i></button>
                <button class="mx-tool-btn" id="mx-clear" title="مسح الكل" style="color:#f87171;"><i class="fas fa-trash-alt"></i></button>
                <button class="mx-tool-btn" id="mx-download" title="PNG"><i class="fas fa-download"></i></button>
            </div>
        </div>
    </div>

    <div id="mx-wb-canvas-wrap">
        <canvas id="mx-whiteboard-canvas"></canvas>
        {{-- Text popup --}}
        <div id="mx-text-popup">
            <input type="text" id="mx-text-input" placeholder="اكتب النص هنا..." autofocus>
            <div class="mx-text-btns">
                <button id="mx-text-add"><i class="fas fa-check"></i> إضافة</button>
                <button id="mx-text-cancel"><i class="fas fa-xmark"></i> إلغاء</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
(function () {
    /* ══════════════════════════════════════════════════════════
       FAB DROPDOWN
    ══════════════════════════════════════════════════════════ */
    var fabMain   = document.getElementById('mx-fab-main');
    var toolsMenu = document.getElementById('mx-tools-menu');
    var btnAnnotate = document.getElementById('mx-btn-annotate');
    var btnBoard    = document.getElementById('mx-btn-board');

    fabMain.addEventListener('click', function(e) {
        e.stopPropagation();
        toolsMenu.classList.toggle('is-open');
        fabMain.classList.toggle('is-open');
    });
    document.addEventListener('click', function() {
        toolsMenu.classList.remove('is-open');
        fabMain.classList.remove('is-open');
    });

    /* ══════════════════════════════════════════════════════════
       ANNOTATION OVERLAY  (رسم على الفيديو)
    ══════════════════════════════════════════════════════════ */
    var annOverlay   = document.getElementById('mx-annotation-overlay');
    var annCanvas    = document.getElementById('mx-annotation-canvas');
    var annCtx       = annCanvas.getContext('2d');
    var annTool      = 'pen';
    var annDrawing   = false;
    var annLastX     = 0, annLastY = 0;
    var annStartX    = 0, annStartY = 0;
    var annSnapshot  = null;
    var annHistory   = [];

    function resizeAnnCanvas() {
        var rect = annOverlay.getBoundingClientRect();
        var imageData = annCtx.getImageData(0, 0, annCanvas.width, annCanvas.height);
        annCanvas.width  = rect.width;
        annCanvas.height = rect.height;
        annCtx.putImageData(imageData, 0, 0);
    }

    function annColor() { return document.getElementById('mx-ann-color').value; }
    function annSize()  { return parseInt(document.getElementById('mx-ann-size').value, 10); }

    function annSaveHistory() {
        annHistory.push(annCtx.getImageData(0, 0, annCanvas.width, annCanvas.height));
        if (annHistory.length > 30) annHistory.shift();
    }

    annCanvas.addEventListener('mousedown', function(e) {
        if (annTool === 'eraser' || annTool === 'pen' || annTool === 'highlighter') {
            annDrawing = true;
            annLastX = e.offsetX; annLastY = e.offsetY;
            annCtx.beginPath();
            annCtx.moveTo(annLastX, annLastY);
            annSaveHistory();
        } else {
            annDrawing = true;
            annStartX = e.offsetX; annStartY = e.offsetY;
            annSnapshot = annCtx.getImageData(0, 0, annCanvas.width, annCanvas.height);
        }
    });

    annCanvas.addEventListener('mousemove', function(e) {
        if (!annDrawing) return;
        var x = e.offsetX, y = e.offsetY;
        if (annTool === 'pen') {
            annCtx.setLineDash([]);
            annCtx.globalAlpha = 1;
            annCtx.strokeStyle = annColor();
            annCtx.lineWidth   = annSize();
            annCtx.lineCap     = 'round';
            annCtx.lineJoin    = 'round';
            annCtx.globalCompositeOperation = 'source-over';
            annCtx.lineTo(x, y);
            annCtx.stroke();
            annLastX = x; annLastY = y;
        } else if (annTool === 'highlighter') {
            annCtx.save();
            annCtx.setLineDash([]);
            annCtx.globalAlpha = 0.38;
            annCtx.strokeStyle = annColor();
            annCtx.lineWidth   = Math.max(10, annSize() * 3);
            annCtx.lineCap     = 'round';
            annCtx.lineJoin    = 'round';
            annCtx.globalCompositeOperation = 'source-over';
            annCtx.lineTo(x, y);
            annCtx.stroke();
            annCtx.restore();
            annLastX = x; annLastY = y;
        } else if (annTool === 'eraser') {
            annCtx.setLineDash([]);
            annCtx.globalAlpha = 1;
            annCtx.globalCompositeOperation = 'destination-out';
            annCtx.lineWidth = annSize() * 4;
            annCtx.lineCap   = 'round';
            annCtx.lineTo(x, y);
            annCtx.stroke();
            annLastX = x; annLastY = y;
        } else {
            annCtx.putImageData(annSnapshot, 0, 0);
            annCtx.strokeStyle = annColor();
            annCtx.lineWidth   = annSize();
            annCtx.globalAlpha = 1;
            annCtx.globalCompositeOperation = 'source-over';
            if (annTool === 'line' || annTool === 'lineDash') {
                annCtx.setLineDash(annTool === 'lineDash' ? [10, 7] : []);
                annCtx.beginPath();
                annCtx.moveTo(annStartX, annStartY);
                annCtx.lineTo(x, y);
                annCtx.stroke();
                annCtx.setLineDash([]);
            } else if (annTool === 'arrow') {
                drawAnnArrow(annStartX, annStartY, x, y);
            } else if (annTool === 'rect') {
                annCtx.beginPath();
                annCtx.strokeRect(annStartX, annStartY, x - annStartX, y - annStartY);
            } else if (annTool === 'circle') {
                var rx = Math.abs(x - annStartX) / 2, ry = Math.abs(y - annStartY) / 2;
                var cx = Math.min(annStartX, x) + rx, cy = Math.min(annStartY, y) + ry;
                annCtx.beginPath();
                annCtx.ellipse(cx, cy, rx, ry, 0, 0, 2 * Math.PI);
                annCtx.stroke();
            }
        }
    });

    annCanvas.addEventListener('mouseup', function() {
        if (annDrawing) { annDrawing = false; annCtx.beginPath(); annSaveHistory(); }
    });
    annCanvas.addEventListener('mouseleave', function() { annDrawing = false; annCtx.beginPath(); });

    function drawAnnArrow(x1, y1, x2, y2) {
        var headlen = 16 + annSize();
        var angle = Math.atan2(y2 - y1, x2 - x1);
        annCtx.beginPath();
        annCtx.moveTo(x1, y1);
        annCtx.lineTo(x2, y2);
        annCtx.stroke();
        annCtx.beginPath();
        annCtx.moveTo(x2, y2);
        annCtx.lineTo(x2 - headlen * Math.cos(angle - Math.PI/6), y2 - headlen * Math.sin(angle - Math.PI/6));
        annCtx.moveTo(x2, y2);
        annCtx.lineTo(x2 - headlen * Math.cos(angle + Math.PI/6), y2 - headlen * Math.sin(angle + Math.PI/6));
        annCtx.stroke();
    }

    function setAnnTool(tool) {
        annTool = tool;
        annCtx.globalCompositeOperation = 'source-over';
        annCtx.globalAlpha = 1;
        document.querySelectorAll('#mx-annotation-toolbar .mx-ann-btn[id^="mx-ann-"]').forEach(function(b) {
            b.classList.remove('active');
        });
        var btnSuffix = tool === 'lineDash' ? 'line-dash' : tool;
        var active = document.getElementById('mx-ann-' + btnSuffix);
        if (active) active.classList.add('active');
        annCanvas.style.cursor = (tool === 'eraser') ? 'cell' : 'crosshair';
    }

    ['pen','highlighter','line','line-dash','arrow','rect','circle','eraser'].forEach(function(id) {
        var t = id === 'line-dash' ? 'lineDash' : id;
        var btn = document.getElementById('mx-ann-' + id);
        if (btn) btn.addEventListener('click', function() { setAnnTool(t); });
    });
    document.querySelectorAll('#mx-annotation-toolbar [data-ann-c]').forEach(function(b) {
        b.addEventListener('click', function(ev) {
            ev.preventDefault();
            document.getElementById('mx-ann-color').value = b.getAttribute('data-ann-c');
        });
    });

    document.getElementById('mx-ann-undo').addEventListener('click', function() {
        if (annHistory.length > 1) {
            annHistory.pop();
            annCtx.putImageData(annHistory[annHistory.length - 1], 0, 0);
        }
    });
    document.getElementById('mx-ann-clear').addEventListener('click', function() {
        annCtx.clearRect(0, 0, annCanvas.width, annCanvas.height);
        annHistory = [];
    });
    document.getElementById('mx-ann-close-btn').addEventListener('click', function() {
        annOverlay.classList.remove('is-open');
    });

    btnAnnotate && btnAnnotate.addEventListener('click', function() {
        toolsMenu.classList.remove('is-open');
        fabMain.classList.remove('is-open');
        annOverlay.classList.toggle('is-open');
        if (annOverlay.classList.contains('is-open')) {
            resizeAnnCanvas();
            if (!annHistory.length) annSaveHistory();
        }
    });

    /* ══════════════════════════════════════════════════════════
       FULL WHITEBOARD PANEL (fabric.js)
    ══════════════════════════════════════════════════════════ */
    var wbPanel   = document.getElementById('mx-whiteboard-panel');
    var wbClose   = document.getElementById('mx-wb-close');
    var wbCanvasEl = document.getElementById('mx-whiteboard-canvas');
    var wbWrap    = document.getElementById('mx-wb-canvas-wrap');
    if (!wbCanvasEl || !wbWrap || typeof fabric === 'undefined') return;

    var wbCanvas  = new fabric.Canvas(wbCanvasEl, { selection: true, preserveObjectStacking: true });
    var WB_SHAPE_TOOLS = ['line','arrow','darrow','rect','roundrect','circle','triangle','diamond','hex','star'];
    var wbTool    = 'select';
    var wbDrawObj = null;
    var wbStart   = null;
    var wbUndo    = [];
    var wbRedo    = [];
    var wbRestoring = false;
    var wbBg      = 'white';
    var wbTextPending = null; // {x, y}

    var bgPatterns = {
        white: '#ffffff',
        cream: '#fffbeb',
        grid:  '#ffffff',
        lined: '#ffffff',
        dark:  '#1e293b',
        black: '#0f172a',
        green: '#166534'
    };

    var STROKE_PRESET_COLORS = ['#0f172a','#ef4444','#f97316','#eab308','#22c55e','#14b8a6','#3b82f6','#8b5cf6','#ec4899','#ffffff','#94a3b8'];

    function hexToRgba(hex, alpha) {
        var h = (hex || '#000000').replace('#', '');
        if (h.length === 3) h = h.split('').map(function(c) { return c + c; }).join('');
        var n = parseInt(h, 16);
        if (isNaN(n)) return 'rgba(0,0,0,' + alpha + ')';
        var r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    function wbDashArray() {
        var v = (document.getElementById('mx-line-dash') && document.getElementById('mx-line-dash').value) || '';
        if (!v) return null;
        var a = v.split(',').map(function(s) { return parseFloat(s.trim()); }).filter(function(n) { return !isNaN(n); });
        return a.length ? a : null;
    }

    function wbFillFromUI() {
        var tr = document.getElementById('mx-fill-transparent');
        if (tr && tr.checked) return 'transparent';
        return wbFillColor();
    }

    function wbNewOpacity() {
        var el = document.getElementById('mx-opacity');
        var n = el ? parseInt(el.value, 10) : 100;
        if (isNaN(n)) n = 100;
        return Math.max(0.15, Math.min(1, n / 100));
    }

    function wbTextSize() {
        var el = document.getElementById('mx-text-size');
        var n = el ? parseInt(el.value, 10) : 22;
        return isNaN(n) ? 22 : n;
    }

    function applyWbStrokeOpts(obj) {
        if (!obj) return;
        var dash = wbDashArray();
        if (dash) obj.set('strokeDashArray', dash);
        else if (obj.strokeDashArray) obj.set('strokeDashArray', null);
        obj.set('opacity', wbNewOpacity());
    }

    (function initStrokePresets() {
        var wrap = document.getElementById('mx-stroke-presets');
        if (!wrap) return;
        STROKE_PRESET_COLORS.forEach(function(c) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'mx-cp';
            b.style.background = c;
            b.title = c;
            if (c === '#ffffff') b.style.border = '1px solid #64748b';
            b.addEventListener('click', function() {
                document.getElementById('mx-stroke-color').value = c;
                document.getElementById('mx-stroke-color').dispatchEvent(new Event('input', { bubbles: true }));
            });
            wrap.appendChild(b);
        });
    })();

    function resizeWbCanvas() {
        var rect = wbWrap.getBoundingClientRect();
        wbCanvas.setDimensions({ width: Math.max(200, rect.width), height: Math.max(180, rect.height) });
        wbCanvas.requestRenderAll();
    }

    function saveWbState() {
        if (wbRestoring) return;
        wbUndo.push(JSON.stringify(wbCanvas.toJSON(['bgPattern'])));
        if (wbUndo.length > 60) wbUndo.shift();
        wbRedo = [];
    }

    function restoreWbState(from, to) {
        if (!from.length) return;
        wbRestoring = true;
        to.push(JSON.stringify(wbCanvas.toJSON(['bgPattern'])));
        var json = from.pop();
        wbCanvas.loadFromJSON(json, function() { wbCanvas.renderAll(); wbRestoring = false; });
    }

    function wbStrokeColor() { return document.getElementById('mx-stroke-color').value || '#0f172a'; }
    function wbFillColor()   { return document.getElementById('mx-fill-color').value || 'transparent'; }
    function wbStrokeWidth() { return parseInt(document.getElementById('mx-stroke-width').value || '3', 10); }

    function applyBg(bg) {
        wbBg = bg;
        var wrap = document.getElementById('mx-wb-canvas-wrap');
        wrap.className = 'mx-wb-canvas-wrap';
        if (bg === 'grid')  wrap.classList.add('mx-bg-grid');
        else if (bg === 'lined') wrap.classList.add('mx-bg-lined');
        else if (bg === 'dark')  wrap.classList.add('mx-bg-dark');
        else if (bg === 'black') wrap.classList.add('mx-bg-black');
        else if (bg === 'cream') wrap.classList.add('mx-bg-cream');
        else if (bg === 'green') wrap.classList.add('mx-bg-green');
        wbCanvas.setBackgroundColor(bgPatterns[bg] || '#ffffff', wbCanvas.renderAll.bind(wbCanvas));
        document.querySelectorAll('.mx-bg-btn').forEach(function(b) {
            b.classList.toggle('is-active', b.getAttribute('data-bg') === bg);
        });
        if (bg === 'dark' || bg === 'green' || bg === 'black') {
            document.getElementById('mx-stroke-color').value = '#ffffff';
        }
        if (wbTool === 'draw' || wbTool === 'highlight' || wbTool === 'eraser') activateWbTool(wbTool);
    }

    function activateWbTool(tool) {
        wbTool = tool;
        wbCanvas.isDrawingMode = (tool === 'draw' || tool === 'highlight' || tool === 'eraser');
        wbCanvas.selection = (tool === 'select');
        wbCanvas.forEachObject(function(o) { o.selectable = (tool === 'select'); o.evented = (tool === 'select'); });
        if (tool === 'draw' || tool === 'highlight' || tool === 'eraser') {
            wbCanvas.freeDrawingBrush = new fabric.PencilBrush(wbCanvas);
            if (tool === 'eraser') {
                wbCanvas.freeDrawingBrush.width = Math.max(14, wbStrokeWidth() * 4);
                wbCanvas.freeDrawingBrush.color = bgPatterns[wbBg] || '#ffffff';
            } else if (tool === 'highlight') {
                wbCanvas.freeDrawingBrush.width = Math.max(12, wbStrokeWidth() * 3);
                wbCanvas.freeDrawingBrush.color = hexToRgba(wbStrokeColor(), 0.38);
            } else {
                wbCanvas.freeDrawingBrush.width = wbStrokeWidth();
                wbCanvas.freeDrawingBrush.color = wbStrokeColor();
            }
        }
        document.querySelectorAll('#mx-whiteboard-panel [data-tool]').forEach(function(b) {
            b.classList.toggle('is-active', b.getAttribute('data-tool') === tool);
        });
        wbCanvasEl.style.cursor = (tool === 'select') ? 'default' : (tool === 'text') ? 'text' : 'crosshair';
    }

    function wbShapeBase() {
        return {
            stroke: wbStrokeColor(),
            strokeWidth: wbStrokeWidth(),
            fill: wbFillFromUI(),
            opacity: wbNewOpacity(),
            selectable: false,
            evented: false
        };
    }

    function diamondPolyPoints(w, h) {
        return [
            { x: w / 2, y: 0 },
            { x: w, y: h / 2 },
            { x: w / 2, y: h },
            { x: 0, y: h / 2 }
        ];
    }

    function hexPolyPoints(w, h) {
        var cx = w / 2, cy = h / 2, r = Math.min(w, h) / 2;
        var pts = [];
        for (var i = 0; i < 6; i++) {
            var a = -Math.PI / 2 + i * Math.PI / 3;
            pts.push({ x: cx + r * Math.cos(a), y: cy + r * Math.sin(a) });
        }
        return pts;
    }

    function starPolyPoints(w, h) {
        var cx = w / 2, cy = h / 2, outer = Math.min(w, h) / 2, inner = outer * 0.42;
        var pts = [];
        for (var i = 0; i < 10; i++) {
            var rad = (i % 2 === 0) ? outer : inner;
            var ang = -Math.PI / 2 + (i * Math.PI) / 5;
            pts.push({ x: cx + rad * Math.cos(ang), y: cy + rad * Math.sin(ang) });
        }
        return pts;
    }

    function createWbShape(tool, x, y) {
        var opts = Object.assign({ left: x, top: y }, wbShapeBase());
        var dash = wbDashArray();
        if (tool === 'line' || tool === 'arrow' || tool === 'darrow') {
            var ln = new fabric.Line([x, y, x, y], Object.assign({}, opts, { fill: 'transparent' }));
            if (dash) ln.set('strokeDashArray', dash.slice());
            return ln;
        }
        if (tool === 'rect') return new fabric.Rect(Object.assign({}, opts, { width: 1, height: 1 }));
        if (tool === 'roundrect') return new fabric.Rect(Object.assign({}, opts, { width: 1, height: 1, rx: 4, ry: 4 }));
        if (tool === 'circle') return new fabric.Ellipse(Object.assign({}, opts, { rx: 1, ry: 1 }));
        if (tool === 'triangle') return new fabric.Triangle(Object.assign({}, opts, { width: 1, height: 1 }));
        if (tool === 'diamond') return new fabric.Polygon(diamondPolyPoints(8, 8), Object.assign({}, opts, { objectCaching: false }));
        if (tool === 'hex') return new fabric.Polygon(hexPolyPoints(8, 8), Object.assign({}, opts, { objectCaching: false }));
        if (tool === 'star') return new fabric.Polygon(starPolyPoints(8, 8), Object.assign({}, opts, { objectCaching: false }));
        return null;
    }

    function stampCharForTool(tool) {
        if (tool === 'stamp-check') return '✓';
        if (tool === 'stamp-x') return '✗';
        if (tool === 'stamp-q') return '?';
        if (tool === 'stamp-bang') return '!';
        return '';
    }

    function addWbStamp(p, ch) {
        var t = new fabric.IText(ch, {
            left: p.x, top: p.y,
            fontSize: Math.max(28, wbTextSize() * 1.4),
            fill: wbStrokeColor(),
            fontFamily: 'IBM Plex Sans Arabic, system-ui, sans-serif',
            fontWeight: '700',
            opacity: wbNewOpacity(),
            selectable: false,
            evented: false
        });
        wbCanvas.add(t).setActiveObject(t);
        saveWbState();
    }

    function addWbMathSymbol(p, sym) {
        var t = new fabric.IText(sym, {
            left: p.x, top: p.y,
            fontSize: wbTextSize(),
            fill: wbStrokeColor(),
            fontFamily: 'IBM Plex Sans Arabic, system-ui, sans-serif',
            opacity: wbNewOpacity(),
            selectable: false,
            evented: false
        });
        wbCanvas.add(t).setActiveObject(t);
        saveWbState();
    }

    /* Text popup */
    var textPopup  = document.getElementById('mx-text-popup');
    var textInput  = document.getElementById('mx-text-input');
    var textAddBtn = document.getElementById('mx-text-add');
    var textCancel = document.getElementById('mx-text-cancel');

    function showTextPopup(x, y) {
        wbTextPending = { x: x, y: y };
        textInput.value = '';
        textPopup.style.left = Math.min(x, wbWrap.clientWidth - 250) + 'px';
        textPopup.style.top  = Math.max(0, y - 10) + 'px';
        textPopup.style.display = 'block';
        setTimeout(function() { textInput.focus(); }, 50);
    }

    function addTextObject() {
        if (!wbTextPending || !textInput.value.trim()) { textPopup.style.display = 'none'; return; }
        var t = new fabric.IText(textInput.value.trim(), {
            left: wbTextPending.x, top: wbTextPending.y,
            fontSize: wbTextSize(), fill: wbStrokeColor(),
            opacity: wbNewOpacity(),
            fontFamily: 'IBM Plex Sans Arabic, sans-serif',
            selectable: (wbTool === 'select'), editable: true
        });
        wbCanvas.add(t).setActiveObject(t);
        textPopup.style.display = 'none';
        wbTextPending = null;
        saveWbState();
    }

    textAddBtn && textAddBtn.addEventListener('click', addTextObject);
    textCancel && textCancel.addEventListener('click', function() {
        textPopup.style.display = 'none';
        wbTextPending = null;
    });
    textInput && textInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') addTextObject();
        if (e.key === 'Escape') { textPopup.style.display = 'none'; wbTextPending = null; }
    });

    wbCanvas.on('mouse:down', function(opt) {
        var p = wbCanvas.getPointer(opt.e);
        if (wbTool === 'text') { showTextPopup(p.x, p.y); return; }
        var mathSel = document.getElementById('mx-math-insert');
        if (wbTool === 'select' && mathSel && mathSel.value) {
            addWbMathSymbol(p, mathSel.value);
            mathSel.value = '';
            return;
        }
        var st = stampCharForTool(wbTool);
        if (st) { addWbStamp(p, st); return; }
        if (WB_SHAPE_TOOLS.indexOf(wbTool) < 0) return;
        wbStart = p;
        wbDrawObj = createWbShape(wbTool, p.x, p.y);
        if (wbDrawObj) wbCanvas.add(wbDrawObj);
    });

    wbCanvas.on('mouse:move', function(opt) {
        if (!wbDrawObj || !wbStart) return;
        var p = wbCanvas.getPointer(opt.e);
        if (wbTool === 'line' || wbTool === 'arrow' || wbTool === 'darrow') {
            wbDrawObj.set({ x2: p.x, y2: p.y });
        } else if (wbTool === 'rect' || wbTool === 'triangle') {
            wbDrawObj.set({ left: Math.min(wbStart.x, p.x), top: Math.min(wbStart.y, p.y), width: Math.abs(wbStart.x - p.x), height: Math.abs(wbStart.y - p.y) });
        } else if (wbTool === 'roundrect') {
            var rl = Math.min(wbStart.x, p.x), rt = Math.min(wbStart.y, p.y);
            var rw = Math.abs(wbStart.x - p.x), rh = Math.abs(wbStart.y - p.y);
            var rr = Math.min(rw / 2, rh / 2, Math.max(4, Math.min(rw, rh) * 0.22));
            wbDrawObj.set({ left: rl, top: rt, width: rw, height: rh, rx: rr, ry: rr });
        } else if (wbTool === 'circle') {
            var rx = Math.abs(p.x - wbStart.x) / 2, ry = Math.abs(p.y - wbStart.y) / 2;
            wbDrawObj.set({ left: Math.min(wbStart.x, p.x), top: Math.min(wbStart.y, p.y), rx: rx, ry: ry });
        } else if (wbTool === 'diamond' || wbTool === 'hex' || wbTool === 'star') {
            var pl = Math.min(wbStart.x, p.x), pt = Math.min(wbStart.y, p.y);
            var pw = Math.max(2, Math.abs(wbStart.x - p.x)), ph = Math.max(2, Math.abs(wbStart.y - p.y));
            var pts = wbTool === 'diamond' ? diamondPolyPoints(pw, ph) : (wbTool === 'hex' ? hexPolyPoints(pw, ph) : starPolyPoints(pw, ph));
            wbDrawObj.set({ left: pl, top: pt, points: pts });
        }
        wbCanvas.requestRenderAll();
    });

    wbCanvas.on('mouse:up', function() {
        if (!wbDrawObj) return;
        var op = wbNewOpacity();
        if (wbTool === 'arrow') {
            var line = wbDrawObj;
            var angle = Math.atan2(line.y2 - line.y1, line.x2 - line.x1);
            var sz = 14 + wbStrokeWidth();
            var head = new fabric.Triangle({ left: line.x2, top: line.y2, originX: 'center', originY: 'center', width: sz, height: sz + 4, fill: wbStrokeColor(), angle: (angle * 180 / Math.PI) + 90, opacity: 1, selectable: false, evented: false });
            var grp = new fabric.Group([line, head], { selectable: false, evented: false, opacity: op });
            wbCanvas.remove(line);
            wbCanvas.add(grp);
        } else if (wbTool === 'darrow') {
            var line2 = wbDrawObj;
            var ang = Math.atan2(line2.y2 - line2.y1, line2.x2 - line2.x1);
            var sz2 = 14 + wbStrokeWidth();
            var h1 = new fabric.Triangle({ left: line2.x2, top: line2.y2, originX: 'center', originY: 'center', width: sz2, height: sz2 + 4, fill: wbStrokeColor(), angle: (ang * 180 / Math.PI) + 90, opacity: 1, selectable: false, evented: false });
            var h2 = new fabric.Triangle({ left: line2.x1, top: line2.y1, originX: 'center', originY: 'center', width: sz2, height: sz2 + 4, fill: wbStrokeColor(), angle: ((ang + Math.PI) * 180 / Math.PI) + 90, opacity: 1, selectable: false, evented: false });
            var grp2 = new fabric.Group([line2, h1, h2], { selectable: false, evented: false, opacity: op });
            wbCanvas.remove(line2);
            wbCanvas.add(grp2);
        } else {
            applyWbStrokeOpts(wbDrawObj);
        }
        wbDrawObj = null; wbStart = null;
        saveWbState();
    });

    wbCanvas.on('path:created', function(opt) {
        var path = opt.path;
        if (wbTool === 'eraser') { saveWbState(); return; }
        if (wbTool === 'highlight') {
            path.set('opacity', 1);
            saveWbState();
            return;
        }
        if (wbTool === 'draw') applyWbStrokeOpts(path);
        saveWbState();
    });
    wbCanvas.on('object:modified', saveWbState);

    /* Tool buttons */
    document.querySelectorAll('#mx-whiteboard-panel [data-tool]').forEach(function(btn) {
        btn.addEventListener('click', function() { activateWbTool(btn.getAttribute('data-tool')); });
    });

    /* Background switcher */
    document.querySelectorAll('.mx-bg-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { applyBg(btn.getAttribute('data-bg')); });
    });

    /* Color/size live update */
    document.getElementById('mx-stroke-color').addEventListener('input', function() {
        if (wbTool === 'draw' || wbTool === 'highlight' || wbTool === 'eraser') activateWbTool(wbTool);
        var obj = wbCanvas.getActiveObject();
        if (obj && wbTool === 'select') {
            obj.set({ stroke: wbStrokeColor() });
            if (obj.type === 'i-text') obj.set('fill', wbStrokeColor());
            wbCanvas.requestRenderAll(); saveWbState();
        }
    });
    document.getElementById('mx-fill-color').addEventListener('input', function() {
        var obj = wbCanvas.getActiveObject();
        if (obj && wbTool === 'select' && obj.type !== 'line' && obj.type !== 'i-text') {
            obj.set('fill', wbFillColor());
            wbCanvas.requestRenderAll(); saveWbState();
        }
    });
    document.getElementById('mx-stroke-width').addEventListener('input', function() {
        if (wbTool === 'draw' || wbTool === 'highlight' || wbTool === 'eraser') activateWbTool(wbTool);
        var obj = wbCanvas.getActiveObject();
        if (obj && wbTool === 'select') {
            obj.set('strokeWidth', wbStrokeWidth());
            wbCanvas.requestRenderAll(); saveWbState();
        }
    });

    var opEl = document.getElementById('mx-opacity');
    if (opEl) opEl.addEventListener('input', function() {
        var obj = wbCanvas.getActiveObject();
        if (obj && wbTool === 'select') {
            obj.set('opacity', wbNewOpacity());
            wbCanvas.requestRenderAll(); saveWbState();
        }
    });

    var dashEl = document.getElementById('mx-line-dash');
    if (dashEl) dashEl.addEventListener('change', function() {
        var obj = wbCanvas.getActiveObject();
        if (obj && wbTool === 'select') {
            var d = wbDashArray();
            obj.set('strokeDashArray', d ? d.slice() : null);
            wbCanvas.requestRenderAll(); saveWbState();
        }
    });

    var fillTr = document.getElementById('mx-fill-transparent');
    if (fillTr) fillTr.addEventListener('change', function() {
        var obj = wbCanvas.getActiveObject();
        if (obj && wbTool === 'select' && obj.type !== 'line' && obj.type !== 'i-text') {
            obj.set('fill', wbFillFromUI());
            wbCanvas.requestRenderAll(); saveWbState();
        }
    });

    var tsEl = document.getElementById('mx-text-size');
    if (tsEl) tsEl.addEventListener('input', function() {
        var obj = wbCanvas.getActiveObject();
        if (obj && wbTool === 'select' && obj.type === 'i-text') {
            obj.set('fontSize', wbTextSize());
            wbCanvas.requestRenderAll(); saveWbState();
        }
    });

    document.getElementById('mx-dup') && document.getElementById('mx-dup').addEventListener('click', function() {
        var obj = wbCanvas.getActiveObject();
        if (!obj) return;
        obj.clone(function(cloned) {
            cloned.set({ left: (obj.left || 0) + 24, top: (obj.top || 0) + 24 });
            wbCanvas.add(cloned);
            wbCanvas.setActiveObject(cloned);
            wbCanvas.requestRenderAll();
            saveWbState();
        });
    });
    document.getElementById('mx-front') && document.getElementById('mx-front').addEventListener('click', function() {
        var o = wbCanvas.getActiveObject();
        if (!o) return;
        wbCanvas.bringToFront(o);
        wbCanvas.requestRenderAll();
        saveWbState();
    });
    document.getElementById('mx-back') && document.getElementById('mx-back').addEventListener('click', function() {
        var o = wbCanvas.getActiveObject();
        if (!o) return;
        wbCanvas.sendToBack(o);
        wbCanvas.requestRenderAll();
        saveWbState();
    });

    /* Undo / Redo / Clear / Download */
    document.getElementById('mx-undo').addEventListener('click', function() { restoreWbState(wbUndo, wbRedo); });
    document.getElementById('mx-redo').addEventListener('click', function() { restoreWbState(wbRedo, wbUndo); });
    document.getElementById('mx-clear').addEventListener('click', function() {
        if (!confirm('هل تريد مسح اللوحة كاملاً؟')) return;
        wbCanvas.clear();
        applyBg(wbBg);
        saveWbState();
    });
    document.getElementById('mx-download').addEventListener('click', function() {
        var link = document.createElement('a');
        link.href = wbCanvas.toDataURL({ format: 'png', multiplier: 2 });
        link.download = 'glottical-board-' + Date.now() + '.png';
        link.click();
    });

    /* Open / Close panel */
    function openWbPanel() {
        wbPanel.classList.add('is-open');
        resizeWbCanvas();
        applyBg(wbBg);
        if (!wbUndo.length) { saveWbState(); }
    }
    function closeWbPanel() {
        wbPanel.classList.remove('is-open');
        textPopup.style.display = 'none';
    }

    btnBoard && btnBoard.addEventListener('click', function() {
        toolsMenu.classList.remove('is-open');
        fabMain.classList.remove('is-open');
        if (wbPanel.classList.contains('is-open')) closeWbPanel();
        else openWbPanel();
    });
    wbClose && wbClose.addEventListener('click', closeWbPanel);
    document.getElementById('mx-wb-minimize') && document.getElementById('mx-wb-minimize').addEventListener('click', function() {
        wbPanel.style.height = wbPanel.style.height === '42px' ? '' : '42px';
    });

    /* Resize panel by dragging the resize handle */
    (function() {
        var resizeHandle = document.getElementById('mx-wb-resize');
        if (!resizeHandle) return;
        var dragging = false, startY = 0, startH = 0;
        resizeHandle.addEventListener('mousedown', function(e) {
            dragging = true; startY = e.clientY;
            startH = wbPanel.offsetHeight;
            e.preventDefault();
        });
        document.addEventListener('mousemove', function(e) {
            if (!dragging) return;
            var newH = Math.max(300, Math.min(window.innerHeight - 100, startH - (e.clientY - startY)));
            wbPanel.style.height = newH + 'px';
            resizeWbCanvas();
        });
        document.addEventListener('mouseup', function() { dragging = false; });
    })();

    window.addEventListener('resize', function() { resizeAnnCanvas(); if (wbPanel.classList.contains('is-open')) resizeWbCanvas(); });
    activateWbTool('select');
    applyBg('white');
    saveWbState();
})();
</script>
