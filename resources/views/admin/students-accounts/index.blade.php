@extends('layouts.admin')

@section('title', 'إدارة الطلاب والحسابات - حصتك')
@section('page_title', 'إدارة الطلاب والحسابات')

@section('content')
@php
    $stats = $stats ?? [];
    $users = $users ?? collect();
    $recentUsers = $recentUsers ?? collect();
    $recentlyActiveUsers = $recentlyActiveUsers ?? collect();
    $usersByMonth = $usersByMonth ?? collect();
    $trend = $stats['trend'] ?? null;

    $kpis = [
        ['label' => 'إجمالي الطلاب', 'value' => $stats['total'] ?? 0, 'icon' => 'fa-user-graduate', 'tone' => 'accent', 'note' => 'كل حسابات الطلاب'],
        ['label' => 'نشطون', 'value' => $stats['active'] ?? 0, 'icon' => 'fa-user-check', 'tone' => 'accent', 'note' => 'حسابات مفعّلة'],
        ['label' => 'غير نشطين', 'value' => $stats['inactive'] ?? 0, 'icon' => 'fa-user-slash', 'tone' => 'muted', 'note' => 'حسابات موقوفة'],
        ['label' => 'جدد هذا الشهر', 'value' => $stats['new_this_month'] ?? 0, 'icon' => 'fa-user-plus', 'tone' => 'metal', 'note' => 'تسجيلات الشهر الحالي'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
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
            <p class="text-xs font-medium text-muted">الحسابات · متابعة طلاب المنصة ونشاطهم</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة الطلاب والحسابات</h2>
            @if(is_array($trend) && isset($trend['difference']))
                @php
                    $diff = (int) round($trend['difference']);
                    $percent = (float) ($trend['percent'] ?? 0);
                    $positive = $diff >= 0;
                @endphp
                <p class="mt-1 text-sm text-muted">
                    تسجيلات هذا الشهر مقارنة بالسابق:
                    <span class="font-semibold {{ $positive ? 'text-accent' : 'text-danger' }}">
                        {{ $positive ? '+' : '' }}{{ number_format($diff) }}
                        ({{ $percent >= 0 ? '+' : '' }}{{ number_format($percent, 1) }}%)
                    </span>
                </p>
            @endif
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.users.create', ['from' => 'students', 'role' => 'student']) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-user-plus text-xs"></i>
                إضافة طالب
            </a>
        </div>
    </section>

    @if(request('created') == '1' || session('success') || request('updated') == '1')
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success', request('created') == '1' ? 'تم إنشاء الحساب بنجاح.' : 'تم التعديل بنجاح.') }}</p>
        </div>
    @endif
    @if(session('warning') || isset($warning))
        <div class="flex items-center gap-3 rounded-2xl border border-metal/30 bg-canvas px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-metal/15 text-metal"><i class="fas fa-exclamation-triangle text-sm"></i></span>
            <p>{{ session('warning', $warning ?? '') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">اختصارات سريعة</h3>
            <p class="mt-0.5 text-xs text-muted">خدمات حصتك الحالية للطالب: كورسات ومجموعات</p>
        </div>
        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-3 sm:p-5">
            @if(config('admin_ui.show_group_classes', false) && Route::has('admin.tutoring-groups.index'))
                <a href="{{ route('admin.tutoring-groups.index', 'individual') }}" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-user text-xs"></i>
                    مجموعات فردية
                </a>
                <a href="{{ route('admin.tutoring-groups.index', 'collective') }}" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                    <i class="fas fa-users text-xs"></i>
                    مجموعات جماعية
                </a>
            @endif
            @if(Route::has('admin.one-to-one-sessions.index'))
                <a href="{{ route('admin.one-to-one-sessions.index') }}" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-chalkboard-teacher text-xs"></i>
                    الحصص الفردية
                </a>
            @endif
            @if(Route::has('admin.advanced-courses.index'))
                <a href="{{ route('admin.advanced-courses.index') }}" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                    <i class="fas fa-graduation-cap text-xs"></i>
                    الكورسات
                </a>
            @endif
        </div>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">ابحث بالاسم أو البريد أو الهاتف، أو صفِّ حسب الحالة</p>
        </div>
        <form method="GET" action="{{ route('admin.students-accounts.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-3 md:items-end">
            <div>
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}"
                       placeholder="الاسم، البريد، أو الهاتف..." class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">كل الحالات</option>
                    <option value="1" @selected(request('status') === '1')>نشط</option>
                    <option value="0" @selected(request('status') === '0')>غير نشط</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تصفية
                </button>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.students-accounts.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح
                    </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">قائمة الطلاب</h3>
                <p class="mt-0.5 text-xs text-muted">{{ number_format($users->total()) }} طالب</p>
            </div>
            <a href="{{ route('admin.users.create', ['from' => 'students', 'role' => 'student']) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-plus text-xs"></i>
                إضافة
            </a>
        </div>

        @if($users->count() > 0)
            <div class="admin-table-wrap">
                <table class="w-full min-w-[900px] text-right text-sm">
                    <thead class="bg-canvas text-[11px] uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">الطالب</th>
                            <th class="px-3 py-3 font-medium">الحالة</th>
                            <th class="px-3 py-3 font-medium">تاريخ التسجيل</th>
                            <th class="px-5 py-3 font-medium">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($users as $user)
                            <tr class="transition hover:bg-canvas">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent">
                                            {{ mb_substr($user->name, 0, 1, 'UTF-8') }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink">{{ $user->name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-muted">
                                                <i class="fas fa-envelope ml-1 text-[10px]"></i>{{ $user->email ?: '—' }}
                                            </p>
                                            @if($user->phone)
                                                <p class="mt-0.5 truncate text-xs text-muted">
                                                    <i class="fas fa-phone ml-1 text-[10px]"></i>{{ $user->phone }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    @if($user->is_active)
                                        <span class="rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">نشط</span>
                                    @else
                                        <span class="rounded-lg bg-canvas-muted px-2.5 py-1 text-xs font-medium text-muted">غير نشط</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <p class="font-medium tabular-nums text-ink">{{ $user->created_at?->format('Y-m-d') }}</p>
                                    <p class="mt-0.5 text-xs tabular-nums text-muted">{{ $user->created_at?->format('H:i') }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('admin.users.show', $user->id) }}"
                                           class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-canvas-muted text-muted transition hover:bg-ink hover:text-white"
                                           title="عرض">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user->id) }}"
                                           class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-accent-soft text-accent transition hover:bg-accent hover:text-white"
                                           title="تعديل">
                                            <i class="fas fa-pen text-xs"></i>
                                        </a>
                                        @if($user->id !== auth()->id())
                                            <button type="button"
                                                    onclick="deleteStudent(this)"
                                                    data-delete-url="{{ route('admin.users.delete', $user->id) }}"
                                                    class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-danger/10 text-danger transition hover:bg-danger hover:text-white"
                                                    title="حذف">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="border-t border-line px-4 py-4 sm:px-5">{{ $users->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="px-4 py-16 text-center sm:px-5">
                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <p class="text-sm font-medium text-ink">لا توجد نتائج</p>
                <p class="mt-1 text-xs text-muted">
                    @if(request()->anyFilled(['search', 'status']))
                        لا توجد نتائج مطابقة للفلتر الحالي.
                    @else
                        <a href="{{ route('admin.users.create', ['from' => 'students', 'role' => 'student']) }}" class="text-accent hover:underline">أضف أول طالب</a>.
                    @endif
                </p>
            </div>
        @endif
    </article>

    <div class="grid gap-5 lg:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">آخر المسجّلين</h3>
                <p class="mt-0.5 text-xs text-muted">أحدث حسابات الطلاب</p>
            </div>
            <div class="divide-y divide-line">
                @forelse($recentUsers as $recentUser)
                    <a href="{{ route('admin.users.show', $recentUser->id) }}" class="flex items-center gap-3 px-4 py-3 transition hover:bg-canvas sm:px-5">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent">
                            {{ mb_substr($recentUser->name, 0, 1, 'UTF-8') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $recentUser->name }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ $recentUser->created_at?->diffForHumans() }}</p>
                        </div>
                        @if($recentUser->is_active)
                            <span class="rounded-lg bg-accent-soft px-2 py-1 text-[11px] font-medium text-accent">نشط</span>
                        @else
                            <span class="rounded-lg bg-canvas-muted px-2 py-1 text-[11px] font-medium text-muted">موقوف</span>
                        @endif
                    </a>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-muted sm:px-5">لا يوجد طلاب بعد</div>
                @endforelse
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">نشطون مؤخراً</h3>
                <p class="mt-0.5 text-xs text-muted">آخر 7 أيام · {{ number_format($stats['active_recently'] ?? 0) }} طالب</p>
            </div>
            <div class="divide-y divide-line">
                @forelse($recentlyActiveUsers as $activeUser)
                    <a href="{{ route('admin.users.show', $activeUser->id) }}" class="flex items-center gap-3 px-4 py-3 transition hover:bg-canvas sm:px-5">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-metal/15 text-sm font-semibold text-metal">
                            {{ mb_substr($activeUser->name, 0, 1, 'UTF-8') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $activeUser->name }}</p>
                            <p class="mt-0.5 text-xs text-muted">آخر نشاط: {{ $activeUser->updated_at?->diffForHumans() }}</p>
                        </div>
                        <span class="size-2 rounded-full bg-accent"></span>
                    </a>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-muted sm:px-5">لا يوجد نشاط حديث</div>
                @endforelse
            </div>
        </article>
    </div>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">تسجيلات آخر 6 أشهر</h3>
            <p class="mt-0.5 text-xs text-muted">عدد الطلاب الجدد شهرياً</p>
        </div>
        <div class="space-y-3 p-4 sm:p-5">
            @forelse($usersByMonth->reverse() as $monthData)
                @php
                    $bar = ((int) $monthData->count / $maxMonthCount) * 100;
                    $label = ($monthNames[(int) $monthData->month] ?? $monthData->month).' '.$monthData->year;
                @endphp
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-ink">{{ $label }}</span>
                        <span class="text-sm font-semibold tabular-nums text-accent">{{ number_format($monthData->count) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-canvas">
                        <div class="h-full rounded-full bg-accent" style="width: {{ max(4, $bar) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-muted">لا توجد بيانات شهرية بعد</p>
            @endforelse
        </div>
    </article>
</div>

@push('scripts')
<script>
    function deleteStudent(btn) {
        var deleteUrl = btn && btn.getAttribute ? btn.getAttribute('data-delete-url') : null;
        if (!deleteUrl) {
            alert('خطأ: رابط الحذف غير متوفر.');
            return;
        }
        if (!confirm('هل أنت متأكد من حذف هذا الطالب؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            return;
        }
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
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
        .then(async function (response) {
            var contentType = response.headers.get('content-type') || '';
            var data = {};
            try {
                var text = await response.text();
                if (text && contentType.indexOf('application/json') !== -1) {
                    data = JSON.parse(text);
                }
            } catch (e) {}
            return { ok: response.ok, status: response.status, data: data };
        })
        .then(function (result) {
            if (result.ok && result.status === 200) {
                alert((result.data && result.data.message) ? result.data.message : 'تم حذف الطالب بنجاح');
                window.location.reload();
                return;
            }
            var errorMsg = (result.data && (result.data.message || result.data.error)) || 'حدث خطأ أثناء الحذف.';
            alert('خطأ: ' + errorMsg);
        })
        .catch(function () {
            alert('حدث خطأ أثناء حذف الطالب.');
        });
    }
</script>
@endpush
@endsection
