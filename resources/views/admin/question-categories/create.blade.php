@extends('layouts.admin')

@section('title', 'إنشاء تصنيف جديد')
@section('header', 'إنشاء تصنيف جديد للأسئلة')

@section('content')
<div class="space-y-6">
    <!-- الهيدر -->
    <div class="flex items-center justify-between">
        <div>
            <nav class="text-sm text-gray-500 mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-primary-600">لوحة التحكم</a>
                <span class="mx-2">/</span>
                <a href="{{ route('admin.question-bank.index') }}" class="hover:text-primary-600">بنك الأسئلة</a>
                <span class="mx-2">/</span>
                <a href="{{ route('admin.question-categories.index') }}" class="hover:text-primary-600">التصنيفات</a>
                <span class="mx-2">/</span>
                <span>إنشاء تصنيف جديد</span>
            </nav>
        </div>
        <a href="{{ route('admin.question-categories.index') }}" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
            <i class="fas fa-arrow-right ml-2"></i>
            العودة
        </a>
    </div>

    <!-- نموذج إنشاء التصنيف -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- المحتوى الرئيسي -->
        <div class="xl:col-span-2">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">بيانات التصنيف</h3>
                </div>

                <form action="{{ route('admin.question-categories.store') }}" method="POST" class="p-6">
                    @csrf
                    
                    <div class="space-y-6">
                        <!-- اسم التصنيف -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                اسم التصنيف <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="مثال: الجبر، الهندسة، القواعد النحوية">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- الوصف -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                وصف التصنيف
                            </label>
                            <textarea name="description" id="description" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                      placeholder="وصف مختصر عن نوع الأسئلة في هذا التصنيف...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- السنة الدراسية والمادة -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    السنة الدراسية <span class="text-red-500">*</span>
                                </label>
                                <select name="academic_year_id" id="academic_year_id" required onchange="loadSubjects()"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">اختر السنة الدراسية</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="academic_subject_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    المادة الدراسية <span class="text-red-500">*</span>
                                </label>
                                <select name="academic_subject_id" id="academic_subject_id" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">اختر المادة الدراسية</option>
                                    @foreach($academicSubjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('academic_subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_subject_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- التصنيف الأب والترتيب -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    التصنيف الأب (اختياري)
                                </label>
                                <select name="parent_id" id="parent_id"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">تصنيف رئيسي</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500">اختر تصنيف أب لإنشاء تصنيف فرعي</p>
                                @error('parent_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                                    ترتيب التصنيف
                                </label>
                                <input type="number" name="order" id="order" min="0" 
                                       value="{{ old('order') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="سيتم تحديده تلقائياً إذا تُرك فارغاً">
                                <p class="mt-1 text-sm text-gray-500">ترتيب ظهور التصنيف في القائمة</p>
                                @error('order')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- حالة التصنيف -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" 
                                       {{ old('is_active', true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500">
                                <span class="mr-2 text-sm font-medium text-gray-700">تصنيف نشط</span>
                            </label>
                            <p class="mt-1 text-sm text-gray-500">التصنيفات غير النشطة لن تظهر عند إنشاء الأسئلة</p>
                        </div>

                        <!-- أزرار الحفظ -->
                        <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                            <a href="{{ route('admin.question-categories.index') }}" 
                               class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium rounded-lg transition-colors">
                                إلغاء
                            </a>
                            <button type="submit" 
                                    class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-save ml-2"></i>
                                حفظ التصنيف
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- الشريط الجانبي -->
        <div class="space-y-6">
            <!-- معلومات سريعة -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">معلومات التصنيفات</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-info-circle text-blue-600 ml-2"></i>
                                <span class="text-sm font-medium text-blue-800">نصائح</span>
                            </div>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• استخدم أسماء واضحة ومحددة</li>
                                <li>• يمكن إنشاء تصنيفات فرعية</li>
                                <li>• التصنيفات تساعد في تنظيم البنك</li>
                                <li>• يمكن إعادة ترتيب التصنيفات لاحقاً</li>
                            </ul>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-lightbulb text-green-600 ml-2"></i>
                                <span class="text-sm font-medium text-green-800">أمثلة</span>
                            </div>
                            <ul class="text-sm text-green-700 space-y-1">
                                <li>• الرياضيات > الجبر > المعادلات</li>
                                <li>• اللغة العربية > النحو > الأفعال</li>
                                <li>• العلوم > الفيزياء > الحركة</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- التصنيفات الموجودة -->
            @if($parentCategories->count() > 0)
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">التصنيفات الموجودة</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($parentCategories as $category)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $category->name }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $category->academicYear->name ?? 'غير محدد' }} - {{ $category->academicSubject->name ?? 'غير محدد' }}
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $category->questions_count ?? 0 }} سؤال
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function loadSubjects() {
    const yearId = document.getElementById('academic_year_id').value;
    const subjectSelect = document.getElementById('academic_subject_id');
    
    // مسح الخيارات الحالية
    subjectSelect.innerHTML = '<option value="">اختر المادة الدراسية</option>';
    
    if (yearId) {
        fetch(`/admin/question-categories/subjects-by-year/${yearId}`)
            .then(response => response.json())
            .then(subjects => {
                subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.id;
                    option.textContent = subject.name;
                    if ('{{ old("academic_subject_id") }}' == subject.id) {
                        option.selected = true;
                    }
                    subjectSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading subjects:', error);
            });
    }
}

// تحميل المواد عند تحميل الصفحة إذا كان هناك سنة محددة
document.addEventListener('DOMContentLoaded', function() {
    const yearId = document.getElementById('academic_year_id').value;
    if (yearId) {
        loadSubjects();
    }
});
</script>
@endpush
@endsection
