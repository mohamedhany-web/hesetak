@extends('layouts.admin')

@section('title', 'عرض المستخدم - ' . $user->name)
@section('page_title', 'عرض المستخدم')

@section('content')
@php
    $roles = [
        'super_admin' => ['label' => 'مدير عام', 'badge' => 'bg-danger/10 text-danger'],
        'admin' => ['label' => 'إداري', 'badge' => 'bg-accent-soft text-accent'],
        'instructor' => ['label' => 'معلم', 'badge' => 'bg-metal/15 text-metal'],
        'teacher' => ['label' => 'معلم (مدرس)', 'badge' => 'bg-metal/15 text-metal'],
        'student' => ['label' => __('admin.student_role_label'), 'badge' => 'bg-accent-soft text-accent'],
        'parent' => ['label' => 'ولي أمر', 'badge' => 'bg-canvas-muted text-muted'],
        'employee' => ['label' => 'موظف', 'badge' => 'bg-metal/15 text-metal'],
    ];
    $roleKey = $user->is_employee ? 'employee' : $user->role;
    $roleMeta = $roles[$roleKey] ?? $roles['student'];
    $isTeacherAccount = in_array($user->role, ['instructor', 'teacher'], true);
    $listRoute = ($user->role === 'student' && Route::has('admin.students-accounts.index'))
        ? route('admin.students-accounts.index')
        : route('admin.users.index', $isTeacherAccount ? ['role' => 'teachers'] : []);
    $listLabel = ($user->role === 'student' && Route::has('admin.students-accounts.index'))
        ? 'إدارة الطلاب والحسابات'
        : ($isTeacherAccount ? 'قائمة المعلمين' : 'إدارة المستخدمين');
    $brandingProfile = $isTeacherAccount ? ($user->instructorProfile ?? null) : null;
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الحسابات · {{ $listLabel }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $user->name }}</h2>
            <p class="mt-1 text-sm text-muted">عضوية #{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }} · تفاصيل الحساب والحالة</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-pen text-xs"></i>
                تعديل
            </a>
            <a href="{{ $listRoute }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $roleMeta['badge'] }}">
                <i class="fas fa-user-shield text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الدور</p>
            <p class="mt-1">
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $roleMeta['badge'] }}">{{ $roleMeta['label'] }}</span>
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $user->is_active ? 'bg-accent-soft text-accent' : 'bg-canvas-muted text-muted' }}">
                <i class="fas fa-{{ $user->is_active ? 'user-check' : 'user-slash' }} text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">حالة الحساب</p>
            <p class="mt-1">
                @if($user->is_active)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">
                        <span class="size-1.5 rounded-full bg-accent"></span>
                        نشط
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-canvas-muted px-2.5 py-1 text-xs font-semibold text-muted">
                        <span class="size-1.5 rounded-full bg-muted"></span>
                        غير نشط
                    </span>
                @endif
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-calendar-plus text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">تاريخ التسجيل</p>
            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '—' }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-clock text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">آخر تسجيل دخول</p>
            <p class="mt-1 text-sm font-semibold tabular-nums text-ink">{{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : '—' }}</p>
        </article>
    </section>

    <div class="grid gap-5 lg:grid-cols-5">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft lg:col-span-3">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">البيانات الأساسية</h3>
                <p class="mt-0.5 text-xs text-muted">الهوية وبيانات التواصل</p>
            </div>
            <div class="p-4 sm:p-5">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                    <div class="shrink-0">
                        @if($user->profile_image)
                            <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}" class="size-24 rounded-2xl border border-line object-cover">
                        @else
                            <span class="inline-flex size-24 items-center justify-center rounded-2xl bg-accent-soft text-3xl font-semibold text-accent">
                                {{ mb_substr($user->name, 0, 1, 'UTF-8') }}
                            </span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 space-y-3">
                        <div class="rounded-xl border border-line bg-canvas/60 p-4">
                            <p class="text-xs font-medium text-muted">الاسم</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ $user->name }}</p>
                        </div>
                        <div class="rounded-xl border border-line bg-canvas/60 p-4">
                            <p class="text-xs font-medium text-muted">البريد الإلكتروني</p>
                            <p class="mt-1 break-all text-sm font-semibold text-ink">{{ $user->email ?: '—' }}</p>
                        </div>
                        <div class="rounded-xl border border-line bg-canvas/60 p-4">
                            <p class="text-xs font-medium text-muted">رقم الهاتف</p>
                            <p class="mt-1 text-sm font-semibold text-ink" dir="ltr">{{ $user->phone ?: '—' }}</p>
                        </div>
                    </div>
                </div>
                @if($user->bio)
                    <div class="mt-5 border-t border-line pt-5">
                        <p class="text-xs font-medium text-muted">النبذة التعريفية</p>
                        <div class="mt-2 rounded-xl border border-line bg-canvas/60 p-4">
                            <p class="whitespace-pre-wrap text-sm leading-7 text-ink">{{ $user->bio }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft lg:col-span-2">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">إجراءات سريعة</h3>
                <p class="mt-0.5 text-xs text-muted">{{ $isTeacherAccount ? 'تعديل الحساب والجدول والملف العام' : 'تعديل أو العودة للقائمة' }}</p>
            </div>
            <div class="space-y-2 p-4 sm:p-5">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-pen text-xs"></i>
                    تعديل بيانات المستخدم
                </a>
                @if($isTeacherAccount)
                    @if(Route::has('admin.tutor-work-schedules.index'))
                        <a href="{{ route('admin.tutor-work-schedules.index', ['instructor_id' => $user->id]) }}" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                            <i class="fas fa-calendar-alt text-xs"></i>
                            جدول عمل المعلم
                        </a>
                    @endif
                    @if(Route::has('admin.one-to-one-sessions.index'))
                        <a href="{{ route('admin.one-to-one-sessions.index', ['instructor_id' => $user->id]) }}" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                            <i class="fas fa-user-clock text-xs"></i>
                            الحصص الخاصة
                        </a>
                    @endif
                    @if($brandingProfile && Route::has('admin.personal-branding.show'))
                        <a href="{{ route('admin.personal-branding.show', $brandingProfile) }}" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                            <i class="fas fa-id-card text-xs"></i>
                            الملف العام / الاعتماد
                        </a>
                    @elseif(Route::has('admin.personal-branding.index'))
                        <a href="{{ route('admin.personal-branding.index') }}" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                            <i class="fas fa-id-card text-xs"></i>
                            الملفات العامة للمعلمين
                        </a>
                    @endif
                    @if(Route::has('public.instructors.show'))
                        <a href="{{ route('public.instructors.show', $user) }}" target="_blank" rel="noopener" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                            <i class="fas fa-external-link-alt text-xs"></i>
                            فتح صفحة المعلم العامة
                        </a>
                    @endif
                    @if($user->id !== auth()->id())
                        <button type="button" onclick="deleteUser(this)"
                                data-delete-url="{{ route('admin.users.delete', $user) }}"
                                class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-medium text-rose-700">
                            <i class="fas fa-trash text-xs"></i>
                            حذف الحساب
                        </button>
                    @endif
                @endif
                <a href="{{ $listRoute }}" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                    <i class="fas fa-arrow-right text-xs"></i>
                    {{ $listLabel }}
                </a>
            </div>
        </article>
    </div>
</div>
@if($isTeacherAccount && $user->id !== auth()->id())
<script>
function deleteUser(btn) {
    if (!confirm('حذف حساب المعلم نهائياً؟')) return;
    var url = btn.getAttribute('data-delete-url');
    var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '{{ csrf_token() }}';
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (data) {
        if (data && data.success === false) {
            alert(data.message || 'تعذر الحذف');
            return;
        }
        window.location.href = '{{ route('admin.users.index', ['role' => 'teachers']) }}';
      }).catch(function () {
        alert('تعذر حذف الحساب');
      });
}
</script>
@endif
@endsection
