@php $adminSidebarDrawer = ! empty($adminSidebarDrawer); @endphp
<div class="flex flex-col h-full">
    @unless($adminSidebarDrawer)
    <!-- Logo -->
    <div class="flex h-[72px] items-center gap-3 border-b border-white/10 px-5 flex-shrink-0">
        <div class="sidebar-logo flex items-center gap-3 min-w-0">
            @php
                $sidebarLogoUrl = $adminPanelLogoUrl
                    ?: \App\Services\AdminPanelBranding::defaultPublicMarkUrl();
                $sidebarLogoVer = @filemtime(public_path(\App\Services\AdminPanelBranding::PUBLIC_MARK_PATH)) ?: time();
                if (is_string($sidebarLogoUrl) && ! str_contains($sidebarLogoUrl, '?') && str_contains($sidebarLogoUrl, 'hesetak-mark')) {
                    $sidebarLogoUrl .= '?v='.$sidebarLogoVer;
                }
            @endphp
            <div class="sidebar-brand-mark">
                <img
                    src="{{ $sidebarLogoUrl }}"
                    alt="{{ config('app.name') }}"
                    width="44"
                    height="44"
                    onerror="this.onerror=null;this.src='{{ \App\Services\AdminPanelBranding::inlineFallbackDataUri() }}';"
                >
            </div>
            <div class="sidebar-logo-text min-w-0">
                <h2 class="text-base font-bold tracking-tight text-white leading-tight truncate">{{ config('app.name') }}</h2>
                <p class="text-[11px] text-white/50 font-medium">{{ __('admin.admin_panel') }}</p>
            </div>
        </div>
    </div>
    @endunless

    @php
        $u = auth()->user();
        $u->loadMissing(['roles.permissions', 'directPermissions']);
        // إخفاء روابط (الإنجازات/الشارات/التقييمات) من السايدبار فقط بدون حذف الصفحات.
        $hideEducationExtrasInSidebar = true;
        // هل المستخدم super_admin بدون RBAC مخصص؟ → يرى كل شيء
        $isFull = $u->isAdmin() && !$u->roles()->exists();
        $rbacStrictEmployee = $u->is_employee && $u->roles()->exists();
        // تسمية القسم + المجموعة الكبيرة (كانت تظهر فقط لـ super_admin بدون أدوار فاختفت عن موظفي RBAC)
        $sidebarStudentHub = $isFull
            || $u->hasPermission('manage.users')
            || $u->hasPermission('manage.students-accounts')
            || $u->hasPermission('manage.enrollments')
            || $u->hasPermission('manage.student-control')
            || $u->hasPermission('manage.support-tickets')
            || $u->hasPermission('manage.consultations')
            || $u->hasPermission('manage.quality-control')
            || $u->hasPermission('manage.tutoring-groups')
            || $u->hasPermission('manage.packages')
            || $u->hasPermission('manage.courses')
            || $u->hasPermission('manage.attendance')
            || $u->hasPermission('view.reports');
    @endphp
    <!-- Navigation -->
    <nav class="sidebar-nav flex-1 px-3 py-4 overflow-y-auto min-h-0">
        <ul class="space-y-0.5">
            {{-- 0) تحليلات الأكاديمية — أقوى قسم قرار --}}
            <li class="sidebar-section-label">ذكاء الأكاديمية</li>
            @php $insightsActive = request()->routeIs('admin.academy-insights.*'); @endphp
            <li>
                <a href="{{ route('admin.academy-insights.index') }}"
                   class="sidebar-link {{ $insightsActive ? 'active' : '' }}"
                   style="{{ $insightsActive ? '' : 'box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);' }}">
                    <i class="fas fa-brain"></i>
                    <span>تحليلات وتوجهات</span>
                    <span class="sidebar-badge bg-accent text-white" style="font-size:9px;letter-spacing:.02em;">LIVE</span>
                </a>
            </li>

            {{-- 1) الرئيسية --}}
            <li class="sidebar-section-label">الرئيسية</li>
            @php $dashboardActive = request()->routeIs('admin.dashboard'); @endphp
            <li>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ $dashboardActive ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>{{ __('admin.dashboard') }}</span>
                </a>
            </li>

            @if(!$rbacStrictEmployee || $u->hasPermission('manage.notifications'))
            @php
                try {
                    $sidebarInboxUnread = \App\Models\Notification::where('user_id', $u->id)->unread()->valid()->count();
                } catch (\Exception $e) {
                    $sidebarInboxUnread = 0;
                }
            @endphp
            <li>
                <a href="{{ route('admin.notifications.inbox') }}" class="sidebar-link {{ request()->routeIs('admin.notifications.inbox') ? 'active' : '' }}">
                    <i class="fas fa-inbox"></i>
                    <span>وارد الإشعارات</span>
                    @if($sidebarInboxUnread > 0)
                        <span class="sidebar-badge bg-rose-500 text-white">{{ $sidebarInboxUnread > 99 ? '99+' : $sidebarInboxUnread }}</span>
                    @endif
                </a>
            </li>
            @endif

            @php $profileActive = request()->routeIs('admin.profile*'); @endphp
            <li>
                <a href="{{ route('admin.profile') }}" class="sidebar-link {{ $profileActive ? 'active' : '' }}">
                    <i class="fas fa-user"></i>
                    <span>{{ __('admin.profile') }}</span>
                </a>
            </li>

            {{-- 2) التشغيل اليومي --}}
            @php
                $showDailyOps = ($isFull || $u->hasPermission('manage.contact-messages') || $u->hasPermission('manage.free-trial-bookings') || $u->hasPermission('manage.users') || $u->hasPermission('manage.personal-branding'));
            @endphp
            @if($showDailyOps)
            <li class="sidebar-section-label">التشغيل</li>
            @endif

            @if($isFull || $u->hasPermission('manage.free-trial-bookings'))
            @php
                try {
                    $sidebarFreeTrialUpcoming = \App\Models\FreeTrialBooking::query()
                        ->whereIn('status', [
                            \App\Models\FreeTrialBooking::STATUS_PENDING,
                            \App\Models\FreeTrialBooking::STATUS_CONFIRMED,
                        ])
                        ->where('starts_at', '>=', now())
                        ->count();
                } catch (\Exception $e) {
                    $sidebarFreeTrialUpcoming = 0;
                }
            @endphp
            <li>
                <a href="{{ route('admin.free-trial-bookings.index') }}" class="sidebar-link {{ request()->routeIs('admin.free-trial-bookings.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i>
                    <span>الحصة المجانية</span>
                    @if($sidebarFreeTrialUpcoming > 0)
                        <span class="sidebar-badge bg-amber-500 text-white">{{ $sidebarFreeTrialUpcoming > 99 ? '99+' : $sidebarFreeTrialUpcoming }}</span>
                    @endif
                </a>
            </li>
            @endif

            @if($isFull || $u->hasPermission('manage.contact-messages'))
            @php
                try {
                    $sidebarContactUnread = \App\Models\ContactMessage::whereNull('read_at')->count();
                } catch (\Exception $e) {
                    $sidebarContactUnread = 0;
                }
            @endphp
            <li>
                <a href="{{ route('admin.contact-messages.index') }}" class="sidebar-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>رسائل التواصل</span>
                    @if($sidebarContactUnread > 0)
                        <span class="sidebar-badge bg-amber-500 text-white">{{ $sidebarContactUnread > 99 ? '99+' : $sidebarContactUnread }}</span>
                    @endif
                </a>
            </li>
            @endif

            {{-- التوظيف: طلبات المعلمين + التفعيل --}}
            @if($isFull || $u->hasPermission('manage.contact-messages') || $u->hasPermission('manage.users') || $u->hasPermission('manage.personal-branding'))
            @php
                try {
                    $sidebarTutorAppsPending = \App\Models\TutorApplication::pending()->count();
                    $sidebarTutorAwaiting = \App\Models\TutorApplication::awaitingActivation()->count();
                } catch (\Exception $e) {
                    $sidebarTutorAppsPending = 0;
                    $sidebarTutorAwaiting = 0;
                }
                $hiringOpen = request()->routeIs('admin.tutor-applications.*') || request()->routeIs('admin.hiring-form.*');
                $hiringBadge = $sidebarTutorAppsPending + $sidebarTutorAwaiting;
            @endphp
            <li class="sidebar-section-label">التوظيف</li>
            <li x-data="{ open: {{ $hiringOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-user-tie"></i>
                        <span>توظيف المعلمين</span>
                        @if($hiringBadge > 0)
                            <span class="sidebar-badge bg-amber-500 text-white">{{ $hiringBadge > 99 ? '99+' : $hiringBadge }}</span>
                        @endif
                    </span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    <li>
                        <a href="{{ route('admin.hiring-form.edit') }}" class="sidebar-sub-link {{ request()->routeIs('admin.hiring-form.*') ? 'active' : '' }}">
                            <i class="fas fa-wpforms"></i><span>منشئ نموذج التقديم</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.tutor-applications.hub') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutor-applications.hub') ? 'active' : '' }}">
                            <i class="fas fa-briefcase"></i><span>لوحة التوظيف</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.tutor-applications.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutor-applications.index') || request()->routeIs('admin.tutor-applications.show') ? 'active' : '' }}">
                            <i class="fas fa-inbox"></i><span>مراجعة الطلبات</span>
                            @if($sidebarTutorAppsPending > 0)
                                <span class="sidebar-badge bg-amber-500 text-white">{{ $sidebarTutorAppsPending }}</span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.tutor-applications.index', ['status' => 'approved']) }}" class="sidebar-sub-link {{ request('status') === 'approved' && request()->routeIs('admin.tutor-applications.index') ? 'active' : '' }}">
                            <i class="fas fa-user-check"></i><span>بانتظار التفعيل</span>
                            @if($sidebarTutorAwaiting > 0)
                                <span class="sidebar-badge bg-sky-500 text-white">{{ $sidebarTutorAwaiting }}</span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.tutor-applications.activated') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutor-applications.activated') ? 'active' : '' }}">
                            <i class="fas fa-chalkboard-teacher"></i><span>المعلمون المفعّلون</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('public.tutor.apply') }}" target="_blank" class="sidebar-sub-link">
                            <i class="fas fa-external-link-alt"></i><span>لينك التقديم</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- 3) الموقع (مجموعة قابلة للطي) --}}
            @php
                $showSiteGroup = $isFull
                    || $u->hasPermission('manage.site-services')
                    || $u->hasPermission('manage.site-testimonials')
                    || $u->hasPermission('manage.system-settings')
                    || $u->hasPermission('manage.about-page')
                    || $u->hasPermission('manage.faq');
                $siteGroupOpen = request()->routeIs('admin.site-services.*')
                    || request()->routeIs('admin.site-testimonials.*')
                    || request()->routeIs('admin.system-settings.*')
                    || request()->routeIs('admin.about.*')
                    || request()->routeIs('admin.faq.*');
            @endphp
            @if($showSiteGroup)
            <li class="sidebar-section-label">الموقع</li>
            <li x-data="{ open: {{ $siteGroupOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-globe"></i>
                        <span>محتوى الموقع</span>
                    </span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.site-services'))
                    <li><a href="{{ route('admin.site-services.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.site-services.*') ? 'active' : '' }}"><i class="fas fa-concierge-bell"></i><span>خدمات الموقع</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.site-testimonials') || $u->hasPermission('manage.site-services'))
                    <li><a href="{{ route('admin.site-testimonials.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.site-testimonials.*') ? 'active' : '' }}"><i class="fas fa-quote-right"></i><span>آراء الرئيسية</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.about-page'))
                    <li><a href="{{ route('admin.about.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.about.*') ? 'active' : '' }}"><i class="fas fa-info-circle"></i><span>من نحن</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.faq')) && Route::has('admin.faq.index'))
                    <li><a href="{{ route('admin.faq.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.faq.*') ? 'active' : '' }}"><i class="fas fa-question-circle"></i><span>الأسئلة الشائعة</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.system-settings'))
                    <li><a href="{{ route('admin.system-settings.edit') }}" class="sidebar-sub-link {{ request()->routeIs('admin.system-settings.*') ? 'active' : '' }}"><i class="fas fa-sliders-h"></i><span>إعدادات النظام</span></a></li>
                    @endif
                </ul>
            </li>
            @endif

            @if($sidebarStudentHub)
            <li class="sidebar-section-label">الطلاب</li>
            @endif

            @if($sidebarStudentHub)
            {{-- الطلاب والخدمات المرتبطة بهم --}}
            @php
                $studentControlOpen = request()->routeIs('admin.students-accounts.*')
                    || request()->routeIs('admin.support-tickets.*')
                    || request()->routeIs('admin.support-inquiry-categories.*')
                    || request()->routeIs('admin.consultations.*')
                    || request()->routeIs('admin.one-to-one-sessions.*')
                    || request()->routeIs('admin.student-lesson-cleanup.*')
                    || request()->routeIs('admin.placement.*')
                    || request()->routeIs('admin.student-entitlements.*')
                    || request()->routeIs('admin.tutoring-subscriptions.*')
                    || request()->routeIs('admin.tutoring-group-bookings.*')
                    || request()->routeIs('admin.tutor-work-schedules.*')
                    || request()->routeIs('admin.tutoring-groups.*')
                    || request()->routeIs('admin.online-enrollments.*')
                    || request()->routeIs('admin.attendance.*')
                    || request()->routeIs('admin.private-courses.*');
            @endphp
            <li x-data="{ open: {{ $studentControlOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-user-graduate"></i>
                        <span>الطلاب والخدمات</span>
                    </span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if(($isFull || $u->hasPermission('manage.users') || $u->hasPermission('manage.students-accounts')) && Route::has('admin.students-accounts.index'))
                    <li>
                        <a href="{{ route('admin.students-accounts.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.students-accounts.*') ? 'active' : '' }}">
                            <i class="fas fa-users"></i><span>الطلاب والحسابات</span>
                            @php
                                try {
                                    $studentsCount = \App\Models\User::where('role', 'student')->count();
                                } catch (\Exception $e) {
                                    $studentsCount = 0;
                                }
                            @endphp
                            @if($studentsCount > 0)
                                <span class="sidebar-badge bg-indigo-500 text-white">{{ $studentsCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.tutoring-groups') || $u->hasPermission('manage.courses') || $u->hasPermission('manage.packages')) && Route::has('admin.placement.index'))
                    <li>
                        <a href="{{ route('admin.placement.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.placement.*') ? 'active' : '' }}">
                            <i class="fas fa-user-check"></i><span>التسكين</span>
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.packages') || $u->hasPermission('manage.tutoring-groups') || $u->hasPermission('manage.users')) && Route::has('admin.student-entitlements.index'))
                    <li>
                        <a href="{{ route('admin.student-entitlements.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.student-entitlements.*') ? 'active' : '' }}">
                            <i class="fas fa-coins"></i><span>أرصدة الطلاب</span>
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.packages') || $u->hasPermission('manage.tutoring-groups') || $u->hasPermission('manage.users')) && Route::has('admin.tutoring-subscriptions.index'))
                    <li>
                        <a href="{{ route('admin.tutoring-subscriptions.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutoring-subscriptions.*') ? 'active' : '' }}">
                            <i class="fas fa-id-card"></i><span>اشتراكات التدريس</span>
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.tutoring-groups') || $u->hasPermission('manage.courses')) && Route::has('admin.one-to-one-sessions.index'))
                    <li>
                        <a href="{{ route('admin.one-to-one-sessions.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.one-to-one-sessions.*') ? 'active' : '' }}">
                            <i class="fas fa-user-clock"></i><span>حصص 1:1</span>
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.tutoring-groups') || $u->hasPermission('manage.courses') || $u->hasPermission('manage.live-sessions')) && Route::has('admin.student-lesson-cleanup.index'))
                    <li>
                        <a href="{{ route('admin.student-lesson-cleanup.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.student-lesson-cleanup.*') ? 'active' : '' }}">
                            <i class="fas fa-broom"></i><span>تنظيف الحصص والتجارب</span>
                        </a>
                    </li>
                    @endif

                    @if(config('admin_ui.show_group_bookings', false) && ($isFull || $u->hasPermission('manage.tutoring-groups')) && Route::has('admin.tutoring-group-bookings.index'))
                    <li>
                        <a href="{{ route('admin.tutoring-group-bookings.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutoring-group-bookings.*') ? 'active' : '' }}">
                            <i class="fas fa-calendar-check"></i><span>تسكين الفصول والحجوزات</span>
                        </a>
                    </li>
                    @endif

                    @if(config('admin_ui.show_group_classes', false) && ($isFull || $u->hasPermission('manage.tutoring-groups')) && Route::has('admin.tutoring-groups.index'))
                    <li>
                        <a href="{{ route('admin.tutoring-groups.index', 'collective') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutoring-groups.*') && request()->route('type') === 'collective' ? 'active' : '' }}">
                            <i class="fas fa-school"></i><span>فصول المدرسة</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.tutoring-groups.index', 'individual') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutoring-groups.*') && request()->route('type') === 'individual' ? 'active' : '' }}">
                            <i class="fas fa-user"></i><span>المجموعات الفردية</span>
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.tutoring-groups')) && Route::has('admin.tutor-work-schedules.index'))
                    <li>
                        <a href="{{ route('admin.tutor-work-schedules.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutor-work-schedules.*') ? 'active' : '' }}">
                            <i class="fas fa-calendar-week"></i><span>جداول عمل المدربين</span>
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.enrollments')) && Route::has('admin.online-enrollments.index'))
                    <li>
                        <a href="{{ route('admin.online-enrollments.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.online-enrollments.*') ? 'active' : '' }}">
                            <i class="fas fa-laptop"></i><span>{{ __('admin.online_enrollments') }}</span>
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.attendance')) && Route::has('admin.attendance.index'))
                    <li>
                        <a href="{{ route('admin.attendance.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}">
                            <i class="fas fa-user-check"></i><span>الحضور والانصراف</span>
                        </a>
                    </li>
                    @endif

                    @if($isFull && Route::has('admin.private-courses.threads'))
                    <li>
                        <a href="{{ route('admin.private-courses.threads') }}" class="sidebar-sub-link {{ request()->routeIs('admin.private-courses.threads*') ? 'active' : '' }}">
                            <i class="fas fa-comments"></i><span>رسائل بريفيت</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.private-courses.receptions') }}" class="sidebar-sub-link {{ request()->routeIs('admin.private-courses.receptions*') ? 'active' : '' }}">
                            <i class="fas fa-handshake"></i><span>استقبال الطلاب</span>
                        </a>
                    </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.support-tickets')) && Route::has('admin.support-tickets.index'))
                    <li>
                        <a href="{{ route('admin.support-tickets.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.support-tickets.*') ? 'active' : '' }}">
                            <i class="fas fa-headset"></i><span>الدعم الفني</span>
                        </a>
                    </li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.support-tickets')) && Route::has('admin.support-inquiry-categories.index'))
                    <li>
                        <a href="{{ route('admin.support-inquiry-categories.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.support-inquiry-categories.*') ? 'active' : '' }}">
                            <i class="fas fa-tags"></i><span>تصنيفات الدعم</span>
                        </a>
                    </li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.consultations')) && Route::has('admin.consultations.index'))
                    <li>
                        <a href="{{ route('admin.consultations.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.consultations.*') ? 'active' : '' }}">
                            <i class="fas fa-comments-dollar"></i><span>استشارات المدربين</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('manage.orders') || $u->hasPermission('manage.leads') || $u->hasPermission('view.sales-analytics'))
            <li class="sidebar-section-label">المبيعات</li>
            @php $salesSectionOpen = request()->routeIs('admin.orders.*') || request()->routeIs('admin.sales.index') || request()->routeIs('admin.sales.leads.*') || request()->routeIs('admin.crm.*'); @endphp
            <li x-data="{ open: {{ $salesSectionOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-shopping-cart"></i><span>المبيعات و CRM</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.leads'))
                    <li>
                        <a href="{{ route('admin.crm.dashboard') }}" class="sidebar-sub-link {{ request()->routeIs('admin.crm.dashboard') ? 'active' : '' }}">
                            <i class="fas fa-funnel-dollar"></i><span>لوحة CRM</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.crm.pipeline') }}" class="sidebar-sub-link {{ request()->routeIs('admin.crm.pipeline') ? 'active' : '' }}">
                            <i class="fas fa-project-diagram"></i><span>Pipeline</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.crm.leads.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.crm.leads.*') ? 'active' : '' }}">
                            <i class="fas fa-user-plus"></i><span>عملاء CRM</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.crm.commissions.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.crm.commissions.*') ? 'active' : '' }}">
                            <i class="fas fa-coins"></i><span>عمولات CRM</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.crm.audit.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.crm.audit.*') ? 'active' : '' }}">
                            <i class="fas fa-history"></i><span>سجل CRM</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.crm.groups.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.crm.groups.*') ? 'active' : '' }}">
                            <i class="fas fa-users"></i><span>فرق CRM</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.sales.leads.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.sales.leads.*') ? 'active' : '' }}">
                            <i class="fas fa-list"></i><span>Leads القديمة</span>
                        </a>
                    </li>
                    @endif
                    @if($isFull || $u->hasPermission('view.sales-analytics'))
                    <li>
                        <a href="{{ route('admin.sales.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.sales.index') ? 'active' : '' }}">
                            <i class="fas fa-chart-line"></i><span>تحليلات المبيعات</span>
                        </a>
                    </li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.orders'))
                    <li>
                        <a href="{{ route('admin.orders.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <i class="fas fa-shopping-bag"></i><span>الطلبات</span>
                            @php try { $pendingOrdersSales = \App\Models\Order::where('status', 'pending')->count(); } catch (\Exception $e) { $pendingOrdersSales = 0; } @endphp
                            @if($pendingOrdersSales > 0)<span class="sidebar-badge bg-indigo-500 text-white">{{ $pendingOrdersSales }}</span>@endif
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('manage.invoices') || $u->hasPermission('manage.payments') || $u->hasPermission('manage.transactions') || $u->hasPermission('manage.wallets') || $u->hasPermission('view.wallets') || $u->hasPermission('manage.salaries') || $u->hasPermission('manage.expenses') || $u->hasPermission('manage.instructor-accounts') || $u->hasPermission('manage.installments'))
            <li class="sidebar-section-label">المالية</li>
            @php $accountingSectionOpen = request()->routeIs('admin.invoices.*') || request()->routeIs('admin.payments.*') || request()->routeIs('admin.wallets.*') || request()->routeIs('admin.salaries.*') || request()->routeIs('admin.expenses.*') || request()->routeIs('admin.installments.*') || request()->routeIs('admin.accounting.*') || request()->routeIs('admin.transactions.*'); @endphp
            <li x-data="{ open: {{ $accountingSectionOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-calculator"></i><span>المحاسبة</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.invoices'))
                    <li><a href="{{ route('admin.invoices.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}"><i class="fas fa-file-invoice"></i><span>الفواتير</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.payments'))
                    <li><a href="{{ route('admin.payments.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"><i class="fas fa-credit-card"></i><span>المدفوعات</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.transactions'))
                    <li><a href="{{ route('admin.transactions.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}"><i class="fas fa-exchange-alt"></i><span>المعاملات</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.wallets') || $u->hasPermission('view.wallets'))
                    <li><a href="{{ route('admin.wallets.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.wallets.*') ? 'active' : '' }}"><i class="fas fa-wallet"></i><span>المحافظ</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.salaries'))
                    <li><a href="{{ route('admin.salaries.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.salaries.*') ? 'active' : '' }}"><i class="fas fa-money-check-alt"></i><span>رواتب المدربين</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.instructor-accounts'))
                    <li><a href="{{ route('admin.accounting.instructor-accounts.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.accounting.instructor-accounts.*') ? 'active' : '' }}"><i class="fas fa-user-tie"></i><span>حسابات المدربين</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.expenses'))
                    <li><a href="{{ route('admin.expenses.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}"><i class="fas fa-receipt"></i><span>المصروفات</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.installments'))
                    <li><a href="{{ route('admin.installments.plans.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.installments.plans.*') ? 'active' : '' }}"><i class="fas fa-layer-group"></i><span>خطط التقسيط</span></a></li>
                    <li><a href="{{ route('admin.installments.agreements.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.installments.agreements.*') ? 'active' : '' }}"><i class="fas fa-handshake"></i><span>اتفاقيات التقسيط</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.invoices') || $u->hasPermission('manage.payments') || $u->hasPermission('manage.transactions'))
                    <li><a href="{{ route('admin.accounting.reports') }}" class="sidebar-sub-link {{ request()->routeIs('admin.accounting.reports*') ? 'active' : '' }}"><i class="fas fa-chart-pie"></i><span>تقارير المحاسبة</span></a></li>
                    @endif
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('manage.users') || $u->hasPermission('manage.notifications') || $u->hasPermission('view.activity-log') || $u->hasPermission('view.statistics') || $u->hasPermission('manage.email-broadcasts') || $u->hasPermission('manage.performance') || $u->hasPermission('manage.two-factor-logs') || $u->hasPermission('manage.system-settings'))
            <li class="sidebar-section-label">النظام</li>
            {{-- إدارة النظام --}}
            @php
                $systemNotificationsActive = request()->routeIs('admin.notifications.*')
                    && !request()->routeIs('admin.notifications.inbox');
                $systemManagementOpen = request()->routeIs('admin.users.*')
                    || $systemNotificationsActive
                    || request()->routeIs('admin.employee-notifications.*')
                    || request()->routeIs('admin.email-broadcasts.*')
                    || request()->routeIs('admin.activity-log*')
                    || request()->routeIs('admin.two-factor-logs.*')
                    || request()->routeIs('admin.statistics.*')
                    || request()->routeIs('admin.performance.*')
                    || request()->routeIs('admin.payment-gateways.*');
            @endphp
            <li x-data="{ open: {{ $systemManagementOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-cogs"></i>
                        <span>إدارة النظام</span>
                    </span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.users'))
                    <li><a href="{{ route('admin.users.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"><i class="fas fa-users"></i><span>{{ __('admin.users') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.system-settings'))
                    <li><a href="{{ route('admin.payment-gateways.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.payment-gateways.*') ? 'active' : '' }}"><i class="fas fa-credit-card"></i><span>بوابات الدفع</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.notifications'))
                    <li><a href="{{ route('admin.notifications.index') }}" class="sidebar-sub-link {{ $systemNotificationsActive ? 'active' : '' }}"><i class="fas fa-bell"></i><span>{{ __('admin.notifications') }}</span></a></li>
                    <li><a href="{{ route('admin.employee-notifications.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.employee-notifications.*') ? 'active' : '' }}"><i class="fas fa-user-tie"></i><span>{{ __('admin.employee_notifications') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.email-broadcasts'))
                    <li><a href="{{ route('admin.email-broadcasts.index', 'all_users') }}" class="sidebar-sub-link {{ request()->routeIs('admin.email-broadcasts.*') ? 'active' : '' }}"><i class="fas fa-envelope"></i><span>إشعارات البريد (Gmail)</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('view.activity-log'))
                    <li><a href="{{ route('admin.activity-log') }}" class="sidebar-sub-link {{ request()->routeIs('admin.activity-log*') ? 'active' : '' }}"><i class="fas fa-history"></i><span>{{ __('admin.activity_log') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.two-factor-logs'))
                    <li><a href="{{ route('admin.two-factor-logs.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.two-factor-logs.*') ? 'active' : '' }}"><i class="fas fa-shield-alt"></i><span>{{ __('admin.two_factor_logs') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('view.statistics'))
                    <li><a href="{{ route('admin.statistics.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.statistics*') ? 'active' : '' }}"><i class="fas fa-chart-bar"></i><span>{{ __('admin.statistics') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.performance'))
                    <li><a href="{{ route('admin.performance.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.performance.*') ? 'active' : '' }}"><i class="fas fa-tachometer-alt"></i><span>{{ __('admin.performance') }}</span></a></li>
                    @endif
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('manage.agreements') || $u->hasPermission('manage.withdrawals') || $u->hasPermission('manage.employee-agreements'))
            <li class="sidebar-section-label">الاتفاقيات</li>
            @php $agreementsOpen = request()->routeIs('admin.agreements.*') || request()->routeIs('admin.withdrawals.*') || request()->routeIs('admin.employee-agreements.*'); @endphp
            <li x-data="{ open: {{ $agreementsOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-handshake"></i><span>الاتفاقيات</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if(($isFull || $u->hasPermission('manage.agreements')) && Route::has('admin.agreements.index'))
                    <li><a href="{{ route('admin.agreements.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.agreements.*') ? 'active' : '' }}"><i class="fas fa-file-contract"></i><span>{{ __('admin.instructor_agreements') }}</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.employee-agreements')) && Route::has('admin.employee-agreements.index'))
                    <li><a href="{{ route('admin.employee-agreements.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.employee-agreements.*') ? 'active' : '' }}"><i class="fas fa-user-tie"></i><span>{{ __('admin.employee_agreements') }}</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.withdrawals')) && Route::has('admin.withdrawals.index'))
                    <li>
                        <a href="{{ route('admin.withdrawals.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.withdrawals.*') ? 'active' : '' }}">
                            <i class="fas fa-money-bill-wave"></i><span>{{ __('admin.withdrawal_requests') }}</span>
                            @php try { $pendingWithdrawals = \App\Models\WithdrawalRequest::where('status', 'pending')->count(); } catch (\Exception $e) { $pendingWithdrawals = 0; } @endphp
                            @if($pendingWithdrawals > 0)<span class="sidebar-badge bg-amber-400 text-amber-900">{{ $pendingWithdrawals }}</span>@endif
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('manage.coupons') || $u->hasPermission('manage.referrals') || $u->hasPermission('manage.popup-ads') || $u->hasPermission('manage.personal-branding'))
            {{-- إدارة التسويق --}}
            @php
                $marketingOpen = request()->routeIs('admin.coupons.*') || request()->routeIs('admin.coupon-commissions.*') || request()->routeIs('admin.referral-programs.*') || request()->routeIs('admin.referrals.*') || request()->routeIs('admin.personal-branding.*') || request()->routeIs('admin.popup-ads.*');
            @endphp
            <li class="sidebar-section-label">التسويق</li>
            <li x-data="{ open: {{ $marketingOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-tags"></i><span>التسويق</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.popup-ads'))
                    <li><a href="{{ route('admin.popup-ads.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.popup-ads.*') ? 'active' : '' }}"><i class="fas fa-bullhorn"></i><span>{{ __('admin.popup_ads') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.personal-branding'))
                    <li><a href="{{ route('admin.personal-branding.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.personal-branding.*') ? 'active' : '' }}"><i class="fas fa-user-tie"></i><span>{{ __('admin.personal_branding') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.coupons'))
                    <li><a href="{{ route('admin.coupons.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.coupons.*') && !request()->routeIs('admin.coupon-commissions.*') ? 'active' : '' }}"><i class="fas fa-ticket-alt"></i><span>{{ __('admin.coupons_discounts') }}</span></a></li>
                    <li><a href="{{ route('admin.coupon-commissions.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.coupon-commissions.*') ? 'active' : '' }}"><i class="fas fa-coins"></i><span>عمولات كوبونات التسويق</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.referrals'))
                    <li><a href="{{ route('admin.referral-programs.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.referral-programs.*') ? 'active' : '' }}"><i class="fas fa-gift"></i><span>{{ __('admin.referral_programs') }}</span></a></li>
                    <li><a href="{{ route('admin.referrals.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.referrals.*') ? 'active' : '' }}"><i class="fas fa-user-friends"></i><span>{{ __('admin.referrals') }}</span></a></li>
                    @endif
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('manage.packages') || $u->hasPermission('manage.tutoring-groups'))
            <li class="sidebar-section-label">مدفوع</li>
            @php
                $paidPackagesOpen = request()->routeIs('admin.packages.*') || request()->routeIs('admin.service-packages.*') || request()->routeIs('admin.service-package-pricing-rules.*');
            @endphp
            <li x-data="{ open: {{ $paidPackagesOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-credit-card"></i><span>الباقات والأسعار</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.packages'))
                    <li><a href="{{ route('admin.packages.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}"><i class="fas fa-tags"></i><span>{{ __('admin.pricing_packages') }}</span></a></li>
                    @endif
                    <li><a href="{{ route('admin.service-packages.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.service-packages.index') || request()->routeIs('admin.service-packages.create') || request()->routeIs('admin.service-packages.edit') ? 'active' : '' }}"><i class="fas fa-box-open"></i><span>باقات الحصص</span></a></li>
                    <li><a href="{{ route('admin.service-packages.grant') }}" class="sidebar-sub-link {{ request()->routeIs('admin.service-packages.grant*') ? 'active' : '' }}"><i class="fas fa-user-plus"></i><span>منح باقة يدوياً</span></a></li>
                    <li><a href="{{ route('admin.service-package-pricing-rules.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.service-package-pricing-rules.*') ? 'active' : '' }}"><i class="fas fa-sliders"></i><span>تسعير خصص باقتك</span></a></li>
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('manage.enrollments') || $u->hasPermission('manage.courses') || $u->hasPermission('manage.exams') || $u->hasPermission('manage.lectures') || $u->hasPermission('manage.assignments') || $u->hasPermission('manage.live-sessions') || $u->hasPermission('manage.live-servers') || $u->hasPermission('manage.question-bank') || $u->hasPermission('manage.attendance') || $u->hasPermission('manage.achievements') || $u->hasPermission('manage.badges') || $u->hasPermission('manage.reviews') || $u->hasPermission('manage.academic-years') || $u->hasPermission('manage.academic-subjects'))
            <li class="sidebar-section-label">التعليم</li>
            {{-- مكتبات الطلاب: ماتريال + فيديو + مناهج --}}
            @if($isFull || $u->hasPermission('manage.lectures') || $u->hasPermission('manage.courses') || $u->hasPermission('manage.live-sessions') || $u->hasPermission('manage.academic-years') || $u->hasPermission('manage.academic-subjects'))
            @php
                $librariesOpen = request()->routeIs('admin.libraries.*') || request()->routeIs('admin.curriculum-library.*');
            @endphp
            <li x-data="{ open: {{ $librariesOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-layer-group"></i><span>مكتبة الملفات</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if(Route::has('admin.libraries.index'))
                    <li><a href="{{ route('admin.libraries.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.libraries.index') ? 'active' : '' }}"><i class="fas fa-th-large"></i><span>مركز الملفات</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.lectures') || $u->hasPermission('manage.courses')) && Route::has('admin.libraries.materials.index'))
                    <li><a href="{{ route('admin.libraries.materials.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.libraries.materials.*') ? 'active' : '' }}"><i class="fas fa-book-open"></i><span>ماتريال (ملفات)</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.curriculum-library') || $u->hasPermission('manage.courses') || $u->hasPermission('manage.lectures')) && Route::has('admin.curriculum-library.index'))
                    <li><a href="{{ route('admin.curriculum-library.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.curriculum-library.*') ? 'active' : '' }}"><i class="fas fa-chalkboard"></i><span>مناهج تفاعلية (ملفات)</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.live-sessions') || $u->hasPermission('manage.lectures') || $u->hasPermission('manage.courses')) && Route::has('admin.libraries.videos.index'))
                    <li><a href="{{ route('admin.libraries.videos.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.libraries.videos.*') ? 'active' : '' }}"><i class="fas fa-film"></i><span>مكتبة الفيديوهات</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.academic-years') || $u->hasPermission('manage.academic-subjects') || $u->hasPermission('manage.courses')) && Route::has('admin.libraries.curriculum.index'))
                    <li><a href="{{ route('admin.libraries.curriculum.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.libraries.curriculum.*') ? 'active' : '' }}"><i class="fas fa-sitemap"></i><span>هيكل الكورسات</span></a></li>
                    @endif
                </ul>
            </li>
            @endif
            @if($isFull || $u->hasPermission('manage.users') || $u->hasPermission('manage.tutoring-groups') || $u->hasPermission('manage.courses'))
            @php $teacherControlOpen = request()->routeIs('admin.teachers.*'); @endphp
            <li x-data="{ open: {{ $teacherControlOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-user-tie"></i><span>تحكم المعلمين</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    <li>
                        <a href="{{ route('admin.teachers.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.teachers.index') ? 'active' : '' }}">
                            <i class="fas fa-th-large"></i><span>مركز التحكم</span>
                        </a>
                    </li>
                    @if(Route::has('admin.academy-instructors.index') && ($isFull || $u->hasPermission('manage.academic-years') || $u->hasPermission('manage.tutoring-groups') || $u->hasPermission('manage.users')))
                    <li>
                        <a href="{{ route('admin.academy-instructors.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.academy-instructors.*') ? 'active' : '' }}">
                            <i class="fas fa-user-check"></i><span>توصيف الطلاب</span>
                        </a>
                    </li>
                    @endif
                    @if(Route::has('admin.tutor-work-schedules.index') && ($isFull || $u->hasPermission('manage.tutoring-groups')))
                    <li>
                        <a href="{{ route('admin.tutor-work-schedules.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tutor-work-schedules.*') ? 'active' : '' }}">
                            <i class="fas fa-calendar-alt"></i><span>جداول العمل</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif

            {{-- إدارة المحتوى — الكورسات ومسارات التعلم --}}
            @if($isFull || $u->hasPermission('manage.courses') || $u->hasPermission('manage.tutoring-groups') || $u->hasPermission('manage.academic-years') || $u->hasPermission('manage.academic-subjects') || $u->hasPermission('manage.lectures') || $u->hasPermission('manage.assignments') || $u->hasPermission('manage.exams') || $u->hasPermission('manage.question-bank') || $u->hasPermission('manage.attendance') || $u->hasPermission('manage.achievements') || $u->hasPermission('manage.badges') || $u->hasPermission('manage.reviews'))
            @php
                $contentManagementOpen = request()->routeIs('admin.advanced-courses.*') || request()->routeIs('admin.academic-years.*') || request()->routeIs('admin.academic-subjects.*') || request()->routeIs('admin.course-categories.*') || request()->routeIs('admin.exams.*') || request()->routeIs('admin.question-bank.*') || request()->routeIs('admin.question-categories.*') || request()->routeIs('admin.lectures.*') || request()->routeIs('admin.assignments.*') || request()->routeIs('admin.achievements.*') || request()->routeIs('admin.badges.*') || request()->routeIs('admin.reviews.*');
            @endphp
            <li x-data="{ open: {{ $contentManagementOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-folder"></i><span>المحتوى والكورسات</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.academic-years'))
                    <li><a href="{{ route('admin.academic-years.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}"><i class="fas fa-school"></i><span>سنوات المدرسة</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.academic-subjects') || $u->hasPermission('manage.academic-years'))
                    <li><a href="{{ route('admin.academic-subjects.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.academic-subjects.*') ? 'active' : '' }}"><i class="fas fa-book"></i><span>مواد المدرسة</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.courses'))
                    @php $advancedCoursesActive = request()->routeIs('admin.advanced-courses.*') || request()->routeIs('admin.courses.lessons.*'); @endphp
                    <li><a href="{{ route('admin.advanced-courses.index') }}" class="sidebar-sub-link {{ $advancedCoursesActive ? 'active' : '' }}"><i class="fas fa-graduation-cap"></i><span>{{ __('admin.courses_management') }}</span></a></li>
                    <li><a href="{{ route('admin.course-categories.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.course-categories.*') ? 'active' : '' }}"><i class="fas fa-tags"></i><span>{{ __('admin.course_categories') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.lectures'))
                    <li><a href="{{ route('admin.lectures.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.lectures.*') ? 'active' : '' }}"><i class="fas fa-video"></i><span>{{ __('admin.lectures') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.assignments'))
                    <li><a href="{{ route('admin.assignments.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}"><i class="fas fa-tasks"></i><span>{{ __('admin.assignments_projects') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.exams'))
                    <li><a href="{{ route('admin.exams.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}"><i class="fas fa-clipboard-check"></i><span>{{ __('admin.exams') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.question-bank'))
                    @php $questionBankActive = request()->routeIs('admin.question-bank.*') || request()->routeIs('admin.question-categories.*'); @endphp
                    <li><a href="{{ route('admin.question-bank.index') }}" class="sidebar-sub-link {{ $questionBankActive ? 'active' : '' }}"><i class="fas fa-database"></i><span>{{ __('admin.question_bank') }}</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.achievements')) && !$hideEducationExtrasInSidebar)
                    <li><a href="{{ route('admin.achievements.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.achievements.*') ? 'active' : '' }}"><i class="fas fa-trophy"></i><span>الإنجازات</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.badges')) && !$hideEducationExtrasInSidebar)
                    <li><a href="{{ route('admin.badges.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.badges.*') ? 'active' : '' }}"><i class="fas fa-medal"></i><span>الشارات</span></a></li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.reviews')) && !$hideEducationExtrasInSidebar)
                    <li><a href="{{ route('admin.reviews.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"><i class="fas fa-star-half-alt"></i><span>التقييمات والمراجعات</span></a></li>
                    @endif
                </ul>
            </li>
            @endif

            {{-- التحكم في جلسات البث المباشر --}}
            @if($isFull || $u->hasPermission('manage.live-sessions') || $u->hasPermission('manage.live-servers'))
            @php
                $liveOpen = request()->routeIs('admin.live-sessions.*')
                    || request()->routeIs('admin.live-recordings.*')
                    || request()->routeIs('admin.classroom-recordings.*')
                    || request()->routeIs('admin.live-servers.*')
                    || request()->routeIs('admin.live-settings.*')
                    || request()->routeIs('admin.n8n.live-session-reports.*')
                    || request()->routeIs('admin.n8n.settings');
            @endphp
            <li x-data="{ open: {{ $liveOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-broadcast-tower"></i>
                        <span>البث المباشر</span>
                    </span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if(($isFull || $u->hasPermission('manage.live-sessions')) && Route::has('admin.live-sessions.index'))
                        <li>
                            <a href="{{ route('admin.live-sessions.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.live-sessions.*') ? 'active' : '' }}">
                                <i class="fas fa-video"></i><span>جلسات البث المباشر</span>
                            </a>
                        </li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.live-sessions')) && Route::has('admin.live-recordings.index'))
                        <li>
                            <a href="{{ route('admin.live-recordings.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.live-recordings.*') ? 'active' : '' }}">
                                <i class="fas fa-play-circle"></i><span>تسجيلات الجلسات</span>
                            </a>
                        </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.live-sessions')) && Route::has('admin.n8n.live-session-reports.index'))
                        <li>
                            <a href="{{ route('admin.n8n.live-session-reports.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.n8n.live-session-reports.*') ? 'active' : '' }}">
                                <i class="fas fa-robot"></i><span>تقارير n8n</span>
                            </a>
                        </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.live-servers')) && Route::has('admin.n8n.settings'))
                        <li>
                            <a href="{{ route('admin.n8n.settings') }}" class="sidebar-sub-link {{ request()->routeIs('admin.n8n.settings') ? 'active' : '' }}">
                                <i class="fas fa-plug"></i><span>إعداد تكامل n8n</span>
                            </a>
                        </li>
                    @endif

                    @if(($isFull || $u->hasPermission('manage.live-sessions')) && Route::has('admin.classroom-recordings.index'))
                        <li>
                            <a href="{{ route('admin.classroom-recordings.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.classroom-recordings.*') ? 'active' : '' }}">
                                <i class="fas fa-chalkboard"></i><span>تسجيلات Classroom</span>
                            </a>
                        </li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.live-servers')) && Route::has('admin.live-servers.index'))
                        <li>
                            <a href="{{ route('admin.live-servers.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.live-servers.index') || request()->routeIs('admin.live-servers.create') || request()->routeIs('admin.live-servers.edit') ? 'active' : '' }}">
                                <i class="fas fa-server"></i><span>سيرفرات البث (VPS)</span>
                            </a>
                        </li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.live-servers')) && Route::has('admin.live-servers.control'))
                        <li>
                            <a href="{{ route('admin.live-servers.control') }}" class="sidebar-sub-link {{ request()->routeIs('admin.live-servers.control') ? 'active' : '' }}">
                                <i class="fas fa-tachometer-alt"></i><span>لوحة التحكم بالسيرفرات</span>
                            </a>
                        </li>
                    @endif
                    @if(($isFull || $u->hasPermission('manage.live-servers')) && Route::has('admin.live-settings.index'))
                        <li>
                            <a href="{{ route('admin.live-settings.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.live-settings.*') ? 'active' : '' }}">
                                <i class="fas fa-sliders-h"></i><span>إعدادات نظام اللايف</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
            @endif

            @endif

            @if($isFull || $u->hasPermission('manage.users') || $u->hasPermission('manage.tasks') || $u->hasPermission('manage.leaves') || $u->hasPermission('manage.instructor-requests') || $u->hasPermission('manage.employee-agreements') || $u->hasPermission('academic_supervision.manage'))
            <li class="sidebar-section-label">الفريق</li>
            {{-- إدارة الموظفين --}}
            @php $employeesOpen = request()->routeIs('admin.employees.*') || request()->routeIs('admin.employee-jobs.*') || request()->routeIs('admin.employee-tasks.*') || request()->routeIs('admin.leaves.*') || request()->routeIs('admin.tasks.*') || request()->routeIs('admin.instructor-requests.*') || request()->routeIs('admin.academic-supervision.*'); @endphp
            <li x-data="{ open: {{ $employeesOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-users-cog"></i><span>الموظفون والمدربون</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.users'))
                    <li><a href="{{ route('admin.employees.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}"><i class="fas fa-user-tie"></i><span>{{ __('admin.employees') }}</span></a></li>
                    <li><a href="{{ route('admin.employee-jobs.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.employee-jobs.*') ? 'active' : '' }}"><i class="fas fa-briefcase"></i><span>{{ __('admin.jobs') }}</span></a></li>
                    <li>
                        <a href="{{ route('admin.employee-tasks.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.employee-tasks.*') ? 'active' : '' }}">
                            <i class="fas fa-tasks"></i><span>{{ __('admin.employee_tasks') }}</span>
                            @php try { $pendingTasks = \App\Models\EmployeeTask::where('status', 'pending')->count(); } catch (\Exception $e) { $pendingTasks = 0; } @endphp
                            @if($pendingTasks > 0)<span class="sidebar-badge bg-amber-400 text-amber-900">{{ $pendingTasks }}</span>@endif
                        </a>
                    </li>
                    @endif
                    @if($isFull || $u->hasPermission('academic_supervision.manage'))
                    <li><a href="{{ route('admin.academic-supervision.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.academic-supervision.*') ? 'active' : '' }}"><i class="fas fa-user-graduate"></i><span>الإشراف الأكاديمي</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.tasks'))
                    <li>
                        <a href="{{ route('admin.tasks.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.tasks.*') ? 'active' : '' }}">
                            <i class="fas fa-chalkboard-teacher"></i><span>{{ __('admin.instructor_tasks') }}</span>
                            @php try { $pendingInstructorTasks = \App\Models\Task::whereIn('user_id', \App\Models\User::whereIn('role', ['instructor', 'teacher'])->pluck('id'))->where('status', 'pending')->count(); } catch (\Exception $e) { $pendingInstructorTasks = 0; } @endphp
                            @if($pendingInstructorTasks > 0)<span class="sidebar-badge bg-amber-500 text-white">{{ $pendingInstructorTasks }}</span>@endif
                        </a>
                    </li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.instructor-requests'))
                    <li>
                        <a href="{{ route('admin.instructor-requests.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.instructor-requests.*') ? 'active' : '' }}">
                            <i class="fas fa-inbox"></i><span>{{ __('admin.instructor_requests_join') }}</span>
                            @php try { $pendingInstructorRequests = \App\Models\InstructorRequest::where('status', 'pending')->count(); } catch (\Exception $e) { $pendingInstructorRequests = 0; } @endphp
                            @if($pendingInstructorRequests > 0)<span class="sidebar-badge bg-amber-500 text-white">{{ $pendingInstructorRequests }}</span>@endif
                        </a>
                    </li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.leaves'))
                    <li>
                        <a href="{{ route('admin.leaves.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.leaves.*') ? 'active' : '' }}">
                            <i class="fas fa-calendar-alt"></i><span>{{ __('admin.leaves') }}</span>
                            @php try { $pendingLeaves = \App\Models\LeaveRequest::where('status', 'pending')->count(); } catch (\Exception $e) { $pendingLeaves = 0; } @endphp
                            @if($pendingLeaves > 0)<span class="sidebar-badge bg-amber-400 text-amber-900">{{ $pendingLeaves }}</span>@endif
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('view.statistics') || $u->hasPermission('manage.quality-control') || $u->hasPermission('manage.student-control'))
            {{-- الرقابة والجودة --}}
            @php $qualityControlOpen = request()->routeIs('admin.quality-control.*'); @endphp
            <li x-data="{ open: {{ $qualityControlOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-shield-alt"></i><span>الرقابة والجودة</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('manage.quality-control') || $u->hasPermission('view.statistics'))
                    <li><a href="{{ route('admin.quality-control.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.quality-control.index') ? 'active' : '' }}"><i class="fas fa-tachometer-alt"></i><span>{{ __('admin.control_panel') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.quality-control') || $u->hasPermission('manage.student-control'))
                    <li><a href="{{ route('admin.quality-control.students') }}" class="sidebar-sub-link {{ request()->routeIs('admin.quality-control.students') ? 'active' : '' }}"><i class="fas fa-user-graduate"></i><span>{{ __('admin.student_control') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('manage.quality-control'))
                    <li><a href="{{ route('admin.quality-control.instructors') }}" class="sidebar-sub-link {{ request()->routeIs('admin.quality-control.instructors') ? 'active' : '' }}"><i class="fas fa-chalkboard-teacher"></i><span>{{ __('admin.instructor_control') }}</span></a></li>
                    <li><a href="{{ route('admin.quality-control.employees') }}" class="sidebar-sub-link {{ request()->routeIs('admin.quality-control.employees') ? 'active' : '' }}"><i class="fas fa-user-tie"></i><span>{{ __('admin.employee_control') }}</span></a></li>
                    <li><a href="{{ route('admin.quality-control.operations') }}" class="sidebar-sub-link {{ request()->routeIs('admin.quality-control.operations') ? 'active' : '' }}"><i class="fas fa-cogs"></i><span>{{ __('admin.operations_followup') }}</span></a></li>
                    @endif
                </ul>
            </li>

            @endif

            @if($isFull || $u->hasPermission('manage.certificates') || $u->hasPermission('manage.roles') || $u->hasPermission('manage.permissions') || $u->hasPermission('manage.messages') || $u->hasPermission('view.statistics') || $u->hasPermission('view.reports') || $u->hasPermission('view.financial-reports') || $u->hasPermission('view.academic-reports'))
            <li class="sidebar-section-label">المزيد</li>
            @endif

            @if($isFull || $u->hasPermission('manage.certificates'))
            {{-- التحكم في الشهادات --}}
            @php $certificatesManagementOpen = request()->routeIs('admin.certificates.*'); @endphp
            <li x-data="{ open: {{ $certificatesManagementOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-certificate"></i><span>الشهادات</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    <li>
                        <a href="{{ route('admin.certificates.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.certificates.index') ? 'active' : '' }}">
                            <i class="fas fa-list"></i><span>{{ __('admin.certificates_list') }}</span>
                            @php try { $totalCertificates = \App\Models\Certificate::count(); } catch (\Throwable $e) { $totalCertificates = 0; } @endphp
                            @if($totalCertificates > 0)<span class="sidebar-badge bg-indigo-400 text-white">{{ $totalCertificates }}</span>@endif
                        </a>
                    </li>
                    <li><a href="{{ route('admin.certificates.create') }}" class="sidebar-sub-link {{ request()->routeIs('admin.certificates.create') ? 'active' : '' }}"><i class="fas fa-plus-circle"></i><span>{{ __('admin.issue_certificate') }}</span></a></li>
                    @php
                        try {
                            $pendingCertificates = \App\Models\Certificate::where(function($q) {
                                $q->where('status', 'pending')->orWhere('is_verified', false);
                            })->count();
                        } catch (\Throwable $e) {
                            $pendingCertificates = 0;
                        }
                    @endphp
                    @if($pendingCertificates > 0)
                    <li>
                        <a href="{{ route('admin.certificates.index', ['status' => 'pending']) }}" class="sidebar-sub-link {{ request()->get('status') == 'pending' ? 'active' : '' }}">
                            <i class="fas fa-clock"></i><span>{{ __('admin.pending_certificates') }}</span>
                            <span class="sidebar-badge bg-amber-400 text-amber-900">{{ $pendingCertificates }}</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            @endif

            {{-- إدارة الصلاحيات والأدوار --}}
            @php $permissionsOpen = request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.user-permissions.*'); @endphp
            @php
                $canManagePermissions = auth()->check() && (
                    auth()->user()->hasPermission('users.permissions')
                    || auth()->user()->hasPermission('manage.roles')
                    || auth()->user()->hasPermission('manage.permissions')
                    || auth()->user()->hasPermission('manage.user-permissions')
                );
            @endphp
            @if($canManagePermissions)
            <li x-data="{ open: {{ $permissionsOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-key"></i><span>الصلاحيات</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    <li><a href="{{ route('admin.roles.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"><i class="fas fa-user-tag"></i><span>{{ __('admin.roles') }}</span></a></li>
                    <li><a href="{{ route('admin.permissions.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}"><i class="fas fa-key"></i><span>{{ __('admin.permissions') }}</span></a></li>
                    <li><a href="{{ route('admin.user-permissions.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.user-permissions.*') ? 'active' : '' }}"><i class="fas fa-user-shield"></i><span>{{ __('admin.user_permissions') }}</span></a></li>
                </ul>
            </li>
            @endif
            {{-- تم إخفاء: الصفحات الخارجية + الإدارة العليا بناءً على طلب العميل --}}

            @if($isFull || $u->hasPermission('manage.messages'))
            {{-- الرسائل --}}
            @php $messagesActive = request()->routeIs('admin.messages.*'); @endphp
            <li>
                <a href="{{ route('admin.messages.index') }}" class="sidebar-link {{ $messagesActive ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i>
                    <span>{{ __('admin.messages') }}</span>
                </a>
            </li>
            @endif

            @if($isFull || $u->hasPermission('view.statistics') || $u->hasPermission('view.reports') || $u->hasPermission('view.financial-reports') || $u->hasPermission('view.academic-reports'))
            {{-- التقارير الشاملة --}}
            @php $reportsOpen = request()->routeIs('admin.reports.*'); @endphp
            <li x-data="{ open: {{ $reportsOpen ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="sidebar-group-btn">
                    <span class="flex items-center gap-3"><i class="fas fa-file-excel"></i><span>التقارير</span></span>
                    <i class="fas fa-chevron-down chevron" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <ul x-show="open" x-cloak class="mt-1 mr-3 space-y-0.5 border-r border-white/10 pr-3">
                    @if($isFull || $u->hasPermission('view.reports') || $u->hasPermission('view.statistics'))
                    <li><a href="{{ route('admin.reports.index') }}" class="sidebar-sub-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}"><i class="fas fa-chart-pie"></i><span>{{ __('admin.reports_dashboard') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('view.reports') || $u->hasPermission('manage.users'))
                    <li><a href="{{ route('admin.reports.users') }}" class="sidebar-sub-link {{ request()->routeIs('admin.reports.users') ? 'active' : '' }}"><i class="fas fa-users"></i><span>{{ __('admin.user_reports') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('view.reports') || $u->hasPermission('manage.courses'))
                    <li><a href="{{ route('admin.reports.courses') }}" class="sidebar-sub-link {{ request()->routeIs('admin.reports.courses') ? 'active' : '' }}"><i class="fas fa-graduation-cap"></i><span>{{ __('admin.course_reports') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('view.financial-reports') || $u->hasPermission('manage.invoices'))
                    <li><a href="{{ route('admin.reports.financial') }}" class="sidebar-sub-link {{ request()->routeIs('admin.reports.financial') ? 'active' : '' }}"><i class="fas fa-money-bill-wave"></i><span>{{ __('admin.financial_reports') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('view.academic-reports') || $u->hasPermission('manage.courses'))
                    <li><a href="{{ route('admin.reports.academic') }}" class="sidebar-sub-link {{ request()->routeIs('admin.reports.academic') ? 'active' : '' }}"><i class="fas fa-book"></i><span>{{ __('admin.academic_reports') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('view.reports') || $u->hasPermission('view.activity-log'))
                    <li><a href="{{ route('admin.reports.activities') }}" class="sidebar-sub-link {{ request()->routeIs('admin.reports.activities') ? 'active' : '' }}"><i class="fas fa-history"></i><span>{{ __('admin.activity_reports') }}</span></a></li>
                    @endif
                    @if($isFull || $u->hasPermission('view.reports') || $u->hasPermission('view.statistics'))
                    <li><a href="{{ route('admin.reports.comprehensive') }}" class="sidebar-sub-link {{ request()->routeIs('admin.reports.comprehensive') ? 'active' : '' }}"><i class="fas fa-file-alt"></i><span>{{ __('admin.comprehensive_report') }}</span></a></li>
                    @endif
                </ul>
            </li>
            @endif
        </ul>
    </nav>

    @unless($adminSidebarDrawer)
    <!-- User Info -->
    <div class="border-t border-white/10 p-4 flex-shrink-0">
        <div class="sidebar-user-wrap rounded-2xl bg-white/5 p-3">
            <div class="flex items-center gap-3">
                @if(auth()->user()->profile_image)
                    <img src="{{ auth()->user()->profile_image_url }}" alt="{{ auth()->user()->name }}" class="size-10 rounded-full object-cover flex-shrink-0" onerror="this.onerror=null;this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');">
                    <div class="size-10 bg-metal/30 rounded-full hidden flex items-center justify-center text-metal font-bold text-sm flex-shrink-0">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                @else
                    <div class="size-10 bg-metal/30 rounded-full flex items-center justify-center text-metal font-bold text-sm flex-shrink-0">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </div>
                @endif
                <div class="sidebar-user-info min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-white/45">{{ auth()->user()->email ?? auth()->user()->phone }}</p>
                </div>
            </div>
            <a href="{{ url('/') }}" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-medium text-white transition hover:bg-white/15">
                فتح واجهة الموقع
            </a>
        </div>
    </div>
    @endunless
</div>
