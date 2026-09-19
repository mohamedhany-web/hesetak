@extends('layouts.admin')

@section('title', 'تعديل التصنيف')
@section('header', 'تعديل التصنيف: ' . $questionCategory->name)

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
                <a href="{{ route('admin.question-categories.show', $questionCategory) }}" class="hover:text-primary-600">{{ $questionCategory->name }}</a>
                <span class="mx-2">/</span>
                <span>تعديل</span>
            </nav>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.question-categories.show', $questionCategory) }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-eye ml-2"></i>
                عرض التصنيف
            </a>
            <a href="{{ route('admin.question-categories.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-arrow-right ml-2"></i>
                العودة
            </a>
        </div>
    </div>

    <!-- نموذج تعديل التصنيف -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- المحتوى الرئيسي -->
        <div class="xl:col-span-2">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">تعديل بيانات التصنيف</h3>
                </div>

                <form action="{{ route('admin.question-categories.update', $questionCategory) }}" method="POST" class="p-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-6">
                        <!-- اسم التصنيف -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                اسم التصنيف <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $questionCategory->name) }}" required
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
                                      placeholder="وصف مختصر عن نوع الأسئلة في هذا التصنيف...">{{ old('description', $questionCategory->description) }}</textarea>
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
                                        <option value="{{ $year->id }}" {{ (old('academic_year_id', $questionCategory->academic_year_id) == $year->id) ? 'selected' : '' }}>
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
                                        <option value="{{ $subject->id }}" {{ (old('academic_subject_id', $questionCategory->academic_subject_id) == $subject->id) ? 'selected' : '' }}>
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
                                        <option value="{{ $parent->id }}" {{ (old('parent_id', $questionCategory->parent_id) == $parent->id) ? 'selected' : '' }}>
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500">اختر تصنيف أب لجعله تصنيف فرعي</p>
                                @error('parent_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                                    ترتيب التصنيف
                                </label>
                                <input type="number" name="order" id="order" min="0" 
                                       value="{{ old('order', $questionCategory->order) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
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
                                       {{ old('is_active', $questionCategory->is_active) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500">
                                <span class="mr-2 text-sm font-medium text-gray-700">تصنيف نشط</span>
                            </label>
                            <p class="mt-1 text-sm text-gray-500">التصنيفات غير النشطة لن تظهر عند إنشاء الأسئلة</p>
                        </div>

                        <!-- أزرار الحفظ -->
                        <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t border-gray-200">
                            <a href="{{ route('admin.question-categories.show', $questionCategory) }}" 
                               class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium rounded-lg transition-colors">
                                إلغاء
                            </a>
                            <button type="submit" 
                                    class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-save ml-2"></i>
                                حفظ التعديلات
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- الشريط الجانبي -->
        <div class="space-y-6">
            <!-- معلومات التصنيف -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">معلومات التصنيف</h3>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500">إجمالي الأسئلة</span>
                        <span class="text-sm font-bold text-gray-900">{{ $questionCategory->total_questions_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500">أسئلة مباشرة</span>
                        <span class="text-sm font-bold text-gray-900">{{ $questionCategory->questions->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500">تصنيفات فرعية</span>
                        <span class="text-sm font-bold text-gray-900">{{ $questionCategory->children->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500">تاريخ الإنشاء</span>
                        <span class="text-sm text-gray-900">{{ $questionCategory->created_at->format('Y-m-d') }}</span>
                    </div>
                </div>
            </div>

            <!-- تحذيرات -->
            @if($questionCategory->questions->count() > 0 || $questionCategory->children->count() > 0)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-exclamation-triangle text-yellow-600 ml-2"></i>
                        <span class="text-sm font-medium text-yellow-800">تنبيه</span>
                    </div>
                    <div class="text-sm text-yellow-700">
                        @if($questionCategory->questions->count() > 0)
                            <p>• هذا التصنيف يحتوي على {{ $questionCategory->questions->count() }} سؤال</p>
                        @endif
                        @if($questionCategory->children->count() > 0)
                            <p>• هذا التصنيف يحتوي على {{ $questionCategory->children->count() }} تصنيف فرعي</p>
                        @endif
                        <p class="mt-2">تغيير السنة أو المادة قد يؤثر على تنظيم الأسئلة</p>
                    </div>
                </div>
            @endif

            <!-- إجراءات سريعة -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">إجراءات سريعة</h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('admin.question-bank.create', ['category_id' => $questionCategory->id]) }}" 
                       class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors block text-center">
                        <i class="fas fa-plus ml-2"></i>
                        إضافة سؤال لهذا التصنيف
                    </a>
                    
                    <a href="{{ route('admin.question-bank.index', ['category_id' => $questionCategory->id]) }}" 
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors block text-center">
                        <i class="fas fa-list ml-2"></i>
                        عرض أسئلة التصنيف
                    </a>
                    
                    @if($questionCategory->questions->count() == 0 && $questionCategory->children->count() == 0)
                        <form action="{{ route('admin.question-categories.destroy', $questionCategory) }}" method="POST"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا التصنيف؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-trash ml-2"></i>
                                حذف التصنيف
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function loadSubjects() {
    const yearId = document.getElementById('academic_year_id').value;
    const subjectSelect = document.getElementById('academic_subject_id');
    const currentSubjectId = '{{ old("academic_subject_id", $questionCategory->academic_subject_id) }}';
    
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
                    if (currentSubjectId == subject.id) {
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

// تحميل المواد عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    const yearId = document.getElementById('academic_year_id').value;
    if (yearId) {
        loadSubjects();
    }
});
</script>
@endpush
@endsection
