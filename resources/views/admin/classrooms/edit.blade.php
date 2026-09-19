@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- الهيدر -->
        <div class="bg-white shadow-lg rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">تعديل الفصل: {{ $classroom->name }}</h1>
                        <p class="text-sm text-gray-500 mt-1">تحديث بيانات الفصل الدراسي</p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.classrooms.show', $classroom) }}" 
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-eye mr-2"></i>
                            عرض الفصل
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

        <!-- نموذج التعديل -->
        <div class="bg-white shadow-lg rounded-lg">
            <form method="POST" action="{{ route('admin.classrooms.update', $classroom) }}">
                @csrf
                @method('PUT')
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- اسم الفصل -->
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                اسم الفصل <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $classroom->name) }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="أدخل اسم الفصل">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- المدرسة -->
                        <div>
                            <label for="school_id" class="block text-sm font-medium text-gray-700 mb-2">
                                المدرسة <span class="text-red-500">*</span>
                            </label>
                            <select name="school_id" id="school_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- اختر المدرسة --</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}" {{ old('school_id', $classroom->school_id) == $school->id ? 'selected' : '' }}>
                                        {{ $school->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('school_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- المعلم -->
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-2">
                                المعلم المسؤول
                            </label>
                            <select name="teacher_id" id="teacher_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- اختر المعلم (اختياري) --</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id', $classroom->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('teacher_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- الوصف -->
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                الوصف
                            </label>
                            <textarea name="description" id="description" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="أدخل وصف الفصل (اختياري)">{{ old('description', $classroom->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- حالة الفصل -->
                        <div class="md:col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" 
                                       {{ old('is_active', $classroom->is_active) ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="is_active" class="mr-2 block text-sm text-gray-700">
                                    الفصل نشط
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                يمكن للطلاب التسجيل في الفصول النشطة فقط
                            </p>
                        </div>
                    </div>
                </div>

                <!-- أزرار الإجراءات -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            آخر تحديث: {{ $classroom->updated_at->format('Y-m-d H:i') }}
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('admin.classrooms.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                إلغاء
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                حفظ التغييرات
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- معلومات إضافية -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- إحصائيات الفصل -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">عدد الطلاب</dt>
                                <dd class="text-lg font-bold text-gray-900">{{ $classroom->students()->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- تاريخ الإنشاء -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-calendar text-green-600"></i>
                            </div>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">تاريخ الإنشاء</dt>
                                <dd class="text-lg font-bold text-gray-900">{{ $classroom->created_at->format('Y-m-d') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الحالة -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-{{ $classroom->is_active ? 'green' : 'red' }}-100 $classroom->is_active ? 'green' : 'red' }}-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-{{ $classroom->is_active ? 'check' : 'times' }} text-{{ $classroom->is_active ? 'green' : 'red' }}-600 $classroom->is_active ? 'green' : 'red' }}-400"></i>
                            </div>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">الحالة</dt>
                                <dd class="text-lg font-bold text-{{ $classroom->is_active ? 'green' : 'red' }}-600 $classroom->is_active ? 'green' : 'red' }}-400">
                                    {{ $classroom->is_active ? 'نشط' : 'معطل' }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection