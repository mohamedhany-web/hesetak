@php
    $user = auth()->user();
    $user->loadMissing('employeeJob');
    $sidebarSections = \App\Support\EmployeeSidebar::sectionsFor($user);
    $firstSidebarLink = \Illuminate\Support\Arr::get($sidebarSections, '0.links.0');
    $employeeHomeUrl = $user->employeeCan('dashboard')
        ? route('employee.dashboard')
        : (! empty($firstSidebarLink['route'] ?? null)
            ? route($firstSidebarLink['route'])
            : url('/'));
@endphp
<div class="flex flex-col h-full bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
    <div class="px-4 py-4 flex-shrink-0 border-b border-slate-700/50 dark:border-slate-600/50">
        <a href="{{ $employeeHomeUrl }}" class="sidebar-logo flex items-center gap-3 w-full">
            @php
                $sidebarLogoUrl = $adminPanelLogoUrl
                    ?: \App\Services\AdminPanelBranding::defaultPublicMarkUrl();
                $sidebarLogoVer = @filemtime(public_path(\App\Services\AdminPanelBranding::PUBLIC_MARK_PATH)) ?: time();
                if (is_string($sidebarLogoUrl) && ! str_contains($sidebarLogoUrl, '?') && str_contains($sidebarLogoUrl, 'hesetak-mark')) {
                    $sidebarLogoUrl .= '?v='.$sidebarLogoVer;
                }
            @endphp
            <div class="sidebar-brand-mark" style="width:36px;height:36px;min-width:36px;border-radius:10px">
                <img
                    src="{{ $sidebarLogoUrl }}"
                    alt="{{ config('app.name') }}"
                    width="36"
                    height="36"
                    onerror="this.onerror=null;this.src='{{ \App\Services\AdminPanelBranding::inlineFallbackDataUri() }}';"
                >
            </div>
            <div class="sidebar-logo-text text-right min-w-0 flex-1">
                <h2 class="text-sm font-bold text-white tracking-tight leading-tight">{{ config('app.name') }}</h2>
                <p class="text-[9px] text-slate-400 font-medium truncate">
                    @if($user->employeeJob)
                        {{ $user->employeeJob->name }}
                    @else
                        لوحة الموظف
                    @endif
                </p>
            </div>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 space-y-5 employee-sidebar-nav">
        @forelse($sidebarSections as $section)
            <div class="space-y-1">
                @if(!empty($section['title']))
                    <p class="px-4 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">{{ $section['title'] }}</p>
                @endif
                @foreach($section['links'] as $link)
                    @php
                        $isActive = \App\Support\EmployeeSidebar::linkIsActive($link);
                        $baseNav = 'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 w-full';
                        $inactiveNav = 'text-slate-300 hover:bg-slate-700/50 hover:text-white dark:text-slate-400';
                        $activeClass = $link['active_class'] ?? 'bg-blue-600 shadow-lg';
                    @endphp
                    @php
                        $linkHref = isset($link['route_params'])
                            ? route($link['route'], $link['route_params'])
                            : route($link['route']);
                    @endphp
                    <a href="{{ $linkHref }}"
                       class="{{ $baseNav }} {{ $isActive ? $activeClass.' text-white' : $inactiveNav }}"
                       @click="if (window.innerWidth < 1024) { $dispatch('close-sidebar'); }">
                        <i class="{{ $link['icon'] }} text-base w-5 text-center shrink-0"></i>
                        <span class="truncate">{{ $link['label'] }}</span>
                        @if(($link['key'] ?? '') === 'notifications')
                            @php
                                try {
                                    $unreadCount = $user->notifications()->whereNull('read_at')->count();
                                } catch (\Exception $e) {
                                    $unreadCount = 0;
                                }
                            @endphp
                            @if($unreadCount > 0)
                                <span class="mr-auto bg-red-500 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[20px] text-center shrink-0">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                            @endif
                        @endif
                    </a>
                @endforeach
            </div>
        @empty
            <div class="px-4 py-6 text-center text-slate-400 text-sm">
                لا توجد عناصر قائمة مفعّلة لوظيفتك. راجع الإدارة.
            </div>
        @endforelse
    </nav>

    <div class="border-t border-slate-700/50 dark:border-slate-600/50 p-4">
        <div class="flex items-center gap-3 mb-3">
            @php $profileImage = $user->profile_image_url ?? null; @endphp
            @if($profileImage)
                <img src="{{ $profileImage }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover border-2 border-blue-400 flex-shrink-0">
            @else
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                    {{ mb_substr($user->name, 0, 1, 'UTF-8') }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ $user->name }}</p>
                <p class="text-xs text-slate-400 truncate">{{ optional($user->employeeJob)->name ?? 'موظف' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-slate-700/50 hover:bg-slate-700 dark:bg-slate-600/50 dark:hover:bg-slate-600 text-white text-sm font-semibold rounded-xl transition-colors">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </button>
        </form>
    </div>
</div>
