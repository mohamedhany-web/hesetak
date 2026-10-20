@extends('layouts.admin')

@section('title', 'تعديل موظف')
@section('header', 'تعديل موظف')

@section('content')
<div class="space-y-6">
    <!-- الهيدر -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">تعديل موظف</h1>
                <p class="text-gray-600 mt-1">تحديث معلومات الموظف</p>
                @if($employee->isSalesDepartmentEmployee())
                <a href="{{ route('admin.crm.sales-permissions.edit', $employee) }}" class="inline-flex items-center gap-2 mt-2 text-sm font-bold text-violet-700 hover:text-violet-900">
                    <i class="fas fa-user-shield"></i> صلاحيات المبيعات / CRM
                </a>
                @endif
            </div>
            <a href="{{ route('admin.employees.show', $employee) }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-right mr-2"></i>العودة للتفاصيل
            </a>
        </div>
    </div>

    <form action="{{ route('admin.employees.update', $employee) }}" method="POST" class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- القسم الأساسي -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">المعلومات الأساسية</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الاسم *</label>
                        <input type="text" name="name" value="{{ old('name', $employee->name) }}" required 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني *</label>
                        <input type="email" name="email" value="{{ old('email', $employee->email) }}" required 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">رقم الهاتف *</label>
                        <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" required 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">كلمة المرور (اتركها فارغة للاحتفاظ بالحالية)</label>
                        <input type="password" name="password" 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- القسم الوظيفي -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">المعلومات الوظيفية</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الوظيفة *</label>
                        <select name="employee_job_id" required 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">اختر الوظيفة</option>
                            @foreach($jobs as $job)
                                <option value="{{ $job->id }}" {{ old('employee_job_id', $employee->employee_job_id) == $job->id ? 'selected' : '' }}>{{ $job->name }}</option>
                            @endforeach
                        </select>
                        @error('employee_job_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">رمز الموظف</label>
                        <input type="text" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('employee_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ التوظيف *</label>
                        <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}" required 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('hire_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ إنهاء الخدمة</label>
                        <input type="date" name="termination_date" value="{{ old('termination_date', $employee->termination_date?->format('Y-m-d')) }}" 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('termination_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الراتب</label>
                        <input type="number" name="salary" value="{{ old('salary', $employee->salary) }}" min="0" step="0.01" 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('salary')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            دور مخصص من الأدوار (لوحة التحكم)
                        </label>
                        @php $currentRoleId = old('rbac_role', optional($employee->roles->first())->id); @endphp
                        <select name="rbac_role"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">بدون دور مخصص</option>
                            @foreach(($roles ?? []) as $role)
                                <option value="{{ $role->id }}" {{ (string)$currentRoleId === (string)$role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }} ({{ $role->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('rbac_role')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">
                            الدور المخصص يفتح <strong>لوحة الإدارة</strong> فقط إن كان يملك صلاحية <span dir="ltr">admin.access</span>.
                            موظفو المبيعات بصلاحيات مخصصة يبقون على لوحة الموظف — لا يعتمدوا على دور واسع كـ«صلاحيات كاملة».
                        </p>
                    </div>
                </div>
            </div>

            <!-- البيانات البنكية لاستلام الراتب -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-university text-indigo-600 mr-2"></i>البيانات البنكية لاستلام الراتب</h2>
                <p class="text-sm text-gray-600 mb-4">يمكن للموظف أيضاً إضافة أو تعديل هذه البيانات من قسم المحاسبة في لوحته.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم البنك</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" placeholder="مثال: البنك الأهلي"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('bank_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الفرع</label>
                        <input type="text" name="bank_branch" value="{{ old('bank_branch', $employee->bank_branch) }}" placeholder="مثال: فرع المعادي"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('bank_branch')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">رقم الحساب البنكي</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" placeholder="رقم الحساب"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('bank_account_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">اسم صاحب الحساب</label>
                        <input type="text" name="bank_account_holder_name" value="{{ old('bank_account_holder_name', $employee->bank_account_holder_name) }}" placeholder="الاسم كما في البطاقة"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('bank_account_holder_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">الآيبان (اختياري)</label>
                        <input type="text" name="bank_iban" value="{{ old('bank_iban', $employee->bank_iban) }}" placeholder="EG..."
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        @error('bank_iban')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- القسم الإداري -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">الإعدادات</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ملاحظات الموظف</label>
                        <textarea name="employee_notes" rows="3" 
                                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">{{ old('employee_notes', $employee->employee_notes) }}</textarea>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $employee->is_active) ? 'checked' : '' }} 
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="mr-2 text-sm font-medium text-gray-700">حساب نشط</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-end gap-4">
            <a href="{{ route('admin.employees.show', $employee) }}" class="px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors">
                <i class="fas fa-times mr-2"></i>إلغاء
            </a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <i class="fas fa-save mr-2"></i>حفظ التغييرات
            </button>
        </div>
    </form>
</div>
@endsection
