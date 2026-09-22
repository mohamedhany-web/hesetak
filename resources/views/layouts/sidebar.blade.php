<div class="flex flex-col h-full bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900">
    <!-- شعار المنصة -->
    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-sky-50 to-slate-50 dark:from-gray-800 dark:to-gray-900">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-sky-500 via-sky-600 via-sky-700 to-slate-600 rounded-xl flex items-center justify-center shadow-lg relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <i class="fas fa-code text-white text-lg relative z-10 rotate-animation"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-gray-900 dark:text-white">{{ config('app.name') }}</h2>
                <p class="text-xs text-sky-600 dark:text-sky-400 font-medium">{{ config('app.name') }}</p>
            </div>
        </div>
    </div>

    <!-- القائمة الرئيسية -->
    <nav class="flex-1 p-4 overflow-y-auto sidebar">
        <ul class="space-y-2">
            <!-- لوحة التحكم -->
            <li>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" 
                       @click="if (window.innerWidth < 1024) { $dispatch('close-sidebar'); }"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group
                              {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-chart-line w-5 relative z-10 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">لوحة التحكم</span>
                        @if(request()->routeIs('admin.dashboard'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                @else
                <a href="{{ route('dashboard') }}" 
                   @click="if (window.innerWidth < 1024) { $dispatch('close-sidebar'); }"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group
                          {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                    <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                    <i class="fas fa-chart-line w-5 relative z-10 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                    <span class="relative z-10 font-semibold">لوحة التحكم</span>
                    @if(request()->routeIs('dashboard'))
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                    @endif
                </a>
                @endif
            </li>


            @hasAnyPermission('manage.users', 'manage.orders', 'manage.notifications', 'view.activity-log', 'view.statistics', 'manage.roles', 'manage.permissions', 'manage.user-permissions')
                <!-- إدارة النظام -->
                @php
                    $systemManagementOpen = request()->routeIs('admin.*') && !(request()->routeIs('admin.academic-years.*') || request()->routeIs('admin.academic-subjects.*') || request()->routeIs('admin.advanced-courses.*') || request()->routeIs('admin.enrollments.*') || request()->routeIs('admin.exams.*') || request()->routeIs('admin.question-bank.*') || request()->routeIs('admin.question-categories.*') || request()->routeIs('admin.invoices.*') || request()->routeIs('admin.payments.*') || request()->routeIs('admin.coupons.*') || request()->routeIs('admin.certificates.*') || request()->routeIs('admin.achievements.*') || request()->routeIs('admin.reviews.*'));
                @endphp
                <li x-data="{ open: {{ $systemManagementOpen ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 rounded-xl hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-700 dark:text-gray-300 group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-cogs w-5 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                            <span class="font-medium">إدارة النظام</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-gray-400 dark:text-gray-500" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-transition class="mt-2 mr-4 space-y-1 border-r-2 border-sky-200 dark:border-sky-800 pr-2">
                        @hasPermission('manage.users')
                        <li><a href="{{ route('admin.users.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.users*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-users w-4"></i>
                            <span>إدارة المستخدمين</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.orders')
                        <li><a href="{{ route('admin.orders.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.orders.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-shopping-cart w-4"></i>
                            <span>الطلبات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.notifications')
                        <li><a href="{{ route('admin.notifications.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.notifications.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-bell w-4"></i>
                            <span>إرسال الإشعارات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('view.activity-log')
                        <li><a href="{{ route('admin.activity-log') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.activity-log') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-history w-4"></i>
                            <span>سجل النشاطات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('view.statistics')
                        <li><a href="{{ route('admin.statistics.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.statistics*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-chart-bar w-4"></i>
                            <span>الإحصائيات</span>
                        </a></li>
                        @endhasPermission
                        @if(auth()->user()->isAdmin())
                        <li><a href="{{ route('admin.performance.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.performance.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-tachometer-alt w-4"></i>
                            <span>أداء الموقع</span>
                        </a></li>
                        @endif
                    </ul>
                </li>
            @endhasAnyPermission

            @hasAnyPermission('manage.invoices', 'manage.payments', 'manage.transactions', 'manage.wallets', 'manage.installments', 'manage.expenses')
                <!-- إدارة المحاسبة -->
                @php
                    $accountingOpen = request()->routeIs('admin.invoices.*') || request()->routeIs('admin.payments.*') || request()->routeIs('admin.transactions.*') || request()->routeIs('admin.wallets.*') || request()->routeIs('admin.installments.*') || request()->routeIs('admin.expenses.*') || request()->routeIs('admin.accounting.reports');
                @endphp
                <li x-data="{ open: {{ $accountingOpen ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 rounded-xl hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-700 dark:text-gray-300 group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-money-bill-wave w-5 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                            <span class="font-medium">إدارة المحاسبة</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-gray-400 dark:text-gray-500" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-transition class="mt-2 mr-4 space-y-1 border-r-2 border-sky-200 dark:border-sky-800 pr-2">
                        @hasPermission('manage.invoices')
                        <li><a href="{{ route('admin.invoices.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.invoices.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-file-invoice w-4"></i>
                            <span>الفواتير</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.payments')
                        <li><a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.payments.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-credit-card w-4"></i>
                            <span>المدفوعات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.transactions')
                        <li><a href="{{ route('admin.transactions.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.transactions.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-exchange-alt w-4"></i>
                            <span>المعاملات المالية</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.wallets')
                        <li><a href="{{ route('admin.wallets.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.wallets.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-wallet w-4"></i>
                            <span>المحافظ</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.expenses')
                        <li><a href="{{ route('admin.expenses.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.expenses.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-receipt w-4"></i>
                            <span>المصروفات</span>
                        </a></li>
                        @endhasPermission
                        @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission('manage.invoices', 'manage.payments', 'manage.transactions'))
                        <li><a href="{{ route('admin.accounting.reports') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.accounting.reports') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-chart-pie w-4"></i>
                            <span>التقارير المحاسبية</span>
                        </a></li>
                        @endif
                        @hasPermission('manage.installments')
                        @php
                            $installmentsOpen = request()->routeIs('admin.installments.plans.*') || request()->routeIs('admin.installments.agreements.*');
                        @endphp
                        <li x-data="{ open: {{ $installmentsOpen ? 'true' : 'false' }} }">
                            <button @click="open = !open"
                                    class="flex items-center justify-between w-full px-4 py-2.5 rounded-lg transition-all duration-300 text-gray-600 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700/70 dark:hover:to-gray-800/70 hover:text-sky-700 dark:hover:text-sky-200">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-calendar-check w-4 text-sky-500 dark:text-sky-300"></i>
                                    <span class="font-medium text-sm">إدارة التقسيط</span>
                                </span>
                                <i class="fas fa-chevron-down text-xs transition-transform duration-300" :class="{ 'rotate-180': open }"></i>
                            </button>
                            <ul x-show="open" x-transition class="mt-2 mr-3 space-y-1 border-r border-sky-100 dark:border-sky-800/60 pr-2">
                                <li>
                                    <a href="{{ route('admin.installments.plans.index') }}"
                                       @click="if (window.innerWidth < 1024) { $dispatch('close-sidebar'); }"
                                       class="flex items-center gap-2 px-4 py-2 text-xs rounded-lg transition-all duration-300 {{ request()->routeIs('admin.installments.plans.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 text-sky-700 font-semibold shadow-sm dark:from-sky-500/25 dark:to-slate-600/25 dark:text-sky-200 dark:shadow-sky-900/40 dark:border dark:border-sky-700/50' : 'text-gray-600 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 hover:text-sky-700 dark:text-gray-300 dark:hover:from-gray-700/70 dark:hover:to-gray-800/70 dark:hover:text-sky-200 border border-transparent dark:hover:border-sky-700/40' }}">
                                        <i class="fas fa-layer-group w-3.5"></i>
                                        <span>خطط التقسيط</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.installments.agreements.index') }}"
                                       @click="if (window.innerWidth < 1024) { $dispatch('close-sidebar'); }"
                                       class="flex items-center gap-2 px-4 py-2 text-xs rounded-lg transition-all duration-300 {{ request()->routeIs('admin.installments.agreements.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 text-sky-700 font-semibold shadow-sm dark:from-sky-500/25 dark:to-slate-600/25 dark:text-sky-200 dark:shadow-sky-900/40 dark:border dark:border-sky-700/50' : 'text-gray-600 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 hover:text-sky-700 dark:text-gray-300 dark:hover:from-gray-700/70 dark:hover:to-gray-800/70 dark:hover:text-sky-200 border border-transparent dark:hover:border-sky-700/40' }}">
                                        <i class="fas fa-handshake w-3.5"></i>
                                        <span>اتفاقيات السداد</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endhasPermission
                    </ul>
                </li>
            @endhasAnyPermission

            @hasAnyPermission('manage.coupons', 'manage.referrals')
                <!-- إدارة التسويق -->
                @php
                    $marketingOpen = request()->routeIs('admin.coupons.*') || request()->routeIs('admin.referral-programs.*') || request()->routeIs('admin.referrals.*');
                @endphp
                <li x-data="{ open: {{ $marketingOpen ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 rounded-xl hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-700 dark:text-gray-300 group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-tags w-5 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                            <span class="font-medium">إدارة التسويق</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-gray-400 dark:text-gray-500" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-transition class="mt-2 mr-4 space-y-1 border-r-2 border-sky-200 dark:border-sky-800 pr-2">
                        @hasPermission('manage.coupons')
                        <li><a href="{{ route('admin.coupons.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.coupons.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-ticket-alt w-4"></i>
                            <span>الكوبونات والخصومات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.referrals')
                        <li><a href="{{ route('admin.referral-programs.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.referral-programs.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-gift w-4"></i>
                            <span>برامج الإحالات</span>
                        </a></li>
                        <li><a href="{{ route('admin.referrals.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.referrals.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-user-friends w-4"></i>
                            <span>الإحالات</span>
                        </a></li>
                        @endhasPermission
                    </ul>
                </li>
            @endhasAnyPermission

            @hasAnyPermission('manage.certificates', 'manage.achievements', 'manage.badges', 'manage.reviews')
                <!-- إدارة الشهادات والإنجازات -->
                @php
                    $certificatesOpen = request()->routeIs('admin.certificates.*') || request()->routeIs('admin.achievements.*') || request()->routeIs('admin.badges.*') || request()->routeIs('admin.reviews.*');
                @endphp
                <li x-data="{ open: {{ $certificatesOpen ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 rounded-xl hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-700 dark:text-gray-300 group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-trophy w-5 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                            <span class="font-medium">{{ config('admin_ui.show_certificates') ? 'الشهادات والإنجازات' : 'الإنجازات والتقييمات' }}</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-gray-400 dark:text-gray-500" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-transition class="mt-2 mr-4 space-y-1 border-r-2 border-sky-200 dark:border-sky-800 pr-2">
                        @if(config('admin_ui.show_certificates'))
                        @hasPermission('manage.certificates')
                        <li><a href="{{ route('admin.certificates.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.certificates.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-certificate w-4"></i>
                            <span>الشهادات</span>
                        </a></li>
                        @endhasPermission
                        @endif
                        @hasPermission('manage.achievements')
                        <li><a href="{{ route('admin.achievements.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.achievements.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-medal w-4"></i>
                            <span>الإنجازات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.badges')
                        <li><a href="{{ route('admin.badges.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.badges.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-award w-4"></i>
                            <span>الشارات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.reviews')
                        <li><a href="{{ route('admin.reviews.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.reviews.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-star-half-alt w-4"></i>
                            <span>التقييمات والمراجعات</span>
                        </a></li>
                        @endhasPermission
                    </ul>
                </li>
            @endhasAnyPermission

            @hasAnyPermission('manage.academic-years', 'manage.academic-subjects', 'manage.courses', 'manage.enrollments', 'manage.lectures', 'manage.assignments', 'manage.exams', 'manage.question-bank')
                <!-- إدارة المحتوى -->
                @php
                    $contentManagementOpen = request()->routeIs('admin.academic-years.*') || request()->routeIs('admin.academic-subjects.*') || request()->routeIs('admin.advanced-courses.*') || request()->routeIs('admin.course-categories.*') || request()->routeIs('admin.enrollments.*') || request()->routeIs('admin.exams.*') || request()->routeIs('admin.question-bank.*') || request()->routeIs('admin.question-categories.*') || request()->routeIs('admin.lectures.*') || request()->routeIs('admin.assignments.*');
                @endphp
                <li x-data="{ open: {{ $contentManagementOpen ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 rounded-xl hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-700 dark:text-gray-300 group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-folder w-5 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                            <span class="font-medium">إدارة المحتوى</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-gray-400 dark:text-gray-500" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-transition class="mt-2 mr-4 space-y-1 border-r-2 border-sky-200 dark:border-sky-800 pr-2">
                        <!-- النظام الأكاديمي الجديد -->
                        <li class="pt-2 pb-1">
                            <div class="flex items-center gap-2 text-xs font-bold text-sky-600 dark:text-sky-400 px-4 py-1 uppercase tracking-wider">
                                <i class="fas fa-route"></i>
                                كتالوج التعلم
                            </div>
                        </li>
                        @hasPermission('manage.academic-years')
                        <li><a href="{{ route('admin.academic-years.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.academic-years.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-compass w-4"></i>
                            <span>سنوات المدرسة</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.academic-subjects')
                        <li><a href="{{ route('admin.academic-subjects.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.academic-subjects.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-layer-group w-4"></i>
                            <span>مواد المدرسة</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.courses')
                        @php
                            $advancedCoursesActive = request()->routeIs('admin.advanced-courses.*') || request()->routeIs('admin.courses.lessons.*');
                        @endphp
                        <li><a href="{{ route('admin.advanced-courses.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ $advancedCoursesActive ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-graduation-cap w-4"></i>
                            <span>إدارة الكورسات</span>
                        </a></li>
                        <li><a href="{{ route('admin.course-categories.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.course-categories.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-tags w-4"></i>
                            <span>{{ __('admin.course_categories') }}</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.enrollments')
                        <li><a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.enrollments.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-user-graduate w-4"></i>
                            <span>إدارة التسجيلات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.lectures')
                        <li><a href="{{ route('admin.lectures.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.lectures.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-video w-4"></i>
                            <span>المحاضرات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.assignments')
                        <li><a href="{{ route('admin.assignments.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.assignments.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-tasks w-4"></i>
                            <span>الواجبات والمشاريع</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.exams')
                        <li><a href="{{ route('admin.exams.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.exams.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-clipboard-check w-4"></i>
                            <span>الامتحانات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.question-bank')
                        @php
                            $questionBankActive = request()->routeIs('admin.question-bank.*') || request()->routeIs('admin.question-categories.*');
                        @endphp
                        <li><a href="{{ route('admin.question-bank.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ $questionBankActive ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-database w-4"></i>
                            <span>بنك الأسئلة</span>
                        </a></li>
                        @endhasPermission
                    </ul>
                </li>
            @endhasAnyPermission

            @hasAnyPermission('manage.roles', 'manage.permissions', 'manage.user-permissions')
                <!-- إدارة الصلاحيات والأدوار -->
                @php
                    $permissionsOpen = request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.user-permissions.*');
                @endphp
                <li x-data="{ open: {{ $permissionsOpen ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 rounded-xl hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-700 dark:text-gray-300 group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-shield-alt w-5 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                            <span class="font-medium">الصلاحيات والأدوار</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-gray-400 dark:text-gray-500" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-transition class="mt-2 mr-4 space-y-1 border-r-2 border-sky-200 dark:border-sky-800 pr-2">
                        @hasPermission('manage.roles')
                        <li><a href="{{ route('admin.roles.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.roles.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-user-tag w-4"></i>
                            <span>الأدوار</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.permissions')
                        <li><a href="{{ route('admin.permissions.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.permissions.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-key w-4"></i>
                            <span>الصلاحيات</span>
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.user-permissions')
                        <li><a href="{{ route('admin.user-permissions.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.user-permissions.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-user-shield w-4"></i>
                            <span>صلاحيات المستخدمين</span>
                        </a></li>
                        @endhasPermission
                    </ul>
                </li>
            @endhasAnyPermission

            @hasPermission('view.wallets')
                <!-- إدارة المحافظ الذكية -->
                <li>
                    <a href="{{ route('admin.wallets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('admin.wallets.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-wallet w-5 relative z-10 {{ request()->routeIs('admin.wallets.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">المحافظ الذكية</span>
                        @if(request()->routeIs('admin.wallets.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
            @endhasPermission

            @hasPermission('manage.tasks')
                <!-- إدارة المهام -->
                <li>
                    <a href="{{ route('admin.tasks.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('admin.tasks.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-list-check w-5 relative z-10 {{ request()->routeIs('admin.tasks.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">المهام</span>
                        @if(request()->routeIs('admin.tasks.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
            @endhasPermission

            @hasAnyPermission('manage.contact-messages', 'manage.packages')
                <!-- إدارة الصفحات الخارجية -->
                @php
                    $externalOpen = request()->routeIs('admin.contact-messages.*') || request()->routeIs('admin.packages.*');
                @endphp
                <li x-data="{ open: {{ $externalOpen ? 'true' : 'false' }} }">
                    <button @click="open = !open" 
                            class="flex items-center justify-between w-full px-4 py-3 rounded-xl hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-700 dark:text-gray-300 group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-globe w-5 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                            <span class="font-medium">إدارة الصفحات الخارجية</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-gray-400 dark:text-gray-500" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-transition class="mt-2 mr-4 space-y-1 border-r-2 border-sky-200 dark:border-sky-800 pr-2">
                        @hasPermission('manage.contact-messages')
                        <li><a href="{{ route('admin.contact-messages.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.contact-messages.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-envelope w-4"></i>
                            <span>رسائل التواصل</span>
                            @php
                                $unreadCount = \App\Models\ContactMessage::whereNull('read_at')->count();
                            @endphp
                            @if($unreadCount > 0)
                                <span class="mr-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $unreadCount }}</span>
                            @endif
                        </a></li>
                        @endhasPermission
                        @hasPermission('manage.packages')
                        <li><a href="{{ route('admin.packages.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800 transition-all duration-300 text-gray-600 dark:text-gray-400 hover:text-sky-700 dark:hover:text-sky-300 {{ request()->routeIs('admin.packages.*') ? 'bg-gradient-to-r from-sky-100 to-slate-100 dark:from-sky-900/30 dark:to-slate-900/30 text-sky-700 dark:text-sky-300 font-semibold' : '' }}">
                            <i class="fas fa-tags w-4"></i>
                            <span>إدارة الأسعار والباقات</span>
                        </a></li>
                        @endhasPermission
                    </ul>
                </li>
            @endhasAnyPermission

            @hasAnyPermission('instructor.view.courses', 'instructor.manage.lectures', 'instructor.manage.assignments', 'instructor.manage.exams', 'instructor.manage.attendance', 'instructor.view.tasks')
                <!-- كورساتي -->
                @hasPermission('instructor.view.courses')
                <li>
                    <a href="{{ route('instructor.courses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('instructor.courses.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-book w-5 relative z-10 {{ request()->routeIs('instructor.courses.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">كورساتي</span>
                        @if(request()->routeIs('instructor.courses.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- المحاضرات -->
                @hasPermission('instructor.manage.lectures')
                <li>
                    <a href="{{ route('instructor.lectures.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('instructor.lectures.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-video w-5 relative z-10 {{ request()->routeIs('instructor.lectures.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">المحاضرات</span>
                        @if(request()->routeIs('instructor.lectures.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- الواجبات -->
                @hasPermission('instructor.manage.assignments')
                <li>
                    <a href="{{ route('instructor.assignments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('instructor.assignments.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-tasks w-5 relative z-10 {{ request()->routeIs('instructor.assignments.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">الواجبات</span>
                        @if(request()->routeIs('instructor.assignments.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- الاختبارات -->
                @hasPermission('instructor.manage.exams')
                <li>
                    <a href="{{ route('instructor.exams.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('instructor.exams.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-clipboard-check w-5 relative z-10 {{ request()->routeIs('instructor.exams.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">الاختبارات</span>
                        @if(request()->routeIs('instructor.exams.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- الحضور -->
                @hasPermission('instructor.manage.attendance')
                <li>
                    <a href="{{ route('instructor.attendance.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('instructor.attendance.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-user-check w-5 relative z-10 {{ request()->routeIs('instructor.attendance.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">الحضور والانصراف</span>
                        @if(request()->routeIs('instructor.attendance.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- المهام -->
                @hasPermission('instructor.view.tasks')
                <li>
                    <a href="{{ route('instructor.tasks.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('instructor.tasks.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-list-check w-5 relative z-10 {{ request()->routeIs('instructor.tasks.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">مهامي</span>
                        @if(request()->routeIs('instructor.tasks.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission
            @endhasAnyPermission

            @hasAnyPermission('student.view.courses', 'student.view.my-courses', 'student.view.orders', 'student.view.invoices', 'student.view.wallet', 'student.view.certificates', 'student.view.achievements', 'student.view.exams', 'student.view.notifications', 'student.view.profile', 'student.view.settings')
                <!-- تصفح الكورسات -->
                @hasPermission('student.view.courses')
                @php
                    $catalogActive = request()->routeIs('academic-years*') || request()->routeIs('subjects.*') || request()->routeIs('courses.*');
                @endphp
                <li>
                    <a href="{{ route('academic-years') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ $catalogActive ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-search w-5 relative z-10 {{ $catalogActive ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">تصفح الكورسات</span>
                        @if($catalogActive)
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- طلباتي -->
                @hasPermission('student.view.orders')
                <li>
                    <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('orders.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-shopping-cart w-5 relative z-10 {{ request()->routeIs('orders.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">طلباتي</span>
                        @if(request()->routeIs('orders.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- كورساتي -->
                @hasPermission('student.view.my-courses')
                <li>
                    <a href="{{ route('my-courses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('my-courses.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-book-open w-5 relative z-10 {{ request()->routeIs('my-courses.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">كورساتي</span>
                        @if(request()->routeIs('my-courses.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                @if(auth()->user()->isStudent())
                <!-- برنامج الإحالات -->
                <li>
                    <a href="{{ route('referrals.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('referrals.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-users w-5 relative z-10 {{ request()->routeIs('referrals.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">برنامج الإحالات</span>
                        @if(request()->routeIs('referrals.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endif

                <!-- فواتيري -->
                @hasPermission('student.view.invoices')
                <li>
                    <a href="{{ route('student.invoices.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('student.invoices.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-file-invoice w-5 relative z-10 {{ request()->routeIs('student.invoices.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">فواتيري</span>
                        @if(request()->routeIs('student.invoices.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- محفظتي -->
                @hasPermission('student.view.wallet')
                <li>
                    <a href="{{ route('student.wallet.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('student.wallet.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-wallet w-5 relative z-10 {{ request()->routeIs('student.wallet.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">محفظتي</span>
                        @if(request()->routeIs('student.wallet.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- شهاداتي -->
                @if(student_ui('show_certificates', false))
                @hasPermission('student.view.certificates')
                <li>
                    <a href="{{ route('student.certificates.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('student.certificates.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-certificate w-5 relative z-10 {{ request()->routeIs('student.certificates.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">شهاداتي</span>
                        @if(request()->routeIs('student.certificates.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission
                @endif

                <!-- إنجازاتي -->
                @hasPermission('student.view.achievements')
                <li>
                    <a href="{{ route('student.achievements.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('student.achievements.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-trophy w-5 relative z-10 {{ request()->routeIs('student.achievements.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">إنجازاتي</span>
                        @if(request()->routeIs('student.achievements.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- الامتحانات -->
                @hasPermission('student.view.exams')
                <li>
                    <a href="{{ route('student.exams.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('student.exams.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-clipboard-check w-5 relative z-10 {{ request()->routeIs('student.exams.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">الامتحانات</span>
                        @if(request()->routeIs('student.exams.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                @if(auth()->user()->isStudent())
                <li>
                    <a href="{{ route('student.assignments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('student.assignments.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-tasks w-5 relative z-10 {{ request()->routeIs('student.assignments.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">واجباتي</span>
                        @if(request()->routeIs('student.assignments.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endif

                <!-- الإشعارات -->
                @hasPermission('student.view.notifications')
                <li>
                    <a href="{{ route('notifications') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('notifications') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-bell w-5 relative z-10 {{ request()->routeIs('notifications') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">الإشعارات</span>
                        @if(request()->routeIs('notifications'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- البروفايل -->
                @hasPermission('student.view.profile')
                <li>
                    <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('profile') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-user w-5 relative z-10 {{ request()->routeIs('profile') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">البروفايل</span>
                        @if(request()->routeIs('profile'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission

                <!-- الإعدادات -->
                @hasPermission('student.view.settings')
                <li>
                    <a href="{{ route('settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('settings') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-cog w-5 relative z-10 {{ request()->routeIs('settings') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">الإعدادات</span>
                        @if(request()->routeIs('settings'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
                @endhasPermission
            @endhasAnyPermission

            {{-- تم إزالة دور ولي الأمر - لن يتم عرض هذا القسم --}}
            @if(false)
                <!-- أطفالي -->
                <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-child w-5 relative z-10 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                        <span class="relative z-10 font-semibold">أطفالي</span>
                    </a>
                </li>

                <!-- تقارير الأداء -->
                <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-chart-bar w-5 relative z-10 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                        <span class="relative z-10 font-semibold">تقارير الأداء</span>
                    </a>
                </li>

                <!-- التواصل مع المدربين -->
                <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-comments w-5 relative z-10 text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400"></i>
                        <span class="relative z-10 font-semibold">التواصل</span>
                    </a>
                </li>
            @endif

            <!-- قسم مشترك -->
            <hr class="my-4 border-gray-200 dark:border-gray-700">



            @hasPermission('manage.messages')
                <!-- الرسائل والتقارير -->
                <li>
                    <a href="{{ route('admin.messages.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('admin.messages.*') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                        <i class="fas fa-envelope w-5 relative z-10 {{ request()->routeIs('admin.messages.*') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                        <span class="relative z-10 font-semibold">الرسائل والتقارير</span>
                        @if(request()->routeIs('admin.messages.*'))
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                        @endif
                    </a>
                </li>
            @endhasPermission

            @hasPermission('view.calendar')
            <!-- التقويم -->
            <li>
                <a href="{{ route('calendar') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 relative overflow-hidden group {{ request()->routeIs('calendar') ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-slate-50 dark:hover:from-gray-700 dark:hover:to-gray-800' }}">
                    <div class="absolute inset-0 bg-gradient-to-r from-sky-400 to-slate-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                    <i class="fas fa-calendar w-5 relative z-10 {{ request()->routeIs('calendar') ? 'text-white' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-600 dark:group-hover:text-sky-400' }}"></i>
                    <span class="relative z-10 font-semibold">التقويم</span>
                    @if(request()->routeIs('calendar'))
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-slate-400 rounded-r"></div>
                    @endif
                </a>
            </li>
            @endhasPermission
        </ul>
    </nav>

    <!-- معلومات المستخدم -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gradient-to-r from-sky-50 to-slate-50 dark:from-gray-800 dark:to-gray-900">
        <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-sky-100 dark:border-sky-900/50 hover:shadow-lg transition-shadow duration-300 group">
            <div class="w-10 h-10 bg-gradient-to-br from-sky-500 via-sky-600 to-slate-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent"></div>
                <span class="relative z-10">{{ substr(auth()->user()->name, 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-sky-600 dark:text-sky-400 font-medium">{{ auth()->user()->phone }}</p>
            </div>
            <div class="w-3 h-3 bg-green-500 rounded-full shadow-md ring-2 ring-green-400 ring-offset-2 ring-offset-white dark:ring-offset-gray-800"></div>
        </div>
    </div>
</div>