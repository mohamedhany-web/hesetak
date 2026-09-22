@extends('layouts.admin')

@section('title', 'صلاحيات ' . $user->name . ' - ' . config('app.name'))
@section('header', 'صلاحيات ' . $user->name)

@section('content')
<div class="space-y-5">
    <!-- معلومات المستخدم -->
    <div class="bg-surface rounded-xl shadow-soft p-6 border border-line">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                @if($user->profile_image)
                    <img class="h-16 w-16 rounded-full" src="{{ $user->profile_image_url }}" alt="{{ $user->name }}">
                @else
                    <div class="h-16 w-16 rounded-full bg-gradient-to-br from-accent to-[#0d4f4a] flex items-center justify-center text-white text-2xl font-bold">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h3 class="text-xl font-bold text-ink">{{ $user->name }}</h3>
                    <p class="text-sm text-muted">{{ $user->email }}</p>
                    <p class="text-sm text-muted">{{ $user->phone }}</p>
                </div>
            </div>
            <div class="text-left">
                <span class="px-3 py-1 text-sm font-semibold rounded-full 
                    {{ $user->role === 'super_admin' ? 'bg-red-100 text-red-800' : ($user->role === 'instructor' ? 'bg-accent-soft text-blue-800' : 'bg-green-100 text-green-800') }}">
                    {{ $user->role === 'super_admin' ? 'مدير عام' : ($user->role === 'instructor' ? 'مدرب' : __('admin.student_role_label')) }}
                </span>
            </div>
        </div>
    </div>

    <!-- إحصائيات الصلاحيات -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-surface rounded-xl shadow-soft p-6 border border-line">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-ink-soft">من الأدوار</p>
                    <p class="text-3xl font-bold text-ink">{{ $rolePermissions->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-accent-soft rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-tag text-accent text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-surface rounded-xl shadow-soft p-6 border border-line">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-ink-soft">مباشرة</p>
                    <p class="text-3xl font-bold text-ink">{{ $directPermissions->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-accent-soft rounded-lg flex items-center justify-center">
                    <i class="fas fa-key text-accent text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-surface rounded-xl shadow-soft p-6 border border-line">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-ink-soft">إجمالي الصلاحيات</p>
                    <p class="text-3xl font-bold text-accent">{{ $allUserPermissions->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-surface rounded-xl shadow-soft p-6 border border-line">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-ink-soft">الأدوار</p>
                    <p class="text-3xl font-bold text-ink">{{ $user->roles->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users-cog text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    @if($user->roles->isNotEmpty() && ! $user->is_employee)
        <div class="p-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 text-sm">
            <p class="font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> تنبيه</p>
            <p class="mt-1">هذا المستخدم مرتبط بأدوار لكنه غير مفعَّل كموظف، ولن تُطبَّق صلاحيات الأدوار في لوحة الإدارة بشكل صحيح. اضغط «حفظ الأدوار» أدناه لتفعيل صفة الموظف تلقائياً.</p>
        </div>
    @endif

    <!-- إدارة الأدوار -->
    <div class="bg-surface rounded-xl shadow-soft border border-line">
        <div class="p-6 border-b border-line">
            <h3 class="text-lg font-semibold text-ink">إدارة الأدوار</h3>
            <p class="text-sm text-muted mt-1">حدد الأدوار المخصصة للمستخدم. صلاحيات الأدوار تُضاف تلقائياً. عند الحفظ مع اختيار دور واحد على الأقل يُفعَّل المستخدم كموظف ليتم تطبيق RBAC في الأدمن.</p>
        </div>

        <form action="{{ route('admin.user-permissions.update-roles', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($allRoles as $role)
                        <label class="flex items-start p-4 border border-line rounded-lg cursor-pointer hover:bg-canvas transition-colors">
                            <input type="checkbox"
                                   name="roles[]"
                                   value="{{ $role->id }}"
                                   {{ $user->roles->contains('id', $role->id) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 text-accent focus:ring-purple-500 border-line rounded">
                            <div class="mr-3 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-ink">{{ $role->display_name }}</span>
                                    @if($role->is_system)
                                        <span class="text-xs px-2 py-1 rounded-full bg-accent-soft text-blue-800" title="دور نظامي">نظام</span>
                                    @endif
                                </div>
                                <p class="text-xs text-muted mt-1">{{ $role->name }}</p>
                                @if($role->description)
                                    <p class="text-xs text-ink-soft mt-1">{{ $role->description }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('roles.*')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="p-6 border-t border-line bg-canvas">
                <div class="flex items-center justify-end">
                    <button type="submit" class="px-6 py-2 bg-accent hover:bg-[#0d4f4a] text-white font-semibold rounded-lg transition-colors">
                        <i class="fas fa-save ml-2"></i>
                        حفظ الأدوار
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- صلاحيات المستخدم الفعلية (من الأدوار + المباشرة) -->
    <div class="bg-surface rounded-xl shadow-soft border border-line">
        <div class="p-6 border-b border-line">
            <h3 class="text-lg font-semibold text-ink">صلاحيات هذا المستخدم</h3>
            <p class="text-sm text-muted mt-1">يُعرض هنا فقط ما يملكه المستخدم حالياً (من أدواره أو مباشرة). أزل التحديد عن الصلاحية المباشرة ثم احفظ لإزالتها من المستخدم.</p>
        </div>

        <form action="{{ route('admin.user-permissions.update', $user) }}" method="POST" id="permissionsForm">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                @if($allUserPermissions->isEmpty())
                    <p class="text-sm text-ink-soft text-center py-8">
                        <i class="fas fa-info-circle text-amber-500 ml-2"></i>
                        لا توجد صلاحيات مرتبطة بهذا المستخدم عبر الأدوار أو التعيين المباشر. اختر أدواراً أعلاه أو أضف صلاحيات مباشرة من
                        <a href="{{ route('admin.permissions.index') }}" class="text-accent font-semibold underline">قائمة الصلاحيات</a>
                        إن وُجدت أداة تعيين سريع لديكم.
                    </p>
                @else
                    @foreach($userPermissionsGrouped as $group => $permissions)
                        <div class="border-b border-line pb-6 last:border-b-0 last:pb-0">
                            <h4 class="text-md font-semibold text-ink mb-4 flex items-center gap-2">
                                <i class="fas fa-folder text-blue-500"></i>
                                {{ $group ?: 'عام' }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($permissions as $permission)
                                    @php
                                        $hasFromRole = $rolePermissions->contains('id', $permission->id);
                                        $hasDirect = $directPermissions->contains('id', $permission->id);
                                    @endphp
                                    @if($hasDirect)
                                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-canvas transition-colors
                                            {{ $hasFromRole ? 'border-accent/40 bg-accent-soft' : 'border-line' }}
                                            border-blue-500 bg-accent-soft">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission->id }}"
                                                   checked
                                                   class="mt-1 h-4 w-4 text-accent focus:ring-blue-500 border-line rounded">
                                            <div class="mr-3 flex-1">
                                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                                    <span class="text-sm font-medium text-ink">{{ $permission->display_name }}</span>
                                                    <div class="flex items-center gap-1 flex-wrap">
                                                        @if($hasFromRole)
                                                            <span class="text-xs px-2 py-1 rounded-full bg-accent-soft text-accent">من الأدوار</span>
                                                        @endif
                                                        <span class="text-xs px-2 py-1 rounded-full bg-accent-soft text-blue-800">مباشرة</span>
                                                    </div>
                                                </div>
                                                <code class="text-[10px] text-muted font-mono block mt-1">{{ $permission->name }}</code>
                                                @if($permission->description)
                                                    <p class="text-xs text-muted mt-1">{{ $permission->description }}</p>
                                                @endif
                                            </div>
                                        </label>
                                    @else
                                        <div class="flex items-start p-4 border rounded-lg border-accent/30 bg-accent-soft/80">
                                            <div class="mt-1 h-4 w-4 flex-shrink-0 rounded border border-accent/40 bg-accent-soft flex items-center justify-center">
                                                <i class="fas fa-lock text-[10px] text-accent"></i>
                                            </div>
                                            <div class="mr-3 flex-1">
                                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                                    <span class="text-sm font-medium text-ink">{{ $permission->display_name }}</span>
                                                    <span class="text-xs px-2 py-1 rounded-full bg-accent-soft text-accent">من الأدوار فقط</span>
                                                </div>
                                                <code class="text-[10px] text-muted font-mono block mt-1">{{ $permission->name }}</code>
                                                @if($permission->description)
                                                    <p class="text-xs text-muted mt-1">{{ $permission->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            @if($allUserPermissions->isNotEmpty())
            <div class="p-6 border-t border-line bg-canvas">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-ink-soft">
                        <i class="fas fa-info-circle ml-2"></i>
                        الصلاحيات «من الأدوار فقط» تُزال بتعديل الأدوار أعلاه. الصلاحيات «مباشرة» تُحفظ هنا عند إلغاء التحديد والضغط على حفظ.
                    </p>
                    <button type="submit" class="px-6 py-2 bg-accent hover:bg-[#0d4f4a] text-white font-semibold rounded-lg transition-colors flex-shrink-0">
                        <i class="fas fa-save ml-2"></i>
                        حفظ الصلاحيات المباشرة
                    </button>
                </div>
            </div>
            @endif
        </form>
    </div>
</div>
@endsection

