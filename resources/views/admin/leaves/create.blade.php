@extends('layouts.admin')

@section('title', 'طلب إجازة جديد')
@section('header', 'طلب إجازة جديد')

@section('content')
<div class="max-w-3xl mx-auto">
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <form action="{{ route('admin.leaves.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <div>
                    <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">
                        الموظف <span class="text-red-500">*</span>
                    </label>
                    <select name="employee_id" id="employee_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('employee_id') border-red-500 @enderror">
                        <option value="">اختر الموظف</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                    @error('employee_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">نوع الإجازة <span class="text-red-500">*</span></label>
                    <select name="type" id="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('type') border-red-500 @enderror">
                        <option value="">اختر نوع الإجازة</option>
                        @foreach(['annual' => 'سنوية', 'sick' => 'مرضية', 'emergency' => 'طارئة', 'unpaid' => 'بدون راتب', 'other' => 'أخرى'] as $val => $label)
                            <option value="{{ $val }}" {{ old('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">من تاريخ <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" id="start_date" required value="{{ old('start_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('start_date') border-red-500 @enderror">
                    @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">إلى تاريخ <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" id="end_date" required value="{{ old('end_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('end_date') border-red-500 @enderror">
                    @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">سبب الإجازة <span class="text-red-500">*</span></label>
                    <textarea name="reason" id="reason" rows="5" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('reason') border-red-500 @enderror"
                              placeholder="اكتب سبب طلب الإجازة...">{{ old('reason') }}</textarea>
                    @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.leaves.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">إلغاء</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                        <i class="fas fa-paper-plane mr-2"></i>
                        تقديم الطلب
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
