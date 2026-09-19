@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- الهيدر -->
        <div class="bg-white shadow-lg rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $classroom->name }}</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            المدرسة: {{ $classroom->school->name ?? 'غير محدد' }}
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.classrooms.edit', $classroom) }}" 
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            تعديل
                        </a>
                        <a href="{{ route('admin.classrooms.index') }}" 
                           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-arrow-right mr-2"></i>
                            العودة للقائمة
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات الفصل -->
        <div class="bg-white shadow-lg rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">معلومات الفصل</h2>
            </div>
            <div class="px-6 py-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">اسم الفصل</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $classroom->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">المدرسة</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $classroom->school->name ?? 'غير محدد' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">المعلم المسؤول</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($classroom->teacher)
                                {{ $classroom->teacher->name }}
                            @else
                                <span class="text-gray-500">غير محدد</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">الحالة</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $classroom->is_active ? 'bg-green-100 text-green-800 ': ''bg-red-100 text-red-800 }}">']
                                {{ $classroom->is_active ? 'نشط' : 'معطل' }}
                            </span>
                        </dd>
                    </div>

                    @if($classroom->description)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">الوصف</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $classroom->description }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection