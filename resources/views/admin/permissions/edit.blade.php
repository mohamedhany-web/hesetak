@extends('layouts.admin')

@section('title', 'تعديل الصلاحية')
@section('header', 'تعديل الصلاحية')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">معلومات الصلاحية</h3>
                    <p class="text-sm text-gray-500 mt-1">تعديل معلومات الصلاحية: {{ $permission->display_name }}</p>
                </div>
                <a href="{{ route('admin.permissions.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-arrow-right mr-2"></i>
                    العودة للقائمة
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.permissions.update', $permission) }}">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-6">
                <!-- اسم الصلاحية -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        اسم الصلاحية <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $permission->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="مثال: manage_courses">
                    <p class="mt-1 text-xs text-gray-500">
                        اسم فريد باللغة الإنجليزية (بدون مسافات، استخدم underscore)
                    </p>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- الاسم المعروض -->
                <div>
                    <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">
                        الاسم المعروض <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $permission->display_name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="مثال: إدارة الكورسات">
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
                              placeholder="وصف مختصر للصلاحية (اختياري)">{{ old('description', $permission->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- المجموعة -->
                <div>
                    <label for="group" class="block text-sm font-medium text-gray-700 mb-2">
                        المجموعة
                    </label>
                    <input type="text" name="group" id="group" value="{{ old('group', $permission->group) }}"
                           list="groups"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="مثال: الكورسات، المستخدمين، النظام...">
                    <datalist id="groups">
                        @foreach($groups as $group)
                            <option value="{{ $group }}">
                        @endforeach
                    </datalist>
                    <p class="mt-1 text-xs text-gray-500">
                        المجموعة التي تنتمي إليها هذه الصلاحية (لتنظيم الصلاحيات)
                    </p>
                    @error('group')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- معلومات إضافية -->
                @if($permission->roles->count() > 0)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        هذه الصلاحية مرتبطة بـ <strong>{{ $permission->roles->count() }}</strong> دور. 
                        تعديلها قد يؤثر على صلاحيات المستخدمين.
                    </p>
                </div>
                @endif

                <!-- أزرار الإجراءات -->
                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.permissions.index') }}" 
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
