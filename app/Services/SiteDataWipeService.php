<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionsAndRolesSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * مسح كل بيانات الموقع مع الإبقاء على حسابات أدمن محددة، ثم إعادة بذر الصلاحيات.
 */
final class SiteDataWipeService
{
    /** جداول لا تُمسح (هيكل النظام فقط). */
    private const PRESERVE_TABLES = [
        'migrations',
    ];

    /**
     * @param  list<int>  $keepUserIds
     * @return array{tables_cleared: int, users_kept: int, users_deleted: int, permissions_seeded: bool}
     */
    public function wipeExceptAdmins(array $keepUserIds, bool $reseedPermissions = true): array
    {
        $keepUserIds = array_values(array_unique(array_filter(array_map('intval', $keepUserIds))));
        if ($keepUserIds === []) {
            throw new \InvalidArgumentException('يجب الإبقاء على حساب أدمن واحد على الأقل.');
        }

        $kept = User::query()->whereIn('id', $keepUserIds)->get();
        if ($kept->isEmpty()) {
            throw new \RuntimeException('حسابات الأدمن المطلوب الإبقاء عليها غير موجودة.');
        }

        $driver = Schema::getConnection()->getDriverName();
        $tables = $this->listTables($driver);
        $usersDeleted = 0;
        $tablesCleared = 0;

        DB::connection()->unsetEventDispatcher();

        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF');
            }

            foreach ($tables as $table) {
                if (in_array($table, self::PRESERVE_TABLES, true)) {
                    continue;
                }

                if ($table === 'users') {
                    $usersDeleted = (int) User::query()->whereNotIn('id', $keepUserIds)->delete();
                    $tablesCleared++;
                    continue;
                }

                if (! Schema::hasTable($table)) {
                    continue;
                }

                try {
                    DB::table($table)->delete();
                    $tablesCleared++;
                } catch (Throwable $e) {
                    // جدول بدون حذف مباشر — جرّب truncate
                    try {
                        Schema::disableForeignKeyConstraints();
                        DB::table($table)->truncate();
                        Schema::enableForeignKeyConstraints();
                        $tablesCleared++;
                    } catch (Throwable) {
                        // تجاهل جداول النظام النادرة
                    }
                }
            }
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }

        Cache::flush();

        $permissionsSeeded = false;
        if ($reseedPermissions) {
            $this->reseedPermissionsAndAttachToAdmins($keepUserIds);
            $permissionsSeeded = true;
        }

        return [
            'tables_cleared' => $tablesCleared,
            'users_kept' => $kept->count(),
            'users_deleted' => $usersDeleted,
            'permissions_seeded' => $permissionsSeeded,
        ];
    }

    /**
     * @param  list<int>  $adminIds
     */
    public function reseedPermissionsAndAttachToAdmins(array $adminIds): void
    {
        Artisan::call('db:seed', [
            '--class' => PermissionsAndRolesSeeder::class,
            '--force' => true,
        ]);
        Artisan::call('db:seed', [
            '--class' => PermissionsSeeder::class,
            '--force' => true,
        ]);

        $permissionIds = Permission::query()->pluck('id')->all();
        $superRole = Role::query()->where('name', 'super_admin')->first()
            ?? Role::query()->where('name', 'admin')->first();

        foreach ($adminIds as $adminId) {
            $user = User::query()->find($adminId);
            if (! $user) {
                continue;
            }

            if (! in_array((string) $user->role, ['super_admin', 'admin'], true)) {
                $user->role = 'super_admin';
            }
            $user->is_active = true;
            $user->is_employee = true;
            $user->save();

            if ($superRole) {
                $user->roles()->syncWithoutDetaching([$superRole->id]);
                if ($permissionIds !== []) {
                    $superRole->permissions()->syncWithoutDetaching($permissionIds);
                }
            }

            if ($permissionIds !== [] && method_exists($user, 'directPermissions')) {
                $user->directPermissions()->syncWithoutDetaching($permissionIds);
            }

            // سوبر أدمن ليس موظفاً مقيداً بـ RBAC map
            if (in_array((string) $user->role, ['super_admin', 'admin'], true)) {
                $user->is_employee = false;
                $user->save();
            }
        }
    }

    /**
     * @return list<string>
     */
    private function listTables(string $driver): array
    {
        if ($driver === 'mysql') {
            $db = Schema::getConnection()->getDatabaseName();
            $rows = DB::select('SHOW TABLES');
            $key = 'Tables_in_'.$db;

            return collect($rows)->map(fn ($r) => (string) ($r->$key ?? array_values((array) $r)[0]))->filter()->values()->all();
        }

        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            return collect($rows)->pluck('name')->map(fn ($n) => (string) $n)->values()->all();
        }

        return Schema::getTableListing();
    }
}
