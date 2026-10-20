<?php

namespace Tests\Feature;

use App\Models\EmployeeJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\EmployeePermissionCatalog;
use App\Support\EmployeeSidebar;
use App\Support\RbacAdminRouteAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * مصفوفة حالات الصلاحيات: سيلز مخصص، وظيفة فقط، محاسب، HR، سوبر أدمن، RBAC غير مبيعات.
 */
class EmployeePermissionMatrixTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
    }

    private function buildSchema(): void
    {
        foreach (['role_permissions', 'user_roles', 'user_permissions', 'permissions', 'roles', 'users', 'employee_jobs'] as $t) {
            Schema::dropIfExists($t);
        }

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
            $table->string('display_name')->nullable();
            $table->string('group')->nullable();
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

    private function makeUser(array $attrs): User
    {
        return User::query()->create(array_merge([
            'password' => Hash::make('password'),
            'role' => 'student',
            'is_active' => true,
            'is_employee' => true,
            'employee_permissions_custom' => false,
            'employee_permissions' => null,
        ], $attrs));
    }

    private function attachRole(User $user, array $permissionNames): Role
    {
        $role = Role::query()->create([
            'name' => 'role_'.$user->id,
            'display_name' => 'Role '.$user->id,
        ]);
        foreach ($permissionNames as $name) {
            $perm = Permission::query()->firstOrCreate(
                ['name' => $name],
                ['display_name' => $name, 'group' => 'test']
            );
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }
        $user->roles()->attach($role->id);

        return $role;
    }

    private function sidebarKeys(User $user): array
    {
        $keys = [];
        foreach (EmployeeSidebar::sectionsFor($user) as $section) {
            foreach ($section['links'] as $link) {
                $keys[] = $link['key'];
            }
        }

        return $keys;
    }

    public function test_sales_custom_sees_only_granted_and_never_admin_finance(): void
    {
        $job = EmployeeJob::query()->create([
            'name' => 'سيلز',
            'code' => 'sales',
            'permissions' => ['sales_desk', 'crm_desk', 'tasks', 'leaves', 'calendar', 'crm_transition_leads', 'crm_view_messages'],
            'is_active' => true,
        ]);

        $user = $this->makeUser([
            'name' => 'Sales Custom',
            'email' => 'sales-custom@hesetak.test',
            'employee_job_id' => $job->id,
            'employee_permissions_custom' => true,
            'employee_permissions' => ['sales_desk', 'crm_desk', 'crm_messages', 'calendar', 'leaves', 'tasks'],
        ]);
        $this->attachRole($user, ['admin.access', 'manage.invoices', 'manage.users', 'manage.orders', 'manage.leads']);

        $this->assertFalse($user->userHasAdminAccessCapability());
        $this->assertFalse($user->hasPermission('manage.invoices'));
        $this->assertFalse($user->hasPermission('manage.users'));
        $this->assertTrue($user->employeeCan('sales_desk'));
        $this->assertTrue($user->employeeCan('crm_desk'));
        $this->assertTrue($user->employeeCan('crm_messages'));
        $this->assertTrue($user->employeeCan('crm_leads')); // via crm_desk umbrella
        $this->assertFalse($user->employeeCan('crm_sales_financial'));
        $this->assertFalse($user->employeeCan('crm_leads_create'));
        $this->assertFalse($user->employeeCan('desk_accountant'));

        $keys = $this->sidebarKeys($user);
        $this->assertContains('sales_desk', $keys);
        $this->assertContains('crm_messages', $keys);
        $this->assertNotContains('admin_panel', $keys);
        $this->assertNotContains('desk_accountant', $keys);
    }

    public function test_sales_job_only_sidebar_and_crm_inbox(): void
    {
        $job = EmployeeJob::query()->create([
            'name' => 'سيلز',
            'code' => 'sales',
            'permissions' => [
                'dashboard', 'tasks', 'leaves', 'reports', 'calendar',
                'profile', 'notifications', 'settings',
                'sales_desk', 'public_catalog',
                'crm_desk', 'crm_edit_leads', 'crm_transition_leads', 'crm_add_notes',
                'crm_submit_reports', 'crm_view_messages', 'crm_send_messages',
            ],
            'is_active' => true,
        ]);

        $user = $this->makeUser([
            'name' => 'Sales Job',
            'email' => 'sales-job@hesetak.test',
            'employee_job_id' => $job->id,
        ]);

        $this->assertFalse($user->userHasAdminAccessCapability());
        $this->assertTrue($user->employeeCan('sales_desk'));
        $this->assertTrue($user->employeeCan('sales_orders')); // alias
        $this->assertTrue($user->employeeCan('crm_leads'));
        $this->assertTrue($user->employeeCan('crm_marketing_inbox')); // transition_leads
        $this->assertTrue($user->employeeCan('crm_messages'));
        $this->assertFalse($user->employeeCan('crm_sales_financial'));
        $this->assertFalse($user->employeeCan('crm_leads_create'));
        $this->assertFalse($user->hasPermission('manage.invoices'));

        $keys = $this->sidebarKeys($user);
        $this->assertContains('sales_desk', $keys);
        $this->assertContains('crm_desk', $keys);
        $this->assertContains('crm_marketing_inbox', $keys);
        $this->assertContains('calendar', $keys);
        $this->assertNotContains('admin_panel', $keys);
    }

    public function test_crm_finance_sees_financial_not_marketing_create(): void
    {
        $job = EmployeeJob::query()->create([
            'name' => 'مالية CRM',
            'code' => 'crm_finance',
            'permissions' => [
                'dashboard', 'tasks', 'leaves', 'calendar', 'profile', 'notifications', 'settings',
                'crm_desk', 'crm_view_all_leads', 'crm_view_all_orders', 'crm_approve_payments',
                'crm_approve_commissions', 'crm_view_sales_financial_reports',
                'crm_view_team_performance', 'crm_view_messages', 'crm_send_messages',
            ],
            'is_active' => true,
        ]);

        $user = $this->makeUser([
            'name' => 'CRM Finance',
            'email' => 'crm-fin@hesetak.test',
            'employee_job_id' => $job->id,
        ]);

        $this->assertTrue($user->employeeCan('crm_sales_financial'));
        $this->assertTrue($user->employeeCan('crm_orders'));
        $this->assertTrue($user->employeeCan('crm_team'));
        $this->assertFalse($user->employeeCan('crm_leads_create'));
        $this->assertFalse($user->employeeCan('crm_marketing_desk'));
        $this->assertFalse($user->hasPermission('manage.invoices'));
    }

    public function test_accountant_job_only_shows_finance_desk(): void
    {
        $job = EmployeeJob::query()->create([
            'name' => 'محاسب',
            'code' => 'accountant',
            'permissions' => [
                'dashboard', 'tasks', 'leaves', 'accounting', 'agreements', 'reports', 'calendar',
                'profile', 'notifications', 'settings', 'desk_accountant',
            ],
            'is_active' => true,
        ]);

        $user = $this->makeUser([
            'name' => 'Accountant',
            'email' => 'acc@hesetak.test',
            'employee_job_id' => $job->id,
        ]);

        $this->assertTrue($user->employeeCan('desk_accountant'));
        $this->assertTrue($user->employeeCan('accounting'));
        $this->assertTrue($user->employeeCan('agreements'));
        $this->assertFalse($user->employeeCan('sales_desk'));
        $this->assertFalse($user->employeeCan('crm_desk'));

        $keys = $this->sidebarKeys($user);
        $this->assertContains('desk_accountant', $keys);
        $this->assertContains('accounting', $keys);
        $this->assertNotContains('sales_desk', $keys);
    }

    public function test_hr_job_shows_hr_children_via_desk(): void
    {
        $job = EmployeeJob::query()->create([
            'name' => 'HR',
            'code' => 'hr',
            'permissions' => [
                'dashboard', 'tasks', 'leaves', 'reports', 'calendar',
                'profile', 'notifications', 'settings', 'hr_desk',
            ],
            'is_active' => true,
        ]);

        $user = $this->makeUser([
            'name' => 'HR User',
            'email' => 'hr@hesetak.test',
            'employee_job_id' => $job->id,
        ]);

        $this->assertTrue($user->employeeCan('hr_desk'));
        $this->assertTrue($user->employeeCan('hr_leave_requests'));
        $this->assertTrue($user->employeeCan('hr_directory'));
        $this->assertTrue($user->employeeCan('hr_recruitment'));

        $keys = $this->sidebarKeys($user);
        $this->assertContains('hr_desk', $keys);
        $this->assertContains('hr_directory', $keys);
    }

    public function test_rbac_non_sales_accountant_enters_admin_with_invoices_only(): void
    {
        $job = EmployeeJob::query()->create([
            'name' => 'مخصص',
            'code' => 'custom',
            'permissions' => [],
            'is_active' => true,
        ]);

        $user = $this->makeUser([
            'name' => 'Rbac Acc',
            'email' => 'rbac-acc@hesetak.test',
            'employee_job_id' => $job->id,
        ]);
        $this->attachRole($user, ['admin.access', 'view.dashboard', 'manage.invoices']);

        $this->assertTrue($user->userHasAdminAccessCapability());
        $this->assertTrue($user->hasPermission('manage.invoices'));
        $this->assertFalse($user->hasPermission('manage.users'));
        $this->assertTrue($user->employeeCan('desk_accountant'));
        $this->assertTrue($user->employeeCan('admin_panel'));
    }

    public function test_super_admin_without_roles_is_unrestricted(): void
    {
        $user = $this->makeUser([
            'name' => 'Super',
            'email' => 'super@hesetak.test',
            'role' => 'super_admin',
            'is_employee' => false,
        ]);

        $this->assertTrue($user->userHasAdminAccessCapability());
        $this->assertTrue($user->hasPermission('manage.invoices'));
        $this->assertTrue($user->hasPermission('manage.users'));
        $this->assertTrue($user->hasPermission('manage.withdrawals'));
    }

    public function test_missing_admin_resources_are_mapped(): void
    {
        foreach ([
            'admin.curriculum-library.index',
            'admin.n8n.index',
            'admin.service-session-rates.index',
            'admin.hiring.settings.edit',
            'admin.tutor-interview-slots.index',
        ] as $route) {
            $this->assertNotNull(
                RbacAdminRouteAccess::permissionsForRoute($route),
                "missing map for {$route}"
            );
        }
    }

    public function test_catalog_never_implies_platform_finance_from_sales_keys(): void
    {
        $implied = EmployeePermissionCatalog::rbacPermissionsImpliedBy([
            'sales_desk', 'crm_desk', 'crm_view_sales_financial_reports', 'crm_approve_payments',
        ]);
        foreach (EmployeePermissionCatalog::financialPermissionNames() as $fin) {
            $this->assertNotContains($fin, $implied, $fin);
        }
    }
}
