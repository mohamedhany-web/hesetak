@extends('layouts.admin')

@section('title', ($pageTitle ?? 'إدارة المستخدمين') . ' - ' . config('app.name'))
@section('page_title', $pageTitle ?? 'إدارة المستخدمين')

@section('content')
@php
    $stats = $stats ?? [];
    $trends = $trends ?? [];
    $users = $users ?? collect();
    $recentUsers = $recentUsers ?? collect();
    $recentlyActiveUsers = $recentlyActiveUsers ?? collect();
    $usersByRole = $usersByRole ?? collect();
    $usersByMonth = $usersByMonth ?? collect();
    $pageMode = $pageMode ?? 'users';
    $pageTitle = $pageTitle ?? 'إدارة المستخدمين';
    $pageDescription = $pageDescription ?? 'متابعة الحسابات، الصلاحيات، وحالة النشاط عبر المنصة';
    $indexRoute = $indexRoute ?? 'admin.users.index';
    $isStudents = $pageMode === 'students';

    $roles = [
        'super_admin' => ['label' => 'مدير عام', 'badge' => 'bg-rose-50 text-rose-700 border-rose-100'],
        'admin' => ['label' => 'إداري', 'badge' => 'bg-rose-50 text-rose-700 border-rose-100'],
        'instructor' => ['label' => 'معلم', 'badge' => 'bg-[#f2f5f4] text-accent border-line'],
        'teacher' => ['label' => 'معلم (مدرس)', 'badge' => 'bg-[#f2f5f4] text-accent border-line'],
        'student' => ['label' => __('admin.student_role_label'), 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-100'],
        'parent' => ['label' => 'ولي أمر', 'badge' => 'bg-canvas text-ink border-line'],
        'employee' => ['label' => 'موظف', 'badge' => 'bg-amber-50 text-amber-800 border-amber-100'],
    ];

    $kpiCards = [
        [
            'label' => $isStudents ? 'إجمالي الطلاب' : 'إجمالي المستخدمين',
            'value' => number_format($stats['total'] ?? 0),
            'meta' => isset($stats['new_this_month']) ? ('+'.number_format($stats['new_this_month']).' هذا الشهر') : null,
            'trend' => $trends['users'] ?? null,
        ],
        [
            'label' => 'نشطون',
            'value' => number_format($stats['active'] ?? 0),
            'meta' => 'حسابات مفعّلة',
            'trend' => null,
        ],
        [
            'label' => 'المعلمون',
            'value' => number_format($stats['teachers'] ?? 0),
            'meta' => isset($stats['new_teachers_this_month']) ? ('+'.number_format($stats['new_teachers_this_month']).' هذا الشهر') : null,
            'trend' => $trends['teachers'] ?? null,
        ],
        [
            'label' => 'الطلاب',
            'value' => number_format($stats['students'] ?? 0),
            'meta' => isset($stats['new_students_this_month']) ? ('+'.number_format($stats['new_students_this_month']).' هذا الشهر') : null,
            'trend' => $trends['students'] ?? null,
        ],
    ];

    $totalForPercentage = max(1, (int) ($stats['total'] ?? 0));
    $roleDistribution = [
        'admin' => [
            'count' => (int) (($usersByRole['admin'] ?? 0) + ($usersByRole['super_admin'] ?? 0)),
            'label' => 'إداريون',
            'icon' => 'fas fa-user-shield',
        ],
        'instructor' => [
            'count' => (int) (($usersByRole['instructor'] ?? 0) + ($usersByRole['teacher'] ?? 0)),
            'label' => 'معلمون',
            'icon' => 'fas fa-chalkboard-teacher',
        ],
        'student' => [
            'count' => (int) ($usersByRole['student'] ?? 0),
            'label' => __('admin.student_role_label'),
            'icon' => 'fas fa-user-graduate',
        ],
        'parent' => [
            'count' => (int) ($usersByRole['parent'] ?? 0),
            'label' => 'أولياء أمور',
            'icon' => 'fas fa-user-friends',
        ],
        'employee' => [
            'count' => (int) \App\Models\User::where('is_employee', true)->count(),
            'label' => 'موظفون',
            'icon' => 'fas fa-briefcase',
        ],
    ];

    $monthNames = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];
    $maxMonthCount = max(1, (int) ($usersByMonth->max('count') ?: 1));
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">{{ $isStudents ? 'الطلاب والحسابات' : 'المستخدمون والصلاحيات' }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $pageTitle }}</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">{{ $pageDescription }}</p>
        </div>
        <a href="{{ route('admin.users.create') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
            <i class="fas fa-user-plus text-xs"></i>
            {{ $isStudents ? 'إضافة طالب' : 'إضافة مستخدم' }}
        </a>
    </section>

    @if(request('created') == '1')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            تم إنشاء المستخدم بنجاح.
        </div>
    @endif
    @if(session('success') || request('updated') == '1')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            {{ session('success', 'تم التعديل بنجاح') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 shadow-soft">
            {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">
            {{ session('error') }}
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpiCards as $card)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <p class="text-xs font-medium text-muted">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $card['value'] }}</p>
                @if(!empty($card['meta']))
                    <p class="mt-1 text-[11px] text-muted">{{ $card['meta'] }}</p>
                @endif
                @if(!empty($card['trend']))
                    @php
                        $diff = (int) round($card['trend']['difference'] ?? 0);
                        $percent = (float) ($card['trend']['percent'] ?? 0);
                        $positive = $diff >= 0;
                    @endphp
                    <p class="mt-2 text-[11px] font-semibold {{ $positive ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $positive ? '+' : '' }}{{ number_format($diff) }}
                        <span class="font-medium text-muted">({{ $percent >= 0 ? '+' : '' }}{{ number_format($percent, 1) }}%)</span>
                    </p>
                @endif
            </article>
        @endforeach
    </section>

    @if($isStudents)
        <section class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="mb-3 text-xs font-medium text-muted">اختصارات الخدمات</p>
            <div class="flex flex-wrap gap-2">
                @if(Route::has('admin.advanced-courses.index'))
                    <a href="{{ route('admin.advanced-courses.index') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                        <i class="fas fa-graduation-cap text-xs"></i> الكورسات
                    </a>
                @endif
            </div>
        </section>
    @endif

    @unless($isStudents)
        <section class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="mb-3 text-xs font-medium text-muted">اختصارات المعلمين</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route($indexRoute, ['role' => 'teachers']) }}"
                   class="btn-press inline-flex h-9 items-center gap-2 rounded-xl {{ request('role') === 'teachers' ? 'bg-accent text-white' : 'border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent' }} px-4 text-sm font-medium">
                    <i class="fas fa-chalkboard-teacher text-xs"></i> كل المعلمين
                </a>
                @if(Route::has('admin.tutor-work-schedules.index'))
                    <a href="{{ route('admin.tutor-work-schedules.index') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                        <i class="fas fa-calendar-alt text-xs"></i> جداول العمل
                    </a>
                @endif
                @if(Route::has('admin.one-to-one-sessions.index'))
                    <a href="{{ route('admin.one-to-one-sessions.index') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                        <i class="fas fa-user-clock text-xs"></i> الحصص الخاصة
                    </a>
                @endif
                @if(Route::has('admin.personal-branding.index'))
                    <a href="{{ route('admin.personal-branding.index') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                        <i class="fas fa-id-card text-xs"></i> الملفات العامة
                    </a>
                @endif
            </div>
        </section>
    @endunless

    <form method="GET" action="{{ route($indexRoute) }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="grid gap-3 md:grid-cols-2 {{ $isStudents ? 'xl:grid-cols-3' : 'xl:grid-cols-4' }}">
            <div class="{{ $isStudents ? 'xl:col-span-1' : '' }}">
                <label class="mb-1.5 block text-xs font-medium text-muted">بحث</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="الاسم، البريد، الهاتف"
                       class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:ring-accent/20">
            </div>
            @unless($isStudents)
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-muted">الدور</label>
                    <select name="role" class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:ring-accent/20">
                        <option value="">جميع الأدوار</option>
                        <option value="teachers" @selected(request('role') == 'teachers')>المعلمون (الكل)</option>
                        <option value="super_admin" @selected(request('role') == 'super_admin')>مدير عام</option>
                        <option value="admin" @selected(request('role') == 'admin')>إداري</option>
                        <option value="instructor" @selected(request('role') == 'instructor')>معلم</option>
                        <option value="teacher" @selected(request('role') == 'teacher')>معلم (مدرس)</option>
                        <option value="student" @selected(request('role') == 'student')>{{ __('admin.student_role_label') }}</option>
                        <option value="parent" @selected(request('role') == 'parent')>ولي أمر</option>
                        <option value="employee" @selected(request('role') == 'employee')>موظف</option>
                    </select>
                </div>
            @endunless
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted">الحالة</label>
                <select name="status" class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:ring-accent/20">
                    <option value="">جميع الحالات</option>
                    <option value="1" @selected(request('status') == '1')>نشط</option>
                    <option value="0" @selected(request('status') == '0')>غير نشط</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-press inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-search text-xs"></i> بحث
                </button>
                @if(request()->anyFilled(['search', 'role', 'status']))
                    <a href="{{ route($indexRoute) }}" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-muted hover:bg-canvas" title="مسح">
                        <i class="fas fa-times text-xs"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">{{ $isStudents ? 'قائمة الطلاب' : 'قائمة المستخدمين' }}</h3>
                <p class="text-xs text-muted"><span class="font-semibold text-accent tabular-nums">{{ number_format($users->total()) }}</span> نتيجة</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">المستخدم</th>
                        <th class="px-4 py-3 text-start font-medium">الدور</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">التسجيل</th>
                        <th class="px-4 py-3 text-end font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($users as $user)
                        @php
                            $roleKey = $user->is_employee ? 'employee' : $user->role;
                            $roleMeta = $roles[$roleKey] ?? $roles['student'];
                        @endphp
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-sm font-bold text-accent">
                                        {{ mb_substr($user->name, 0, 1, 'UTF-8') }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-ink">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-muted">{{ $user->email ?: '—' }}</p>
                                        <p class="truncate text-xs text-muted tabular-nums">{{ $user->phone ?: '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $roleMeta['badge'] }}">
                                    {{ $roleMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $user->is_active ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-rose-100 bg-rose-50 text-rose-700' }}">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    {{ $user->is_active ? 'نشط' : 'غير نشط' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium tabular-nums text-ink">{{ $user->created_at?->format('Y-m-d') }}</p>
                                <p class="text-xs text-muted tabular-nums">{{ $user->created_at?->format('H:i') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-canvas hover:text-accent"
                                       title="عرض"><i class="fas fa-eye text-xs"></i></a>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                                       title="تعديل"><i class="fas fa-pen text-xs"></i></a>
                                    @if(in_array($user->role, ['instructor', 'teacher'], true) && Route::has('admin.tutor-work-schedules.index'))
                                        <a href="{{ route('admin.tutor-work-schedules.index', ['instructor_id' => $user->id]) }}"
                                           class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                                           title="جدول العمل"><i class="fas fa-calendar-alt text-xs"></i></a>
                                    @endif
                                    @if(in_array($user->role, ['instructor', 'teacher'], true) && Route::has('public.instructors.show'))
                                        <a href="{{ route('public.instructors.show', $user) }}" target="_blank" rel="noopener"
                                           class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                                           title="الصفحة العامة"><i class="fas fa-external-link-alt text-xs"></i></a>
                                    @endif
                                    @if($user->id !== auth()->id())
                                        <button type="button" onclick="deleteUser(this)"
                                                data-delete-url="{{ route('admin.users.delete', $user) }}"
                                                class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-rose-600 hover:bg-rose-50"
                                                title="حذف"><i class="fas fa-trash text-xs"></i></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-muted">لا توجد نتائج مطابقة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="border-t border-line px-4 py-3">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @endif
    </article>

    <div class="grid gap-5 lg:grid-cols-2">
        <article class="rounded-2xl border border-line bg-surface shadow-soft overflow-hidden">
            <div class="border-b border-line px-4 py-3">
                <h3 class="text-sm font-semibold text-ink">آخر المسجّلين</h3>
                <p class="text-xs text-muted">أحدث 10 حسابات</p>
            </div>
            <div class="max-h-96 space-y-1 overflow-y-auto p-2">
                @forelse($recentUsers as $recentUser)
                    @php $rk = $recentUser->is_employee ? 'employee' : ($recentUser->role ?? 'student'); @endphp
                    <a href="{{ route('admin.users.show', $recentUser) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-canvas">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-xs font-bold text-accent">
                            {{ mb_substr($recentUser->name, 0, 1, 'UTF-8') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $recentUser->name }}</p>
                            <p class="text-[11px] text-muted">{{ ($roles[$rk] ?? $roles['student'])['label'] }} · {{ $recentUser->created_at?->diffForHumans() }}</p>
                        </div>
                        <span class="size-2 shrink-0 rounded-full {{ $recentUser->is_active ? 'bg-emerald-500' : 'bg-rose-400' }}"></span>
                    </a>
                @empty
                    <p class="px-3 py-8 text-center text-xs text-muted">لا يوجد مستخدمون بعد.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface shadow-soft overflow-hidden">
            <div class="border-b border-line px-4 py-3">
                <h3 class="text-sm font-semibold text-ink">نشطون مؤخراً</h3>
                <p class="text-xs text-muted">خلال آخر 7 أيام</p>
            </div>
            <div class="max-h-96 space-y-1 overflow-y-auto p-2">
                @forelse($recentlyActiveUsers as $activeUser)
                    @php $ak = $activeUser->is_employee ? 'employee' : ($activeUser->role ?? 'student'); @endphp
                    <a href="{{ route('admin.users.show', $activeUser) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-canvas">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-xs font-bold text-accent">
                            {{ mb_substr($activeUser->name, 0, 1, 'UTF-8') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $activeUser->name }}</p>
                            <p class="text-[11px] text-muted">{{ ($roles[$ak] ?? $roles['student'])['label'] }} · {{ $activeUser->updated_at?->diffForHumans() }}</p>
                        </div>
                        <span class="size-2 shrink-0 rounded-full bg-emerald-500"></span>
                    </a>
                @empty
                    <p class="px-3 py-8 text-center text-xs text-muted">لا يوجد نشاط حديث.</p>
                @endforelse
            </div>
        </article>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <h3 class="text-sm font-semibold text-ink">توزيع الأدوار</h3>
            <p class="mb-4 text-xs text-muted">نسبة كل دور من إجمالي المستخدمين</p>
            <div class="space-y-3">
                @foreach($roleDistribution as $roleData)
                    @php $pct = round(($roleData['count'] / $totalForPercentage) * 100, 1); @endphp
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-[#f2f5f4] text-accent"><i class="{{ $roleData['icon'] }} text-xs"></i></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink">{{ $roleData['label'] }}</p>
                                    <p class="text-[11px] text-muted tabular-nums">{{ number_format($roleData['count']) }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-semibold tabular-nums text-accent">{{ $pct }}%</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-canvas">
                            <div class="h-full rounded-full bg-accent" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <h3 class="text-sm font-semibold text-ink">التسجيل الشهري</h3>
            <p class="mb-4 text-xs text-muted">آخر 6 أشهر</p>
            @if($usersByMonth->count() > 0)
                <div class="space-y-3">
                    @foreach($usersByMonth->reverse() as $monthData)
                        @php
                            $bar = round(((int) $monthData->count / $maxMonthCount) * 100);
                            $label = ($monthNames[(int) $monthData->month] ?? $monthData->month).' '.$monthData->year;
                        @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-ink">{{ $label }}</span>
                                <span class="text-xs font-semibold tabular-nums text-ink">{{ number_format($monthData->count) }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-canvas">
                                <div class="h-full rounded-full bg-accent/80" style="width: {{ max(4, $bar) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="py-8 text-center text-xs text-muted">لا توجد بيانات شهرية.</p>
            @endif
        </article>
    </div>

    <section class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <h3 class="mb-3 text-sm font-semibold text-ink">إجراءات سريعة</h3>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @if(Route::has('admin.roles.index'))
                <a href="{{ route('admin.roles.index') }}" class="rounded-xl border border-line px-4 py-3 hover:bg-canvas">
                    <p class="text-sm font-semibold text-ink">إدارة الأدوار</p>
                    <p class="mt-0.5 text-xs text-muted">تعريف الصلاحيات حسب الفريق</p>
                </a>
            @endif
            @if(Route::has('admin.permissions.index'))
                <a href="{{ route('admin.permissions.index') }}" class="rounded-xl border border-line px-4 py-3 hover:bg-canvas">
                    <p class="text-sm font-semibold text-ink">مصفوفة الصلاحيات</p>
                    <p class="mt-0.5 text-xs text-muted">صلاحيات دقيقة لكل مستخدم</p>
                </a>
            @endif
            <a href="{{ route('admin.users.create') }}" class="rounded-xl border border-line px-4 py-3 hover:bg-canvas">
                <p class="text-sm font-semibold text-ink">إضافة حساب</p>
                <p class="mt-0.5 text-xs text-muted">إنشاء مدرب أو طالب أو موظف</p>
            </a>
            @if(Route::has('admin.activity-log'))
                <a href="{{ route('admin.activity-log') }}" class="rounded-xl border border-line px-4 py-3 hover:bg-canvas">
                    <p class="text-sm font-semibold text-ink">سجل النشاطات</p>
                    <p class="mt-0.5 text-xs text-muted">مراجعة تحركات الفريق</p>
                </a>
            @endif
        </div>
    </section>
</div>

@push('scripts')
<script>
    function deleteUser(btn) {
        const deleteUrl = btn && btn.getAttribute ? btn.getAttribute('data-delete-url') : null;
        if (!deleteUrl) {
            alert('خطأ: رابط الحذف غير متوفر. حدّث الصفحة وحاول مرة أخرى.');
            return;
        }
        if (!confirm('هل أنت متأكد من حذف هذا المستخدم؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            return;
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            alert('خطأ: لم يتم العثور على CSRF token');
            return;
        }

        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(async function(response) {
            var contentType = response.headers.get('content-type') || '';
            var data = {};
            try {
                var text = await response.text();
                if (text && contentType.indexOf('application/json') !== -1) {
                    data = JSON.parse(text);
                } else if (text && text.trim().length > 0) {
                    data = { message: text };
                }
            } catch (e) {
                data = {};
            }
            return { ok: response.ok, status: response.status, data: data };
        })
        .then(function(result) {
            if (result.ok && result.status === 200) {
                var msg = (result.data && result.data.message) ? result.data.message : 'تم حذف المستخدم بنجاح';
                if (result.data && result.data.success === false) {
                    alert('خطأ: ' + (result.data.message || msg));
                    return;
                }
                alert(msg);
                window.location.reload();
                return;
            }
            var errorMsg = (result.data && (result.data.message || result.data.error)) || '';
            if (!errorMsg) {
                if (result.status === 419) errorMsg = 'انتهت الجلسة. حدّث الصفحة وحاول مرة أخرى.';
                else if (result.status === 403) errorMsg = 'غير مصرح لك بهذا الإجراء.';
                else if (result.status === 404) errorMsg = 'المستخدم غير موجود.';
                else errorMsg = 'حدث خطأ أثناء حذف المستخدم.';
            }
            alert('خطأ: ' + errorMsg);
        })
        .catch(function(error) {
            console.error('Error:', error);
            alert('حدث خطأ أثناء حذف المستخدم: ' + (error.message || 'تأكد من الاتصال ثم أعد المحاولة.'));
        });
    }
</script>
@endpush
@endsection
