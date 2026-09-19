@extends('layouts.admin')

@section('title', 'تعديل الدور')
@section('header', 'تعديل الدور')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">معلومات الدور</h3>
                    <p class="text-sm text-gray-500 mt-1">تعديل معلومات الدور: {{ $role->display_name }}</p>
                </div>
                <a href="{{ route('admin.roles.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-arrow-right mr-2"></i>
                    العودة للقائمة
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-6">
                <!-- اسم الدور -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        اسم الدور <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="مثال: content_manager"
                           {{ $role->is_system ? 'readonly' : '' }}>
                    @if($role->is_system)
                        <p class="mt-1 text-xs text-blue-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            هذا دور نظامي ولا يمكن تعديل اسمه
                        </p>
                    @endif
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- الاسم المعروض -->
                <div>
                    <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">
                        الاسم المعروض <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $role->display_name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="مثال: مدير المحتوى">
                    @error('display_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- الوصف -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        الوصف
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="وصف مختصر للدور (اختياري)">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- خريطة الصلاحيات → أقسام السايدبار -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-map text-blue-600"></i>
                        <h4 class="text-sm font-semibold text-blue-800">دليل: أي صلاحية تُظهر أي قسم في سايدبار الموظف؟</h4>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                        @php
                        $sidebarMap = [
                            ['perm' => 'manage.orders',   'icon' => 'fa-chart-line',    'color' => 'emerald', 'sections' => 'لوحة المبيعات + طلبات المبيعات'],
                            ['perm' => 'manage.invoices', 'icon' => 'fa-calculator',    'color' => 'amber',   'sections' => 'لوحة المحاسب + محاسبتي + الاتفاقيات'],
                            ['perm' => 'manage.users',    'icon' => 'fa-users',         'color' => 'rose',    'sections' => 'الموارد البشرية + الإجازات + الدليل + التوظيف'],
                            ['perm' => 'manage.tasks',    'icon' => 'fa-tasks',         'color' => 'sky',     'sections' => 'مهامي + إجازاتي'],
                            ['perm' => 'view.statistics', 'icon' => 'fa-chart-bar',     'color' => 'purple',  'sections' => 'تقاريري + لوحة الإشراف'],
                            ['perm' => 'view.calendar',   'icon' => 'fa-calendar-alt',  'color' => 'orange',  'sections' => 'التقويم'],
                            ['perm' => 'manage.courses',  'icon' => 'fa-graduation-cap','color' => 'teal',    'sections' => 'تصفح الكورسات'],
                        ];
                        $colorMap = [
                            'emerald' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'amber'   => 'bg-amber-100 text-amber-800 border-amber-200',
                            'rose'    => 'bg-rose-100 text-rose-800 border-rose-200',
                            'sky'     => 'bg-sky-100 text-sky-800 border-sky-200',
                            'purple'  => 'bg-purple-100 text-purple-800 border-purple-200',
                            'orange'  => 'bg-orange-100 text-orange-800 border-orange-200',
                            'teal'    => 'bg-teal-100 text-teal-800 border-teal-200',
                        ];
                        @endphp
                        @foreach($sidebarMap as $item)
                        <div class="flex items-start gap-2 p-2 rounded-lg border {{ $colorMap[$item['color']] }}">
                            <i class="fas {{ $item['icon'] }} mt-0.5 flex-shrink-0"></i>
                            <div>
                                <code class="font-mono font-bold text-xs block">{{ $item['perm'] }}</code>
                                <span class="text-xs opacity-80">{{ $item['sections'] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-blue-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        العناصر: لوحة التحكم، الملف الشخصي، الإشعارات، الإعدادات — تظهر دائماً لكل موظف بغض النظر عن الصلاحيات.
                    </p>
                </div>

                <!-- الصلاحيات -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        الصلاحيات
                    </label>
                    <div class="border border-gray-300 rounded-lg p-4 max-h-96 overflow-y-auto">
                        @if($permissions->count() > 0)
                            @foreach($permissions as $group => $groupPermissions)
                                <div class="mb-6 last:mb-0">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-3 pb-2 border-b border-gray-200">
                                        {{ $group ?? 'عام' }}
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($groupPermissions as $permission)
                                            <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                       {{ in_array($permission->id, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}
                                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                <div class="mr-3">
                                                    <span class="text-sm font-medium text-gray-900">{{ $permission->display_name }}</span>
                                                    @if($permission->description)
                                                        <p class="text-xs text-gray-500">{{ $permission->description }}</p>
                                                    @endif
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">
                                لا توجد صلاحيات متاحة. <a href="{{ route('admin.permissions.create') }}" class="text-blue-600 hover:underline">أضف صلاحيات جديدة</a>
                            </p>
                        @endif
                    </div>
                    @error('permissions.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- أزرار الإجراءات -->
                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.roles.index') }}" 
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        إلغاء
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>
                        حفظ التغييرات
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
