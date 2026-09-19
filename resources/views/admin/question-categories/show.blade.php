@extends('layouts.admin')

@section('title', 'تفاصيل التصنيف')
@section('header', 'تفاصيل التصنيف: ' . $questionCategory->name)

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
                <span>{{ $questionCategory->name }}</span>
            </nav>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.question-bank.create', ['category_id' => $questionCategory->id]) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-plus ml-2"></i>
                إضافة سؤال
            </a>
            <a href="{{ route('admin.question-categories.edit', $questionCategory) }}" 
               class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-edit ml-2"></i>
                تعديل
            </a>
            <a href="{{ route('admin.question-categories.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-arrow-right ml-2"></i>
                العودة
            </a>
        </div>
    </div>

    <!-- معلومات التصنيف والإحصائيات -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- معلومات التصنيف -->
        <div class="xl:col-span-3">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">معلومات التصنيف</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ $questionCategory->is_active ? 'bg-green-100 text-green-800 ': ''bg-red-100 text-red-800 }}">']
                        {{ $questionCategory->is_active ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-500 mb-1">اسم التصنيف</label>
                                <div class="font-semibold text-gray-900">{{ $questionCategory->name }}</div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-500 mb-1">السنة الدراسية</label>
                                <div class="text-gray-900">{{ $questionCategory->academicYear->name ?? 'غير محدد' }}</div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-500 mb-1">المادة الدراسية</label>
                                <div class="text-gray-900">{{ $questionCategory->academicSubject->name ?? 'غير محدد' }}</div>
                            </div>
                        </div>
                        <div>
                            @if($questionCategory->parent)
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-500 mb-1">التصنيف الأب</label>
                                    <div class="text-gray-900">{{ $questionCategory->parent->name }}</div>
                                </div>
                            @endif
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-500 mb-1">المسار الكامل</label>
                                <div class="text-gray-900">{{ $questionCategory->full_path }}</div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-500 mb-1">الترتيب</label>
                                <div class="text-gray-900">{{ $questionCategory->order }}</div>
                            </div>
                        </div>
                    </div>

                    @if($questionCategory->description)
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-500 mb-2">الوصف</label>
                            <div class="text-gray-900 bg-gray-50 p-3 rounded-lg">
                                {{ $questionCategory->description }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- إحصائيات -->
        <div class="space-y-4">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100">
                        <i class="fas fa-question-circle text-blue-600"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_questions'] }}</p>
                        <p class="text-sm text-gray-500">إجمالي الأسئلة</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-green-100">
                        <i class="fas fa-list text-green-600"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['direct_questions'] }}</p>
                        <p class="text-sm text-gray-500">أسئلة مباشرة</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-purple-100">
                        <i class="fas fa-folder text-purple-600"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['subcategories'] }}</p>
                        <p class="text-sm text-gray-500">تصنيفات فرعية</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- التصنيفات الفرعية -->
    @if($questionCategory->children->count() > 0)
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h4 class="text-lg font-semibold text-gray-900">التصنيفات الفرعية</h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($questionCategory->children as $subCategory)
                        <div class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="font-medium text-gray-900">{{ $subCategory->name }}</h5>
                                <span class="text-sm text-gray-500">{{ $subCategory->questions->count() }} سؤال</span>
                            </div>
                            @if($subCategory->description)
                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($subCategory->description, 80) }}</p>
                            @endif
                            <div class="flex space-x-2 space-x-reverse">
                                <a href="{{ route('admin.question-categories.show', $subCategory) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-eye ml-1"></i>عرض
                                </a>
                                <a href="{{ route('admin.question-bank.create', ['category_id' => $subCategory->id]) }}" 
                                   class="text-green-600 hover:text-green-800 text-sm">
                                    <i class="fas fa-plus ml-1"></i>إضافة سؤال
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- الأسئلة المباشرة -->
    @if($questionCategory->questions->count() > 0)
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h4 class="text-lg font-semibold text-gray-900">الأسئلة في هذا التصنيف</h4>
                    <a href="{{ route('admin.question-bank.index', ['category_id' => $questionCategory->id]) }}" 
                       class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                        عرض جميع الأسئلة
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($questionCategory->questions->take(5) as $question)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ Str::limit($question->question, 100) }}
                                </p>
                                <div class="flex items-center gap-4 text-xs text-gray-500 mt-1">
                                    <span>{{ $question->type_text }}</span>
                                    <span>{{ $question->difficulty_text }}</span>
                                    <span>{{ $question->points }} نقطة</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <a href="{{ route('admin.question-bank.show', $question) }}" 
                                   class="text-blue-600 hover:text-blue-800 transition-colors">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.question-bank.edit', $question) }}" 
                                   class="text-indigo-600 hover:text-indigo-800 transition-colors">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if($questionCategory->questions->count() > 5)
                    <div class="mt-4 text-center">
                        <a href="{{ route('admin.question-bank.index', ['category_id' => $questionCategory->id]) }}" 
                           class="text-primary-600 hover:text-primary-700 font-medium">
                            عرض جميع الأسئلة ({{ $questionCategory->questions->count() }})
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- إحصائيات مفصلة -->
    @if($stats['by_type']->count() > 0 || $stats['by_difficulty']->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- توزيع حسب النوع -->
            @if($stats['by_type']->count() > 0)
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900">توزيع الأسئلة حسب النوع</h4>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($stats['by_type'] as $type => $count)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">
                                        {{ \App\Models\Question::getQuestionTypes()[$type] ?? $type }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- توزيع حسب الصعوبة -->
            @if($stats['by_difficulty']->count() > 0)
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900">توزيع الأسئلة حسب الصعوبة</h4>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($stats['by_difficulty'] as $difficulty => $count)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">
                                        {{ \App\Models\Question::getDifficultyLevels()[$difficulty] ?? $difficulty }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
