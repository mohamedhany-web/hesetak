@extends('layouts.admin')

@section('title', 'تعديل الشارة')
@section('header', 'تعديل الشارة')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">تعديل الشارة</h1>
        
        <form action="{{ route('admin.badges.update', $badge) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">الاسم *</label>
                    <input type="text" name="name" required value="{{ old('name', $badge->name) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">الفئة *</label>
                    <select name="category" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="excellence" {{ ($badge->category ?? $badge->type) == 'excellence' ? 'selected' : '' }}>تفوق</option>
                        <option value="dedication" {{ ($badge->category ?? $badge->type) == 'dedication' ? 'selected' : '' }}>تفاني</option>
                        <option value="leadership" {{ ($badge->category ?? $badge->type) == 'leadership' ? 'selected' : '' }}>قيادة</option>
                        <option value="community" {{ ($badge->category ?? $badge->type) == 'community' ? 'selected' : '' }}>مجتمع</option>
                        <option value="skill" {{ ($badge->category ?? $badge->type) == 'skill' ? 'selected' : '' }}>مهارة</option>
                        <option value="other" {{ ($badge->category ?? $badge->type) == 'other' || ($badge->category ?? $badge->type) == 'special' ? 'selected' : '' }}>أخرى</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">الأيقونة</label>
                    <input type="text" name="icon" value="{{ old('icon', $badge->icon ?? 'fas fa-award') }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                           placeholder="fas fa-award">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">اللون</label>
                    <input type="text" name="color" value="{{ old('color', $badge->color ?? 'sky') }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                           placeholder="sky, blue, green, etc.">
                    <p class="mt-1 text-xs text-gray-500">أو hex code مثل #3B82F6</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">الوصف</label>
                <textarea name="description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('description', $badge->description) }}</textarea>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $badge->is_active ?? true) ? 'checked' : '' }} 
                       class="w-4 h-4 text-sky-600 border-gray-300 rounded focus:ring-sky-500">
                <label class="mr-2 text-sm font-medium text-gray-700">نشط</label>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-gradient-to-r from-sky-600 to-sky-700 hover:from-sky-700 hover:to-sky-800 text-white px-6 py-3 rounded-lg font-medium transition-colors shadow-lg shadow-sky-500/30">
                    تحديث الشارة
                </button>
                <a href="{{ route('admin.badges.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-medium transition-colors">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

