@extends('layouts.admin')

@section('title', 'رقابة الطالب')
@section('page_title', 'رقابة الطالب')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">رقابة الطالب</h1>
        <p class="text-sm text-gray-600 mb-4">متابعة نشاط الطلاب مرتّبة حسب آخر ظهور على المنصة.</p>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="البحث..." class="px-4 py-2 border border-gray-300 rounded-lg">
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">جميع الحالات</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غير نشط</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">بحث</button>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الطالب</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">التسجيلات</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الكورسات المكتملة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">آخر نشاط</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($students as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $student->name }}</td>
                        <td class="px-6 py-4">{{ $student->enrollments_count }}</td>
                        <td class="px-6 py-4">{{ $student->completed_courses }}</td>
                        <td class="px-6 py-4">{{ $student->last_activity ? $student->last_activity->diffForHumans() : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $students->links() }}</div>
    </div>
</div>
@endsection
