@extends('layouts.admin')

@section('title', 'تفاصيل الموظف')
@section('header', 'تفاصيل الموظف')

@section('content')
<div class="space-y-6">
    <!-- الهيدر -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $employee->name }}</h1>
                <p class="text-gray-600 mt-1">عرض تفاصيل الموظف</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.employees.index') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-arrow-right mr-2"></i>العودة
                </a>
                <a href="{{ route('admin.employees.edit', $employee) }}" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-edit mr-2"></i>تعديل
                </a>
                @if($employee->isSalesDepartmentEmployee())
                <a href="{{ route('admin.crm.sales-permissions.edit', $employee) }}" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-user-shield mr-2"></i>صلاحيات CRM
                </a>
                @endif
                <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="inline"
                      onsubmit="return confirm('هل أنت متأكد من حذف الموظف {{ $employee->name }}؟ لا يمكن التراجع عن هذا الإجراء.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-trash-alt mr-2"></i>حذف
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- الإحصائيات -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="dashboard-card rounded-2xl p-5 sm:p-6 card-hover-effect relative overflow-hidden group border-2 border-blue-200/50 hover:border-blue-300/70 shadow-xl hover:shadow-2xl transition-all duration-300" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(240, 249, 255, 0.95) 50%, rgba(224, 242, 254, 0.9) 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">إجمالي المهام</p>
                        <p class="text-3xl font-black text-gray-900">{{ $stats['total_tasks'] }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-tasks text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card rounded-2xl p-5 sm:p-6 card-hover-effect relative overflow-hidden group border-2 border-yellow-200/50 hover:border-yellow-300/70 shadow-xl hover:shadow-2xl transition-all duration-300" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 251, 235, 0.95) 50%, rgba(254, 243, 199, 0.9) 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">معلقة</p>
                        <p class="text-3xl font-black text-yellow-700">{{ $stats['pending_tasks'] }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card rounded-2xl p-5 sm:p-6 card-hover-effect relative overflow-hidden group border-2 border-blue-200/50 hover:border-blue-300/70 shadow-xl hover:shadow-2xl transition-all duration-300" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(240, 249, 255, 0.95) 50%, rgba(224, 242, 254, 0.9) 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">قيد التنفيذ</p>
                        <p class="text-3xl font-black text-blue-700">{{ $stats['in_progress_tasks'] }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-spinner text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card rounded-2xl p-5 sm:p-6 card-hover-effect relative overflow-hidden group border-2 border-green-200/50 hover:border-green-300/70 shadow-xl hover:shadow-2xl transition-all duration-300" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(240, 253, 250, 0.95) 50%, rgba(209, 250, 229, 0.9) 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">مكتملة</p>
                        <p class="text-3xl font-black text-green-700">{{ $stats['completed_tasks'] }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card rounded-2xl p-5 sm:p-6 card-hover-effect relative overflow-hidden group border-2 border-red-200/50 hover:border-red-300/70 shadow-xl hover:shadow-2xl transition-all duration-300" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(254, 242, 242, 0.95) 50%, rgba(254, 226, 226, 0.9) 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">متأخرة</p>
                        <p class="text-3xl font-black text-red-700">{{ $stats['overdue_tasks'] }}</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات الموظف -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h2 class="text-xl font-bold text-gray-900 mb-4">معلومات الموظف</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">الاسم</p>
                <p class="font-semibold text-gray-900 text-lg">{{ $employee->name }}</p>
            </div>
            @if($employee->employee_code)
            <div>
                <p class="text-sm text-gray-600 mb-1">رمز الموظف</p>
                <p class="font-semibold text-gray-900 text-lg">{{ $employee->employee_code }}</p>
            </div>
            @endif
            @if($employee->employeeJob)
            <div>
                <p class="text-sm text-gray-600 mb-1">الوظيفة</p>
                <p class="font-semibold text-gray-900 text-lg">{{ $employee->employeeJob->name }}</p>
            </div>
            @endif
            @if($employee->email)
            <div>
                <p class="text-sm text-gray-600 mb-1">البريد الإلكتروني</p>
                <p class="font-semibold text-gray-900 text-lg">{{ $employee->email }}</p>
            </div>
            @endif
            @if($employee->phone)
            <div>
                <p class="text-sm text-gray-600 mb-1">رقم الهاتف</p>
                <p class="font-semibold text-gray-900 text-lg">{{ $employee->phone }}</p>
            </div>
            @endif
            @if($employee->hire_date)
            <div>
                <p class="text-sm text-gray-600 mb-1">تاريخ التوظيف</p>
                <p class="font-semibold text-gray-900 text-lg">{{ $employee->hire_date->format('Y-m-d') }}</p>
            </div>
            @endif
            @if($employee->salary)
            <div>
                <p class="text-sm text-gray-600 mb-1">الراتب</p>
                <p class="font-semibold text-gray-900 text-lg">{{ number_format($employee->salary, 2) }} {{ currency_symbol() }}</p>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-600 mb-1">الحالة</p>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $employee->is_active && !$employee->termination_date ? 'bg-green-100 text-green-800' : ($employee->termination_date ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                    @if($employee->termination_date) منتهي الخدمة
                    @elseif($employee->is_active) نشط
                    @else غير نشط
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- البيانات البنكية لاستلام الراتب -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h2 class="text-xl font-bold text-gray-900 mb-4"><i class="fas fa-university text-indigo-600 mr-2"></i>البيانات البنكية لاستلام الراتب</h2>
        @if($employee->bank_name || $employee->bank_account_number || $employee->bank_account_holder_name)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($employee->bank_name)
                    <div><p class="text-sm text-gray-600 mb-1">البنك</p><p class="font-semibold text-gray-900">{{ $employee->bank_name }}</p></div>
                @endif
                @if($employee->bank_branch)
                    <div><p class="text-sm text-gray-600 mb-1">الفرع</p><p class="font-semibold text-gray-900">{{ $employee->bank_branch }}</p></div>
                @endif
                @if($employee->bank_account_number)
                    <div><p class="text-sm text-gray-600 mb-1">رقم الحساب</p><p class="font-semibold text-gray-900 font-mono">{{ $employee->bank_account_number }}</p></div>
                @endif
                @if($employee->bank_account_holder_name)
                    <div><p class="text-sm text-gray-600 mb-1">اسم صاحب الحساب</p><p class="font-semibold text-gray-900">{{ $employee->bank_account_holder_name }}</p></div>
                @endif
                @if($employee->bank_iban)
                    <div class="md:col-span-2"><p class="text-sm text-gray-600 mb-1">الآيبان</p><p class="font-semibold text-gray-900 font-mono">{{ $employee->bank_iban }}</p></div>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-3">يمكن تعديل البيانات من صفحة تعديل الموظف، أو أن يضيفها الموظف من قسم المحاسبة.</p>
        @else
            <p class="text-gray-600">لم يتم إضافة بيانات بنكية بعد. يمكن للموظف إضافتها من <strong>قسم المحاسبة والراتب</strong> أو يمكنك إضافتها من <a href="{{ route('admin.employees.edit', $employee) }}" class="text-blue-600 hover:underline">تعديل الموظف</a>.</p>
        @endif
    </div>

    <!-- المهام الأخيرة -->
    @if($employee->employeeTasks->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900">المهام</h2>
            <a href="{{ route('admin.employee-tasks.index', ['employee_id' => $employee->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                عرض الكل <i class="fas fa-arrow-left mr-1"></i>
            </a>
        </div>
        <div class="space-y-3">
            @foreach($employee->employeeTasks->take(5) as $task)
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 mb-1">{{ $task->title }}</h3>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span><i class="fas fa-user mr-1"></i>{{ $task->assigner->name }}</span>
                                @if($task->deadline)
                                    <span><i class="fas fa-calendar mr-1"></i>{{ $task->deadline->format('Y-m-d') }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($task->status === 'completed') bg-green-100 text-green-800
                            @elseif($task->status === 'in_progress') bg-blue-100 text-blue-800
                            @elseif($task->status === 'pending') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            @if($task->status === 'completed') مكتملة
                            @elseif($task->status === 'in_progress') قيد التنفيذ
                            @elseif($task->status === 'pending') معلقة
                            @else {{ $task->status }}
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
