@extends('layouts.admin')

@section('title', 'تفاصيل الصلاحية')
@section('header', 'تفاصيل الصلاحية')

@section('content')
<div class="space-y-6">
    <!-- معلومات الصلاحية -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $permission->display_name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $permission->name }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.permissions.edit', $permission) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>
                        تعديل
                    </a>
                    <a href="{{ route('admin.permissions.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                        <i class="fas fa-arrow-right mr-2"></i>
                        العودة
                    </a>
                </div>
            </div>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">الاسم</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $permission->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">الاسم المعروض</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $permission->display_name }}</dd>
                </div>
                @if($permission->group)
                <div>
                    <dt class="text-sm font-medium text-gray-500">المجموعة</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $permission->group }}</dd>
                </div>
                @endif
                @if($permission->description)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">الوصف</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $permission->description }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">عدد الأدوار المرتبطة</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $permission->roles->count() }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- الأدوار المرتبطة -->
    @if($permission->roles->count() > 0)
    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">الأدوار المرتبطة بهذه الصلاحية</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($permission->roles as $role)
                    <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="text-sm font-medium text-gray-900 mb-1">
                                    {{ $role->display_name }}
                                </h5>
                                <p class="text-xs text-gray-500 mb-2">
                                    {{ $role->name }}
                                </p>
                                @if($role->is_system)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        دور نظامي
                                    </span>
                                @endif
                            </div>
                            <a href="{{ route('admin.roles.show', $role) }}" 
                               class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-tag text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد أدوار مرتبطة</h3>
            <p class="text-gray-500">هذه الصلاحية غير مرتبطة بأي دور حالياً</p>
        </div>
    </div>
    @endif
</div>
@endsection


