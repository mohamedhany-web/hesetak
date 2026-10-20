@extends('layouts.admin')

@section('title', 'صلاحيات '.$employee->name)
@section('header', 'صلاحيات موظف المبيعات')

@section('content')
@php
  $customOn = (bool) old('employee_permissions_custom', $employee->usesCustomEmployeePermissions());
  $effective = $employee->effectiveEmployeePermissions();
  $implied = \App\Support\EmployeePermissionCatalog::rbacPermissionsImpliedBy($effective);
@endphp
<div class="space-y-4 max-w-4xl" x-data="{ custom: {{ $customOn ? 'true' : 'false' }} }">

  <div class="rounded-xl border bg-white p-4 text-sm">
    <p><strong>{{ $employee->name }}</strong> — {{ $employee->employeeJob?->name }}</p>
    <p class="text-gray-500">{{ $employee->email }}</p>
    @if($employee->roles->isNotEmpty())
      <p class="mt-2 text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs">
        لهذا الموظف دور تحكم (RBAC):
        <strong>{{ $employee->roles->pluck('display_name')->join('، ') }}</strong>.
        عند تفعيل الصلاحيات المخصصة يُوجَّه إلى <strong>لوحة الموظف</strong> فقط ولا يرى المحاسبة أو أرقام الإيرادات في لوحة الإدارة.
      </p>
    @endif
  </div>

  <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700 space-y-1">
    <p class="font-bold text-slate-900">الصلاحيات الفعلية الآن</p>
    <p>المصدر: {{ $employee->usesCustomEmployeePermissions() ? 'مخصصة لهذا الموظف' : 'صلاحيات الوظيفة العامة' }}</p>
    <p dir="ltr" class="font-mono break-all">{{ $effective !== [] ? implode(', ', $effective) : '—' }}</p>
    @if($implied !== [])
      <p class="text-slate-500">ما يقابلها في الأدمن (إن وُجد دخول): <span dir="ltr" class="font-mono">{{ implode(', ', $implied) }}</span></p>
    @endif
  </div>

  <form method="POST" action="{{ route('admin.crm.sales-permissions.update', $employee) }}" class="rounded-2xl border bg-white p-6 space-y-6">
    @csrf
    @method('PUT')

    <label class="flex items-start gap-3 p-4 rounded-xl border-2 border-violet-200 bg-violet-50 cursor-pointer">
      <input type="checkbox" name="employee_permissions_custom" value="1"
             x-model="custom"
             @checked($customOn)
             class="mt-1 w-4 h-4 text-violet-600 rounded">
      <span>
        <span class="font-bold text-violet-900 block">صلاحيات مخصصة لهذا الموظف</span>
        <span class="text-sm text-violet-800">عند التفعيل تُستخدم القائمة أدناه فقط (وليس صلاحيات الوظيفة العامة ولا دور RBAC الواسع). اختيار أي خانة يفعّل هذا المفتاح تلقائياً عند الحفظ.</span>
      </span>
    </label>

    <p x-show="!custom" x-cloak class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
      القائمة معطّلة لأن المفتاح غير مفعّل — تُطبَّق صلاحيات الوظيفة فقط. فعّل المفتاح أو علّم خانة ليُحفظ التخصيص.
    </p>

    <fieldset :disabled="!custom" :class="!custom ? 'opacity-50 pointer-events-none' : ''">
      @foreach($groups as $groupTitle => $items)
        <div class="mb-6">
          <h3 class="font-bold text-gray-900 mb-2">{{ $groupTitle }}</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2 border rounded-xl p-4">
            @foreach($items as $key => $label)
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="employee_permissions[]" value="{{ $key }}"
                       @checked(in_array($key, $selected, true))
                       @change="if ($event.target.checked) custom = true"
                       class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                <span>{{ $label }}</span>
              </label>
            @endforeach
          </div>
        </div>
      @endforeach
    </fieldset>

    <div class="flex gap-3 pt-4 border-t">
      <a href="{{ route('admin.crm.sales-permissions.index') }}" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white font-bold text-sm">إلغاء</a>
      <button class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-bold text-sm">حفظ الصلاحيات</button>
    </div>
  </form>
</div>
@endsection
