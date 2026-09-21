<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حصة مباشرة — {{ $meeting->roomChromeTitle() }} | حصتك</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/hesetak-live-meeting.css') }}?v=hstk-live-1">
    <link rel="stylesheet" href="{{ asset('css/classroom-curriculum-presenter.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('js/classroom-curriculum-presenter.js') }}" defer></script>
    <script src="{{ asset('js/classroom-whiteboard-sync.js') }}?v=wb-sync-2"></script>
    <style>
        * { font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif; }
        html { height: 100%; height: 100dvh; }
        body {
            margin: 0;
            padding: 0;
            background: #152A4A;
            overflow: hidden;
            min-height: 100vh;
            min-height: 100dvh;
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
        }
        #meeting-video-root {
            width: 100%;
            flex: 1;
            min-height: 0;
            background: #0f172a;
        }
        .room-body {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
        }
        #meeting-video-root iframe { width: 100% !important; height: 100% !important; border: none; }
        #meeting-stage { flex: 1; min-height: 0; position: relative; display: flex; flex-direction: column; width: 100%; }
        #wb-popup { z-index: 100120 !important; }
        /* عدم خلط display مع Tailwind: عند الإغلاق لا يبقى flex يتعارض مع hidden */
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
        /* القوائم: fixed + فوق الدرج والـ iframe قدر الإمكان */
        #hstk-record-dd-panel {
            z-index: 220;
            will-change: auto;
        }
        #hstk-record-dd-panel.hstk-dd-visible {
            will-change: opacity;
        }
        #hstk-record-dd-panel { box-shadow: 0 14px 36px rgba(0, 0, 0, 0.42), 0 0 0 1px rgba(148, 163, 184, 0.08); }
        #hstk-classroom-nav-drawer { z-index: 205; }
        #hstk-classroom-nav-drawer[data-open="1"] { visibility: visible !important; pointer-events: auto !important; }
        #hstk-classroom-nav-drawer[data-open="1"] #hstk-nav-drawer-backdrop { opacity: 1; pointer-events: auto; }
        #hstk-classroom-nav-drawer[data-open="1"] #hstk-nav-drawer-aside {
            transform: translateX(0) !important;
            pointer-events: auto;
        }
        .classroom-room-toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3125rem 0.625rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1.25;
            transition: background-color 0.15s, border-color 0.15s, color 0.15s;
        }
        @media (min-width: 640px) {
            .classroom-room-toolbar-btn { padding: 0.375rem 0.75rem; }
        }
        .mx-mobile-toolbar-scroll {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .mx-mobile-toolbar-scroll::-webkit-scrollbar {
            display: none;
        }
        #wb-popup-stage { min-height: 50vh; }
        .classroom-excalidraw-host {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }
        .classroom-excalidraw-host .excalidraw {
            --color-surface-lowest: #0f172a;
        }
        /* حصتك Whiteboard: مكتبة + روابط وخدمات خارجية داخل واجهة اللوحة */
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
        /* قائمة البرغر: روابط خارجية (GitHub / Discord / Twitter …) + عنوان المجموعة */
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
        /* مساعدة: شريط المدونة والتوثيق وGitHub */
        .hstk-wb .excalidraw .HelpDialog__header {
            display: none !important;
        }
        /* تعاون مباشر (خوادم خارجية) */
        .hstk-wb .excalidraw [data-testid="collab-button"] {
            display: none !important;
            pointer-events: none !important;
        }
        /* شاشة الترحيب: شعار Excalidraw وروابط ترحيب خارجية */
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
        /* حوارات محددة: روابط خارجية (بدون لمس نوافذ رابط الشكل على العناصر) */
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
        .classroom-excalidraw-loading {
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
</head>
<body class="bg-slate-950">
@php
    $academicObserverMode = !empty($academicObserverMode);
    $rp = ($useInstructorRoutes ?? false) ? 'instructor.' : 'student.';
    $mxRoute = static function (string $name, $params = [], $absolute = true) {
        return \Illuminate\Support\Facades\Route::has($name) ? route($name, $params, $absolute) : '';
    };
    if ($academicObserverMode) {
        $roomExitUrl = $academicObserverExitUrl ?? route('employee.dashboard');
    } elseif (($useInstructorRoutes ?? false)) {
        $roomExitUrl = $meeting->consultation_request_id ? route('instructor.consultations.show', $meeting->consultation_request_id) : route('instructor.consultations.index');
    } else {
        $roomExitUrl = $roomExitUrl ?? route('dashboard');
    }
@endphp
    {{-- شريط حصتك العلوي — على الهاتف: صف علوي + زر سايدبار؛ من md: شريط أدوات أفقي --}}
    <header class="min-h-14 shrink-0 bg-gradient-to-l from-slate-900 to-slate-800 border-b border-slate-700/50 flex flex-col gap-2 px-3 sm:px-4 pt-[max(0.5rem,env(safe-area-inset-top))] pb-2 md:py-0 md:flex-row md:items-center md:justify-between md:gap-2 shadow-lg">
        <div class="flex items-center justify-between gap-2 w-full min-w-0 md:w-auto md:flex-1 md:justify-start">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
            <a href="{{ $roomExitUrl }}" class="flex items-center gap-1.5 text-slate-300 hover:text-white transition-colors shrink-0">
                <span class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center">
                    <i class="fas fa-video text-sm sm:text-[15px]"></i>
                </span>
                <span class="font-bold text-white text-[11px] sm:text-sm truncate max-w-[6.5rem] sm:max-w-[8rem] md:max-w-none">حصتك</span>
            </a>
            <span class="w-px h-5 bg-slate-600 hidden sm:block shrink-0"></span>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse shadow shadow-emerald-400/40 shrink-0"></span>
                <span class="text-white font-semibold text-xs sm:text-sm truncate">{{ $meeting->roomChromeTitle() }}</span>
                <span class="text-slate-400 text-[10px] sm:text-xs px-1.5 py-0.5 rounded bg-slate-700/80 font-mono shrink-0">{{ $meeting->code }}</span>
            </div>
            </div>
            <button type="button" id="hstk-nav-drawer-toggle" class="md:hidden shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-600 bg-slate-800 text-slate-100 hover:bg-slate-700 hover:border-cyan-500/30 transition-colors" aria-expanded="false" aria-controls="hstk-classroom-nav-drawer" title="أدوات الغرفة">
                <i class="fas fa-bars text-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div id="mx-toolbar-desktop-slot" class="hidden md:block w-full md:w-auto mx-mobile-toolbar-scroll overflow-x-auto overflow-y-visible pb-0.5 md:pb-0 touch-pan-x">
        <div id="hstk-classroom-toolbar-inner" class="flex w-full flex-col items-stretch gap-3 md:w-auto md:min-w-max md:flex-row md:flex-nowrap md:items-center md:justify-end md:gap-1 md:gap-2 md:max-w-[min(100%,42rem)] lg:max-w-none pe-1 ps-0.5">
            <div class="flex flex-wrap items-center gap-1.5 md:flex-nowrap">
            @if(!empty($canManageMeeting))
            <span class="hidden sm:inline-flex text-slate-300 text-[10px] sm:text-[11px] px-1.5 py-0.5 rounded-md bg-slate-700/80 whitespace-nowrap">
                طلاب: {{ (int) ($meeting->max_participants ?? 25) }}
            </span>
            @endif
            <span class="inline-flex sm:hidden text-amber-200 text-[10px] px-1.5 py-0.5 rounded-md bg-amber-500/20 border border-amber-500/30 whitespace-nowrap" id="meeting-timer-chip-mobile">
                {{ (int) $effectiveDurationMinutes }} د
            </span>
            <span class="hidden sm:inline-flex text-amber-200 text-[10px] sm:text-[11px] px-1.5 py-0.5 rounded-md bg-amber-500/20 border border-amber-500/30 whitespace-nowrap" id="meeting-timer-chip">
                مدة الاجتماع: {{ (int) $effectiveDurationMinutes }} دقيقة (الحد {{ (int) $maxDurationMinutes }})
            </span>
            @if($academicObserverMode)
            <span class="inline-flex text-violet-200 text-[10px] sm:text-[11px] px-1.5 py-0.5 rounded-md bg-violet-500/20 border border-violet-500/30 whitespace-nowrap" title="لا كاميرا ولا مايك — غير ظاهر للمشاركين">
                <i class="fas fa-user-secret ml-1"></i> مراقبة صامتة
            </span>
            @endif
            <span class="hidden text-sky-200 text-[10px] sm:text-[11px] px-1.5 py-0.5 rounded-md bg-sky-500/20 border border-sky-500/30 max-w-[10rem] sm:max-w-[14rem] truncate" id="record-status-chip"></span>
            </div>
            <span class="hidden xl:block w-px h-4 bg-slate-600/50 shrink-0 rounded-full" aria-hidden="true"></span>
            <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row md:flex-nowrap md:items-center md:justify-end md:gap-1.5">
            @unless($academicObserverMode)
            @if(!empty($canManageMeeting))
            <button type="button" id="mx-ml-btn-curriculum" class="classroom-room-toolbar-btn w-full justify-center gap-2 bg-indigo-600/25 hover:bg-indigo-600/35 text-indigo-100 border border-indigo-500/40 md:w-auto md:justify-start" title="عرض منهج تفاعلي" aria-pressed="false">
                <i class="fas fa-book-open text-indigo-300 text-[11px]"></i>
                <span class="sm:inline">عرض منهج</span>
            </button>
            <button type="button" id="btn-wb-popup-open" class="classroom-room-toolbar-btn w-full justify-center gap-2 bg-amber-600/25 hover:bg-amber-600/35 text-amber-100 border border-amber-500/40 md:w-auto md:justify-start" title="فتح الوايت بورد في نافذة منبثقة">
                <i class="fas fa-expand text-amber-300 text-[11px]"></i>
                <span class="sm:inline">الوايت بورد</span>
            </button>
            <button type="button" id="btn-mx-host-share-draw" class="classroom-room-toolbar-btn w-full justify-center gap-2 bg-sky-600/25 hover:bg-sky-600/35 text-sky-100 border border-sky-500/40 md:w-auto md:justify-start" title="قلم على الشاشة المشتركة (مثل زوم) — يبقى فوق التطبيقات ويظهر للطالب">
                <i class="fas fa-pen-fancy text-sky-300 text-[11px]"></i>
                <span class="sm:inline">قلم الشاشة</span>
            </button>
            <label class="classroom-room-toolbar-btn w-full justify-between bg-slate-700/50 border border-slate-600 cursor-pointer select-none text-slate-200 md:w-auto md:max-w-[13rem]"
                   title="الضيف يرسم قلم/ممحاة فوق عرض الاجتماع؛ يظهر عندك فوق نفس الشاشة">
                <input type="checkbox" id="hstk-classroom-toggle-guest-wb" class="rounded border-slate-500 text-amber-500 focus:ring-amber-500 shrink-0 scale-90"
                       {{ $meeting->allowsParticipantWhiteboard() ? 'checked' : '' }}>
                <span class="font-medium truncate"><span class="hidden sm:inline">رسم الضيف فوق العرض</span><span class="sm:hidden">رسم ضيف</span></span>
            </label>
            <span id="mx-auto-rec-badge" class="hidden classroom-room-toolbar-btn cursor-default bg-rose-900/40 text-rose-200 border border-rose-700/50 md:w-auto" title="تسجيل تلقائي للحصة">
                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse shrink-0"></span>
                <span class="font-semibold text-xs">REC</span>
            </span>
            @if(!empty($guestJoinEnabled))
            <button type="button" id="btn-classroom-copy-join" class="classroom-room-toolbar-btn w-full justify-center gap-2 bg-slate-700/80 hover:bg-slate-600 text-slate-200 border border-slate-600 md:w-auto md:justify-start" title="نسخ رابط الانضمام" data-join-url="{{ url('classroom/join/' . $meeting->code) }}">
                <i class="fas fa-link text-[10px] btn-copy-join-ic"></i>
                <span class="btn-copy-join-tx min-w-0 truncate">مشاركة الرابط</span>
                <span class="btn-copy-join-tx-sm hidden min-w-0 truncate" aria-hidden="true">رابط</span>
            </button>
            @else
            <span class="classroom-room-toolbar-btn w-full justify-center gap-2 bg-emerald-900/40 text-emerald-200 border border-emerald-700/50 md:w-auto md:justify-start cursor-default" title="الدخول محمي داخل المنصة">
                <i class="fas fa-shield-alt text-[10px]"></i>
                <span class="min-w-0 truncate">دخول محمي · بدون رابط ضيف</span>
            </span>
            @endif
            <form method="POST" action="{{ $mxRoute($rp.'classroom.end', $meeting) }}" class="inline w-full shrink-0 md:w-auto {{ !\Illuminate\Support\Facades\Route::has($rp.'classroom.end') ? 'hidden' : '' }}" id="mx-end-meeting-form" onsubmit="return (typeof window.__mxClassroomConfirmEnd === 'function') ? window.__mxClassroomConfirmEnd() : confirm('إنهاء الاجتماع للجميع؟');">
                @csrf
                <button type="submit" id="mx-end-meeting-btn" class="classroom-room-toolbar-btn w-full justify-center bg-rose-600 hover:bg-rose-500 text-white font-semibold border border-rose-500/50 shadow-sm shadow-rose-900/20 md:w-auto md:justify-start">
                    <i class="fas fa-stop text-[10px]"></i><span class="hidden md:inline">إنهاء الاجتماع</span><span class="md:hidden">إنهاء</span>
                </button>
            </form>
            @else
            <a href="{{ $roomExitUrl ?? route('dashboard') }}" class="classroom-room-toolbar-btn w-full justify-center gap-2 bg-slate-700/80 hover:bg-slate-600 text-slate-100 border border-slate-600 md:w-auto md:justify-start" title="العودة للوحة">
                <i class="fas fa-arrow-right text-[10px]"></i>
                <span>العودة للوحة</span>
            </a>
            @endif
            @else
            <span class="text-amber-200 text-[11px] px-2 py-1 rounded-md bg-amber-500/15 border border-amber-500/30 font-semibold">
                <i class="fas fa-eye text-[10px] ms-0.5"></i> مراقبة
            </span>
            @endunless
            </div>
        </div>
        </div>
    </header>

    {{-- درج أدوات الغرفة (هاتف فقط): نفس عناصر الشريط تُنقل هنا عبر JS --}}
    <div id="hstk-classroom-nav-drawer" class="md:hidden fixed inset-0 invisible pointer-events-none" data-open="0" aria-hidden="true">
        <div id="hstk-nav-drawer-backdrop" class="absolute inset-0 bg-slate-950/65 opacity-0 transition-opacity duration-200 pointer-events-none" aria-hidden="true"></div>
        <aside id="hstk-nav-drawer-aside" class="absolute end-0 top-0 flex h-full min-h-0 w-[min(20rem,calc(100vw-2.5rem))] max-w-[100vw] flex-col border-s border-slate-600/80 bg-slate-900 shadow-2xl transition-transform duration-200 ease-out ltr:translate-x-full rtl:-translate-x-full pointer-events-none pt-[max(0.5rem,env(safe-area-inset-top))]" role="dialog" aria-modal="true" aria-labelledby="mx-nav-drawer-title">
            <div class="flex items-center justify-between gap-2 border-b border-slate-700/80 px-3 py-2.5 shrink-0">
                <h2 id="mx-nav-drawer-title" class="text-sm font-bold text-white m-0 truncate">أدوات الغرفة</h2>
                <button type="button" id="mx-nav-drawer-close" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-600 bg-slate-800 text-slate-200 hover:bg-slate-700 hover:text-white" aria-label="إغلاق القائمة">
                    <i class="fas fa-times text-sm" aria-hidden="true"></i>
                </button>
            </div>
            <div id="mx-toolbar-drawer-slot" class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto overscroll-contain p-3 pb-[max(1rem,env(safe-area-inset-bottom))]"></div>
        </aside>
    </div>
    <script>
        (function () {
            var mq = window.matchMedia('(max-width: 767px)');
            var inner = document.getElementById('hstk-classroom-toolbar-inner');
            var desk = document.getElementById('mx-toolbar-desktop-slot');
            var slot = document.getElementById('mx-toolbar-drawer-slot');
            var drawer = document.getElementById('hstk-classroom-nav-drawer');
            var toggle = document.getElementById('hstk-nav-drawer-toggle');
            var closeBtn = document.getElementById('mx-nav-drawer-close');
            var backdrop = document.getElementById('hstk-nav-drawer-backdrop');
            var asideEl = document.getElementById('hstk-nav-drawer-aside');

            function setDrawerOpen(open) {
                if (!drawer || !toggle) return;
                drawer.setAttribute('data-open', open ? '1' : '0');
                drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.classList.toggle('hstk-classroom-drawer-open', !!open);
                if (open) {
                    try { window.dispatchEvent(new Event('resize')); } catch (e) {}
                }
            }

            function placeToolbar() {
                if (!inner || !desk || !slot) return;
                if (mq.matches) {
                    slot.appendChild(inner);
                } else {
                    desk.appendChild(inner);
                    setDrawerOpen(false);
                }
            }

            placeToolbar();
            if (typeof mq.addEventListener === 'function') {
                mq.addEventListener('change', placeToolbar);
            } else if (typeof mq.addListener === 'function') {
                mq.addListener(placeToolbar);
            }
            window.addEventListener('resize', placeToolbar);

            if (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setDrawerOpen(drawer.getAttribute('data-open') !== '1');
                });
            }
            if (closeBtn) closeBtn.addEventListener('click', function () { setDrawerOpen(false); });
            if (backdrop) {
                backdrop.addEventListener('click', function () { setDrawerOpen(false); });
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') setDrawerOpen(false);
            });
            document.addEventListener('mousedown', function (e) {
                if (!drawer || drawer.getAttribute('data-open') !== '1') return;
                if (toggle && toggle.contains(e.target)) return;
                if (backdrop && e.target === backdrop) {
                    setDrawerOpen(false);
                    return;
                }
                if (asideEl && asideEl.contains(e.target)) return;
                setDrawerOpen(false);
            }, true);
        })();
    </script>

    <div class="room-body">

    {{-- منطقة الاجتماع --}}
    <div id="meeting-stage" class="flex-1 min-h-0 relative w-full">
        @if(!empty($livekitConfigured) && !empty($livekitToken) && !empty($livekitUrl))
            @include('partials.hesetak-live-room', [
                'livekitUrl' => $livekitUrl,
                'livekitToken' => $livekitToken,
                'user' => $user,
                'lkRole' => $lkRole ?? 'participant',
                'lkTheme' => (!empty($canManageMeeting) || !empty($useInstructorRoutes)) ? 'instructor' : 'student',
                'lkLeaveUrl' => $roomExitUrl ?? url('/'),
                'lkAllowScreenShare' => $academicObserverMode
                    ? false
                    : ((!empty($canManageMeeting) || !empty($useInstructorRoutes)) ? ($allowScreenShare ?? true) : false),
                'lkStartAudio' => $academicObserverMode ? false : ($lkStartAudio ?? true),
                'lkStartVideo' => $academicObserverMode ? false : ($lkStartVideo ?? true),
            ])
        @else
            <main id="meeting-video-root" class="flex-1 min-h-0 relative w-full flex flex-col items-center justify-center gap-3 p-8 text-center text-slate-300" role="application" aria-label="غرفة الاجتماع">
                <i class="fas fa-exclamation-triangle text-amber-400 text-3xl"></i>
                <p class="font-bold text-white">إعدادات LiveKit غير مكتملة</p>
                <p class="text-sm text-slate-400">اضبط مفاتيح LiveKit ونطاق السيرفر من لوحة الإدارة → سيرفرات البث.</p>
            </main>
        @endif
        @unless(!empty($academicObserverMode))
        @include('partials.hesetak-live-share-annotation', [
            'mxAnnRole' => !empty($canManageMeeting) ? 'host_emit' : 'viewer_poll',
            'mxAnnPostUrl' => !empty($canManageMeeting) ? $mxRoute($rp . 'classroom.share-annotation', $meeting) : '',
            'mxAnnPollUrl' => $mxRoute($rp . 'classroom.share-annotations', $meeting),
            'mxAnnSelfKey' => (string) ($user->id ?? auth()->id() ?? ''),
        ])
        @endunless
    </div>
    </div>

    {{-- لوحة بيضاء منبثقة — للمعلم/المضيف فقط --}}
    @if(!empty($canManageMeeting))
    <div id="wb-popup" class="hidden fixed inset-0 p-2 sm:p-4" inert aria-hidden="true" role="dialog" aria-labelledby="wb-popup-title" aria-modal="true">
        <div id="wb-popup-backdrop" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer" aria-hidden="true"></div>
        <div id="wb-popup-panel" class="relative z-[141] flex flex-col w-full max-w-[min(1680px,99vw)] h-[min(92vh,calc(100dvh-1rem))] rounded-2xl border border-slate-600 bg-slate-900 shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-700 bg-slate-800/95 shrink-0">
                <h2 id="wb-popup-title" class="text-base font-bold text-white m-0 flex items-center gap-2">
                    <i class="fas fa-chalkboard text-amber-400"></i>
                    الوايت بورد
                </h2>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-wb-popup-fullscreen" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-medium border border-slate-600" title="ملء الشاشة (اخرج بـ Esc)">
                        <i class="fas fa-expand"></i>
                        <span class="hidden sm:inline">ملء الشاشة</span>
                    </button>
                    <button type="button" id="wb-popup-close" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-700 hover:bg-rose-600/80 text-white text-lg leading-none border border-slate-600" aria-label="إغلاق اللوحة">&times;</button>
                </div>
            </div>
            <div id="wb-popup-stage" class="relative flex-1 min-h-0 bg-[#121212]">
                <div id="classroom-excalidraw-root" class="classroom-excalidraw-host hstk-wb" data-view-only="0" data-lang="ar"></div>
                <div id="classroom-excalidraw-loading" class="classroom-excalidraw-loading">جاري تحميل حصتك Whiteboard…</div>
            </div>
            <div id="wb-popup-toolbar" class="flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 border-t border-slate-700 bg-slate-800/95 shrink-0">
                <span class="text-slate-400 text-[11px] leading-relaxed text-center max-w-3xl">
                    <strong class="text-slate-200">سبورة تفاعلية مباشرة</strong> — ما ترسمه يظهر فوراً عند الطالب عبر البث. لقلم فوق الشاشة (شير) استخدم «قلم الشاشة».
                </span>
            </div>
        </div>
    </div>
    @endif

    {{-- رفع التسجيل للمعلم صامت تلقائياً — بدون مودال أو تاب --}}

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
    {{-- محاذاة القوائم المنسدلة خارج منطقة overflow-x (الهاتف) --}}
    <script>
        /**
         * @param {HTMLElement} panel
         * @param {HTMLElement} alignEl — عنصر المحاذاة الأفقية (مثل الحاوية relative + end-0)
         * @param {HTMLElement} [topEl] — أسفل هذا العنصر يُفتح اللوح (افتراضياً alignEl)
         */
        window.mxPositionClassroomDropdown = function (panel, alignEl, topEl) {
            if (!panel || !alignEl) return;
            if (!topEl) topEl = alignEl;

            var gap = 6;
            var vw = window.innerWidth || document.documentElement.clientWidth || 0;
            var vh = window.innerHeight || document.documentElement.clientHeight || 0;
            var ar = alignEl.getBoundingClientRect();
            var tr = topEl.getBoundingClientRect();
            var rtl = ((document.documentElement.getAttribute('dir') || 'ltr').toLowerCase()) === 'rtl';

            panel.style.position = 'fixed';
            panel.style.zIndex = '220';

            var pw = panel.offsetWidth;

            var wantLeft = rtl ? ar.left : (ar.right - pw);
            if (wantLeft + pw > vw - 8) wantLeft = vw - 8 - pw;
            if (wantLeft < 8) wantLeft = 8;
            panel.style.left = wantLeft + 'px';
            panel.style.right = 'auto';

            var ph = panel.offsetHeight;
            var top = tr.bottom + gap;
            if (top + ph > vh - 8) top = tr.top - gap - ph;
            if (top < 8) top = 8;
            panel.style.top = top + 'px';
            panel.style.bottom = 'auto';
        };
        window.mxClearClassroomDropdownPosition = function (panel) {
            if (!panel) return;
            panel.classList.remove('hstk-dd-visible');
            ['position', 'top', 'left', 'right', 'width', 'maxWidth', 'bottom', 'zIndex', 'opacity', 'transition', 'pointerEvents'].forEach(function (k) {
                panel.style[k] = '';
            });
        };
    </script>
    {{-- حصتك Whiteboard: تحميل ديناميكي + أكثر من مسار (Laravel ثم ملفات public المباشرة) --}}
    <script>
        (function() {
            var meetingEndsAt = {!! json_encode(optional($meetingEndsAt)->toIso8601String()) !!};
            var timerChip = document.getElementById('meeting-timer-chip');
            var timerChipMobile = document.getElementById('meeting-timer-chip-mobile');
            var mxMeetingId = {{ (int) $meeting->id }};
            var mxSilentAutoRecording = {{ !empty($canManageMeeting) ? 'true' : 'false' }};
            var mxAutoRecBadge = document.getElementById('mx-auto-rec-badge');
            var mxAutoRecordStarted = false;
            var recordDdWrap = document.getElementById('hstk-record-dd-wrap');
            var btnRecordMenu = document.getElementById('btn-record-menu');
            var btnRecordStop = document.getElementById('btn-record-stop');
            var endMeetingForm = document.getElementById('mx-end-meeting-form');
            var endMeetingBtn = document.getElementById('mx-end-meeting-btn');
            var recordIdleWrap = document.getElementById('hstk-record-idle-wrap');
            var recordDdPanel = document.getElementById('hstk-record-dd-panel');
            var recordDdChevron = document.getElementById('record-dd-chevron');
            var recordIconIdle = document.getElementById('record-icon-idle');
            var recordLabelIdle = document.getElementById('record-label-idle');
            var recordIconActive = document.getElementById('record-icon-active');
            var recordLabelActive = document.getElementById('record-label-active');
            var recordStatusChip = document.getElementById('record-status-chip');
            var mxUploadModal = document.getElementById('mx-upload-modal');
            var mxUploadModalBar = document.getElementById('mx-upload-modal-bar');
            var mxUploadModalStatus = document.getElementById('mx-upload-modal-status');
            var mxUploadModalTitle = document.getElementById('mx-upload-modal-title');
            var mxUploadModalSub = document.getElementById('mx-upload-modal-sub');
            var mxUploadModalBg = document.getElementById('mx-upload-modal-bg');
            var mxUploadModalRetry = document.getElementById('mx-upload-modal-retry');
            var mxUploadChip = document.getElementById('mx-upload-chip');
            var mxUploadChipText = document.getElementById('mx-upload-chip-text');
            var uploadRecordingUrl = @json($mxRoute($rp . 'classroom.recording.upload', $meeting));
            var presignRecordingUrl = @json($mxRoute($rp . 'classroom.recording.presign', $meeting));
            var completeRecordingUrl = @json($mxRoute($rp . 'classroom.recording.complete', $meeting));
            var presignAudioUrl = @json($mxRoute($rp . 'classroom.recording-audio.presign', $meeting));
            var uploadAudioUrl = @json($mxRoute($rp . 'classroom.recording-audio.upload', $meeting));
            var completeAudioUrl = @json($mxRoute($rp . 'classroom.recording-audio.complete', $meeting));
            var recordingUploadTabBaseUrl = @json($mxRoute($rp . 'classroom.recording.upload-tab', $meeting));
            var csrfToken = '{{ csrf_token() }}';
            var participantWbUrl = @json($mxRoute($rp . 'classroom.participant-whiteboard', $meeting));
            var mxClassroomGuestWbToggle = document.getElementById('hstk-classroom-toggle-guest-wb');
            var mxClassroomGuestWbSaving = false;
            if (mxClassroomGuestWbToggle) {
                mxClassroomGuestWbToggle.addEventListener('change', function () {
                    if (mxClassroomGuestWbSaving) return;
                    mxClassroomGuestWbSaving = true;
                    var want = mxClassroomGuestWbToggle.checked;
                    fetch(participantWbUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ allow: want }),
                    }).then(function (r) {
                        if (!r.ok) mxClassroomGuestWbToggle.checked = !want;
                    }).catch(function () {
                        mxClassroomGuestWbToggle.checked = !want;
                    }).finally(function () {
                        mxClassroomGuestWbSaving = false;
                    });
                });
            }
            var hostShareDrawBtn = document.getElementById('btn-mx-host-share-draw');
            if (hostShareDrawBtn) {
                hostShareDrawBtn.addEventListener('click', function () {
                    if (typeof window.__mxLkToggleScreenAnnotate === 'function') {
                        window.__mxLkToggleScreenAnnotate();
                        return;
                    }
                    if (typeof window.__mxShareAnnOpenToolbar === 'function') {
                        window.__mxShareAnnSetAllowed?.(true);
                        window.__mxShareAnnOpenToolbar();
                    }
                });
            }
            var btnClassroomCopyJoin = document.getElementById('btn-classroom-copy-join');
            if (btnClassroomCopyJoin) {
                btnClassroomCopyJoin.addEventListener('click', function () {
                    var joinUrl = btnClassroomCopyJoin.getAttribute('data-join-url') || '';
                    var ic = btnClassroomCopyJoin.querySelector('.btn-copy-join-ic');
                    var tx = btnClassroomCopyJoin.querySelector('.btn-copy-join-tx');
                    var txSm = btnClassroomCopyJoin.querySelector('.btn-copy-join-tx-sm');
                    function restoreJoinBtn() {
                        if (ic) {
                            ic.className = 'fas fa-link text-[10px] btn-copy-join-ic';
                        }
                        if (tx) tx.textContent = 'مشاركة الرابط';
                        if (txSm) txSm.textContent = 'رابط';
                    }
                    function showCopied() {
                        if (ic) {
                            ic.className = 'fas fa-check text-[10px] btn-copy-join-ic text-emerald-400';
                        }
                        if (tx) tx.textContent = 'تم النسخ';
                        if (txSm) txSm.textContent = 'تم';
                    }
                    navigator.clipboard.writeText(joinUrl).then(function () {
                        showCopied();
                        setTimeout(restoreJoinBtn, 2000);
                    }).catch(function () {
                        if (tx) tx.textContent = 'فشل النسخ';
                        if (txSm) tx.textContent = '!';
                        setTimeout(restoreJoinBtn, 2500);
                    });
                });
            }
            var roomExitUrl = {!! json_encode($roomExitUrl) !!};
            var permissionGate = document.getElementById('permission-gate');
            var permissionHelp = document.getElementById('permission-help');
            var requestMediaBtn = document.getElementById('btn-request-media');
            var joinWithoutMediaBtn = document.getElementById('btn-join-without-media');
            var api = null;
            var hasJoinedConference = false;
            var isRecording = false;
            var recordingKind = null;
            var mediaRecorder = null;
            var recordedChunks = [];
            var recordingStartedAt = null;
            var activeRecordingStream = null;
            var micStream = null;
            var audioRecorder = null;
            var recordedAudioChunks = [];
            var audioOnlyStream = null;
            var mxRecordDdOpen = false;
            var mxUploadModalMinimized = false;
            var mxCurrentUploadJob = null;
            var mxLastFailedJob = null;
            var pendingEndMeetingSubmit = false;
            var mxUploadsInFlight = 0;
            var mxUploadChain = Promise.resolve();
            var lectureCanvas = null;
            var lectureCtx = null;
            var lectureCanvasStream = null;
            var lectureDisplayStream = null;
            var lectureDisplayVideo = null;
            var lectureRafId = null;
            var lectureCompositeInterval = null;
            var lectureAudioCtx = null;
            var lectureAudioDest = null;
            var lectureAudioSources = [];
            var lectureAudioTrackIds = {};
            var mxSkipEndConfirm = false;
            var mxAutoEndingMeeting = false;
            var btnLectureAddScreen = document.getElementById('btn-lecture-add-screen');

            window.__mxClassroomConfirmEnd = function () {
                if (mxSkipEndConfirm) return true;
                return confirm('إنهاء الاجتماع للجميع؟');
            };

            var wbCanvas = null;
            var wbCtx = null;

            var wbPopup = document.getElementById('wb-popup');
            var wbPopupStage = document.getElementById('wb-popup-stage');
            var wbPopupPanel = document.getElementById('wb-popup-panel');
            var excRoot = document.getElementById('classroom-excalidraw-root');
            var excLoading = document.getElementById('classroom-excalidraw-loading');
            var excReactRoot = null;
            var excMounted = false;
            var excMountPromise = null;
            var wbPopupClosing = false;
            var mxExcalidrawBases = {!! json_encode($mxExBases) !!};
            var excVendorPromise = null;

            function excShowLoading(on) {
                if (excLoading) excLoading.style.display = on ? 'flex' : 'none';
            }

            function nudgeClassroomExLayout() {
                window.dispatchEvent(new Event('resize'));
                if (window.requestAnimationFrame) {
                    requestAnimationFrame(function() { window.dispatchEvent(new Event('resize')); });
                }
            }

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
                                throw new Error('تعذّر تعريف مكوّنات حصتك Whiteboard بعد التحميل');
                            }
                        });
                }

                function tryNext(i) {
                    if (i >= bases.length) {
                        return Promise.reject(new Error('فشل كل مسارات التحميل. تأكد من وجود public/vendor/excalidraw ومسار Laravel /mx-vendor/excalidraw'));
                    }
                    return loadFromBase(bases[i]).catch(function() { return tryNext(i + 1); });
                }

                excVendorPromise = tryNext(0).catch(function(e) {
                    excVendorPromise = null;
                    throw e;
                });
                return excVendorPromise;
            }

            function mountClassroomExcalidrawOnce() {
                if (excMounted) return Promise.resolve();
                if (excMountPromise) return excMountPromise;
                if (!excRoot) return Promise.reject(new Error('no excalidraw root'));
                excShowLoading(true);

                function failMount(err) {
                    console.error('[حصتك Whiteboard]', err);
                    excMountPromise = null;
                    excShowLoading(false);
                    if (excLoading) {
                        var detail = (err && err.message) ? String(err.message) : '';
                        if (detail.length > 240) detail = detail.slice(0, 237) + '…';
                        excLoading.textContent = 'تعذّر تهيئة حصتك Whiteboard.' + (detail ? (' ' + detail) : '') + ' — Network: جرّب ‎/mx-vendor/excalidraw/react.production.min.js‎ أو ‎/vendor/excalidraw/…‎ برمز 200.';
                        excLoading.style.display = 'flex';
                    }
                }

                excMountPromise = ensureExcalidrawVendorLoaded()
                    .then(function() {
                        return new Promise(function(resolve, reject) {
                            var deadline = Date.now() + 12000;
                            function tryMount() {
                                var Lib = getExcalidrawLib();
                                var ReactMod = window.React;
                                var ReactDOM = window.ReactDOM;
                                if (!Lib || !ReactMod || !ReactDOM) {
                                    failMount(new Error('المكتبات غير متاحة بعد التحميل'));
                                    reject(new Error('missing after load'));
                                    return;
                                }
                                var rect = excRoot.getBoundingClientRect();
                                if (rect.width < 8 || rect.height < 8) {
                                    if (Date.now() > deadline) {
                                        try {
                                            excRoot.style.minHeight = '60vh';
                                            if (wbPopupStage) wbPopupStage.style.minHeight = '60vh';
                                        } catch (eDim) {}
                                        rect = excRoot.getBoundingClientRect();
                                        if (rect.width < 8 || rect.height < 8) {
                                            failMount(new Error('الحاوية بلا أبعاد كافية بعد فتح النافذة.'));
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
                                    // مكوّن اللوحة مُصدَّر كـ React.memo — typeof يكون "object" وليس "function"
                                    if (Excalidraw == null || (typeof Excalidraw !== 'function' && typeof Excalidraw !== 'object')) {
                                        throw new Error('حزمة حصتك Whiteboard غير صالحة (مكوّن اللوحة).');
                                    }
                                    if (typeof createRoot !== 'function') {
                                        throw new Error('ReactDOM.createRoot غير متاح (تحقق من react-dom 18).');
                                    }
                                    var viewOnly = excRoot.getAttribute('data-view-only') === '1';
                                    var lang = excRoot.getAttribute('data-lang') || '';
                                    var props = {
                                        viewModeEnabled: viewOnly,
                                        onChange: function () {
                                            if (window.__mxClassroomWbSync && typeof window.__mxClassroomWbSync.onLocalChange === 'function') {
                                                window.__mxClassroomWbSync.onLocalChange();
                                            }
                                        },
                                        excalidrawAPI: function(api) {
                                            window.__mxClassroomExcalidrawAPI = api;
                                            (function bindWbSync(tries) {
                                                try {
                                                    if (window.__mxClassroomWbSync) {
                                                        if (typeof window.__mxClassroomWbSync.requestRemote === 'function') {
                                                            window.__mxClassroomWbSync.requestRemote();
                                                        }
                                                        return;
                                                    }
                                                    if (!window.MxClassroomWhiteboardSync) {
                                                        if (tries < 40) setTimeout(function () { bindWbSync(tries + 1); }, 100);
                                                        return;
                                                    }
                                                    window.__mxClassroomWbSync = window.MxClassroomWhiteboardSync.attach({
                                                        getApi: function () { return window.__mxClassroomExcalidrawAPI; },
                                                        role: 'host',
                                                        canEmit: true,
                                                        canReceive: true,
                                                        mergeRemote: {{ !empty($meeting->allowsParticipantWhiteboard()) ? 'true' : 'false' }},
                                                        stateUrl: @json($mxRoute($rp . 'classroom.whiteboard.state', $meeting)),
                                                        pushUrl: @json($mxRoute($rp . 'classroom.whiteboard.push', $meeting)),
                                                        csrf: csrfToken,
                                                    });
                                                } catch (eWb) {}
                                            })(0);
                                        }
                                    };
                                    if (lang.indexOf('ar') === 0) props.langCode = 'ar-SA';
                                    excReactRoot = createRoot(excRoot);
                                    excReactRoot.render(ReactMod.createElement(Excalidraw, props));
                                    excMounted = true;
                                    excShowLoading(false);
                                    nudgeClassroomExLayout();
                                    resolve();
                                } catch (err) {
                                    failMount(err);
                                    reject(err);
                                }
                            }
                            requestAnimationFrame(tryMount);
                        });
                    })
                    .catch(function(err) {
                        failMount(err);
                        excMountPromise = null;
                        return Promise.reject(err);
                    });

                return excMountPromise;
            }

            function mergeExcalidrawToMain(done) {
                var api = window.__mxClassroomExcalidrawAPI;
                if (!api || !wbCanvas || !wbCtx) {
                    if (done) done();
                    return;
                }
                function runExport() {
                    var Lib = getExcalidrawLib();
                    var exportToBlob = Lib && Lib.exportToBlob;
                    if (typeof exportToBlob !== 'function') {
                        if (done) done();
                        return;
                    }
                    exportToBlob({
                        elements: api.getSceneElements(),
                        appState: api.getAppState(),
                        files: api.getFiles ? api.getFiles() : null,
                        mimeType: 'image/png',
                        exportWithDarkMode: false,
                        exportBackground: true
                    }).then(function(blob) {
                        if (!blob) {
                            if (done) done();
                            return;
                        }
                        var url = URL.createObjectURL(blob);
                        var img = new Image();
                        img.onload = function() {
                            resizeWbCanvas();
                            wbCtx.save();
                            wbCtx.setTransform(1, 0, 0, 1, 0, 0);
                            wbCtx.clearRect(0, 0, wbCanvas.width, wbCanvas.height);
                            wbCtx.drawImage(img, 0, 0, wbCanvas.width, wbCanvas.height);
                            wbCtx.restore();
                            var dpr = window.devicePixelRatio || 1;
                            wbCtx.setTransform(dpr, 0, 0, dpr, 0, 0);
                            wbCtx.lineCap = 'round';
                            wbCtx.lineJoin = 'round';
                            URL.revokeObjectURL(url);
                            if (done) done();
                        };
                        img.onerror = function() {
                            URL.revokeObjectURL(url);
                            if (done) done();
                        };
                        img.src = url;
                    }).catch(function() {
                        if (done) done();
                    });
                }
                if (getExcalidrawLib()) {
                    runExport();
                } else {
                    ensureExcalidrawVendorLoaded().then(runExport).catch(function() { if (done) done(); });
                }
            }

            function openWbPopup() {
                if (!wbPopup) return;
                wbPopup.removeAttribute('inert');
                wbPopup.classList.remove('hidden');
                wbPopup.classList.add('is-open');
                wbPopup.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                document.body.classList.add('hstk-wb-open');
                requestAnimationFrame(function () {
                    mountClassroomExcalidrawOnce().then(function() {
                        setTimeout(nudgeClassroomExLayout, 80);
                        setTimeout(nudgeClassroomExLayout, 400);
                        setTimeout(nudgeClassroomExLayout, 900);
                        try {
                            if (window.__mxClassroomWbSync && typeof window.__mxClassroomWbSync.requestRemote === 'function') {
                                window.__mxClassroomWbSync.requestRemote();
                            } else if (window.__mxClassroomWbSync && typeof window.__mxClassroomWbSync.flush === 'function') {
                                window.__mxClassroomWbSync.flush();
                            }
                        } catch (eReq) {}
                    }).catch(function (err) {
                        console.error('[حصتك Whiteboard]', err);
                    });
                });
            }

            function closeWbPopup() {
                if (wbPopupClosing) return;
                if (!wbPopup || wbPopup.classList.contains('hidden')) return;
                wbPopupClosing = true;

                function detachWhiteboardFromMeetingUi() {
                    var ae = document.activeElement;
                    if (ae && typeof ae.blur === 'function' && wbPopup.contains(ae)) {
                        ae.blur();
                    }
                    try {
                        var sel = window.getSelection && window.getSelection();
                        if (sel && typeof sel.removeAllRanges === 'function') {
                            sel.removeAllRanges();
                        }
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
                    mergeExcalidrawToMain(function() {
                        detachWhiteboardFromMeetingUi();
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

            function resizeWbCanvas() {}

            if (wbPopup) {
                var wbOpenPopupBtn = document.getElementById('btn-wb-popup-open');
                if (wbOpenPopupBtn) wbOpenPopupBtn.addEventListener('click', openWbPopup);
                var wbClosePopupBtn = document.getElementById('wb-popup-close');
                if (wbClosePopupBtn) wbClosePopupBtn.addEventListener('click', closeWbPopup);
                var wbBackdropEl = document.getElementById('wb-popup-backdrop');
                if (wbBackdropEl) wbBackdropEl.addEventListener('click', closeWbPopup);
                var wbFsBtn = document.getElementById('btn-wb-popup-fullscreen');
                if (wbFsBtn && wbPopupPanel) {
                    wbFsBtn.addEventListener('click', function() {
                        if (!document.fullscreenElement) {
                            wbPopupPanel.requestFullscreen().catch(function() {});
                        } else {
                            try { document.exitFullscreen(); } catch (ex) {}
                        }
                    });
                }

                document.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Escape' && wbPopup && !wbPopup.classList.contains('hidden')) {
                        closeWbPopup();
                    }
                });

                if (wbPopupStage && typeof ResizeObserver !== 'undefined') {
                    new ResizeObserver(function() {
                        if (wbPopup && !wbPopup.classList.contains('hidden')) {
                            nudgeClassroomExLayout();
                        }
                    }).observe(wbPopupStage);
                }

                // تحميل مسبق لمكتبة السبورة حتى تفتح فوراً عند الضغط
                setTimeout(function () {
                    try { ensureExcalidrawVendorLoaded().catch(function () {}); } catch (ePre) {}
                }, 1200);
            }

            function showError() {
                console.error('Classroom room media error');
            }

            function setRecordDdOpen(open) {
                mxRecordDdOpen = !!open;
                if (btnRecordMenu) btnRecordMenu.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (recordDdChevron) recordDdChevron.style.transform = open ? 'rotate(180deg)' : '';
                if (!open) {
                    if (recordDdPanel) recordDdPanel.classList.add('hidden');
                    if (typeof window.mxClearClassroomDropdownPosition === 'function') {
                        window.mxClearClassroomDropdownPosition(recordDdPanel);
                    }
                } else if (recordDdPanel && recordDdWrap && typeof window.mxPositionClassroomDropdown === 'function') {
                    recordDdPanel.style.opacity = '0';
                    recordDdPanel.style.pointerEvents = 'none';
                    recordDdPanel.classList.remove('hidden');
                    recordDdPanel.classList.add('hstk-dd-visible');
                    window.mxPositionClassroomDropdown(recordDdPanel, recordDdWrap, btnRecordMenu);
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            recordDdPanel.style.transition = 'opacity 0.14s ease-out';
                            recordDdPanel.style.opacity = '1';
                            recordDdPanel.style.pointerEvents = '';
                            window.mxPositionClassroomDropdown(recordDdPanel, recordDdWrap, btnRecordMenu);
                        });
                    });
                }
            }

            function setRecordButtonState(recording) {
                if (mxSilentAutoRecording && mxAutoRecBadge) {
                    mxAutoRecBadge.classList.toggle('hidden', !recording);
                }
                if (mxSilentAutoRecording) return;
                if (recordIdleWrap) recordIdleWrap.classList.toggle('hidden', !!recording);
                if (btnRecordStop) btnRecordStop.classList.toggle('hidden', !recording);
                if (btnLectureAddScreen) {
                    btnLectureAddScreen.classList.toggle('hidden', !recording || recordingKind !== 'lecture');
                }
                if (recording) {
                    if (recordIconActive) recordIconActive.className = 'fas fa-stop';
                    if (recordLabelActive) {
                        recordLabelActive.textContent = recordingKind === 'report' ? 'إيقاف — تقرير صوتي' : 'إيقاف — تسجيل المحاضرة';
                    }
                } else {
                    if (recordIconIdle) recordIconIdle.className = 'fas fa-circle-dot text-rose-400';
                    if (recordLabelIdle) recordLabelIdle.textContent = 'تسجيل أو تقرير';
                }
            }

            function setRecordButtonBusy(isBusy) {
                if (btnRecordMenu) {
                    btnRecordMenu.disabled = isBusy;
                    btnRecordMenu.classList.toggle('opacity-70', isBusy);
                    btnRecordMenu.classList.toggle('cursor-not-allowed', isBusy);
                }
                if (btnRecordStop) {
                    btnRecordStop.disabled = isBusy;
                    btnRecordStop.classList.toggle('opacity-70', isBusy);
                    btnRecordStop.classList.toggle('cursor-not-allowed', isBusy);
                }
                if (btnLectureAddScreen) {
                    btnLectureAddScreen.disabled = isBusy;
                    btnLectureAddScreen.classList.toggle('opacity-70', isBusy);
                    btnLectureAddScreen.classList.toggle('cursor-not-allowed', isBusy);
                }
            }

            function setRecordStatus(message, isError) {
                if (mxSilentAutoRecording) return;
                if (!recordStatusChip) return;
                if (!message) {
                    recordStatusChip.classList.add('hidden');
                    recordStatusChip.textContent = '';
                    return;
                }
                recordStatusChip.classList.remove('hidden');
                recordStatusChip.textContent = message;
                recordStatusChip.classList.remove('bg-sky-500/20', 'border-sky-500/30', 'text-sky-200', 'bg-rose-600/20', 'border-rose-500/30', 'text-rose-200');
                if (isError) {
                    recordStatusChip.classList.add('bg-rose-600/20', 'border-rose-500/30', 'text-rose-200');
                } else {
                    recordStatusChip.classList.add('bg-sky-500/20', 'border-sky-500/30', 'text-sky-200');
                }
            }

            function stopCaptureTracks(stream) {
                if (!stream) return;
                try {
                    stream.getTracks().forEach(function(track) { track.stop(); });
                } catch (err) {
                    console.warn('Track stop warning:', err);
                }
            }

            function pickMediaRecorderOptions() {
                var candidates = [
                    'video/webm;codecs=vp9,opus',
                    'video/webm;codecs=vp8,opus',
                    'video/webm'
                ];
                var mimeType = '';
                for (var i = 0; i < candidates.length; i++) {
                    if (MediaRecorder.isTypeSupported(candidates[i])) {
                        mimeType = candidates[i];
                        break;
                    }
                }
                var opts = { videoBitsPerSecond: 1200000, audioBitsPerSecond: 96000 };
                if (mimeType) {
                    opts.mimeType = mimeType;
                }
                return opts;
            }

            function pickAudioRecorderOptions() {
                var candidates = [
                    'audio/webm;codecs=opus',
                    'audio/webm',
                    'audio/ogg;codecs=opus',
                    'audio/ogg',
                    'audio/mp4'
                ];
                for (var i = 0; i < candidates.length; i++) {
                    if (MediaRecorder.isTypeSupported(candidates[i])) {
                        return { mimeType: candidates[i], audioBitsPerSecond: 96000 };
                    }
                }
                return { audioBitsPerSecond: 96000 };
            }

            function normalizeAudioMimeType(mime) {
                var raw = String(mime || '').toLowerCase();
                if (!raw) return 'audio/webm';
                if (raw.indexOf('audio/') === 0) return raw;
                if (raw.indexOf('video/webm') === 0) return 'audio/webm';
                if (raw.indexOf('video/ogg') === 0) return 'audio/ogg';
                if (raw.indexOf('video/mp4') === 0) return 'audio/mp4';
                return 'audio/webm';
            }

            function audioFileNameByMime(mime) {
                var m = normalizeAudioMimeType(mime);
                if (m.indexOf('audio/mpeg') === 0) return 'meeting-audio.mp3';
                if (m.indexOf('audio/mp4') === 0) return 'meeting-audio.m4a';
                if (m.indexOf('audio/ogg') === 0) return 'meeting-audio.ogg';
                return 'meeting-audio.webm';
            }

            function formatBytes(n) {
                var x = Number(n) || 0;
                if (x < 1024) {
                    return x + ' B';
                }
                if (x < 1048576) {
                    return (x / 1024).toFixed(1) + ' KB';
                }
                if (x < 1073741824) {
                    return (x / 1048576).toFixed(1) + ' MB';
                }
                return (x / 1073741824).toFixed(2) + ' GB';
            }

            var mxUploadDbPromise = null;

            function mxOpenUploadDb() {
                if (mxUploadDbPromise) return mxUploadDbPromise;
                mxUploadDbPromise = new Promise(function(resolve, reject) {
                    var req = indexedDB.open('mxClassroomRecordings', 1);
                    req.onerror = function() { reject(req.error); };
                    req.onsuccess = function() { resolve(req.result); };
                    req.onupgradeneeded = function(e) {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('pendingUploads')) {
                            db.createObjectStore('pendingUploads', { keyPath: 'id' });
                        }
                    };
                });
                return mxUploadDbPromise;
            }

            function mxIdbPutJob(job) {
                return mxOpenUploadDb().then(function(db) {
                    return new Promise(function(resolve, reject) {
                        var tx = db.transaction('pendingUploads', 'readwrite');
                        tx.oncomplete = function() { resolve(); };
                        tx.onerror = function() { reject(tx.error); };
                        tx.objectStore('pendingUploads').put(job);
                    });
                });
            }

            function mxIdbDeleteJob(id) {
                return mxOpenUploadDb().then(function(db) {
                    return new Promise(function(resolve, reject) {
                        var tx = db.transaction('pendingUploads', 'readwrite');
                        tx.oncomplete = function() { resolve(); };
                        tx.onerror = function() { reject(tx.error); };
                        tx.objectStore('pendingUploads').delete(id);
                    });
                });
            }

            function mxOpenRecordingUploadTab(jobId) {
                // التسجيل التلقائي للمعلم: لا تفتح تاب الرفع أبداً
                if (mxSilentAutoRecording) return null;
                if (!jobId || !recordingUploadTabBaseUrl) return null;
                var sep = recordingUploadTabBaseUrl.indexOf('?') >= 0 ? '&' : '?';
                var url = recordingUploadTabBaseUrl + sep + 'job=' + encodeURIComponent(jobId);
                try {
                    return window.open(url, '_blank', 'noopener,noreferrer');
                } catch (e) {
                    return null;
                }
            }

            function mxIdbListMeetingJobs() {
                return mxOpenUploadDb().then(function(db) {
                    return new Promise(function(resolve, reject) {
                        var out = [];
                        var tx = db.transaction('pendingUploads', 'readonly');
                        var rq = tx.objectStore('pendingUploads').openCursor();
                        rq.onerror = function() { reject(rq.error); };
                        rq.onsuccess = function(e) {
                            var c = e.target.result;
                            if (!c) {
                                resolve(out);
                                return;
                            }
                            var v = c.value;
                            if (v && Number(v.meetingId) === Number(mxMeetingId)) {
                                out.push(v);
                            }
                            c.continue();
                        };
                    });
                });
            }

            function mxSetUploadBar(percent) {
                var p = percent == null ? 0 : Math.max(0, Math.min(100, Number(percent)));
                if (mxUploadModalBar) mxUploadModalBar.style.width = p + '%';
            }

            function mxRefreshUploadChipText(line, percent) {
                if (!mxUploadChipText) return;
                if (percent != null && !isNaN(percent)) {
                    mxUploadChipText.textContent = line + ' — ' + Math.round(percent) + '%';
                } else {
                    mxUploadChipText.textContent = line;
                }
            }

            function mxShowUploadModal(full) {
                if (mxSilentAutoRecording) return;
                mxUploadModalMinimized = !full;
                if (mxUploadModal) {
                    mxUploadModal.classList.remove('hidden');
                    mxUploadModal.setAttribute('aria-hidden', 'false');
                }
                if (full) {
                    if (mxUploadChip) mxUploadChip.classList.add('hidden');
                } else {
                    if (mxUploadModal) mxUploadModal.classList.add('hidden');
                    if (mxUploadChip) mxUploadChip.classList.remove('hidden');
                }
            }

            function mxHideUploadUi() {
                if (mxUploadModal) {
                    mxUploadModal.classList.add('hidden');
                    mxUploadModal.setAttribute('aria-hidden', 'true');
                }
                if (mxUploadChip) mxUploadChip.classList.add('hidden');
                mxUploadModalMinimized = false;
                mxCurrentUploadJob = null;
                if (mxUploadModalRetry) mxUploadModalRetry.classList.add('hidden');
            }

            function putBlobToPresignedUrl(url, blob, contentType, extraHeaders, onPercent) {
                return new Promise(function(resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('PUT', url, true);
                    xhr.timeout = 0;
                    if (contentType) {
                        xhr.setRequestHeader('Content-Type', contentType);
                    }
                    if (extraHeaders && typeof extraHeaders === 'object') {
                        Object.keys(extraHeaders).forEach(function(k) {
                            try {
                                xhr.setRequestHeader(k, extraHeaders[k]);
                            } catch (hErr) {}
                        });
                    }
                    xhr.upload.onprogress = function(e) {
                        if (typeof onPercent !== 'function') return;
                        if (e.lengthComputable && e.total > 0) {
                            onPercent(Math.min(99, Math.round((e.loaded / e.total) * 100)));
                        }
                    };
                    xhr.onload = function() {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            resolve();
                            return;
                        }
                        reject(new Error('فشل رفع التسجيل (HTTP ' + xhr.status + '). إن تكرر ذلك، تحقق من إعدادات الموقع أو تواصل مع الدعم.'));
                    };
                    xhr.onerror = function() {
                        reject(new Error('انقطع الاتصال أثناء رفع التسجيل.'));
                    };
                    xhr.ontimeout = function() {
                        reject(new Error('انتهت مهلة رفع التسجيل.'));
                    };
                    xhr.send(blob);
                });
            }

            function mxReportUploadProgress(opts) {
                if (mxSilentAutoRecording) return;
                opts = opts || {};
                var t = opts.text || '';
                var pct = opts.percent;
                if (mxUploadModalStatus && (!mxUploadModalMinimized || opts.forceChip)) {
                    mxUploadModalStatus.textContent = t;
                }
                if (pct != null && !isNaN(pct)) {
                    mxSetUploadBar(pct);
                }
                if (mxUploadModalMinimized || opts.toChip) {
                    mxRefreshUploadChipText(t.replace(/\s+/g, ' ').slice(0, 80), pct);
                }
                if (opts.shortStatus) {
                    setRecordStatus(opts.shortStatus, !!opts.isError);
                }
            }

            function uploadRecordedBlobViaFormData(blob, durationSeconds, onProgress) {
                return new Promise(function(resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadRecordingUrl, true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.timeout = 0;

                    xhr.upload.onprogress = function(e) {
                        if (typeof onProgress === 'function') {
                            if (e.lengthComputable && e.total > 0) {
                                var p = Math.min(100, Math.round((e.loaded / e.total) * 100));
                                onProgress({ text: 'جاري الرفع عبر الخادم ' + p + '%...', percent: p, toChip: true });
                            } else if (e.loaded) {
                                onProgress({ text: 'جاري الرفع عبر الخادم... ' + formatBytes(e.loaded), percent: null, toChip: true });
                            }
                        }
                        if (e.lengthComputable && e.total > 0) {
                            var p2 = Math.min(100, Math.round((e.loaded / e.total) * 100));
                            setRecordStatus('جاري الرفع عبر الخادم ' + p2 + '%.', false);
                        }
                    };

                    xhr.onerror = function() {
                        reject(new Error('فشل الاتصال أثناء الرفع. تحقق من الإنترنت وحاول مرة أخرى.'));
                    };
                    xhr.ontimeout = function() {
                        reject(new Error('انتهت مهلة الرفع. جرّب شبكة أسرع أو قسّم المحاضرة إلى جزئين.'));
                    };

                    xhr.onload = function() {
                        var raw = xhr.responseText || '';
                        var data = {};
                        try {
                            data = raw ? JSON.parse(raw) : {};
                        } catch (parseErr) {
                            if (xhr.status === 413) {
                                reject(new Error('حجم الملف يتجاوز حد السيرفر الحالي. جرّب رفع التسجيل عبر اتصال مباشر، أو راجع إعدادات حجم الرفع في الاستضافة.'));
                                return;
                            }
                            reject(new Error('استجابة غير متوقعة من الخادم (رمز ' + xhr.status + ').'));
                            return;
                        }

                        if (xhr.status >= 200 && xhr.status < 300) {
                            resolve({ ok: true, data: data });
                            return;
                        }

                        var msg = (data && data.message) ? data.message : 'فشل رفع التسجيل.';
                        if (data && data.errors) {
                            var firstKey = Object.keys(data.errors)[0];
                            if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                                msg = data.errors[firstKey][0];
                            }
                        }
                        if (xhr.status === 413) {
                            msg = 'حجم الملف كبير جداً لإعدادات السيرفر الحالية.';
                        }
                        reject(new Error(msg));
                    };

                    var formData = new FormData();
                    formData.append('recording', blob, 'meeting-recording.webm');
                    formData.append('duration_seconds', String(durationSeconds || 0));
                    xhr.send(formData);
                });
            }

            async function uploadRecordedBlob(blob, durationSeconds, onProgress) {
                var putSucceeded = false;
                var ct = blob.type || 'audio/webm';
                try {
                    if (typeof onProgress === 'function') {
                        onProgress({ text: 'جاري تجهيز رابط الرفع...', percent: 2, toChip: true });
                    }
                    var presignRes = await fetch(presignRecordingUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            content_type: ct,
                        }),
                    });
                    var presignData = {};
                    try {
                        presignData = await presignRes.json();
                    } catch (je) {
                        presignData = {};
                    }

                    if (presignRes.ok && presignData.direct_upload === false) {
                        return uploadRecordedBlobViaFormData(blob, durationSeconds, onProgress);
                    }

                    if (presignRes.ok && presignData.upload_url && presignData.upload_token && presignData.content_type) {
                        if (typeof onProgress === 'function') {
                            onProgress({ text: 'جاري رفع التسجيل (' + formatBytes(blob.size) + ')...', percent: 5, toChip: true });
                        }
                        await putBlobToPresignedUrl(
                            presignData.upload_url,
                            blob,
                            presignData.content_type,
                            presignData.headers || {},
                            function(p) {
                                if (typeof onProgress === 'function') {
                                    var scaled = 5 + Math.round((p / 100) * 80);
                                    onProgress({ text: 'جاري رفع التسجيل...', percent: scaled, toChip: true });
                                }
                            }
                        );
                        putSucceeded = true;
                        if (typeof onProgress === 'function') {
                            onProgress({ text: 'جاري تأكيد الملف على الخادم...', percent: 90, toChip: true });
                        }

                        var completeRes = await fetch(completeRecordingUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                upload_token: presignData.upload_token,
                                duration_seconds: durationSeconds || 0,
                            }),
                        });
                        var completeData = {};
                        try {
                            completeData = await completeRes.json();
                        } catch (je2) {
                            completeData = {};
                        }
                        if (!completeRes.ok) {
                            var cmsg = (completeData && completeData.message) ? completeData.message : 'فشل ربط الملف بالاجتماع بعد الرفع.';
                            throw new Error(cmsg);
                        }
                        if (typeof onProgress === 'function') {
                            onProgress({ text: 'تم الرفع بنجاح.', percent: 100, toChip: true });
                        }
                        return { ok: true, data: completeData };
                    }
                } catch (err) {
                    if (putSucceeded) {
                        throw err;
                    }
                    console.warn('Direct upload path skipped or failed, using server upload:', err);
                }
                return uploadRecordedBlobViaFormData(blob, durationSeconds, onProgress);
            }

            async function uploadAudioBlob(blob, durationSeconds, onProgress) {
                var effectiveAudioMime = normalizeAudioMimeType(blob && blob.type ? blob.type : 'audio/webm');
                function uploadAudioBlobViaFormData() {
                    return new Promise(function(resolve, reject) {
                        var formData = new FormData();
                        formData.append('recording_audio', blob, audioFileNameByMime(effectiveAudioMime));
                        formData.append('duration_seconds', String(durationSeconds || 0));

                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', uploadAudioUrl, true);
                        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        xhr.upload.onprogress = function(e) {
                            if (typeof onProgress === 'function' && e.lengthComputable && e.total > 0) {
                                var p = Math.min(100, Math.round((e.loaded / e.total) * 100));
                                onProgress({ text: 'جاري رفع التقرير الصوتي عبر الخادم ' + p + '%...', percent: p, toChip: true });
                            }
                        };
                        xhr.onload = function() {
                            var data = {};
                            try { data = xhr.responseText ? JSON.parse(xhr.responseText) : {}; } catch (e) {}
                            if (xhr.status >= 200 && xhr.status < 300) {
                                resolve({ ok: true, data: data });
                                return;
                            }
                            reject(new Error((data && data.message) ? data.message : 'فشل رفع ملف الصوت عبر السيرفر.'));
                        };
                        xhr.onerror = function() {
                            reject(new Error('فشل الاتصال أثناء رفع ملف الصوت.'));
                        };
                        xhr.send(formData);
                    });
                }

                if (typeof onProgress === 'function') {
                    onProgress({ text: 'جاري تجهيز رابط رفع التقرير...', percent: 2, toChip: true });
                }

                var presignRes = await fetch(presignAudioUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        content_type: effectiveAudioMime,
                    }),
                });
                var presignData = {};
                try {
                    presignData = await presignRes.json();
                } catch (je) {
                    presignData = {};
                }

                if (presignRes.ok && presignData.direct_upload === false) {
                    return uploadAudioBlobViaFormData();
                }

                if (!presignRes.ok || !presignData.upload_url || !presignData.upload_token || !presignData.content_type) {
                    return uploadAudioBlobViaFormData();
                }

                if (typeof onProgress === 'function') {
                    onProgress({ text: 'جاري رفع التقرير الصوتي...', percent: 5, toChip: true });
                }
                await putBlobToPresignedUrl(
                    presignData.upload_url,
                    blob,
                    presignData.content_type,
                    presignData.headers || {},
                    function(p) {
                        if (typeof onProgress === 'function') {
                            var scaled = 5 + Math.round((p / 100) * 80);
                            onProgress({ text: 'جاري رفع التسجيل...', percent: scaled, toChip: true });
                        }
                    }
                );

                if (typeof onProgress === 'function') {
                    onProgress({ text: 'جاري تأكيد ملف التقرير...', percent: 90, toChip: true });
                }

                var completeRes = await fetch(completeAudioUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        upload_token: presignData.upload_token,
                        duration_seconds: durationSeconds || 0,
                    }),
                });
                var completeData = {};
                try {
                    completeData = await completeRes.json();
                } catch (je2) {
                    completeData = {};
                }
                if (!completeRes.ok) {
                    throw new Error((completeData && completeData.message) ? completeData.message : 'فشل حفظ ملف الصوت.');
                }
                if (typeof onProgress === 'function') {
                    onProgress({ text: 'تم رفع التقرير الصوتي.', percent: 100, toChip: true });
                }
                return { ok: true, data: completeData };
            }

            function mxMakeUploadJobId() {
                return 'mx-' + mxMeetingId + '-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
            }

            async function mxRunUploadJob(job) {
                mxCurrentUploadJob = job;
                mxLastFailedJob = null;
                if (mxUploadModalRetry) mxUploadModalRetry.classList.add('hidden');
                if (!mxSilentAutoRecording) {
                    mxShowUploadModal(true);
                    if (mxUploadModalTitle) {
                        mxUploadModalTitle.textContent = job.kind === 'report' ? 'جاري رفع التقرير الصوتي' : 'جاري رفع تسجيل المحاضرة';
                    }
                    mxSetUploadBar(0);
                    mxReportUploadProgress({
                        text: 'جاري حفظ نسخة محلية ثم رفع التسجيل...',
                        percent: 1,
                        toChip: true,
                    });
                }

                var persisted = Object.assign({}, job, { status: 'uploading', updatedAt: Date.now() });
                try {
                    await mxIdbPutJob(persisted);
                } catch (idbErr) {
                    console.warn('IndexedDB persist failed:', idbErr);
                }

                var onProg = function(o) {
                    if (!mxSilentAutoRecording) mxReportUploadProgress(o);
                };

                try {
                    if (job.kind === 'report') {
                        await uploadAudioBlob(job.blob, job.durationSeconds, onProg);
                    } else {
                        await uploadRecordedBlob(job.blob, job.durationSeconds, onProg);
                        if (job.secondaryBlob && job.secondaryBlob.size > 0) {
                            if (typeof onProg === 'function') {
                                onProg({ text: 'جاري رفع ملف الصوت المصاحب للفيديو...', percent: 92, toChip: true });
                            }
                            await uploadAudioBlob(job.secondaryBlob, job.durationSeconds, onProg);
                        }
                    }
                    await mxIdbDeleteJob(job.id);
                    if (!mxSilentAutoRecording) {
                        mxReportUploadProgress({ text: 'تم رفع وحفظ التسجيل بنجاح.', percent: 100, toChip: true });
                        setRecordStatus(job.kind === 'report' ? 'تم رفع التقرير الصوتي.' : 'تم رفع تسجيل المحاضرة.', false);
                        setTimeout(function() {
                            mxHideUploadUi();
                        }, 2200);
                    }
                } catch (err) {
                    console.error('mxRunUploadJob:', err);
                    var msg = (err && err.message) ? err.message : 'فشل الرفع.';
                    persisted.status = 'failed';
                    persisted.lastError = msg;
                    persisted.updatedAt = Date.now();
                    try {
                        await mxIdbPutJob(persisted);
                    } catch (idbErr2) {}
                    mxLastFailedJob = persisted;
                    if (!mxSilentAutoRecording) {
                        mxReportUploadProgress({
                            text: msg + '\n\nيمكنك الضغط على «إعادة المحاولة» أو انتظار عودة الإنترنت لإعادة المحاولة تلقائياً.',
                            percent: null,
                            isError: true,
                            shortStatus: 'فشل الرفع — يمكن إعادة المحاولة من النافذة أو الشريط.',
                            toChip: true,
                        });
                        if (mxUploadModalRetry) mxUploadModalRetry.classList.remove('hidden');
                        setRecordStatus('فشل الرفع — أعد المحاولة أو انتظر الاتصال.', true);
                    }
                    throw err;
                }
            }

            function mxTrackUploadPromise(p) {
                mxUploadsInFlight++;
                var tracked = Promise.resolve(p).catch(function () {
                    return null;
                }).finally(function () {
                    mxUploadsInFlight = Math.max(0, mxUploadsInFlight - 1);
                });
                mxUploadChain = mxUploadChain.then(function () { return tracked; }, function () { return tracked; });
                return tracked;
            }

            function mxQueueBlobUpload(blob, durationSeconds, kind, secondaryBlob) {
                var job = {
                    id: mxMakeUploadJobId(),
                    meetingId: mxMeetingId,
                    kind: kind,
                    blob: blob,
                    secondaryBlob: (secondaryBlob && secondaryBlob.size > 0) ? secondaryBlob : null,
                    durationSeconds: durationSeconds || 0,
                    status: 'pending',
                    createdAt: Date.now(),
                };
                // المعلم: رفع صامت في الخلفية — نحفظ في IndexedDB أولاً ثم نرفع
                if (mxSilentAutoRecording) {
                    var silentUpload = mxIdbPutJob(job).catch(function () {
                        return null;
                    }).then(function () {
                        return mxRunUploadJob(Object.assign({}, job, { status: 'pending' })).catch(function (err) {
                            console.warn('Silent auto-upload failed, retrying once:', err);
                            return new Promise(function (resolve) {
                                setTimeout(function () {
                                    mxRunUploadJob(Object.assign({}, job, { status: 'pending' }))
                                        .then(resolve)
                                        .catch(function (err2) {
                                            console.warn('Silent auto-upload retry failed:', err2);
                                            resolve(null);
                                        });
                                }, 2500);
                            });
                        });
                    });
                    return mxTrackUploadPromise(silentUpload);
                }
                mxIdbPutJob(job).then(function() {
                    var w = mxOpenRecordingUploadTab(job.id);
                    if (!w) {
                        setRecordStatus('المتصفح منع التاب الجديد — سيتم الرفع من هذه الصفحة.', true);
                        mxTrackUploadPromise(mxRunUploadJob(Object.assign({}, job, { status: 'pending' })));
                        return;
                    }
                    setRecordStatus('تم فتح تاب الرفع في نافذة جديدة — أكمل الرفع هناك وتابع الاجتماع في هذا التاب.', false);
                }).catch(function(idbErr) {
                    console.warn('IndexedDB before upload tab:', idbErr);
                    mxTrackUploadPromise(mxRunUploadJob(Object.assign({}, job, { status: 'pending' })));
                });
                return Promise.resolve();
            }

            var mxEndMeetingCommitPromise = null;
            var mxEndMeetingStopWatch = null;
            var mxEndMeetingUploadWaitMs = 45000;
            var mxEndMeetingStopWaitMs = 4000;
            var mxRecordingFinalizedForEnd = false;

            function mxSetEndMeetingUiBusy(on) {
                if (!endMeetingBtn) return;
                endMeetingBtn.disabled = !!on;
                endMeetingBtn.classList.toggle('opacity-70', !!on);
                endMeetingBtn.classList.toggle('cursor-not-allowed', !!on);
                if (on) {
                    endMeetingBtn.setAttribute('data-mx-end-busy', '1');
                    var label = endMeetingBtn.querySelector('span.md\\:hidden') || endMeetingBtn.querySelector('span');
                    if (label) label.textContent = 'جاري الإنهاء…';
                }
            }

            function mxResolveEndRedirectUrl(response) {
                try {
                    if (response && response.url) return response.url;
                } catch (e) {}
                return roomExitUrl || '/';
            }

            /**
             * إنهاء الاجتماع فوراً عبر fetch ثم انتظار رفع قصير (بدون تعليق لا نهائي).
             */
            function mxCommitEndMeeting() {
                if (mxEndMeetingCommitPromise) return mxEndMeetingCommitPromise;
                if (!endMeetingForm) {
                    window.location.href = roomExitUrl || '/';
                    return Promise.resolve();
                }

                pendingEndMeetingSubmit = false;
                mxAutoEndingMeeting = true;
                mxSkipEndConfirm = true;
                mxSetEndMeetingUiBusy(true);
                setRecordStatus('جاري إنهاء الاجتماع...', false);

                var action = endMeetingForm.getAttribute('action') || endMeetingForm.action;
                var fd = new FormData(endMeetingForm);

                mxEndMeetingCommitPromise = fetch(action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html,application/xhtml+xml,application/json',
                    },
                    body: fd,
                    redirect: 'follow',
                }).then(function (res) {
                    var redirectTo = mxResolveEndRedirectUrl(res);
                    if (mxUploadsInFlight > 0) {
                        setRecordStatus('تم إنهاء الاجتماع. جاري رفع التسجيل ثم المغادرة...', false);
                        return Promise.race([
                            mxUploadChain.catch(function () { return null; }),
                            new Promise(function (resolve) {
                                setTimeout(resolve, mxEndMeetingUploadWaitMs);
                            }),
                        ]).then(function () {
                            return redirectTo;
                        });
                    }
                    return redirectTo;
                }).catch(function (err) {
                    console.warn('end meeting fetch failed, falling back to form submit', err);
                    try {
                        endMeetingForm.submit();
                    } catch (e2) {
                        window.location.href = roomExitUrl || '/';
                    }
                    return null;
                }).then(function (redirectTo) {
                    if (!redirectTo) return;
                    window.location.href = redirectTo;
                });

                return mxEndMeetingCommitPromise;
            }

            function mxFinishPendingEndMeeting() {
                if (!pendingEndMeetingSubmit) return;
                if (mxEndMeetingStopWatch) {
                    clearTimeout(mxEndMeetingStopWatch);
                    mxEndMeetingStopWatch = null;
                }
                // لا نعلّق على اكتمال الرفع قبل الإنهاء — الإنهاء أولاً ثم رفع بمهلة
                mxCommitEndMeeting();
            }

            function mxGracefulEndMeeting(reason) {
                if (mxAutoEndingMeeting || mxEndMeetingCommitPromise) return;
                mxSkipEndConfirm = true;
                if (!endMeetingForm) {
                    window.location.href = roomExitUrl;
                    return;
                }
                pendingEndMeetingSubmit = true;
                mxRecordingFinalizedForEnd = false;
                mxSetEndMeetingUiBusy(true);
                setRecordStatus((reason || 'جاري حفظ التسجيل وإنهاء الاجتماع') + '...', false);
                if (isRecording) {
                    stopBrowserRecording();
                    return;
                }
                mxFinishPendingEndMeeting();
            }

            function mxAlert(msg) {
                if (mxSilentAutoRecording) {
                    console.warn('[classroom-auto-rec]', msg);
                    return;
                }
                alert(msg);
            }

            function cleanupLectureRecordingVisuals() {
                if (lectureRafId != null) {
                    cancelAnimationFrame(lectureRafId);
                    lectureRafId = null;
                }
                if (lectureCompositeInterval != null) {
                    clearInterval(lectureCompositeInterval);
                    lectureCompositeInterval = null;
                }
                stopCaptureTracks(lectureDisplayStream);
                lectureDisplayStream = null;
                if (lectureDisplayVideo) {
                    try {
                        lectureDisplayVideo.pause();
                        lectureDisplayVideo.srcObject = null;
                    } catch (e) {}
                }
                lectureCanvasStream = null;
                lectureCtx = null;
                lectureCanvas = null;
                try {
                    lectureAudioSources.forEach(function (s) {
                        try { s.disconnect(); } catch (e) {}
                    });
                } catch (e2) {}
                lectureAudioSources = [];
                lectureAudioTrackIds = {};
                // لا نغلق AudioContext بالكامل حتى لا نكسر جلسات لاحقة في نفس الصفحة
            }

            function drawLectureFrameFromSource(sourceElOrCanvas, w, h) {
                if (!sourceElOrCanvas) return false;
                var vw = sourceElOrCanvas.videoWidth || sourceElOrCanvas.width || 0;
                var vh = sourceElOrCanvas.videoHeight || sourceElOrCanvas.height || 0;
                if (!vw || !vh) return false;
                var scale = Math.min(w / vw, h / vh);
                var dw = Math.floor(vw * scale);
                var dh = Math.floor(vh * scale);
                var ox = Math.floor((w - dw) / 2);
                var oy = Math.floor((h - dh) / 2);
                lectureCtx.fillStyle = '#0f172a';
                lectureCtx.fillRect(0, 0, w, h);
                try {
                    lectureCtx.drawImage(sourceElOrCanvas, ox, oy, dw, dh);
                    return true;
                } catch (drawErr) {
                    return false;
                }
            }

            function drawLectureFrameCover(sourceEl, dx, dy, dw, dh) {
                if (!sourceEl || !lectureCtx) return false;
                var vw = sourceEl.videoWidth || sourceEl.width || 0;
                var vh = sourceEl.videoHeight || sourceEl.height || 0;
                if (!vw || !vh || !dw || !dh) return false;
                var scale = Math.max(dw / vw, dh / vh);
                var sw = Math.floor(dw / scale);
                var sh = Math.floor(dh / scale);
                var sx = Math.floor((vw - sw) / 2);
                var sy = Math.floor((vh - sh) / 2);
                try {
                    lectureCtx.drawImage(sourceEl, sx, sy, sw, sh, dx, dy, dw, dh);
                    return true;
                } catch (e) {
                    return false;
                }
            }

            function drawLectureCameraGrid(cameraVideos, w, h) {
                if (!lectureCtx || !Array.isArray(cameraVideos) || !cameraVideos.length) return false;
                var videos = cameraVideos.filter(function (item) {
                    var v = item && (item.video || item);
                    return v && (v.readyState >= 2) && ((v.videoWidth || v.width || 0) > 0);
                });
                if (!videos.length) return false;

                lectureCtx.fillStyle = '#0b1220';
                lectureCtx.fillRect(0, 0, w, h);

                var n = videos.length;
                var cols = n === 1 ? 1 : (n <= 4 ? 2 : 3);
                var rows = Math.ceil(n / cols);
                var gap = 8;
                var cellW = Math.floor((w - gap * (cols + 1)) / cols);
                var cellH = Math.floor((h - gap * (rows + 1)) / rows);
                var drawnAny = false;

                for (var i = 0; i < n; i++) {
                    var col = i % cols;
                    var row = Math.floor(i / cols);
                    var x = gap + col * (cellW + gap);
                    var y = gap + row * (cellH + gap);
                    var item = videos[i];
                    var v = item.video || item;
                    lectureCtx.fillStyle = '#111827';
                    lectureCtx.fillRect(x, y, cellW, cellH);
                    if (drawLectureFrameCover(v, x, y, cellW, cellH)) {
                        drawnAny = true;
                    }
                    var label = (item && item.label) ? String(item.label) : '';
                    if (label) {
                        lectureCtx.fillStyle = 'rgba(2,6,23,0.72)';
                        lectureCtx.fillRect(x + 8, y + cellH - 34, Math.min(cellW - 16, 220), 26);
                        lectureCtx.fillStyle = '#e2e8f0';
                        lectureCtx.font = '600 13px Cairo, Tajawal, sans-serif';
                        lectureCtx.textAlign = 'right';
                        lectureCtx.textBaseline = 'middle';
                        lectureCtx.fillText(label, x + Math.min(cellW - 16, 220) - 4, y + cellH - 21);
                    }
                }
                return drawnAny;
            }

            function lectureCompositeDraw() {
                if (!lectureCtx || !lectureCanvas) return;
                var w = lectureCanvas.width;
                var h = lectureCanvas.height;
                var drawn = false;
                var lk = (typeof window.__mxLkGetRecordCapture === 'function')
                    ? window.__mxLkGetRecordCapture()
                    : null;
                var wbPopupEl = document.getElementById('wb-popup');
                var wbOpen = !!(wbPopupEl && !wbPopupEl.classList.contains('hidden'));

                // 1) شير LiveKit المركّب (شاشة + قلم) — أولوية عند وجوده
                if (lk && lk.canvas && lk.canvas.width > 0) {
                    drawn = drawLectureFrameFromSource(lk.canvas, w, h);
                }
                // 2) فيديو التركيز لنفس الشير
                if (!drawn && lk && lk.videoElement && lk.videoElement.readyState >= 2) {
                    drawn = drawLectureFrameFromSource(lk.videoElement, w, h);
                }
                // 3) مشاركة شاشة مستقلة قديمة (إن وُجدت)
                var v = lectureDisplayVideo;
                if (!drawn && v && v.srcObject && v.readyState >= 2 && v.videoWidth > 0) {
                    drawn = drawLectureFrameFromSource(v, w, h);
                }

                // أثناء الشير: أضف كاميرات صغيرة فوق الزاوية حتى لا يضيع وجه المعلم/الطالب
                if (drawn && lk && lk.screenSharing && lk.cameraVideos && lk.cameraVideos.length) {
                    var cams = lk.cameraVideos.filter(function (item) {
                        var vv = item && (item.video || item);
                        return vv && vv.readyState >= 2 && ((vv.videoWidth || vv.width || 0) > 0);
                    }).slice(0, 3);
                    var pipW = Math.floor(w * 0.22);
                    var pipH = Math.floor(h * 0.22);
                    var gap = 10;
                    cams.forEach(function (item, idx) {
                        var x = w - gap - pipW;
                        var y = h - gap - (pipH + gap) * (idx + 1) + gap;
                        lectureCtx.fillStyle = 'rgba(15,23,42,0.85)';
                        lectureCtx.fillRect(x - 2, y - 2, pipW + 4, pipH + 4);
                        drawLectureFrameCover(item.video || item, x, y, pipW, pipH);
                    });
                }

                // 4) السبورة إن كانت مفتوحة (درس على اللوحة)
                if (!drawn && wbOpen) {
                    var wbHost = document.getElementById('classroom-excalidraw-root')
                        || document.getElementById('mx-excalidraw-root');
                    if (wbHost) {
                        var best = null;
                        var bestArea = 0;
                        wbHost.querySelectorAll('canvas').forEach(function (c) {
                            var area = (c.width || 0) * (c.height || 0);
                            if (area > bestArea) {
                                bestArea = area;
                                best = c;
                            }
                        });
                        if (best && bestArea > 0) {
                            drawn = drawLectureFrameFromSource(best, w, h);
                        }
                    }
                }

                // 5) كاميرات الغرفة من أول لحظة — بدون انتظار شير
                if (!drawn && lk && lk.cameraVideos && lk.cameraVideos.length) {
                    drawn = drawLectureCameraGrid(lk.cameraVideos, w, h);
                }

                if (!drawn) {
                    lectureCtx.fillStyle = '#0f172a';
                    lectureCtx.fillRect(0, 0, w, h);
                    lectureCtx.fillStyle = 'rgba(148,163,184,0.75)';
                    lectureCtx.font = '600 20px Cairo, Tajawal, sans-serif';
                    lectureCtx.textAlign = 'center';
                    lectureCtx.textBaseline = 'middle';
                    lectureCtx.fillText('التسجيل يعمل — جاري التقاط الغرفة…', w / 2, h / 2 - 12);
                    lectureCtx.font = '500 14px Cairo, Tajawal, sans-serif';
                    lectureCtx.fillStyle = 'rgba(148,163,184,0.55)';
                    lectureCtx.fillText('الكاميرات ومشاركة الشاشة والسبورة تُضاف تلقائياً', w / 2, h / 2 + 18);
                }
            }

            function scheduleLectureComposite() {
                if (lectureRafId != null) {
                    cancelAnimationFrame(lectureRafId);
                    lectureRafId = null;
                }
                if (lectureCompositeInterval != null) {
                    clearInterval(lectureCompositeInterval);
                    lectureCompositeInterval = null;
                }
                if (!lectureCtx || !lectureCanvas) return;
                // في التاب الخلفي المتصفح يوقف requestAnimationFrame — نستخدم interval لإبقاء التسجيل حيّاً
                if (document.hidden) {
                    lectureCompositeDraw();
                    lectureCompositeInterval = setInterval(lectureCompositeDraw, 100);
                    return;
                }
                function loop() {
                    lectureCompositeDraw();
                    lectureRafId = requestAnimationFrame(loop);
                }
                loop();
                // تعزيز إضافي حتى مع تباطؤ rAF
                lectureCompositeInterval = setInterval(lectureCompositeDraw, 200);
            }

            function lectureCompositeTick() {
                scheduleLectureComposite();
            }

            function mxConnectTrackToLectureAudio(track) {
                if (!lectureAudioCtx || !lectureAudioDest || !track) return;
                if (track.readyState !== 'live') return;
                var tid = track.id || '';
                if (tid && lectureAudioTrackIds[tid]) return;
                try {
                    var src = lectureAudioCtx.createMediaStreamSource(new MediaStream([track]));
                    var gain = lectureAudioCtx.createGain();
                    gain.gain.value = 0.75;
                    src.connect(gain);
                    gain.connect(lectureAudioDest);
                    lectureAudioSources.push(gain);
                    if (tid) lectureAudioTrackIds[tid] = true;
                } catch (e) {
                    console.warn('lecture audio connect:', e);
                }
            }

            async function mxBuildLectureAudioTrack() {
                var AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) {
                    micStream = await navigator.mediaDevices.getUserMedia({
                        audio: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: false,
                        },
                        video: false,
                    });
                    return micStream.getAudioTracks()[0];
                }
                if (!lectureAudioCtx) lectureAudioCtx = new AudioCtx();
                if (lectureAudioCtx.state === 'suspended') {
                    try { await lectureAudioCtx.resume(); } catch (e) {}
                }
                lectureAudioDest = lectureAudioCtx.createMediaStreamDestination();
                lectureAudioSources = [];
                lectureAudioTrackIds = {};

                // 1) أولوية لمسارات LiveKit (الميكروفون غالباً مشغول بها — لا نطلب getUserMedia أولاً)
                var lk = (typeof window.__mxLkGetRecordCapture === 'function')
                    ? window.__mxLkGetRecordCapture()
                    : null;
                if (lk && lk.audioTracks && lk.audioTracks.length) {
                    lk.audioTracks.forEach(function (t) {
                        try {
                            mxConnectTrackToLectureAudio(t.clone ? t.clone() : t);
                        } catch (e) {
                            mxConnectTrackToLectureAudio(t);
                        }
                    });
                }

                // 2) احتياط: ميكروفون المتصفح إن لم يتوفر صوت من LiveKit بعد
                if (!lectureAudioDest.stream.getAudioTracks().length) {
                    try {
                        micStream = await navigator.mediaDevices.getUserMedia({
                            audio: {
                                echoCancellation: true,
                                noiseSuppression: true,
                                autoGainControl: false,
                            },
                            video: false,
                        });
                        micStream.getAudioTracks().forEach(mxConnectTrackToLectureAudio);
                    } catch (micErr) {
                        console.warn('lecture mic getUserMedia:', micErr);
                        micStream = null;
                    }
                }

                // 3) مسار صامت جداً حتى لا يفشل MediaRecorder إن تأخر الصوت لحظياً
                if (!lectureAudioDest.stream.getAudioTracks().length) {
                    try {
                        var osc = lectureAudioCtx.createOscillator();
                        var gain = lectureAudioCtx.createGain();
                        gain.gain.value = 0.00001;
                        osc.connect(gain);
                        gain.connect(lectureAudioDest);
                        osc.start();
                        lectureAudioSources.push(gain);
                    } catch (eSilent) {}
                }

                var out = lectureAudioDest.stream.getAudioTracks()[0];
                if (!out) {
                    throw new Error('no-audio');
                }
                return out;
            }

            function mxRefreshLectureAudioFromLiveKit() {
                if (recordingKind !== 'lecture' || !lectureAudioDest || !lectureAudioCtx) return;
                var lk = (typeof window.__mxLkGetRecordCapture === 'function')
                    ? window.__mxLkGetRecordCapture()
                    : null;
                if (!lk || !lk.audioTracks) return;
                lk.audioTracks.forEach(function (t) {
                    try {
                        mxConnectTrackToLectureAudio(t.clone ? t.clone() : t);
                    } catch (e) {
                        mxConnectTrackToLectureAudio(t);
                    }
                });
            }

            async function attachLectureDisplayStream() {
                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getDisplayMedia !== 'function') {
                    alert('هذا المتصفح لا يدعم مشاركة الشاشة. جرّب Chrome أو Edge.');
                    return;
                }
                if (lectureDisplayStream) {
                    setRecordStatus('مشاركة الشاشة مفعّلة بالفعل.', false);
                    return;
                }
                var stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
                lectureDisplayStream = stream;
                if (!lectureDisplayVideo) {
                    lectureDisplayVideo = document.createElement('video');
                    lectureDisplayVideo.setAttribute('playsinline', '');
                    lectureDisplayVideo.setAttribute('muted', '');
                    lectureDisplayVideo.muted = true;
                    lectureDisplayVideo.playsInline = true;
                }
                lectureDisplayVideo.srcObject = stream;
                try {
                    await lectureDisplayVideo.play();
                } catch (playErr) {
                    console.warn('Display video play:', playErr);
                }
                stream.getVideoTracks().forEach(function(track) {
                    track.addEventListener('ended', function() {
                        if (recordingKind !== 'lecture') return;
                        stopCaptureTracks(lectureDisplayStream);
                        lectureDisplayStream = null;
                        if (lectureDisplayVideo) {
                            try {
                                lectureDisplayVideo.pause();
                                lectureDisplayVideo.srcObject = null;
                            } catch (e) {}
                        }
                        setRecordStatus('انتهت مشاركة الشاشة — يستمر التسجيل صوتيًا.', false);
                    });
                });
                setRecordStatus('تم ربط الشاشة بالفيديو المسجّل.', false);
            }

            async function startLectureRecording() {
                if (isRecording || (mediaRecorder && mediaRecorder.state === 'recording')) {
                    return;
                }
                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    mxAlert('المتصفح لا يدعم تسجيل الصوت من الميكروفون.');
                    return;
                }
                if (!hasJoinedConference) {
                    mxAlert('ادخل الغرفة أولاً ثم أعد محاولة التسجيل.');
                    return;
                }

                setRecordDdOpen(false);
                setRecordButtonBusy(true);
                recordingKind = 'lecture';
                audioRecorder = null;
                recordedAudioChunks = [];
                stopCaptureTracks(audioOnlyStream);
                audioOnlyStream = null;

                cleanupLectureRecordingVisuals();

                var mixedAudioTrack = null;
                try {
                    mixedAudioTrack = await mxBuildLectureAudioTrack();
                } catch (err) {
                    setRecordButtonBusy(false);
                    recordingKind = null;
                    micStream = null;
                    mxAlert('لم يُسمح بالميكروفون أو تعذر التقاط الصوت. تحقق من أذونات المتصفح.');
                    return;
                }

                lectureCanvas = document.createElement('canvas');
                lectureCanvas.width = 1280;
                lectureCanvas.height = 720;
                lectureCtx = lectureCanvas.getContext('2d', { alpha: false });
                lectureCtx.fillStyle = '#0f172a';
                lectureCtx.fillRect(0, 0, lectureCanvas.width, lectureCanvas.height);

                try {
                    lectureCanvasStream = lectureCanvas.captureStream(15);
                } catch (capErr) {
                    stopCaptureTracks(micStream);
                    micStream = null;
                    cleanupLectureRecordingVisuals();
                    setRecordButtonBusy(false);
                    recordingKind = null;
                    mxAlert('تعذر تهيئة مسار الفيديو. جرّب Chrome أو Edge بإصدار حديث.');
                    return;
                }

                var vidTracks = lectureCanvasStream.getVideoTracks();
                if (!vidTracks.length || !mixedAudioTrack) {
                    stopCaptureTracks(micStream);
                    micStream = null;
                    cleanupLectureRecordingVisuals();
                    setRecordButtonBusy(false);
                    recordingKind = null;
                    mxAlert('تعذر إنشاء مسار الفيديو/الصوت للتسجيل.');
                    return;
                }

                activeRecordingStream = new MediaStream([vidTracks[0], mixedAudioTrack]);
                lectureCompositeTick();
                // التقط الشير الحالي فوراً إن كان شغال
                lectureCompositeDraw();
                mxRefreshLectureAudioFromLiveKit();

                var recorderOpts = pickMediaRecorderOptions();
                try {
                    mediaRecorder = new MediaRecorder(activeRecordingStream, recorderOpts);
                } catch (err) {
                    try {
                        var fallbackLec = recorderOpts.mimeType ? { mimeType: recorderOpts.mimeType } : {};
                        mediaRecorder = new MediaRecorder(activeRecordingStream, fallbackLec);
                    } catch (err2) {
                        stopCaptureTracks(activeRecordingStream);
                        activeRecordingStream = null;
                        stopCaptureTracks(micStream);
                        micStream = null;
                        cleanupLectureRecordingVisuals();
                        setRecordButtonBusy(false);
                        recordingKind = null;
                        mxAlert('تعذر بدء تسجيل الفيديو. جرّب Chrome أو Edge بإصدار حديث.');
                        return;
                    }
                }

                recordedChunks = [];
                recordingStartedAt = Date.now();

                mediaRecorder.addEventListener('dataavailable', function(event) {
                    if (event.data && event.data.size > 0) {
                        recordedChunks.push(event.data);
                    }
                });

                mediaRecorder.addEventListener('stop', async function onLectureRecorderStopped() {
                    isRecording = false;
                    setRecordButtonState(false);
                    recordingKind = null;
                    if (mxEndMeetingStopWatch) {
                        clearTimeout(mxEndMeetingStopWatch);
                        mxEndMeetingStopWatch = null;
                    }

                    stopCaptureTracks(activeRecordingStream);
                    activeRecordingStream = null;
                    stopCaptureTracks(micStream);
                    micStream = null;
                    cleanupLectureRecordingVisuals();

                    var durationSeconds = recordingStartedAt ? Math.max(1, Math.round((Date.now() - recordingStartedAt) / 1000)) : 0;
                    var outType = (mediaRecorder && mediaRecorder.mimeType) ? mediaRecorder.mimeType : 'video/webm';
                    var blob = new Blob(recordedChunks, { type: outType });

                    if (!blob.size) {
                        setRecordButtonBusy(false);
                        setRecordStatus('لا يوجد محتوى في تسجيل المحاضرة.', true);
                        mxAlert('لا يوجد محتوى في التسجيل. تأكد من عمل الميكروفون ثم أعد المحاولة.');
                        recordedChunks = [];
                        mxFinishPendingEndMeeting();
                        return;
                    }

                    setRecordButtonBusy(false);
                    setRecordStatus(mxSilentAutoRecording ? '' : 'تم إيقاف تسجيل المحاضرة. جاري الرفع...', false);

                    // عند الإنهاء: ابدأ الرفع ولا تنتظر اكتماله قبل إغلاق الاجتماع
                    if (pendingEndMeetingSubmit) {
                        if (mxRecordingFinalizedForEnd) return;
                        mxRecordingFinalizedForEnd = true;
                        mxQueueBlobUpload(blob, durationSeconds, 'lecture', null);
                        recordedChunks = [];
                        mxFinishPendingEndMeeting();
                        return;
                    }

                    try {
                        await mxQueueBlobUpload(blob, durationSeconds, 'lecture', null);
                    } catch (uploadErr) {
                        console.warn(uploadErr);
                    }
                    recordedChunks = [];
                    mxFinishPendingEndMeeting();
                });

                mediaRecorder.start(2000);
                isRecording = true;
                setRecordButtonState(true);
                setRecordStatus(mxSilentAutoRecording
                    ? ''
                    : 'جاري تسجيل الحصة (كاميرات + شير + صوت تلقائياً).', false);
                setRecordButtonBusy(false);
            }

            async function startMicRecording() {
                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    mxAlert('المتصفح لا يدعم تسجيل الصوت من الميكروفون.');
                    return;
                }
                if (!hasJoinedConference) {
                    mxAlert('ادخل الغرفة أولاً ثم أعد محاولة التسجيل.');
                    return;
                }

                setRecordDdOpen(false);
                setRecordButtonBusy(true);
                recordingKind = 'report';

                try {
                    activeRecordingStream = await navigator.mediaDevices.getUserMedia({
                        audio: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: false,
                        },
                        video: false,
                    });
                } catch (err) {
                    setRecordButtonBusy(false);
                    recordingKind = null;
                    mxAlert('لم يُسمح بالميكروفون أو تعذر تشغيله. تحقق من أذونات المتصفح.');
                    return;
                }

                var recorderOpts = pickAudioRecorderOptions();
                try {
                    mediaRecorder = new MediaRecorder(activeRecordingStream, recorderOpts);
                } catch (err) {
                    try {
                        var fallback2 = recorderOpts.mimeType ? { mimeType: recorderOpts.mimeType, audioBitsPerSecond: 96000 } : { audioBitsPerSecond: 96000 };
                        mediaRecorder = new MediaRecorder(activeRecordingStream, fallback2);
                    } catch (err2) {
                        stopCaptureTracks(activeRecordingStream);
                        activeRecordingStream = null;
                        setRecordButtonBusy(false);
                        recordingKind = null;
                        mxAlert('تعذر بدء التسجيل الصوتي. جرّب Chrome أو Edge بإصدار حديث.');
                        return;
                    }
                }

                recordedChunks = [];
                recordingStartedAt = Date.now();

                mediaRecorder.addEventListener('dataavailable', function(event) {
                    if (event.data && event.data.size > 0) {
                        recordedChunks.push(event.data);
                    }
                });

                mediaRecorder.addEventListener('stop', async function onReportRecorderStopped() {
                    isRecording = false;
                    setRecordButtonState(false);
                    recordingKind = null;
                    if (mxEndMeetingStopWatch) {
                        clearTimeout(mxEndMeetingStopWatch);
                        mxEndMeetingStopWatch = null;
                    }

                    stopCaptureTracks(activeRecordingStream);
                    activeRecordingStream = null;

                    var durationSeconds = recordingStartedAt ? Math.max(1, Math.round((Date.now() - recordingStartedAt) / 1000)) : 0;
                    var outType = normalizeAudioMimeType((mediaRecorder && mediaRecorder.mimeType) ? mediaRecorder.mimeType : 'audio/webm');
                    var blob = new Blob(recordedChunks, { type: outType });

                    if (!blob.size) {
                        setRecordButtonBusy(false);
                        setRecordStatus('لا يوجد محتوى في التسجيل.', true);
                        mxAlert('لا يوجد محتوى في التسجيل الصوتي.');
                        recordedChunks = [];
                        mxFinishPendingEndMeeting();
                        return;
                    }

                    setRecordButtonBusy(false);
                    setRecordStatus(mxSilentAutoRecording ? '' : 'تم إيقاف تسجيل التقرير. جاري الرفع...', false);
                    if (pendingEndMeetingSubmit) {
                        if (mxRecordingFinalizedForEnd) return;
                        mxRecordingFinalizedForEnd = true;
                        mxQueueBlobUpload(blob, durationSeconds, 'report', null);
                        recordedChunks = [];
                        mxFinishPendingEndMeeting();
                        return;
                    }
                    try {
                        await mxQueueBlobUpload(blob, durationSeconds, 'report', null);
                    } catch (uploadErr) {
                        console.warn(uploadErr);
                    }
                    recordedChunks = [];
                    mxFinishPendingEndMeeting();
                });

                mediaRecorder.start(4000);
                isRecording = true;
                setRecordButtonState(true);
                setRecordStatus('تسجيل تقرير صوتي (يمكنك متابعة الاجتماع)...', false);
                setRecordButtonBusy(false);
            }

            function mxForceStopRecordingForEnd() {
                if (mxEndMeetingCommitPromise || mxRecordingFinalizedForEnd) {
                    mxFinishPendingEndMeeting();
                    return;
                }
                mxRecordingFinalizedForEnd = true;
                if (mxEndMeetingStopWatch) {
                    clearTimeout(mxEndMeetingStopWatch);
                    mxEndMeetingStopWatch = null;
                }
                try {
                    if (mediaRecorder && mediaRecorder.state === 'recording') {
                        mediaRecorder.stop();
                    }
                } catch (e) {}
                isRecording = false;
                setRecordButtonState(false);
                var kind = recordingKind || 'lecture';
                recordingKind = null;
                stopCaptureTracks(activeRecordingStream);
                activeRecordingStream = null;
                stopCaptureTracks(micStream);
                micStream = null;
                cleanupLectureRecordingVisuals();
                var durationSeconds = recordingStartedAt ? Math.max(1, Math.round((Date.now() - recordingStartedAt) / 1000)) : 0;
                var outType = kind === 'report'
                    ? normalizeAudioMimeType((mediaRecorder && mediaRecorder.mimeType) ? mediaRecorder.mimeType : 'audio/webm')
                    : ((mediaRecorder && mediaRecorder.mimeType) ? mediaRecorder.mimeType : 'video/webm');
                var blob = new Blob(recordedChunks || [], { type: outType });
                recordedChunks = [];
                if (blob.size > 0) {
                    mxQueueBlobUpload(blob, durationSeconds, kind === 'report' ? 'report' : 'lecture', null);
                }
                mxFinishPendingEndMeeting();
            }

            function stopBrowserRecording() {
                if (!mediaRecorder || mediaRecorder.state !== 'recording') {
                    mxFinishPendingEndMeeting();
                    return;
                }
                setRecordButtonBusy(true);
                setRecordStatus(recordingKind === 'lecture' ? 'جاري إنهاء تسجيل المحاضرة...' : 'جاري إنهاء التسجيل ودمج المقاطع...', false);
                try {
                    if (typeof mediaRecorder.requestData === 'function') {
                        mediaRecorder.requestData();
                    }
                    if (audioRecorder && audioRecorder.state === 'recording' && typeof audioRecorder.requestData === 'function') {
                        audioRecorder.requestData();
                    }
                } catch (reqErr) {
                    console.warn('requestData:', reqErr);
                }
                if (audioRecorder && audioRecorder.state === 'recording') {
                    audioRecorder.stop();
                }
                if (pendingEndMeetingSubmit) {
                    if (mxEndMeetingStopWatch) clearTimeout(mxEndMeetingStopWatch);
                    mxEndMeetingStopWatch = setTimeout(function () {
                        mxEndMeetingStopWatch = null;
                        if (pendingEndMeetingSubmit && (isRecording || (mediaRecorder && mediaRecorder.state === 'recording'))) {
                            console.warn('[classroom-end] MediaRecorder stop timed out — forcing end');
                            mxForceStopRecordingForEnd();
                        }
                    }, mxEndMeetingStopWaitMs);
                }
                try {
                    mediaRecorder.stop();
                } catch (stopErr) {
                    console.warn('mediaRecorder.stop:', stopErr);
                    if (pendingEndMeetingSubmit) mxForceStopRecordingForEnd();
                }
            }

            if (btnRecordMenu && recordDdPanel && recordDdWrap) {
                btnRecordMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (isRecording) return;
                    setRecordDdOpen(recordDdPanel.classList.contains('hidden'));
                });
                recordDdPanel.querySelectorAll('[data-mx-rec-mode]').forEach(function(el) {
                    el.addEventListener('click', function() {
                        var mode = el.getAttribute('data-mx-rec-mode');
                        setRecordDdOpen(false);
                        if (mode === 'lecture') {
                            startLectureRecording();
                        } else if (mode === 'report') {
                            startMicRecording();
                        }
                    });
                });
                document.addEventListener('mousedown', function (e) {
                    if (!recordDdWrap.contains(e.target)) setRecordDdOpen(false);
                }, true);
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') setRecordDdOpen(false);
                });
                var recResizeT = null;
                window.addEventListener('resize', function () {
                    if (!mxRecordDdOpen || !recordDdPanel || typeof window.mxPositionClassroomDropdown !== 'function') return;
                    if (recResizeT) clearTimeout(recResizeT);
                    recResizeT = setTimeout(function () {
                        recResizeT = null;
                        window.mxPositionClassroomDropdown(recordDdPanel, recordDdWrap, btnRecordMenu);
                    }, 80);
                });
            }

            if (btnRecordStop) {
                btnRecordStop.addEventListener('click', function() {
                    stopBrowserRecording();
                });
            }

            if (endMeetingForm && endMeetingBtn) {
                endMeetingForm.addEventListener('submit', function(e) {
                    if (mxEndMeetingCommitPromise || mxAutoEndingMeeting) {
                        e.preventDefault();
                        return;
                    }
                    // دائماً نتحكم بالمسار حتى لا يعلّق المتصفح على رفع طويل
                    e.preventDefault();
                    pendingEndMeetingSubmit = true;
                    mxRecordingFinalizedForEnd = false;
                    mxSetEndMeetingUiBusy(true);
                    if (isRecording) {
                        setRecordStatus('جاري إيقاف التسجيل وإنهاء الاجتماع...', false);
                        stopBrowserRecording();
                        return;
                    }
                    if (mxUploadsInFlight > 0) {
                        setRecordStatus('جاري إنهاء الاجتماع مع إكمال الرفع...', false);
                    }
                    mxFinishPendingEndMeeting();
                });
            }

            if (btnLectureAddScreen) {
                btnLectureAddScreen.addEventListener('click', function() {
                    if (!isRecording || recordingKind !== 'lecture') return;
                    setRecordButtonBusy(true);
                    attachLectureDisplayStream().then(function() {
                        setRecordButtonBusy(false);
                    }).catch(function() {
                        setRecordButtonBusy(false);
                        if (lectureDisplayStream) {
                            stopCaptureTracks(lectureDisplayStream);
                            lectureDisplayStream = null;
                        }
                        alert('تم الإلغاء أو لم يُسمح بمشاركة الشاشة.');
                    });
                });
            }

            if (mxUploadModalBg) {
                mxUploadModalBg.addEventListener('click', function() {
                    mxShowUploadModal(false);
                });
            }
            if (mxUploadChip) {
                mxUploadChip.addEventListener('click', function() {
                    if (mxLastFailedJob && mxLastFailedJob.id && (mxLastFailedJob.status === 'failed' || mxLastFailedJob.status === 'uploading')) {
                        if (!mxOpenRecordingUploadTab(mxLastFailedJob.id)) {
                            mxShowUploadModal(true);
                            if (mxUploadModalTitle) mxUploadModalTitle.textContent = 'رفع معلّق';
                            if (mxUploadModalStatus) {
                                mxUploadModalStatus.textContent = 'تعذر فتح تاب جديد. اضغط «إعادة المحاولة» للرفع من هذه الصفحة.';
                            }
                            if (mxUploadModalRetry) mxUploadModalRetry.classList.remove('hidden');
                        } else {
                            setRecordStatus('تم فتح تاب الرفع لاستكمال الرفع.', false);
                        }
                        return;
                    }
                    mxShowUploadModal(true);
                });
            }
            if (mxUploadModalRetry) {
                mxUploadModalRetry.addEventListener('click', function() {
                    if (!mxLastFailedJob || !mxLastFailedJob.blob) return;
                    if (mxLastFailedJob.id && mxOpenRecordingUploadTab(mxLastFailedJob.id)) {
                        mxHideUploadUi();
                        setRecordStatus('تم فتح تاب الرفع لإعادة المحاولة.', false);
                        return;
                    }
                    var retryJob = Object.assign({}, mxLastFailedJob, { status: 'pending' });
                    mxRunUploadJob(retryJob).catch(function() {});
                });
            }

            if (!window.__mxClassroomOnlineHook) {
                window.__mxClassroomOnlineHook = true;
                window.addEventListener('online', function() {
                    if (mxLastFailedJob && mxLastFailedJob.id && (mxLastFailedJob.status === 'failed' || mxLastFailedJob.status === 'uploading')) {
                        if (mxOpenRecordingUploadTab(mxLastFailedJob.id)) {
                            setRecordStatus('عاد الاتصال — تم فتح تاب الرفع لإكمال الرفع.', false);
                            return;
                        }
                        var retryJob = Object.assign({}, mxLastFailedJob, { status: 'pending' });
                        mxShowUploadModal(true);
                        if (mxUploadModalStatus) {
                            mxUploadModalStatus.textContent = 'عاد الاتصال — جاري إعادة المحاولة من هذا التاب...';
                        }
                        mxRunUploadJob(retryJob).catch(function() {});
                    }
                });
            }

            mxIdbListMeetingJobs().then(function(list) {
                if (!list || !list.length) return;
                var failed = list.filter(function(j) { return j.status === 'failed' || j.status === 'uploading' || j.status === 'pending'; });
                if (!failed.length) return;
                mxLastFailedJob = failed.sort(function(a, b) { return (b.updatedAt || b.createdAt || 0) - (a.updatedAt || a.createdAt || 0); })[0];
                if (mxSilentAutoRecording && mxLastFailedJob && mxLastFailedJob.blob) {
                    mxTrackUploadPromise(
                        mxRunUploadJob(Object.assign({}, mxLastFailedJob, { status: 'pending' })).catch(function () { return null; })
                    );
                    return;
                }
                if (mxUploadChip && mxUploadChipText) {
                    mxUploadChipText.textContent = 'رفع معلّق — اضغط للمتابعة';
                    mxUploadChip.classList.remove('hidden');
                }
                setRecordStatus('يوجد رفع غير مكتمل — اضغط الشريط بالأسفل لفتح تاب الرفع وإكمال الرفع.', true);
            }).catch(function() {});

            function hidePermissionGate() {
                if (!permissionGate) return;
                permissionGate.classList.add('hidden');
            }

            function setPermissionHelp(message, isError) {
                if (!permissionHelp) return;
                permissionHelp.textContent = message || '';
                permissionHelp.className = 'mt-4 text-xs ' + (isError ? 'text-rose-300' : 'text-slate-400');
            }

            function mapMediaErrorToArabic(err) {
                var code = err && err.name ? String(err.name) : '';
                if (code === 'NotAllowedError' || code === 'PermissionDeniedError') {
                    return 'المتصفح رفض الإذن. افتح رمز القفل بجانب الرابط ثم اسمح للكاميرا والميكروفون.';
                }
                if (code === 'NotFoundError' || code === 'DevicesNotFoundError') {
                    return 'لا توجد كاميرا أو ميكروفون متصل بالجهاز.';
                }
                if (code === 'NotReadableError' || code === 'TrackStartError') {
                    return 'تعذر تشغيل الكاميرا/الميكروفون (قد يكون مستخدمًا في تطبيق آخر مثل Zoom/Teams).';
                }
                if (code === 'OverconstrainedError' || code === 'ConstraintNotSatisfiedError') {
                    return 'إعدادات الجهاز غير متوافقة مع طلب الفيديو/الصوت. جرّب إغلاق الكاميرا من التطبيقات الأخرى.';
                }
                if (code === 'SecurityError') {
                    return 'حظر أمني من المتصفح. تأكد من فتح الموقع عبر HTTPS أو localhost.';
                }
                return 'تعذر الوصول للكاميرا أو الميكروفون. جرّب مرة أخرى أو تحقق من إعدادات المتصفح.';
            }


            var hasJoinedConference = true;
            var api = null;
            var permissionGate = document.getElementById('permission-gate');
            if (permissionGate) permissionGate.classList.add('hidden');

            function tickMeetingTimer() {
                if (!meetingEndsAt || (!timerChip && !timerChipMobile)) return;
                if (mxAutoEndingMeeting) return;
                var end = new Date(meetingEndsAt).getTime();
                var nowTs = Date.now();
                var diff = end - nowTs;
                if (diff <= 0) {
                    if (timerChip) {
                        timerChip.textContent = 'انتهت المدة — جاري الحفظ';
                        timerChip.classList.remove('bg-amber-500/20', 'border-amber-500/30', 'text-amber-200');
                        timerChip.classList.add('bg-rose-600/20', 'border-rose-500/30', 'text-rose-200');
                    }
                    if (timerChipMobile) {
                        timerChipMobile.textContent = 'حفظ وإنهاء';
                        timerChipMobile.classList.remove('bg-amber-500/20', 'border-amber-500/30', 'text-amber-200');
                        timerChipMobile.classList.add('bg-rose-600/20', 'border-rose-500/30', 'text-rose-200');
                    }
                    // لا نغادر الصفحة قبل إيقاف/رفع التسجيل ثم إنهاء الاجتماع رسمياً
                    mxGracefulEndMeeting('انتهت المدة المسموح بها — جاري حفظ التسجيل وإنهاء الاجتماع');
                    return;
                }
                var mins = Math.floor(diff / 60000);
                var secs = Math.floor((diff % 60000) / 1000);
                var fullText = 'الوقت المتبقي: ' + mins + ':' + String(secs).padStart(2, '0');
                var shortText = mins + ':' + String(secs).padStart(2, '0');
                if (timerChip) timerChip.textContent = fullText;
                if (timerChipMobile) timerChipMobile.textContent = shortText;
            }
            setInterval(tickMeetingTimer, 1000);
            tickMeetingTimer();

            document.addEventListener('visibilitychange', function () {
                if (isRecording && recordingKind === 'lecture' && lectureCtx && lectureCanvas) {
                    scheduleLectureComposite();
                }
            });

            window.addEventListener('beforeunload', function (e) {
                if (mxAutoEndingMeeting) return;
                if (isRecording || mxUploadsInFlight > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });


            @unless(!empty($academicObserverMode))
            function attachCurriculumPresenter() {
                if (!window.MxClassroomCurriculumPresenter || window.__mxCurriculumPresenter) return;
                window.__mxCurriculumPresenter = window.MxClassroomCurriculumPresenter.attach(null, {
                    isHost: {{ !empty($canManageMeeting) ? 'true' : 'false' }},
                    catalogUrl: @json($mxRoute($rp . 'classroom.curriculum.catalog', $meeting)),
                    presentUrl: @json($mxRoute($rp . 'classroom.curriculum.present', $meeting)),
                    stateUrl: @json($mxRoute($rp . 'classroom.curriculum.state', $meeting)),
                    slideUpdateUrl: @json($mxRoute($rp . 'classroom.curriculum.slide.update', $meeting)),
                    stopUrl: @json($mxRoute($rp . 'classroom.curriculum.stop', $meeting)),
                    pollIntervalMs: 1500,
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', attachCurriculumPresenter);
            } else {
                attachCurriculumPresenter();
            }
            setTimeout(attachCurriculumPresenter, 50);
            @endunless

            if (mxSilentAutoRecording && !mxAutoRecordStarted) {
                mxAutoRecordStarted = true;
                var mxSilentRecAttempts = 0;
                var mxSilentRecMaxAttempts = 30;
                var mxSilentRecInFlight = false;
                var mxSilentRecTimer = null;

                function mxScheduleSilentRecording(delayMs) {
                    if (!mxSilentAutoRecording || isRecording || mxSilentRecInFlight) return;
                    if (mxSilentRecAttempts >= mxSilentRecMaxAttempts) return;
                    if (mxSilentRecTimer) clearTimeout(mxSilentRecTimer);
                    mxSilentRecTimer = setTimeout(mxTryStartSilentRecording, delayMs || 0);
                }

                function mxTryStartSilentRecording() {
                    mxSilentRecTimer = null;
                    if (!mxSilentAutoRecording || isRecording || mxSilentRecInFlight) return;
                    if (mxSilentRecAttempts >= mxSilentRecMaxAttempts) return;
                    mxSilentRecAttempts += 1;

                    var lkReady = typeof window.__mxLkIsConnected !== 'function' || window.__mxLkIsConnected();
                    var lk = (typeof window.__mxLkGetRecordCapture === 'function')
                        ? window.__mxLkGetRecordCapture()
                        : null;
                    var hasVisual = !!(lk && (
                        (lk.cameraVideos && lk.cameraVideos.length) ||
                        lk.canvas ||
                        lk.videoElement ||
                        lk.screenSharing
                    ));
                    var hasAudio = !!(lk && lk.audioTracks && lk.audioTracks.length);

                    // انتظر اتصال الغرفة أو ظهور كاميرا/صوت قبل البدء (مع حد أقصى للمحاولات)
                    if ((!lkReady || (!hasVisual && !hasAudio)) && mxSilentRecAttempts < mxSilentRecMaxAttempts) {
                        mxScheduleSilentRecording(1000);
                        return;
                    }

                    mxSilentRecInFlight = true;
                    startLectureRecording().then(function () {
                        mxSilentRecInFlight = false;
                        if (!isRecording && mxSilentRecAttempts < mxSilentRecMaxAttempts) {
                            mxScheduleSilentRecording(2000);
                        }
                    }).catch(function (err) {
                        mxSilentRecInFlight = false;
                        console.warn('[classroom-auto-rec] start failed', err);
                        if (mxSilentRecAttempts < mxSilentRecMaxAttempts) {
                            mxScheduleSilentRecording(2000);
                        }
                    });
                }

                // ابدأ المحاولة بعد مهلة قصيرة؛ الدالة تنتظر جاهزية LiveKit داخلياً
                mxScheduleSilentRecording(1500);

                // إن اتصلت الغرفة لاحقاً وما زال التسجيل متوقفاً — أعد المحاولة (debounced)
                window.addEventListener('mx-lk-record-capture-changed', function () {
                    if (mxSilentAutoRecording && !isRecording && !mxSilentRecInFlight) {
                        mxScheduleSilentRecording(400);
                    }
                });
            }

            window.addEventListener('mx-lk-record-capture-changed', function () {
                if (recordingKind !== 'lecture' || !isRecording) return;
                lectureCompositeDraw();
                mxRefreshLectureAudioFromLiveKit();
            });
            // إعادة مزامنة دورية أثناء التسجيل حتى لو فات حدث الشير
            setInterval(function () {
                if (recordingKind !== 'lecture' || !isRecording) return;
                lectureCompositeDraw();
                mxRefreshLectureAudioFromLiveKit();
            }, 1500);
        })();
    </script>
</body>
</html>
