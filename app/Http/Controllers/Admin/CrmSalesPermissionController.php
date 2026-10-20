<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeJob;
use App\Models\User;
use App\Support\EmployeePermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmSalesPermissionController extends Controller
{
    private function gate(): void
    {
        abort_unless(
            auth()->user()->role === 'super_admin' || auth()->user()->hasPermission('manage.leads'),
            403
        );
    }

    public function index(Request $request): View
    {
        $this->gate();

        $jobCodes = EmployeePermissionCatalog::salesDepartmentJobCodes();
        $jobs = EmployeeJob::query()->whereIn('code', $jobCodes)->orderBy('name')->get();

        $query = User::employees()
            ->with('employeeJob:id,name,code')
            ->whereHas('employeeJob', fn ($q) => $q->whereIn('code', $jobCodes));

        if ($request->filled('job_code')) {
            $query->whereHas('employeeJob', fn ($q) => $q->where('code', $request->string('job_code')));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('custom')) {
            $query->where('employee_permissions_custom', $request->string('custom') === '1');
        }

        $employees = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.crm.sales-permissions.index', compact('employees', 'jobs', 'jobCodes'));
    }

    public function edit(User $employee): View
    {
        $this->gate();
        abort_unless($employee->is_employee, 404);
        abort_unless($employee->isSalesDepartmentEmployee(), 404);

        $employee->load('employeeJob', 'roles');
        $groups = EmployeePermissionCatalog::salesPermissionGroups();
        $selected = old('employee_permissions', $employee->usesCustomEmployeePermissions()
            ? $employee->effectiveEmployeePermissions()
            : $employee->effectiveEmployeePermissions());

        return view('admin.crm.sales-permissions.edit', compact('employee', 'groups', 'selected'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->gate();
        abort_unless($employee->is_employee, 404);
        abort_unless($employee->isSalesDepartmentEmployee(), 404);

        $data = $request->validate([
            'employee_permissions_custom' => ['nullable', 'boolean'],
            'employee_permissions' => ['nullable', 'array'],
            'employee_permissions.*' => ['string', 'max:64'],
        ]);

        $useCustom = $request->boolean('employee_permissions_custom');
        $rawPermissions = EmployeePermissionCatalog::sanitize($data['employee_permissions'] ?? []);

        // أي علامة صح تفعّل التخصيص تلقائياً — حتى لا تُحفظ خانات وهمية والمفتاح مطفأ
        if ($rawPermissions !== []) {
            $useCustom = true;
        }

        $permissions = $useCustom ? $rawPermissions : null;

        $employee->update([
            'employee_permissions_custom' => $useCustom,
            'employee_permissions' => $useCustom ? $permissions : null,
        ]);

        $scope = $useCustom
            ? 'صلاحيات مخصصة (لوحة الموظف فقط)'
            : 'صلاحيات الوظيفة العامة';

        return redirect()
            ->route('admin.crm.sales-permissions.index')
            ->with('success', 'تم تحديث صلاحيات '.$employee->name.' — '.$scope.'.');
    }
}
