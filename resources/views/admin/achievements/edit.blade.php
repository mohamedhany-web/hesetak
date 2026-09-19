@extends('layouts.admin')

@section('title', 'تعديل الإنجاز')
@section('header', 'تعديل الإنجاز')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">تعديل الإنجاز</h1>
        
        <form action="{{ route('admin.achievements.update', $achievement) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">الاسم *</label>
                    <input type="text" name="name" required value="{{ old('name', $achievement->name) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">الفئة *</label>
                    <select name="category" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="course_completion" {{ ($achievement->category ?? $achievement->type) == 'course_completion' ? 'selected' : '' }}>إكمال كورس</option>
                        <option value="exam_excellence" {{ ($achievement->category ?? $achievement->type) == 'exam_excellence' ? 'selected' : '' }}>تفوق في الامتحان</option>
                        <option value="attendance" {{ ($achievement->category ?? $achievement->type) == 'attendance' ? 'selected' : '' }}>حضور</option>
                        <option value="assignment" {{ ($achievement->category ?? $achievement->type) == 'assignment' ? 'selected' : '' }}>واجب</option>
                        <option value="other" {{ ($achievement->category ?? $achievement->type) == 'other' || ($achievement->category ?? $achievement->type) == 'custom' ? 'selected' : '' }}>أخرى</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">الأيقونة</label>
                    <input type="text" name="icon" value="{{ old('icon', $achievement->icon ?? 'fas fa-medal') }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                           placeholder="fas fa-medal">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">النقاط</label>
                    <input type="number" name="points" min="0" value="{{ old('points', $achievement->points_reward ?? $achievement->points ?? 0) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">الوصف</label>
                <textarea name="description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('description', $achievement->description) }}</textarea>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $achievement->is_active ?? true) ? 'checked' : '' }} 
                       class="w-4 h-4 text-sky-600 border-gray-300 rounded focus:ring-sky-500">
                <label class="mr-2 text-sm font-medium text-gray-700">نشط</label>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-gradient-to-r from-sky-600 to-sky-700 hover:from-sky-700 hover:to-sky-800 text-white px-6 py-3 rounded-lg font-medium transition-colors shadow-lg shadow-sky-500/30">
                    تحديث الإنجاز
                </button>
                <a href="{{ route('admin.achievements.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-medium transition-colors">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

