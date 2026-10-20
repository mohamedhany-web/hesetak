@php $empLocale = app()->getLocale(); $empRtl = $empLocale === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ $empLocale }}" dir="{{ $empRtl ? 'rtl' : 'ltr' }}" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('auth.dashboard')) - {{ config('app.name') }}</title>
    <script>
        (function() {
            var s = localStorage.getItem('theme');
            var d = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (s === 'dark' || (!s && d)) {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            }
        })();
    </script>
    
    <!-- Favicon -->
    @include('partials.favicon-links')
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    
    <script>
        document.addEventListener('alpine:init', function () {
            Alpine.data('employeeNavNotifications', function (config) {
                config = config || {};
                var initialUnread = Number(config.unread) || 0;
                var initialItems = Array.isArray(config.items) ? config.items : [];
                var pollUrl = config.pollUrl || '';
                return {
                    openNotif: false,
                    unread: initialUnread,
                    lastSynced: initialUnread,
                    firstPoll: true,
                    items: initialItems.slice(),
                    pollUrl: pollUrl,
                    audioUnlocked: false,
                    _pollTimer: null,
                    init: function () {
                        var self = this;
                        document.body.addEventListener('click', function () { self.audioUnlocked = true; }, { once: true });
                        document.body.addEventListener('keydown', function () { self.audioUnlocked = true; }, { once: true });
                        this._pollTimer = setInterval(function () { self.poll(); }, 8000);
                        this.poll();
                    },
                    destroy: function () {
                        if (this._pollTimer) clearInterval(this._pollTimer);
                    },
                    poll: async function () {
                        if (!this.pollUrl) return;
                        try {
                            var token = document.querySelector('meta[name="csrf-token"]');
                            var res = await fetch(this.pollUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                                },
                                credentials: 'same-origin'
                            });
                            if (!res.ok) return;
                            var d = await res.json();
                            if (!this.firstPoll && d.unread_count > this.lastSynced) {
                                this.playBeep();
                            }
                            this.firstPoll = false;
                            this.lastSynced = d.unread_count;
                            this.unread = d.unread_count;
                            this.items = Array.isArray(d.items) ? d.items : [];
                        } catch (e) { /* ignore */ }
                    },
                    playBeep: function () {
                        if (!this.audioUnlocked) return;
                        try {
                            var Ctx = window.AudioContext || window.webkitAudioContext;
                            if (!Ctx) return;
                            var ctx = new Ctx();
                            var osc = ctx.createOscillator();
                            var gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.value = 880;
                            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.22);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(ctx.currentTime);
                            osc.stop(ctx.currentTime + 0.25);
                            setTimeout(function () { ctx.close(); }, 400);
                        } catch (e) { /* ignore */ }
                    }
                };
            });
        });
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        * {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
        }
        /* إخفاء شريط التمرير في سايدبار الموظف مع بقاء التمرير يعمل */
        .employee-sidebar-nav {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .employee-sidebar-nav::-webkit-scrollbar {
            display: none;
        }

        /* الوضع الداكن — نصوص وبطاقات متناسقة (الهيدر + main) */
        html.dark header.sticky {
            background: linear-gradient(to left, #0f172a, #1e293b, #0f172a) !important;
            border-bottom-color: #334155 !important;
        }
        html.dark main.flex-1 {
            background: #0f172a !important;
            color: #e2e8f0;
        }
        html.dark main h1, html.dark main h2, html.dark main h3, html.dark main h4 {
            color: #f1f5f9 !important;
        }
        html.dark main .bg-white, html.dark header .bg-white {
            background: #1e293b !important;
            border-color: #475569 !important;
        }
        html.dark main .bg-gray-50,
        html.dark main .bg-gray-100 { background-color: #0f172a !important; }
        html.dark main .min-h-screen.bg-gray-50,
        html.dark main .min-h-screen.bg-white { background-color: #0f172a !important; }
        html.dark .focus-within\:bg-white:focus-within { background-color: #1e293b !important; }
        html.dark [class*="text-slate-8"], html.dark [class*="text-slate-9"], html.dark [class*="text-slate-7"],
        html.dark [class*="text-gray-8"], html.dark [class*="text-gray-9"], html.dark [class*="text-gray-7"] { color: #e2e8f0 !important; }
        html.dark [class*="text-slate-6"], html.dark [class*="text-slate-5"],
        html.dark [class*="text-gray-6"], html.dark [class*="text-gray-5"] { color: #94a3b8 !important; }
        html.dark main [class*="text-[#1C"], html.dark main [class*="text-[#1F3"], html.dark main [class*="text-[#1F2"], html.dark main [class*="text-[#283593]"] { color: #f1f5f9 !important; }
        html.dark main input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]),
        html.dark main textarea,
        html.dark main select { background: #334155 !important; border-color: #475569 !important; color: #e2e8f0 !important; }
        html.dark main table th, html.dark main table td { color: #e2e8f0; border-color: #334155; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-slate-900 dark:text-slate-100 transition-colors">
    <div x-data="{ sidebarOpen: window.innerWidth >= 1024 }" 
         x-init="
          // إغلاق السايدبار عند النقر على الروابط
          window.addEventListener('close-sidebar', () => {
              sidebarOpen = false;
          });
          
          // إغلاق السايدبار عند تغيير حجم النافذة إلى desktop
          let resizeTimeout;
          window.addEventListener('resize', () => {
              clearTimeout(resizeTimeout);
              resizeTimeout = setTimeout(() => {
                  if (window.innerWidth >= 1024) {
                      sidebarOpen = false;
                  }
              }, 150);
          });
      "
      @close-sidebar.window="sidebarOpen = false">
    <div class="flex min-h-screen lg:h-screen overflow-x-hidden">
        <!-- Sidebar - Fixed -->
        <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:right-0 lg:z-20 flex-shrink-0 inset-y-0">
            @include('layouts.employee-sidebar')
        </aside>

        <!-- Mobile sidebar -->
        <div x-show="sidebarOpen" 
             x-cloak
             @click.away="if (window.innerWidth < 1024) sidebarOpen = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 lg:hidden"
             style="display: none;">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="sidebarOpen = false"></div>
            <div class="absolute inset-y-0 right-0 flex flex-col w-64 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 shadow-2xl transform transition-transform duration-150 ease-out border-l border-slate-700/50"
                 :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'">
                <div class="absolute top-4 left-4 z-50">
                    <button @click="sidebarOpen = false" class="flex items-center justify-center h-10 w-10 rounded-full bg-slate-700/50 hover:bg-slate-600/50 text-slate-200 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                @include('layouts.employee-sidebar')
            </div>
        </div>

        <!-- Main content area -->
        <div class="flex flex-col flex-1 min-w-0 lg:pr-64 w-full lg:h-screen">
            <!-- Top navigation -->
            <header class="sticky top-0 z-30 flex-shrink-0 flex h-14 sm:h-16 bg-gradient-to-r from-slate-50 via-blue-50 to-slate-100 shadow-lg border-b border-slate-200/50 backdrop-blur-sm">
                <button @click="sidebarOpen = true" class="px-3 sm:px-4 border-l border-slate-200/50 text-slate-700 hover:bg-slate-100/50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-400 lg:hidden transition-colors">
                    <i class="fas fa-bars text-base sm:text-lg"></i>
                </button>
                
                <div class="flex-1 px-3 sm:px-6 flex justify-between items-center gap-2">
                    <div class="flex-1 flex items-center gap-2 sm:gap-4 min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">
                            @yield('header', 'لوحة الموظف')
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-2 sm:gap-4">
                        @php
                            $empUserId = auth()->id();
                            $empUnreadCount = \App\Models\Notification::where('user_id', $empUserId)
                                ->where('audience', 'employee')
                                ->where('is_read', false)
                                ->whereNull('read_at')
                                ->valid()
                                ->count();
                            $empUnreadList = \App\Models\Notification::where('user_id', $empUserId)
                                ->where('audience', 'employee')
                                ->where('is_read', false)
                                ->whereNull('read_at')
                                ->valid()
                                ->orderByDesc('created_at')
                                ->limit(5)
                                ->get();
                            $empNavItems = $empUnreadList->map(fn ($n) => [
                                'id' => $n->id,
                                'title' => $n->title,
                                'message' => $n->message,
                                'priority' => $n->priority,
                                'href' => $n->action_url ? route('employee.notifications.go', $n) : route('employee.notifications.show', $n),
                                'time' => $n->created_at->diffForHumans(),
                                'icon' => $n->type_icon,
                            ])->values();
                            $empNavBellConfig = [
                                'unread' => (int) $empUnreadCount,
                                'items' => $empNavItems->all(),
                                'pollUrl' => route('employee.api.nav-notifications'),
                            ];
                        @endphp
                        <div class="relative"
                             x-data="employeeNavNotifications({{ \Illuminate\Support\Js::from($empNavBellConfig) }})"
                             @click.away="openNotif = false">
                            <button type="button"
                                    @click="openNotif = !openNotif"
                                    class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                    aria-label="{{ $empRtl ? 'الإشعارات' : 'Notifications' }}">
                                <i class="fas fa-bell text-lg"></i>
                                <span x-show="unread > 0" x-cloak
                                      class="absolute -top-0.5 {{ $empRtl ? '-left-0.5' : '-right-0.5' }} min-w-[18px] h-[18px] bg-rose-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center ring-2 ring-white dark:ring-slate-800 px-1"
                                      x-text="unread > 9 ? '9+' : unread"></span>
                            </button>
                            <div x-show="openNotif" x-cloak
                                 x-transition
                                 class="absolute {{ $empRtl ? 'right-0' : 'left-0' }} mt-2 w-80 max-h-[420px] overflow-hidden rounded-xl bg-white dark:bg-slate-800 shadow-xl border border-gray-200 dark:border-slate-600 z-50">
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-600 flex items-center justify-between bg-slate-50/80 dark:bg-slate-700/50">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                                            <i class="fas fa-bell text-amber-500"></i>
                                            {{ $empRtl ? 'أحدث الإشعارات' : 'Recent notifications' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5" x-text="unread > 0 ? ('{{ $empRtl ? 'لديك ' : 'You have ' }}' + unread + '{{ $empRtl ? ' إشعار غير مقروء' : ' unread' }}') : '{{ $empRtl ? 'لا توجد إشعارات جديدة' : 'No new notifications' }}'"></p>
                                    </div>
                                    <a href="{{ route('employee.notifications') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700 shrink-0 ms-2">
                                        {{ $empRtl ? 'عرض الكل' : 'All' }}
                                    </a>
                                </div>
                                <div class="max-h-[320px] overflow-y-auto">
                                    <template x-for="item in items" :key="item.id">
                                        <a :href="item.href"
                                           class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors border-b border-gray-50 dark:border-slate-600 last:border-b-0">
                                            <div class="mt-0.5">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs"
                                                      :class="{
                                                          'bg-rose-100 text-rose-600': item.priority === 'urgent',
                                                          'bg-amber-100 text-amber-600': item.priority === 'high',
                                                          'bg-sky-100 text-sky-600': item.priority !== 'urgent' && item.priority !== 'high'
                                                      }">
                                                    <i :class="item.icon"></i>
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 {{ $empRtl ? 'text-right' : 'text-left' }}">
                                                <p class="text-xs font-bold text-gray-900 dark:text-slate-100 truncate" x-text="item.title"></p>
                                                <p class="text-xs text-gray-600 dark:text-slate-300 mt-0.5 line-clamp-2" x-text="item.message"></p>
                                                <p class="text-[10px] text-gray-400 mt-1" x-text="item.time"></p>
                                            </div>
                                        </a>
                                    </template>
                                    <div x-show="items.length === 0" class="px-4 py-6 text-center text-xs text-gray-500 dark:text-slate-400">
                                        <p>{{ $empRtl ? 'لا توجد إشعارات جديدة' : 'No new notifications' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                @php
                                    $user = auth()->user();
                                    $profileImage = $user->profile_image_url;
                                @endphp
                                @if($profileImage)
                                    <img src="{{ $profileImage }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover border-2 border-blue-200">
                                @else
                                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                        {{ mb_substr($user->name, 0, 1, 'UTF-8') }}
                                    </div>
                                @endif
                                <span class="hidden sm:block">{{ $user->name }}</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            
                            <div x-show="open" 
                                 @click.away="open = false"
                                 x-transition
                                 class="absolute {{ $empRtl ? 'right-0' : 'left-0' }} mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('employee.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-home mr-2"></i>لوحة التحكم
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-sign-out-alt mr-2"></i>تسجيل الخروج
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                <div class="p-3 sm:p-4 md:p-6 space-y-4">
                    @include('partials.admin-flash')

                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    </div>

    <!-- Employee Notification Popup -->
    <div id="employeeNotificationPopup" 
         x-data="{ 
             show: false, 
             notification: null,
             readNotifications: JSON.parse(localStorage.getItem('readEmployeeNotifications') || '[]'),
             checkNotifications() {
                 fetch('{{ route('employee.notifications.unread') }}')
                     .then(response => response.json())
                     .then(data => {
                         if (data.success && data.notifications.length > 0) {
                             // عرض أول إشعار غير مقروء (لم يتم قراءته من قبل)
                             const unreadNotification = data.notifications.find(n => 
                                 !this.readNotifications.includes(n.id.toString())
                             );
                             
                             if (unreadNotification && !this.show) {
                                 this.notification = unreadNotification;
                                 this.show = true;
                             }
                         } else {
                             this.show = false;
                         }
                     })
                     .catch(error => console.error('Error fetching notifications:', error));
             },
             dismissNotification() {
                 // إغلاق فقط بدون قراءة - سيظهر مرة أخرى
                 this.show = false;
                 setTimeout(() => {
                     this.checkNotifications();
                 }, 1000);
             },
             markAsRead() {
                 if (this.notification) {
                     const notificationId = this.notification.id.toString();
                     const actionUrl = this.notification.action_url;
                     
                     fetch(`/employee/api/notifications/${this.notification.id}/mark-read`, {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                             'Accept': 'application/json'
                         }
                     })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             // إضافة إلى قائمة المقروءة
                             if (!this.readNotifications.includes(notificationId)) {
                                 this.readNotifications.push(notificationId);
                                 localStorage.setItem('readEmployeeNotifications', JSON.stringify(this.readNotifications));
                             }
                             
                             this.show = false;
                             this.notification = null;
                             
                             // إذا كان هناك رابط إجراء، انتقل إليه
                             if (actionUrl) {
                                 setTimeout(() => {
                                     window.location.href = actionUrl;
                                 }, 300);
                             } else {
                                 // فحص إشعارات جديدة بعد قراءة هذا
                                 setTimeout(() => {
                                     this.checkNotifications();
                                 }, 1000);
                             }
                         }
                     })
                     .catch(error => {
                         console.error('Error marking as read:', error);
                         this.show = false;
                     });
                 }
             }
         }"
         x-init="
             // فحص الإشعارات عند تحميل الصفحة
             setTimeout(() => {
                 checkNotifications();
             }, 1000);
             
             // فحص الإشعارات كل 30 ثانية (Real-time)
             setInterval(() => {
                 if (!show) {
                     checkNotifications();
                 }
             }, 30000);
         "
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 border-2 border-blue-200 animate-scale-in"
             @click.away="dismissNotification()">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white"
                         :class="{
                             'from-red-500 to-red-600': notification?.priority === 'urgent',
                             'from-orange-500 to-orange-600': notification?.priority === 'high',
                             'from-yellow-500 to-yellow-600': notification?.priority === 'normal',
                             'from-blue-500 to-blue-600': !notification?.priority || notification?.priority === 'low'
                         }">
                        <i class="fas fa-bell text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-gray-900" x-text="notification?.title"></h3>
                        <span class="text-xs text-gray-500" x-text="notification?.created_at"></span>
                    </div>
                </div>
                <button @click="dismissNotification()" 
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="mb-4">
                <p class="text-gray-700 leading-relaxed whitespace-pre-wrap" x-text="notification?.message"></p>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="markAsRead()" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-semibold transition-colors">
                    <i class="fas fa-check ml-2"></i>
                    قرأت الإشعار
                </button>
                <button @click="dismissNotification()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors">
                    إغلاق
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes scale-in {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-scale-in {
            animation: scale-in 0.3s ease-out;
        }
        [x-cloak] { display: none !important; }
    </style>

    @stack('scripts')
</body>
</html>
