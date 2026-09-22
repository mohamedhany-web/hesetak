@extends('layouts.admin')

@section('title', 'إدارة الأدوار')
@section('header', 'إدارة الأدوار')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الصلاحيات · الأدوار</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة الأدوار</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">عرّف أدوار الموظفين واربطها بالصلاحيات التي تظهر في سايدبار لوحة الإدارة.</p>
        </div>
        <a href="{{ route('admin.roles.create') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
            <i class="fas fa-plus text-xs"></i>
            إضافة دور جديد
        </a>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
        </div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-canvas/80">
                    <tr class="text-right text-xs font-bold uppercase tracking-wide text-muted">
                        <th class="px-4 py-3">الاسم</th>
                        <th class="px-4 py-3">الاسم المعروض</th>
                        <th class="px-4 py-3">الوصف</th>
                        <th class="px-4 py-3">الصلاحيات</th>
                        <th class="px-4 py-3">المستخدمون</th>
                        <th class="px-4 py-3">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($roles as $role)
                        <tr class="hover:bg-canvas/60 transition">
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <code class="rounded-lg bg-canvas px-2 py-0.5 text-xs font-semibold text-ink">{{ $role->name }}</code>
                                    @if($role->is_system)
                                        <span class="rounded-full bg-accent-soft px-2 py-0.5 text-[10px] font-bold text-accent">نظام</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-ink whitespace-nowrap">{{ $role->display_name }}</td>
                            <td class="px-4 py-3.5 text-ink-soft max-w-xs truncate">{{ $role->description ?: '—' }}</td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex rounded-full bg-accent-soft px-2.5 py-1 text-xs font-bold text-accent">
                                    {{ $role->permissions->count() }} صلاحية
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex rounded-full border border-line bg-canvas px-2.5 py-1 text-xs font-bold text-ink-soft">
                                    {{ $role->users->count() }} مستخدم
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.roles.show', $role) }}"
                                       class="inline-flex size-8 items-center justify-center rounded-lg border border-line text-ink-soft hover:bg-accent-soft hover:text-accent"
                                       title="الصلاحيات">
                                        <i class="fas fa-key text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="inline-flex size-8 items-center justify-center rounded-lg border border-line text-ink-soft hover:bg-accent-soft hover:text-accent"
                                       title="تعديل">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    @if(! $role->is_system)
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline"
                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex size-8 items-center justify-center rounded-lg border border-line text-rose-600 hover:bg-rose-50"
                                                    title="حذف">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-muted">لا توجد أدوار</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
