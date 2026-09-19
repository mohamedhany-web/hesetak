@extends('layouts.admin')

@section('title', 'صلاحيات الدور: ' . $role->display_name)
@section('header', 'إدارة صلاحيات الدور')

@section('content')
<div class="space-y-5">

    @if(session('success'))
        <div class="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm">
            <i class="fas fa-check-circle text-green-500 flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm space-y-1">
            <p class="font-bold"><i class="fas fa-exclamation-circle mr-1"></i> لم يُحفَظ التعديل</p>
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center shadow">
                <i class="fas fa-user-shield text-white"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ $role->display_name }}</h2>
                <p class="text-xs text-gray-400 font-mono">{{ $role->name }}</p>
            </div>
            <div class="flex items-center gap-2 mr-2">
                <span class="text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded-lg border border-blue-100 font-semibold">
                    <span id="headerCount">{{ $role->permissions->count() }}</span> / {{ $permissions->flatten()->count() }} صلاحية
                </span>
                <span class="text-xs px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg border border-emerald-100 font-semibold">
                    {{ $role->users->count() }} موظف
                </span>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.roles.edit', $role) }}"
               class="text-sm px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-edit mr-1"></i> تعديل البيانات
            </a>
            <a href="{{ route('admin.roles.index') }}"
               class="text-sm px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-right mr-1"></i> الأدوار
            </a>
        </div>
    </div>

    {{-- توضيح --}}
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 space-y-2">
        @foreach(\App\Support\AdminSidebarRoleMap::introLines() as $line)
            <p class="text-xs text-indigo-900 leading-relaxed">{{ $line }}</p>
        @endforeach
        <p class="text-xs text-indigo-800 leading-relaxed">
            <i class="fas fa-user-shield mr-1"></i>
            للموظف (<code class="text-[10px] bg-white/80 px-1 rounded">is_employee</code> + دور RBAC): فتح الصفحات يخضع أيضاً لـ
            <code class="text-[10px] bg-white/80 px-1 rounded">config/rbac_admin_route_access.php</code>.
        </p>
        <p class="text-xs text-gray-600">
            <i class="fas fa-code-branch mr-1 text-indigo-500"></i>
            الخريطة مأخوذة من <code class="text-[10px] bg-white px-1 rounded border">config/admin_sidebar_role_map.php</code> وتطابق ترتيب سايدبار الإدارة في <code class="text-[10px] bg-white px-1 rounded border">layouts/admin-sidebar.blade.php</code>.
        </p>
    </div>

    {{-- نموذج الصلاحيات --}}
    <form method="POST" action="{{ route('admin.roles.update-permissions', $role) }}" id="permissionsForm">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200">

            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-sm font-bold text-gray-800">
                    <i class="fas fa-bars text-indigo-500 mr-1"></i>
                    صلاحيات الدور — مطابقة سايدبار لوحة الإدارة + صلاحيات أخرى
                </h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="toggleAll(true)"
                            class="text-xs px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 border border-blue-200 font-medium">
                        <i class="fas fa-check-double mr-1"></i> تحديد الكل
                    </button>
                    <button type="button" onclick="toggleAll(false)"
                            class="text-xs px-3 py-1.5 bg-gray-100 text-gray-500 rounded-lg hover:bg-gray-200 border border-gray-200 font-medium">
                        <i class="fas fa-times mr-1"></i> إلغاء الكل
                    </button>
                    <span class="text-xs text-gray-500 bg-gray-50 px-2 py-1.5 rounded-lg border border-gray-200">
                        <span id="checkedCount">{{ $role->permissions->count() }}</span>/{{ $permissions->flatten()->count() }}
                    </span>
                </div>
            </div>

            <div class="p-4">
                @php $rolePermIds = $role->permissions->pluck('id')->toArray(); @endphp

                {{-- 1) خريطة السايدبار --}}
                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-1.5 h-5 bg-indigo-600 rounded-full flex-shrink-0"></span>
                        <h4 class="text-sm font-bold text-gray-800">خريطة سايدبار الإدارة — فعّل الصلاحية ليظهر العنصر (أي صلاحية من المذكورة تكفي لظهور الرابط)</h4>
                    </div>
                    <div class="space-y-4">
                        @foreach($adminSidebarBlocks as $block)
                            <div class="rounded-xl border border-indigo-100 bg-white overflow-hidden shadow-sm">
                                <div class="px-4 py-3 bg-indigo-50/90 border-b border-indigo-100 flex items-center justify-between gap-2 flex-wrap">
                                    <div class="min-w-0">
                                        <h5 class="text-xs font-bold text-indigo-900">{{ $block['section']['title'] ?? '' }}</h5>
                                        @if(!empty($block['section']['note']))
                                            <p class="text-[10px] text-amber-900 bg-amber-50 border border-amber-100 rounded-lg px-2 py-1 mt-1.5 leading-relaxed">{{ $block['section']['note'] }}</p>
                                        @endif
                                    </div>
                                    <button type="button" data-group="sidebarBlock{{ $loop->index }}" onclick="toggleGroup(this)"
                                            class="text-[10px] px-2 py-1 text-indigo-600 hover:bg-indigo-100 rounded-lg font-medium border border-indigo-200 flex-shrink-0">
                                        تحديد القسم
                                    </button>
                                </div>
                                <div id="sidebarBlock{{ $loop->index }}">
                                    @foreach($block['rows'] as $row)
                                        @if(($row['type'] ?? '') === 'group')
                                            <div class="px-4 py-2 bg-slate-50 border-b border-gray-100">
                                                <span class="text-xs font-bold text-slate-700" style="padding-right: {{ (int)($row['depth'] ?? 0) * 12 }}px">{{ $row['label'] ?? '' }}</span>
                                                @if(!empty($row['note']))
                                                    <p class="text-[10px] text-gray-500 mt-0.5">{{ $row['note'] }}</p>
                                                @endif
                                            </div>
                                        @elseif(($row['type'] ?? '') === 'item')
                                            <div class="flex flex-wrap items-start gap-2 px-4 py-2.5 border-b border-gray-100 hover:bg-gray-50/80 transition-colors">
                                                <div class="flex-1 min-w-[180px]" style="padding-right: {{ (int)($row['depth'] ?? 0) * 12 }}px">
                                                    <span class="text-xs font-semibold text-gray-800">{{ $row['label'] ?? '' }}</span>
                                                    @if(!empty($row['note']))
                                                        <p class="text-[10px] text-gray-500 mt-0.5">{{ $row['note'] }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap gap-1.5 justify-end max-w-full">
                                                    @foreach($row['permissions'] ?? [] as $meta)
                                                        @if(!empty($meta['missing']))
                                                            <span class="text-[10px] text-red-600 bg-red-50 border border-red-100 px-2 py-1 rounded-lg font-mono">{{ $meta['name'] }} غير موجودة في الجدول</span>
                                                        @elseif(!empty($meta['first']))
                                                            @php $isChecked = in_array($meta['id'], $rolePermIds); @endphp
                                                            <label class="perm-card inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg border cursor-pointer transition-all select-none text-xs max-w-[220px]
                                                                {{ $isChecked ? 'bg-indigo-50 border-indigo-400' : 'bg-white border-gray-200 hover:border-indigo-300' }}">
                                                                <input type="checkbox" name="permissions[]" value="{{ $meta['id'] }}"
                                                                       {{ $isChecked ? 'checked' : '' }}
                                                                       onchange="onPermChange(this)"
                                                                       class="w-3.5 h-3.5 text-indigo-600 border-gray-300 rounded flex-shrink-0">
                                                                <div class="min-w-0">
                                                                    <span class="font-semibold text-gray-800 block truncate leading-tight">{{ $meta['display_name'] }}</span>
                                                                    <code class="text-[9px] text-gray-400 font-mono truncate block">{{ $meta['name'] }}</code>
                                                                </div>
                                                            </label>
                                                        @else
                                                            <span class="text-[10px] text-gray-500 bg-gray-100 border border-gray-200 px-2 py-1 rounded-lg font-mono" title="راجع مربع نفس الصلاحية أعلى في الخريطة">
                                                                {{ $meta['name'] }} <span class="text-gray-400">(مكررة)</span>
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- 2) صلاحيات لا تظهر في سايدبار الإدارة --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-1.5 h-5 bg-slate-400 rounded-full flex-shrink-0"></span>
                        <h4 class="text-sm font-bold text-gray-700">صلاحيات أخرى (طالب، مدرب، تقويم، … — لا تظهر في سايدبار الإدارة)</h4>
                    </div>
                    @if($otherPermissions->flatten()->isEmpty())
                        <p class="text-xs text-gray-500 py-3">لا توجد صلاحيات خارج خريطة السايدبار.</p>
                    @else
                        <div class="space-y-5">
                            @foreach($otherPermissions as $group => $groupPermissions)
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-1.5 h-4 bg-slate-300 rounded-full flex-shrink-0"></span>
                                    <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wide">{{ $group ?? 'عام' }}</h4>
                                    <div class="flex-1 h-px bg-gray-100"></div>
                                    <button type="button" data-group="other{{ $loop->index }}" onclick="toggleGroup(this)"
                                            class="text-xs text-slate-500 hover:text-slate-700 font-medium flex-shrink-0">
                                        تحديد
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-1.5" id="other{{ $loop->index }}">
                                    @foreach($groupPermissions as $permission)
                                    @php $isChecked = in_array($permission->id, $rolePermIds); @endphp
                                    <label class="perm-card flex items-center gap-2 px-2.5 py-2 rounded-lg border cursor-pointer transition-all select-none text-xs
                                                  {{ $isChecked ? 'bg-indigo-50 border-indigo-400' : 'bg-gray-50 border-gray-200 hover:border-indigo-300' }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                               {{ $isChecked ? 'checked' : '' }}
                                               onchange="onPermChange(this)"
                                               class="w-3.5 h-3.5 text-indigo-600 border-gray-300 rounded flex-shrink-0">
                                        <div class="min-w-0">
                                            <span class="font-semibold text-gray-800 block truncate leading-tight">{{ $permission->display_name }}</span>
                                            <code class="text-[10px] text-gray-400 font-mono truncate block">{{ $permission->name }}</code>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-50 rounded-b-xl border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-600 max-w-xl space-y-1">
                    <span class="block"><i class="fas fa-database mr-1 text-indigo-500"></i>
                    الصلاحيات المحددة تُخزَّن في جدول الربط <code class="text-[10px] bg-white px-1 rounded">role_permissions</code> مع الدور؛ المستخدم يحصل عليها عبر جدول <code class="text-[10px] bg-white px-1 rounded">user_roles</code> عند ربطه بالدور من
                    <a href="{{ route('admin.user-permissions.index') }}" class="text-indigo-600 font-semibold underline">صلاحيات المستخدمين</a>
                    (يُفعَّل <code class="text-[10px] bg-white px-1 rounded">is_employee</code> تلقائياً عند الحاجة).</span>
                    <span class="block font-semibold text-indigo-800"><i class="fas fa-bars mr-1"></i>
                    في واجهة الموظف: تظهر أقسام القائمة المخصصة ثم مجموعات بعنوان مجموعة الصلاحية من قاعدة البيانات، وكل صلاحية مفعّلة لها رابط يطابق صفحة الإدارة/الموظف (حسب <code class="text-[10px]">config/rbac_permission_sidebar.php</code>).</span>
                </p>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-white rounded-xl font-bold text-sm shadow-sm"
                        style="background-color:#16a34a;">
                    <i class="fas fa-save"></i> حفظ
                </button>
            </div>
        </div>
    </form>

    {{-- المستخدمون --}}
    @if($role->users->count() > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="text-sm font-bold text-gray-700 mb-3">
            <i class="fas fa-users text-indigo-500 mr-1"></i>
            الموظفون بهذا الدور ({{ $role->users->count() }})
        </h3>
        <div class="flex flex-wrap gap-2">
            @foreach($role->users as $user)
            <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                <div class="w-7 h-7 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                    {{ mb_substr($user->name, 0, 1, 'UTF-8') }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-900 truncate max-w-[120px]">{{ $user->name }}</p>
                    <p class="text-[10px] text-gray-400 truncate max-w-[120px]">{{ $user->email ?? '—' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

<script>
function onPermChange(cb) {
    const label = cb.closest('label');
    label.classList.toggle('bg-indigo-50', cb.checked);
    label.classList.toggle('border-indigo-400', cb.checked);
    label.classList.toggle('bg-gray-50', !cb.checked);
    label.classList.toggle('border-gray-200', !cb.checked);
    updateCount();
}
function toggleAll(state) {
    document.querySelectorAll('#permissionsForm input[type=checkbox]').forEach(cb => {
        if (cb.checked !== state) { cb.checked = state; onPermChange(cb); }
    });
}
function toggleGroup(btn) {
    const boxes = document.getElementById(btn.dataset.group).querySelectorAll('input[type=checkbox]');
    const allOn = [...boxes].every(c => c.checked);
    boxes.forEach(c => { if (c.checked !== !allOn) { c.checked = !allOn; onPermChange(c); } });
    btn.textContent = allOn ? 'تحديد' : 'إلغاء';
}
function updateCount() {
    const n = document.querySelectorAll('#permissionsForm input[type=checkbox]:checked').length;
    document.getElementById('checkedCount').textContent = n;
    document.getElementById('headerCount').textContent = n;
}
</script>
@endsection
