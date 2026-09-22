@extends('layouts.admin')

@section('title', 'إدارة الصلاحيات')
@section('header', 'إدارة الصلاحيات')

@section('content')
@php
    $translations = [
        'إدارة المحاسبة' => 'إدارة المحاسبة (فواتير، مدفوعات، تقسيط، محافظ)',
        'إدارة النظام' => 'إدارة النظام (مستخدمون، إعدادات، نشاطات)',
        'إدارة الصفحات الخارجية' => 'الصفحات العامة (تواصل، خدمات، آراء، إعدادات الواجهة، من نحن)',
        'إدارة المحتوى' => 'المحتوى الأكاديمي (كورسات، محاضرات، واجبات، امتحانات، حضور، بنك أسئلة)',
        'cms' => 'محتوى الموقع (قديم)',
        'system' => 'النظام (صلاحيات قديمة)',
        'users' => 'المستخدمون (صلاحيات تفصيلية قديمة)',
        'courses' => 'الكورسات (صلاحيات تفصيلية قديمة)',
        'lectures' => 'المحاضرات (صلاحيات تفصيلية قديمة)',
        'assignments' => 'الواجبات (صلاحيات تفصيلية قديمة)',
        'exams' => 'الامتحانات (صلاحيات تفصيلية قديمة)',
        'finance' => 'المالية (صلاحيات تفصيلية قديمة)',
        'notifications' => 'الإشعارات (صلاحيات تفصيلية قديمة)',
        'certificates' => 'الشهادات (صلاحيات تفصيلية قديمة)',
        'reports' => 'التقارير (صلاحيات تفصيلية قديمة)',
        'tasks' => 'المهام (صلاحيات تفصيلية قديمة)',
        'supervision' => 'الإشراف الأكاديمي',
    ];
    $totalPerms = $permissions->flatten()->count();
    $rolesLinked = $permissions->flatten()->sum('roles_count');
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الصلاحيات · الكتالوج</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة الصلاحيات</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">عرض الصلاحيات المعرفة في النظام. الإنشاء والتعديل من مسؤولية الفريق التقني.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
            <i class="fas fa-user-tag text-xs"></i>
            الأدوار
        </a>
    </section>

    <section class="grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-key text-sm"></i>
            </div>
            <p class="mt-3 text-xs font-medium text-muted">إجمالي الصلاحيات</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $totalPerms }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-folder text-sm"></i>
            </div>
            <p class="mt-3 text-xs font-medium text-muted">المجموعات</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $permissions->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-link text-sm"></i>
            </div>
            <p class="mt-3 text-xs font-medium text-muted">ارتباطات الأدوار</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $rolesLinked }}</p>
        </article>
    </section>

    <section class="rounded-2xl border border-line bg-surface shadow-soft overflow-hidden">
        <div class="border-b border-line px-4 py-3.5 sm:px-5">
            <h3 class="text-sm font-bold text-ink">الصلاحيات حسب المجموعة</h3>
            <p class="mt-0.5 text-xs text-muted">مرجع للقراءة فقط — اربط الصلاحيات من صفحة الدور أو صلاحيات المستخدم.</p>
        </div>

        <div class="p-4 sm:p-5 space-y-5">
            @forelse($permissions as $group => $groupPermissions)
                <div class="rounded-xl border border-line overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 bg-canvas/70 px-4 py-3 border-b border-line">
                        <h4 class="text-sm font-bold text-ink flex items-center gap-2">
                            <i class="fas fa-folder text-accent text-xs"></i>
                            {{ $translations[$group] ?? ($group ?? 'عام') }}
                        </h4>
                        <span class="rounded-full border border-line bg-surface px-2.5 py-0.5 text-[11px] font-bold text-muted">
                            {{ $groupPermissions->count() }} صلاحية
                        </span>
                    </div>
                    <div class="grid gap-3 p-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($groupPermissions as $permission)
                            <article class="rounded-xl border border-line bg-surface p-3.5 hover:border-accent/40 transition">
                                <h5 class="text-sm font-bold text-ink">{{ $permission->display_name }}</h5>
                                <code class="mt-1 block text-[10px] font-mono text-muted truncate">{{ $permission->name }}</code>
                                @if($permission->description)
                                    <p class="mt-2 text-xs leading-relaxed text-ink-soft">{{ $permission->description }}</p>
                                @endif
                                <span class="mt-2 inline-flex rounded-full bg-accent-soft px-2 py-0.5 text-[10px] font-bold text-accent">
                                    {{ $permission->roles_count }} دور
                                </span>
                            </article>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="py-14 text-center">
                    <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-canvas text-muted">
                        <i class="fas fa-key text-xl"></i>
                    </div>
                    <p class="font-bold text-ink">لا توجد صلاحيات مسجلة</p>
                    <p class="mt-1 text-sm text-muted">أبلغ الفريق التقني إن كان من المفترض وجود صلاحيات.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
