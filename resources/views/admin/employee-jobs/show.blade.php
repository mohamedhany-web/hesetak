@extends('layouts.admin')

@section('title', 'تفاصيل الوظيفة')
@section('header', 'تفاصيل الوظيفة')

@section('content')
<div class="space-y-6">
    <!-- الهيدر -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $employeeJob->name }}</h1>
                <p class="text-gray-600 mt-1">عرض تفاصيل الوظيفة</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.employee-jobs.index') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-arrow-right mr-2"></i>العودة
                </a>
                <a href="{{ route('admin.employee-jobs.edit', $employeeJob) }}" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-edit mr-2"></i>تعديل
                </a>
            </div>
        </div>
    </div>

    <!-- الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="dashboard-card rounded-2xl p-5 sm:p-6 card-hover-effect relative overflow-hidden group border-2 border-blue-200/50 hover:border-blue-300/70 shadow-xl hover:shadow-2xl transition-all duration-300" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(240, 249, 255, 0.95) 50%, rgba(224, 242, 254, 0.9) 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">إجمالي الموظفين</p>
                        <p class="text-3xl font-black text-gray-900">{{ $employeeJob->employees_count ?? 0 }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card rounded-2xl p-5 sm:p-6 card-hover-effect relative overflow-hidden group border-2 border-green-200/50 hover:border-green-300/70 shadow-xl hover:shadow-2xl transition-all duration-300" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(240, 253, 250, 0.95) 50%, rgba(209, 250, 229, 0.9) 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">الموظفين النشطين</p>
                        <p class="text-3xl font-black text-green-700">{{ $employeeJob->employees()->where('is_active', true)->whereNull('termination_date')->count() }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-user-check text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات الوظيفة -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h2 class="text-xl font-bold text-gray-900 mb-4">معلومات الوظيفة</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">اسم الوظيفة</p>
                <p class="font-semibold text-gray-900 text-lg">{{ $employeeJob->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">رمز الوظيفة</p>
                <p class="font-semibold text-gray-900 text-lg">{{ $employeeJob->code }}</p>
            </div>
            @if($employeeJob->description)
            <div class="md:col-span-2">
                <p class="text-sm text-gray-600 mb-1">الوصف</p>
                <p class="text-gray-900 leading-relaxed">{{ $employeeJob->description }}</p>
            </div>
            @endif
            @if($employeeJob->responsibilities)
            <div class="md:col-span-2">
                <p class="text-sm text-gray-600 mb-1">المسؤوليات</p>
                <p class="text-gray-900 leading-relaxed whitespace-pre-line">{{ $employeeJob->responsibilities }}</p>
            </div>
            @endif
            @if($employeeJob->min_salary || $employeeJob->max_salary)
            <div>
                <p class="text-sm text-gray-600 mb-1">نطاق الراتب</p>
                <p class="font-semibold text-gray-900 text-lg">
                    @if($employeeJob->min_salary && $employeeJob->max_salary)
                        {{ number_format($employeeJob->min_salary) }} - {{ number_format($employeeJob->max_salary) }} {{ currency_symbol() }}
                    @elseif($employeeJob->min_salary)
                        من {{ number_format($employeeJob->min_salary) }} {{ currency_symbol() }}
                    @elseif($employeeJob->max_salary)
                        حتى {{ number_format($employeeJob->max_salary) }} {{ currency_symbol() }}
                    @endif
                </p>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-600 mb-1">الحالة</p>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $employeeJob->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $employeeJob->is_active ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>
    </div>

    <!-- الموظفين -->
    @if($employees->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h2 class="text-xl font-bold text-gray-900 mb-4">الموظفين في هذه الوظيفة ({{ $employees->total() }})</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($employees as $employee)
                <a href="{{ route('admin.employees.show', $employee) }}" class="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $employee->name }}</h3>
                            @if($employee->employee_code)
                                <p class="text-sm text-gray-600">{{ $employee->employee_code }}</p>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $employee->is_active && !$employee->termination_date ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $employee->is_active && !$employee->termination_date ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
        
        <!-- Pagination -->
        @if($employees->hasPages())
        <div class="mt-6 pt-6 border-t border-gray-200">
            {{ $employees->links() }}
        </div>
        @endif
    </div>
    @else
    <div class="bg-white rounded-xl shadow-lg p-12 text-center border border-gray-200">
        <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-users text-3xl text-gray-400"></i>
        </div>
        <p class="text-lg font-semibold text-gray-700 mb-2">لا يوجد موظفين في هذه الوظيفة</p>
        <p class="text-sm text-gray-600">يمكنك تعيين موظفين لهذه الوظيفة من صفحة إدارة الموظفين</p>
    </div>
    @endif
</div>
@endsection
