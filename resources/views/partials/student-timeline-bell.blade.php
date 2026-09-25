@php
    $bellUnread = 0;
    $bellItems = [];
    $bellIsInstructor = auth()->check() && (auth()->user()->isInstructor() || auth()->user()->isTeacher());
    $bellGoRoute = $bellIsInstructor && Route::has('instructor.notifications.go')
        ? 'instructor.notifications.go'
        : 'notifications.go';
    $bellShowRoute = $bellIsInstructor && Route::has('instructor.notifications.index')
        ? 'instructor.notifications.index'
        : (Route::has('notifications.show') ? 'notifications.show' : 'notifications');
    $bellInboxRoute = $bellIsInstructor && Route::has('instructor.notifications.index')
        ? 'instructor.notifications.index'
        : 'notifications';
    $bellPollRoute = $bellIsInstructor && Route::has('instructor.notifications.unread-count')
        ? 'instructor.notifications.unread-count'
        : (Route::has('notifications.nav-poll') ? 'notifications.nav-poll' : null);
    $bellMarkAllRoute = $bellIsInstructor && Route::has('instructor.notifications.mark-all-read')
        ? 'instructor.notifications.mark-all-read'
        : 'notifications.mark-all-read';
    if (auth()->check()) {
        try {
            $bellAudience = $bellIsInstructor ? 'instructor' : 'student';
            $bellBase = auth()->user()->customNotifications()
                ->where(function ($q) use ($bellAudience) {
                    $q->whereNull('audience')->orWhere('audience', $bellAudience);
                })
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            $bellUnread = (clone $bellBase)->unread()->count();
            $bellItems = (clone $bellBase)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
                ->map(function ($n) use ($bellGoRoute, $bellShowRoute) {
                    $href = '#';
                    if ($n->action_url) {
                        $href = Route::has($bellGoRoute)
                            ? route($bellGoRoute, $n)
                            : $n->action_url;
                    } elseif (Route::has($bellShowRoute)) {
                        $href = $bellShowRoute === 'instructor.notifications.index'
                            ? route($bellShowRoute)
                            : route($bellShowRoute, $n);
                    }

                    return [
                        'id' => $n->id,
                        'title' => $n->title,
                        'message' => \Illuminate\Support\Str::limit((string) $n->message, 110),
                        'is_read' => (bool) $n->is_read,
                        'href' => $href,
                        'time' => optional($n->created_at)->diffForHumans(),
                        'icon' => $n->type_icon,
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            $bellUnread = 0;
            $bellItems = [];
        }
    }
    $bellConfig = [
        'pollUrl' => $bellPollRoute && Route::has($bellPollRoute) ? route($bellPollRoute) : null,
        'markAllUrl' => Route::has($bellMarkAllRoute) ? route($bellMarkAllRoute) : null,
        'inboxUrl' => Route::has($bellInboxRoute) ? route($bellInboxRoute) : '#',
        'unread' => $bellUnread,
        'items' => $bellItems,
        'email' => auth()->user()?->email,
        'labels' => [
            'title' => __('student_timeline.nav_messages'),
            'empty' => __('student_timeline.no_events'),
            'viewAll' => __('student_timeline.see_all'),
            'markAll' => app()->getLocale() === 'ar' ? 'تعيين الكل كمقروء' : 'Mark all read',
            'sentTo' => app()->getLocale() === 'ar' ? 'يُرسل أيضاً إلى' : 'Also emailed to',
            'unread' => app()->getLocale() === 'ar' ? 'غير مقروء' : 'unread',
        ],
    ];
@endphp
<div class="st-bell-wrap" data-st-bell='@json($bellConfig)'>
    <button type="button" class="st-bell" data-st-bell-toggle aria-expanded="false" aria-haspopup="true" aria-controls="stBellPanel" aria-label="{{ __('student_timeline.nav_messages') }}">
        <img src="{{ asset('img/student-timeline/bell.svg') }}" alt="" width="20" height="20">
        <span class="st-bell__dot" data-st-bell-dot hidden aria-hidden="true"></span>
        <span class="st-bell__count" data-st-bell-count hidden>0</span>
    </button>
    <div class="st-bell-backdrop" data-st-bell-backdrop hidden aria-hidden="true"></div>
    <div class="st-bell-panel" id="stBellPanel" data-st-bell-panel hidden role="dialog" aria-modal="true" aria-label="{{ __('student_timeline.nav_messages') }}">
        <div class="st-bell-panel__head">
            <div>
                <p class="st-bell-panel__title">{{ __('student_timeline.nav_messages') }}</p>
                <p class="st-bell-panel__sub" data-st-bell-sub></p>
            </div>
            <div class="st-bell-panel__actions">
                <button type="button" class="st-bell-panel__link" data-st-bell-mark-all>{{ app()->getLocale() === 'ar' ? 'قراءة الكل' : 'Read all' }}</button>
                <a href="{{ $bellConfig['inboxUrl'] }}" class="st-bell-panel__link">{{ __('student_timeline.see_all') }}</a>
                <button type="button" class="st-bell-panel__close" data-st-bell-close aria-label="{{ app()->getLocale() === 'ar' ? 'إغلاق' : 'Close' }}">
                    <i class="fas fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="st-bell-panel__list" data-st-bell-list></div>
        <div class="st-bell-panel__foot" data-st-bell-email></div>
    </div>
</div>
<script>
(function () {
    var wraps = document.querySelectorAll('[data-st-bell]');
    wraps.forEach(function (wrap) {
        if (wrap.__stBellBound) return;
        wrap.__stBellBound = true;
        var cfg = {};
        try { cfg = JSON.parse(wrap.getAttribute('data-st-bell') || '{}'); } catch (e) { cfg = {}; }
        var btn = wrap.querySelector('[data-st-bell-toggle]');
        var panel = wrap.querySelector('[data-st-bell-panel]');
        var backdrop = wrap.querySelector('[data-st-bell-backdrop]');
        var list = wrap.querySelector('[data-st-bell-list]');
        var dot = wrap.querySelector('[data-st-bell-dot]');
        var countEl = wrap.querySelector('[data-st-bell-count]');
        var sub = wrap.querySelector('[data-st-bell-sub]');
        var emailEl = wrap.querySelector('[data-st-bell-email]');
        var markAllBtn = wrap.querySelector('[data-st-bell-mark-all]');
        var closeBtn = wrap.querySelector('[data-st-bell-close]');
        var labels = cfg.labels || {};
        var unread = Number(cfg.unread) || 0;
        var items = Array.isArray(cfg.items) ? cfg.items.slice() : [];
        var lastSynced = unread;
        var firstPoll = true;
        var audioUnlocked = false;
        var open = false;
        var scrollY = 0;

        function csrf() {
            var t = document.querySelector('meta[name="csrf-token"]');
            return t ? t.getAttribute('content') : '';
        }

        function isMobile() {
            return window.matchMedia('(max-width: 768px)').matches;
        }

        function lockScroll(on) {
            if (!isMobile()) {
                document.documentElement.classList.remove('st-bell-lock');
                document.body.classList.remove('st-bell-lock');
                document.body.style.top = '';
                if (!on && scrollY) {
                    window.scrollTo(0, scrollY);
                    scrollY = 0;
                }
                return;
            }
            if (on) {
                scrollY = window.scrollY || window.pageYOffset || 0;
                document.documentElement.classList.add('st-bell-lock');
                document.body.classList.add('st-bell-lock');
                document.body.style.top = '-' + scrollY + 'px';
            } else {
                document.documentElement.classList.remove('st-bell-lock');
                document.body.classList.remove('st-bell-lock');
                document.body.style.top = '';
                window.scrollTo(0, scrollY || 0);
                scrollY = 0;
            }
        }

        function render() {
            if (dot) dot.hidden = unread <= 0;
            if (countEl) {
                if (unread > 0) {
                    countEl.hidden = false;
                    countEl.textContent = unread > 99 ? '99+' : String(unread);
                } else {
                    countEl.hidden = true;
                }
            }
            if (sub) {
                sub.textContent = unread > 0
                    ? ((labels.unread ? (unread + ' ' + labels.unread) : (unread + ' unread')))
                    : (labels.empty || '');
            }
            if (emailEl && cfg.email) {
                emailEl.textContent = (labels.sentTo || 'Email') + ': ' + cfg.email;
            }
            if (!list) return;
            if (!items.length) {
                list.innerHTML = '<div class="st-bell-panel__empty">' + (labels.empty || '—') + '</div>';
                return;
            }
            list.innerHTML = items.map(function (item) {
                return '<a class="st-bell-item' + (item.is_read ? '' : ' is-unread') + '" href="' + (item.href || '#') + '">' +
                    '<span class="st-bell-item__icon"><i class="' + (item.icon || 'fas fa-bell') + '" aria-hidden="true"></i></span>' +
                    '<span class="st-bell-item__body">' +
                    '<span class="st-bell-item__title"></span>' +
                    '<span class="st-bell-item__msg"></span>' +
                    '<span class="st-bell-item__time"></span>' +
                    '</span></a>';
            }).join('');
            Array.prototype.forEach.call(list.querySelectorAll('.st-bell-item'), function (el, i) {
                var item = items[i];
                if (!item) return;
                el.querySelector('.st-bell-item__title').textContent = item.title || '';
                el.querySelector('.st-bell-item__msg').textContent = item.message || '';
                el.querySelector('.st-bell-item__time').textContent = item.time || '';
            });
        }

        function setOpen(next) {
            open = !!next;
            if (panel) panel.hidden = !open;
            if (backdrop) backdrop.hidden = !open;
            if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            wrap.classList.toggle('is-open', open);
            lockScroll(open);
        }

        function playBeep() {
            if (!audioUnlocked) return;
            try {
                var Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return;
                var ctx = new Ctx();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.setValueAtTime(0.0001, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.1, ctx.currentTime + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.22);
                setTimeout(function () { ctx.close(); }, 350);
            } catch (e) {}
        }

        async function poll() {
            if (!cfg.pollUrl) return;
            try {
                var res = await fetch(cfg.pollUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf()
                    },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                var data = await res.json();
                var next = Number(data.unread_count) || 0;
                if (!firstPoll && next > lastSynced) playBeep();
                firstPoll = false;
                lastSynced = next;
                unread = next;
                items = Array.isArray(data.items) ? data.items : [];
                if (data.email) cfg.email = data.email;
                render();
            } catch (e) {}
        }

        async function markAll() {
            if (!cfg.markAllUrl) return;
            try {
                var res = await fetch(cfg.markAllUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf()
                    },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                unread = 0;
                items = items.map(function (it) { return Object.assign({}, it, { is_read: true }); });
                lastSynced = 0;
                render();
            } catch (e) {}
        }

        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                audioUnlocked = true;
                setOpen(!open);
            });
        }
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                markAll();
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                setOpen(false);
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () { setOpen(false); });
        }
        document.addEventListener('click', function (e) {
            if (!open) return;
            if (isMobile()) return;
            if (!wrap.contains(e.target)) setOpen(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
        window.addEventListener('resize', function () {
            if (!open) return;
            lockScroll(true);
        });
        document.body.addEventListener('click', function () { audioUnlocked = true; }, { once: true });

        render();
        poll();
        setInterval(poll, 5000);
    });
})();
</script>
