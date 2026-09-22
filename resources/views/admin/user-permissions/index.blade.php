@extends('layouts.admin')

@section('title', 'صلاحيات المستخدمين')
@section('header', 'صلاحيات المستخدمين')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الصلاحيات · المستخدمون</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">صلاحيات المستخدمين</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">اربط الأدوار والصلاحيات المباشرة بكل مستخدم موظف.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.roles.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-user-tag text-xs"></i>
                الأدوار
            </a>
            <a href="{{ route('admin.permissions.index') }}"
               class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-key text-xs"></i>
                الصلاحيات
            </a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-users text-sm"></i>
            </div>
            <p class="mt-3 text-xs font-medium text-muted">إجمالي المستخدمين</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $users->total() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-key text-sm"></i>
            </div>
            <p class="mt-3 text-xs font-medium text-muted">إجمالي الصلاحيات</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $allPermissions->flatten()->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-[#f2f5f4] text-accent">
                <i class="fas fa-folder text-sm"></i>
            </div>
            <p class="mt-3 text-xs font-medium text-muted">المجموعات</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ $allPermissions->count() }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-3.5 sm:px-5">
            <h3 class="text-sm font-bold text-ink">قائمة المستخدمين</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-canvas/80">
                    <tr class="text-right text-xs font-bold uppercase tracking-wide text-muted">
                        <th class="px-4 py-3">المستخدم</th>
                        <th class="px-4 py-3">الدور الأساسي</th>
                        <th class="px-4 py-3">الأدوار المخصصة</th>
                        <th class="px-4 py-3">مباشرة</th>
                        <th class="px-4 py-3">الإجمالي</th>
                        <th class="px-4 py-3">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($users as $user)
                        @php
                            $rolePermissions = $user->roles()->with('permissions')->get()->pluck('permissions')->flatten()->unique('id');
                            $directPermissions = $user->directPermissions;
                            $totalPermissions = $rolePermissions->merge($directPermissions)->unique('id');
                        @endphp
                        <tr class="hover:bg-canvas/60 transition">
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @if($user->profile_image)
                                        <img class="size-10 rounded-full object-cover" src="{{ $user->profile_image_url }}" alt="">
                                    @else
                                        <div class="flex size-10 items-center justify-center rounded-full bg-accent text-sm font-bold text-white">
                                            {{ mb_substr($user->name, 0, 1, 'UTF-8') }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-semibold text-ink truncate">{{ $user->name }}</p>
                                        <p class="text-xs text-muted truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold
                                    {{ $user->role === 'super_admin' ? 'bg-rose-50 text-rose-700' : ($user->role === 'instructor' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700') }}">
                                    {{ $user->role === 'super_admin' ? 'مدير عام' : ($user->role === 'instructor' ? 'مدرب' : __('admin.student_role_label')) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($user->roles as $role)
                                        <span class="rounded-full bg-accent-soft px-2 py-0.5 text-[11px] font-bold text-accent">
                                            {{ $role->display_name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-muted">لا يوجد</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap font-semibold text-ink-soft">
                                {{ $directPermissions->count() }}
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap font-bold text-accent">
                                {{ $totalPermissions->count() }}
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <a href="{{ route('admin.user-permissions.show', $user) }}"
                                   class="btn-press inline-flex h-8 items-center gap-1.5 rounded-xl bg-accent px-3 text-xs font-bold text-white hover:bg-[#0d4f4a]">
                                    <i class="fas fa-user-shield text-[10px]"></i>
                                    إدارة
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-muted">لا يوجد مستخدمون</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $users->links() }}</div>
        @endif
    </section>
</div>
@endsection
