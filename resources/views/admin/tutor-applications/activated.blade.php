@extends('layouts.admin')

@section('title', 'المعلمون المفعّلون - حصتك')
@section('page_title', 'المعلمون المفعّلون من التوظيف')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5" x-data="{ hireOpen: {{ ($errors->any() && old('email')) ? 'true' : 'false' }} }">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التوظيف · المعلمون المفعّلون</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">المعلمون المفعّلون</h2>
            <p class="mt-1 text-sm text-muted">حسابات تم تفعيل ملفها العام من مسار التوظيف.</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.tutor-applications.hub') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:text-accent">
                <i class="fas fa-briefcase text-xs"></i>
                لوحة التوظيف
            </a>
            <button type="button" @click="hireOpen = true" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-user-plus text-xs"></i>
                توظيف يدوي بالإيميل
            </button>
        </div>
    </section>

    @if(session('hired_email'))
        <article class="rounded-2xl border border-accent/30 bg-accent-soft/40 px-4 py-4 sm:px-5">
            <p class="text-sm font-semibold text-ink">بيانات الدخول للمعلم</p>
            <p class="mt-1 text-xs text-muted" dir="ltr">{{ session('hired_email') }}</p>
            @if(session('hired_password'))
                <p class="mt-2 text-sm font-bold text-ink" dir="ltr">{{ session('hired_password') }}</p>
                <p class="mt-1 text-xs text-muted">انسخ كلمة المرور الآن — لن تظهر مرة أخرى. أُرسلت أيضاً إلى الإيميل.</p>
            @endif
        </article>
    @endif

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">بحث</h3>
            <p class="mt-0.5 text-xs text-muted">ابحث بالاسم أو البريد</p>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-3 p-4 sm:p-5">
            <div class="min-w-[220px] flex-1">
                <label class="mb-1.5 block text-xs font-medium text-muted" for="search">بحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="اسم أو بريد" class="{{ $fieldClass }}">
            </div>
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-search text-xs"></i>
                بحث
            </button>
            @if(request()->filled('search'))
                <a href="{{ route('admin.tutor-applications.activated') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">مسح</a>
            @endif
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">قائمة المفعّلين</h3>
                <p class="mt-0.5 text-xs text-muted">{{ number_format($applications->total()) }} معلم</p>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="w-full min-w-[860px] text-right text-sm">
                <thead class="bg-[#f7f8fa] text-[11px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">المعلم</th>
                        <th class="px-3 py-3 font-medium">الحساب</th>
                        <th class="px-3 py-3 font-medium">التفعيل</th>
                        <th class="px-3 py-3 font-medium">الحالة</th>
                        <th class="px-5 py-3 font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($applications as $app)
                        <tr class="transition hover:bg-[#f7f8fa]">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-ink">{{ $app->full_name }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $app->headline }}</p>
                            </td>
                            <td class="px-3 py-3 text-xs text-muted">
                                @if($app->user)
                                    <p class="font-medium text-ink">{{ $app->user->name }}</p>
                                    <p class="mt-0.5" dir="ltr">{{ $app->user->email }}</p>
                                @else
                                    <span>—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs text-muted">
                                <p class="tabular-nums">{{ $app->activated_at?->format('Y-m-d H:i') ?: '—' }}</p>
                                <p class="mt-0.5">بواسطة: {{ $app->activatedByUser->name ?? '—' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @if($app->user)
                                    <span class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $app->user->is_active ? 'bg-accent-soft text-accent' : 'bg-danger/10 text-danger' }}">
                                        {{ $app->user->is_active ? 'نشط' : 'معطّل' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.tutor-applications.show', $app) }}"
                                       class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-accent-soft text-accent transition hover:bg-accent hover:text-white"
                                       title="الطلب">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    @if($app->user)
                                        <a href="{{ route('public.instructors.show', $app->user) }}"
                                           target="_blank"
                                           class="btn-press inline-flex size-8 items-center justify-center rounded-lg border border-line text-muted transition hover:text-accent"
                                           title="الملف العام">
                                            <i class="fas fa-external-link-alt text-xs"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <p class="text-sm font-medium text-ink">لا يوجد معلمون مفعّلون بعد</p>
                                <p class="mt-1 text-xs text-muted">سيظهر هنا من يتم تفعيل ملفهم من مراجعة الطلبات.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $applications->withQueryString()->links() }}</div>
        @endif
    </article>

<div x-show="hireOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-ink/40" @click="hireOpen = false"></div>
    <article class="relative w-full max-w-md overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-ink">توظيف يدوي بالإيميل</h3>
                <p class="mt-0.5 text-xs text-muted">يُنشأ الحساب ويُفعَّل فوراً، وتُرسل بيانات الدخول إلى البريد.</p>
            </div>
            <button type="button" class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-canvas hover:text-ink" @click="hireOpen = false" aria-label="إغلاق">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.tutor-applications.hire-manually') }}" class="space-y-4 p-5">
            @csrf
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted" for="hire_email">البريد الإلكتروني</label>
                <input id="hire_email" type="email" name="email" value="{{ old('email') }}" required dir="ltr" placeholder="teacher@example.com" class="{{ $fieldClass }}">
                @error('email')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted" for="hire_name">الاسم الكامل</label>
                <input id="hire_name" type="text" name="full_name" value="{{ old('full_name') }}" required maxlength="160" placeholder="اسم المعلم" class="{{ $fieldClass }}">
                @error('full_name')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted" for="hire_phone">رقم الجوال <span class="font-normal">(اختياري)</span></label>
                <input id="hire_phone" type="text" name="phone" value="{{ old('phone') }}" maxlength="40" dir="ltr" placeholder="+20..." class="{{ $fieldClass }}">
                @error('phone')<p class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-wrap justify-end gap-2 pt-1">
                <button type="button" class="btn-press inline-flex h-11 items-center rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas" @click="hireOpen = false">إلغاء</button>
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-paper-plane text-xs"></i>
                    توظيف وإرسال الإيميل
                </button>
            </div>
        </form>
    </article>
</div>
</div>
@endsection
