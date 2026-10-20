<?php

namespace Tests\Feature;

use App\Models\EmployeeJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\EmployeePermissionCatalog;
use App\Support\RbacAdminRouteAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeePermissionBoundaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildMinimalEmployeeSchema();
    }

    private function buildMinimalEmployeeSchema(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('employee_jobs');

        Schema::create('employee_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('progress_share_token', 64)->nullable()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('student');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_employee')->default(false);
            $table->foreignId('employee_job_id')->nullable();
            $table->boolean('employee_permissions_custom')->default(false);
            $table->json('employee_permissions')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
            $table->primary(['user_id', 'permission_id']);
        });
    }

    private function makeFatAdminRole(): Role
    {
        $role = Role::query()->create(['name' => 'ops_admin', 'display_name' => 'Ops']);
        foreach ([
            'admin.access', 'view.dashboard', 'manage.orders', 'manage.leads',
            'manage.invoices', 'manage.payments', 'manage.users', 'manage.withdrawals',
            'view.statistics', 'view.financial-reports', 'manage.system-settings',
        ] as $name) {
            $perm = Permission::query()->firstOrCreate(['name' => $name]);
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }

        return $role;
    }

    public function test_custom_sales_permissions_deny_finance_and_admin_shell(): void
    {
        $job = EmployeeJob::query()->create([
            'name' => 'سيلز',
            'code' => 'sales',
            'permissions' => ['sales_desk', 'crm_desk', 'tasks'],
            'is_active' => true,
        ]);

        $employee = User::query()->create([
            'name' => 'TEST',
            'email' => 'test-sales@hesetak.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'is_active' => true,
            'is_employee' => true,
            'employee_job_id' => $job->id,
            'employee_permissions_custom' => true,
            'employee_permissions' => ['sales_desk', 'crm_desk', 'crm_messages', 'calendar', 'leaves'],
        ]);
        $employee->roles()->attach($this->makeFatAdminRole()->id);

        $this->assertFalse($employee->userHasAdminAccessCapability());
        $this->assertTrue($employee->employeeCan('sales_desk'));
        $this->assertTrue($employee->employeeCan('crm_messages'));
        $this->assertTrue($employee->employeeCan('calendar'));
        $this->assertTrue($employee->hasPermission('manage.orders'));
        $this->assertTrue($employee->hasPermission('manage.leads'));
        $this->assertFalse($employee->hasPermission('manage.invoices'));
        $this->assertFalse($employee->hasPermission('manage.payments'));
        $this->assertFalse($employee->hasPermission('manage.withdrawals'));
        $this->assertFalse($employee->hasPermission('manage.users'));
        $this->assertFalse($employee->hasPermission('view.financial-reports'));
        $this->assertFalse($employee->hasPermission('admin.access'));
    }

    public function test_sales_job_intersects_fat_rbac_without_custom_toggle(): void
    {
        $job = EmployeeJob::query()->create([
            'name' => 'سيلز',
            'code' => 'sales',
            'permissions' => ['sales_desk', 'crm_desk', 'leaves', 'calendar'],
            'is_active' => true,
        ]);

        $employee = User::query()->create([
            'name' => 'TEST2',
            'email' => 'test2-sales@hesetak.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'is_active' => true,
            'is_employee' => true,
            'employee_job_id' => $job->id,
            'employee_permissions_custom' => false,
            'employee_permissions' => null,
        ]);
        $employee->roles()->attach($this->makeFatAdminRole()->id);

        $this->assertFalse($employee->userHasAdminAccessCapability());
        $this->assertTrue($employee->hasPermission('manage.orders'));
        $this->assertTrue($employee->hasPermission('manage.leads'));
        $this->assertFalse($employee->hasPermission('manage.invoices'));
        $this->assertFalse($employee->hasPermission('manage.users'));
        $this->assertFalse($employee->hasPermission('manage.withdrawals'));
        $this->assertFalse($employee->userHasAdminAccessCapability());
    }

    public function test_hiring_settings_route_is_mapped_for_rbac_employees(): void
    {
        $required = RbacAdminRouteAccess::permissionsForRoute('admin.hiring.settings.edit');
        $this->assertNotNull($required);
        $this->assertContains('manage.users', $required);

        $slots = RbacAdminRouteAccess::permissionsForRoute('admin.tutor-interview-slots.index');
        $this->assertNotNull($slots);
    }

    public function test_catalog_maps_sales_keys_without_finance(): void
    {
        $implied = EmployeePermissionCatalog::rbacPermissionsImpliedBy([
            'sales_desk', 'crm_messages', 'calendar', 'leaves',
        ]);

        $this->assertContains('manage.orders', $implied);
        $this->assertContains('manage.leads', $implied);
        $this->assertContains('view.calendar', $implied);
        foreach (EmployeePermissionCatalog::financialPermissionNames() as $fin) {
            $this->assertNotContains($fin, $implied);
        }
    }
}
