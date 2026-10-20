@extends('layouts.admin')

@section('title', 'إدارة المستخدمين - ' . config('app.name'))
@section('page_title', 'إدارة المستخدمين')

@section('content')
@php
    /** @var \App\Services\AdminUsersDirectory $directory */
    $type = $type ?? 'all';
    $counts = $counts ?? [];
    $canViewContacts = (bool) ($canViewContacts ?? false);
    $filters = $filters ?? ['search' => null, 'status' => null];
    $viewer = auth()->user();

    $tabs = [
        'all' => ['label' => 'الكل', 'icon' => 'fa-users'],
        'student' => ['label' => 'طلاب', 'icon' => 'fa-user-graduate'],
        'parent' => ['label' => 'أولياء أمور', 'icon' => 'fa-people-roof'],
        'instructor' => ['label' => 'معلمون', 'icon' => 'fa-chalkboard-teacher'],
        'employee' => ['label' => 'موظفون', 'icon' => 'fa-briefcase'],
    ];

    $detailHeader = match ($type) {
        'student' => 'المرحلة / المنهج / ولي الأمر',
        'parent' => 'الأبناء',
        'instructor' => 'اعتماد الملف',
        'employee' => 'المسمى / الصلاحيات',
        default => 'تفاصيل النوع',
    };

    $queryBase = array_filter([
        'search' => $filters['search'] ?? null,
        'status' => $filters['status'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">إدارة النظام · المستخدمون</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">دليل المستخدمين</h2>
            <p class="mt-1 max-w-3xl text-sm text-muted">الأنواع الأربعة في مكان واحد: طلاب، أولياء أمور، معلمون، وموظفون — مع بحث وفلترة وتصدير.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(Route::has('admin.students-accounts.index'))
                <a href="{{ route('admin.students-accounts.index') }}" class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                    <i class="fas fa-graduation-cap text-xs"></i> الطلاب والحسابات
                </a>
            @endif
            @if(Route::has('admin.parents.index'))
                <a href="{{ route('admin.parents.index') }}" class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                    <i class="fas fa-people-roof text-xs"></i> أولياء الأمور
                </a>
            @endif
            @if(Route::has('admin.employees.index'))
                <a href="{{ route('admin.employees.index') }}" class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-canvas">
                    <i class="fas fa-briefcase text-xs"></i> الموظفون
                </a>
            @endif
            <a href="{{ route('admin.users.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-user-plus text-xs"></i> إضافة مستخدم
            </a>
        </div>
    </section>

    @if(request('created') == '1')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">تم إنشاء المستخدم بنجاح.</div>
    @endif
    @if(session('success') || request('updated') == '1')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">{{ session('success', 'تم التعديل بنجاح') }}</div>
    @endif
    @if(session('warning'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 shadow-soft">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">{{ session('error') }}</div>
    @endif

    @unless($canViewContacts)
        <div class="rounded-2xl border border-line bg-canvas px-4 py-3 text-xs text-muted">
            <i class="fas fa-eye-slash me-1"></i>
            البريد والجوال مخفيان جزئياً لصلاحيتك الحالية. التصدير يُسجَّل في سجل التدقيق.
        </div>
    @endunless

    <nav class="flex flex-wrap gap-2" aria-label="أنواع المستخدمين">
        @foreach($tabs as $key => $tab)
            @php
                $href = route('admin.users.index', array_merge($queryBase, $key === 'all' ? [] : ['type' => $key]));
                $on = $type === $key;
                $count = (int) ($counts[$key] ?? 0);
            @endphp
            <a href="{{ $href }}"
               class="inline-flex h-10 items-center gap-2 rounded-xl border px-3.5 text-sm font-medium transition {{ $on ? 'border-accent bg-accent text-white' : 'border-line bg-surface text-ink hover:bg-accent-soft hover:text-accent' }}">
                <i class="fas {{ $tab['icon'] }} text-xs"></i>
                <span>{{ $tab['label'] }}</span>
                <span class="tabular-nums rounded-lg px-1.5 py-0.5 text-[11px] {{ $on ? 'bg-white/20' : 'bg-canvas text-muted' }}">{{ number_format($count) }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.users.index') }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        @if($type !== 'all')
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="xl:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-muted">بحث</label>
                <input type="text" name="search" value="{{ $filters['search'] }}"
                       placeholder="الاسم، البريد، الجوال"
                       class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:ring-accent/20">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted">الحالة</label>
                <select name="status" class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:ring-accent/20">
                    <option value="">الكل</option>
                    <option value="1" @selected(($filters['status'] ?? '') === '1')>نشط</option>
                    <option value="0" @selected(($filters['status'] ?? '') === '0')>موقوف</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-press inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-search text-xs"></i> بحث
                </button>
                @if(($filters['search'] ?? null) || ($filters['status'] ?? null) || $type !== 'all')
                    <a href="{{ route('admin.users.index') }}" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-muted hover:bg-canvas" title="مسح">
                        <i class="fas fa-times text-xs"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">القائمة</h3>
                <p class="text-xs text-muted"><span class="font-semibold text-accent tabular-nums">{{ number_format($users->total()) }}</span> نتيجة</p>
            </div>
            <a href="{{ route('admin.users.export', array_merge($queryBase, $type === 'all' ? [] : ['type' => $type])) }}"
               class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-3 text-xs font-semibold text-ink hover:bg-canvas">
                <i class="fas fa-file-csv text-xs"></i> تصدير CSV
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">الاسم</th>
                        <th class="px-4 py-3 text-start font-medium">البريد</th>
                        <th class="px-4 py-3 text-start font-medium">الجوال</th>
                        <th class="px-4 py-3 text-start font-medium">النوع</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">التسجيل</th>
                        <th class="px-4 py-3 text-start font-medium">آخر دخول</th>
                        <th class="px-4 py-3 text-start font-medium">{{ $detailHeader }}</th>
                        <th class="px-4 py-3 text-end font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($users as $user)
                        @php
                            $payload = $directory->rowPayload($user, $canViewContacts, forceType: $type);
                        @endphp
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#f2f5f4] text-xs font-bold text-accent">
                                        {{ mb_substr($user->name, 0, 1, 'UTF-8') }}
                                    </div>
                                    <p class="truncate font-semibold text-ink">{{ $user->name }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted" dir="ltr">{{ $payload['email'] }}</td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted" dir="ltr">{{ $payload['phone'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg border border-line bg-canvas px-2.5 py-1 text-[11px] font-semibold text-ink">
                                    {{ $payload['type_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold {{ $user->is_active ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-rose-100 bg-rose-50 text-rose-700' }}">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    {{ $payload['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs tabular-nums text-ink">{{ $user->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">{{ $user->last_login_at?->format('Y-m-d H:i') ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-muted max-w-[220px]">{{ $payload['type_detail'] }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-canvas hover:text-accent"
                                       title="تفاصيل"><i class="fas fa-eye text-xs"></i></a>
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-amber-50 hover:text-amber-800"
                                                title="{{ $user->is_active ? 'إيقاف' : 'تفعيل' }}"
                                                @disabled($viewer && $viewer->id === $user->id)>
                                            <i class="fas {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }} text-xs"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.resend-credentials', $user) }}" class="inline"
                                          onsubmit="return confirm('إرسال رابط إعادة تعيين كلمة المرور لهذا المستخدم؟');">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                                                title="إعادة إرسال بيانات الدخول"
                                                @disabled(! $user->email)>
                                            <i class="fas fa-paper-plane text-xs"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="inline-flex size-9 items-center justify-center rounded-xl border border-line text-muted hover:bg-accent-soft hover:text-accent"
                                       title="تعديل"><i class="fas fa-pen text-xs"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-muted">لا توجد نتائج مطابقة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="border-t border-line px-4 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </article>
</div>
@endsection
