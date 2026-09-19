@extends('layouts.admin')

@section('title', 'تسجيل طالب جديد')
@section('header', 'تسجيل طالب جديد')

@section('content')
<div class="space-y-6">
    <!-- معلومات التسجيل -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">تسجيل طالب في كورس</h3>
                <a href="{{ route('admin.enrollments.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-arrow-right mr-2"></i>
                    العودة للقائمة
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.enrollments.store') }}" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- الطالب -->
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                        اختيار الطالب <span class="text-red-500">*</span>
                    </label>
                    <select name="user_id" id="user_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">اختر الطالب</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" 
                                    {{ (old('user_id', request('student_id')) == $student->id) ? 'selected' : '' }}
                                    data-phone="{{ $student->phone }}"
                                    data-parent-phone="{{ $student->parent_phone }}">
                                {{ $student->name }} - {{ $student->phone }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- الكورس -->
                <div>
                    <label for="advanced_course_id" class="block text-sm font-medium text-gray-700 mb-2">
                        اختيار الكورس <span class="text-red-500">*</span>
                    </label>
                    <select name="advanced_course_id" id="advanced_course_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">اختر الكورس</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('advanced_course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->title }} - {{ $course->academicYear->name ?? 'غير محدد' }}
                            </option>
                        @endforeach
                    </select>
                    @error('advanced_course_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- حالة التسجيل -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        حالة التسجيل <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">اختر حالة التسجيل</option>
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>نشط</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">
                        "نشط" يعني أن الطالب يمكنه الوصول للكورس فوراً
                    </p>
                </div>
            </div>

            <!-- الملاحظات -->
            <div class="mt-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                    ملاحظات إدارية
                </label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="أي ملاحظات خاصة بهذا التسجيل (اختياري)">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- معلومات الطالب المختار -->
            <div id="studentInfo" class="mt-6 hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 mb-2">معلومات الطالب المختار:</h4>
                    <div id="studentDetails" class="text-sm text-blue-800">
                        <!-- ستتم إضافة معلومات الطالب هنا بواسطة JavaScript -->
                    </div>
                </div>
            </div>

            <!-- البحث السريع بالهاتف -->
            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-3">البحث السريع بالهاتف</h4>
                <div class="flex gap-3">
                    <input type="text" id="quickPhoneSearch" placeholder="أدخل رقم هاتف الطالب أو ولي الأمر..."
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" onclick="searchByPhone()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
                        <i class="fas fa-search mr-2"></i>
                        بحث
                    </button>
                </div>
                <div id="phoneSearchResult" class="mt-3 hidden"></div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('admin.enrollments.index') }}" 
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                        إلغاء
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>
                        تسجيل الطالب
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// عرض معلومات الطالب عند الاختيار
document.getElementById('user_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const studentInfo = document.getElementById('studentInfo');
    const studentDetails = document.getElementById('studentDetails');
    
    if (this.value) {
        const phone = selectedOption.getAttribute('data-phone');
        const parentPhone = selectedOption.getAttribute('data-parent-phone');
        
        let details = `
            <p><strong>الاسم:</strong> ${selectedOption.text.split(' - ')[0]}</p>
            <p><strong>هاتف الطالب:</strong> ${phone}</p>
        `;
        
        if (parentPhone) {
            details += `<p><strong>هاتف ولي الأمر:</strong> ${parentPhone}</p>`;
        }
        
        studentDetails.innerHTML = details;
        studentInfo.classList.remove('hidden');
    } else {
        studentInfo.classList.add('hidden');
    }
});

// البحث بالهاتف
function searchByPhone() {
    const phone = document.getElementById('quickPhoneSearch').value.trim();
    const resultDiv = document.getElementById('phoneSearchResult');
    
    if (!phone) {
        alert('يرجى إدخال رقم الهاتف');
        return;
    }
    
    // إظهار loader
    resultDiv.innerHTML = '<div class="text-center py-2"><i class="fas fa-spinner fa-spin text-blue-600"></i> جاري البحث...</div>';
    resultDiv.classList.remove('hidden');
    
    fetch(`{{ route('admin.enrollments.search-by-phone') }}?phone=${encodeURIComponent(phone)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const student = data.student;
                
                // اختيار الطالب في القائمة
                const userSelect = document.getElementById('user_id');
                userSelect.value = student.id;
                userSelect.dispatchEvent(new Event('change'));
                
                resultDiv.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded p-3">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            <span class="text-green-800">تم العثور على الطالب واختياره تلقائياً</span>
                        </div>
                    </div>
                `;
                
                // إخفاء النتيجة بعد 3 ثوان
                setTimeout(() => {
                    resultDiv.classList.add('hidden');
                }, 3000);
            } else {
                resultDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded p-3">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                            <span class="text-red-800">${data.error}</span>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded p-3">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-red-800">حدث خطأ في البحث</span>
                    </div>
                </div>
            `;
        });
}

// البحث عند الضغط على Enter
document.getElementById('quickPhoneSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchByPhone();
    }
});

// إذا كان هناك student_id في الـ URL، إظهار معلومات الطالب
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById('user_id');
    if (userSelect.value) {
        userSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
