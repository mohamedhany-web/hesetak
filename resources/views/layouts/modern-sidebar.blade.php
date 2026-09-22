<div class="flex flex-col h-full">
    <!-- Logo Section -->
    <div class="sidebar-logo flex items-center gap-3 px-6">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
            <i class="fas fa-code text-white text-lg"></i>
        </div>
        <div>
            <h2 class="text-lg font-black text-gray-900">{{ config('app.name') }}</h2>
            <p class="text-xs text-gray-500">{{ config('app.name') }}</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto sidebar-scroll py-4">
        @if(auth()->check())
            @php
                $hasStudentPermissions = (
                    auth()->user()->hasPermission('student.view.courses') ||
                    auth()->user()->hasPermission('student.view.my-courses') ||
                    auth()->user()->hasPermission('student.view.orders') ||
                    auth()->user()->hasPermission('student.view.invoices') ||
                    auth()->user()->hasPermission('student.view.wallet') ||
                    auth()->user()->hasPermission('student.view.certificates') ||
                    auth()->user()->hasPermission('student.view.achievements') ||
                    auth()->user()->hasPermission('student.view.exams') ||
                    auth()->user()->hasPermission('student.view.notifications') ||
                    auth()->user()->hasPermission('student.view.profile') ||
                    auth()->user()->hasPermission('student.view.settings')
                );
            @endphp
            @if($hasStudentPermissions)
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>لوحة التحكم</span>
            </a>

            <!-- Browse Courses -->
            @if(auth()->check() && auth()->user()->hasPermission('student.view.courses'))
            @php
                $catalogActive = request()->routeIs('academic-years*') || request()->routeIs('subjects.*') || request()->routeIs('courses.*');
            @endphp
            <a href="{{ route('academic-years') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ $catalogActive ? 'active' : '' }}">
                <i class="fas fa-search"></i>
                <span>تصفح الكورسات</span>
            </a>
            @endif

            <!-- My Courses -->
            @if(auth()->check() && auth()->user()->hasPermission('student.view.my-courses'))
            <a href="{{ route('my-courses.index') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('my-courses.*') ? 'active' : '' }}">
                <i class="fas fa-book-open"></i>
                <span>كورساتي</span>
            </a>
            @endif

            <!-- Orders -->
            @if(auth()->check() && auth()->user()->hasPermission('student.view.orders'))
            <a href="{{ route('orders.index') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <i class="fas fa-shopping-cart"></i>
                <span>طلباتي</span>
            </a>
            @endif

            <!-- Exams -->
            @if(auth()->check() && auth()->user()->hasPermission('student.view.exams'))
            <a href="{{ route('student.exams.index') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('student.exams.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-check"></i>
                <span>الامتحانات</span>
            </a>
            @endif

            @if(auth()->check() && auth()->user()->isStudent())
            <a href="{{ route('student.assignments.index') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i>
                <span>واجباتي</span>
            </a>
            @endif

            <!-- Certificates -->
            @if(student_ui('show_certificates', false) && auth()->check() && auth()->user()->hasPermission('student.view.certificates'))
            <a href="{{ route('student.certificates.index') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('student.certificates.*') ? 'active' : '' }}">
                <i class="fas fa-certificate"></i>
                <span>شهاداتي</span>
            </a>
            @endif

            <!-- Wallet -->
            @if(auth()->check() && auth()->user()->hasPermission('student.view.wallet'))
            <a href="{{ route('student.wallet.index') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('student.wallet.*') ? 'active' : '' }}">
                <i class="fas fa-wallet"></i>
                <span>محفظتي</span>
            </a>
            @endif

            <!-- Notifications -->
            @if(auth()->check() && auth()->user()->hasPermission('student.view.notifications'))
            <a href="{{ route('notifications') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('notifications') ? 'active' : '' }}">
                <i class="fas fa-bell"></i>
                <span>الإشعارات</span>
            </a>
            @endif

            <!-- Profile -->
            @if(auth()->check() && auth()->user()->hasPermission('student.view.profile'))
            <a href="{{ route('profile') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('profile') ? 'active' : '' }}">
                <i class="fas fa-user"></i>
                <span>الملف الشخصي</span>
            </a>
            @endif

            <!-- Settings -->
            @if(auth()->check() && auth()->user()->hasPermission('student.view.settings'))
            <a href="{{ route('settings') }}" 
               @click="if (window.innerWidth < 1024) sidebarOpen = false"
               class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('settings') ? 'active' : '' }}">
                <i class="fas fa-cog"></i>
                <span>الإعدادات</span>
            </a>
            @endif
            @endif
        @endif

        @if(auth()->check())
            @php
                $isAdminOrInstructor = auth()->user()->isAdmin() || auth()->user()->isInstructor();
                $isAdmin = auth()->user()->isAdmin();
                $isInstructor = auth()->user()->isInstructor();
            @endphp
            @if($isAdminOrInstructor)
            <hr class="my-4 mx-4 border-gray-200">
            
            @if($isAdmin)
                <a href="{{ route('admin.dashboard') }}" 
                   @click="if (window.innerWidth < 1024) sidebarOpen = false"
                   class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>لوحة التحكم</span>
                </a>
            @endif
            
            @if($isInstructor)
                <a href="{{ route('dashboard') }}" 
                   @click="if (window.innerWidth < 1024) sidebarOpen = false"
                   class="sidebar-nav-item flex items-center gap-3 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>لوحة التحكم</span>
                </a>
            @endif
            @endif
        @endif
    </nav>

    <!-- User Info at Bottom -->
    @if(auth()->check())
    <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                @if(auth()->user()->profile_image)
                    <img src="{{ auth()->user()->profile_image_url }}" alt="" class="w-full h-full rounded-full object-cover">
                @else
                    {{ substr(auth()->user()->name, 0, 1) }}
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500 truncate">
                    @php
                        $userRole = auth()->user()->isAdmin() ? 'مدير' : (auth()->user()->isInstructor() ? 'مدرب' : __('student.student_role'));
                    @endphp
                    {{ $userRole }}
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
