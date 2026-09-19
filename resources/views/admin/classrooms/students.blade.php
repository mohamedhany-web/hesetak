@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- الهيدر -->
        <div class="bg-white shadow-lg rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">إدارة طلاب الفصل: {{ $classroom->name }}</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            المدرسة: {{ $classroom->school->name ?? 'غير محدد' }}
                            @if($classroom->teacher)
                                | المعلم: {{ $classroom->teacher->name }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('admin.classrooms.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <i class="fas fa-arrow-right mr-2"></i>
                        العودة للفصول
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- قائمة الطلاب الحاليين -->
            <div class="bg-white shadow-lg rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        الطلاب المسجلين ({{ $classroom->students->count() }})
                    </h2>
                </div>
                <div class="p-6">
                    @if($classroom->students->count() > 0)
                        <div class="space-y-4">
                            @foreach($classroom->students as $student)
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-user-graduate text-blue-600"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">{{ $student->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $student->email }}</p>
                                        @if($student->pivot->enrolled_at)
                                            <p class="text-xs text-gray-400">
                                                مسجل منذ: {{ \Carbon\Carbon::parse($student->pivot->enrolled_at)->format('Y-m-d') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $student->pivot->is_active ? 'bg-green-100 text-green-800 ': ''bg-red-100 text-red-800 }}">']
                                        {{ $student->pivot->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                    <form method="POST" action="{{ route('admin.classrooms.remove-student', [$classroom, $student]) }}" 
                                          class="inline" onsubmit="return confirm('هل أنت متأكد من إزالة هذا الطالب من الفصل؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            <i class="fas fa-trash mr-1"></i>
                                            إزالة
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-500">
                                <i class="fas fa-user-graduate text-4xl mb-4"></i>
                                <p class="text-lg font-medium">لا يوجد طلاب مسجلين</p>
                                <p class="text-sm mt-2">لم يتم تسجيل أي طالب في هذا الفصل بعد</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- إضافة طلاب جدد -->
            <div class="bg-white shadow-lg rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">إضافة طالب جديد</h2>
                </div>
                <div class="p-6">
                    @if($availableStudents->count() > 0)
                        <form method="POST" action="{{ route('admin.classrooms.add-student', $classroom) }}" class="mb-6">
                            @csrf
                            <div class="mb-4">
                                <label for="student_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    اختر طالب
                                </label>
                                <select name="student_id" id="student_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- اختر طالب --</option>
                                    @foreach($availableStudents as $student)
                                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                                    @endforeach
                                </select>
                                @error('student_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" 
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                إضافة الطالب
                            </button>
                        </form>

                        <!-- قائمة الطلاب المتاحين للإضافة -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-4">
                                الطلاب المتاحين ({{ $availableStudents->count() }})
                            </h3>
                            <div class="max-h-64 overflow-y-auto space-y-2">
                                @foreach($availableStudents as $student)
                                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600 text-xs"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $student->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $student->email }}</p>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.classrooms.add-student', $classroom) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            <i class="fas fa-plus mr-1"></i>
                                            إضافة
                                        </button>
                                    </form>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-500">
                                <i class="fas fa-users text-4xl mb-4"></i>
                                <p class="text-lg font-medium">لا يوجد طلاب متاحين</p>
                                <p class="text-sm mt-2">جميع الطلاب مسجلين بالفعل في هذا الفصل أو لا يوجد طلاب في النظام</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                <dt class="text-sm font-medium text-gray-500 truncate">إجمالي الطلاب</dt>
                                <dd class="text-lg font-bold text-gray-900">{{ $classroom->students->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">الطلاب النشطين</dt>
                                <dd class="text-lg font-bold text-gray-900">
                                    {{ $classroom->students->where('pivot.is_active', true)->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-plus text-yellow-600"></i>
                            </div>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">متاح للإضافة</dt>
                                <dd class="text-lg font-bold text-gray-900">{{ $availableStudents->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection









