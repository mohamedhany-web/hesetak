@extends('layouts.admin')

@section('title', 'إضافة دور جديد')
@section('header', 'إضافة دور جديد')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الصلاحيات · الأدوار</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إضافة دور جديد</h2>
            <p class="mt-1 text-sm text-muted">عرّف الدور ثم اختر الصلاحيات المناسبة.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-canvas">
            <i class="fas fa-arrow-right text-xs"></i>
            العودة
        </a>
    </section>

    <form method="POST" action="{{ route('admin.roles.store') }}" class="rounded-2xl border border-line bg-surface shadow-soft overflow-hidden">
        @csrf
        <div class="space-y-5 p-4 sm:p-5">
            <div>
                <label for="name" class="mb-1.5 block text-xs font-medium text-muted">اسم الدور <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                       placeholder="مثال: content_manager">
                <p class="mt-1 text-xs text-muted">اسم فريد بالإنجليزية بدون مسافات</p>
                @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="display_name" class="mb-1.5 block text-xs font-medium text-muted">الاسم المعروض <span class="text-rose-500">*</span></label>
                <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required
                       class="h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                       placeholder="مثال: مدير المحتوى">
                @error('display_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="mb-1.5 block text-xs font-medium text-muted">الوصف</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                          placeholder="وصف مختصر (اختياري)">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-medium text-muted">الصلاحيات</label>
                <div class="max-h-96 overflow-y-auto rounded-xl border border-line p-4">
                    @if($permissions->count() > 0)
                        @foreach($permissions as $group => $groupPermissions)
                            <div class="mb-5 last:mb-0">
                                <h4 class="mb-2 border-b border-line pb-2 text-sm font-bold text-ink">{{ $group ?? 'عام' }}</h4>
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($groupPermissions as $permission)
                                        <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-line p-3 hover:bg-canvas">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                   class="mt-0.5 size-4 rounded border-line text-accent focus:ring-accent">
                                            <span class="min-w-0">
                                                <span class="block text-sm font-semibold text-ink">{{ $permission->display_name }}</span>
                                                @if($permission->description)
                                                    <span class="block text-xs text-muted">{{ $permission->description }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="py-6 text-center text-sm text-muted">لا توجد صلاحيات متاحة.</p>
                    @endif
                </div>
                @error('permissions.*')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line bg-canvas/50 px-4 py-3.5 sm:px-5">
            <a href="{{ route('admin.roles.index') }}"
               class="inline-flex h-9 items-center rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-canvas">إلغاء</a>
            <button type="submit"
                    class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-save text-xs"></i>
                حفظ الدور
            </button>
        </div>
    </form>
</div>
@endsection
