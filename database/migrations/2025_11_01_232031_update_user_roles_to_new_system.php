<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: إعادة إنشاء الجدول بدون CHECK constraint
            DB::statement("PRAGMA foreign_keys=OFF");

            if (Schema::hasTable('users_backup')) {
                DB::statement('DROP TABLE users_backup');
            }

            // إنشاء جدول جديد بدون CHECK constraint
            DB::statement("CREATE TABLE users_backup AS SELECT * FROM users");
            
            // الحصول على قائمة الأعمدة الموجودة فعلياً
            $columns = DB::select("PRAGMA table_info(users_backup)");
            $columnNames = array_map(fn($col) => $col->name, $columns);
            
            // حذف الجدول القديم
            DB::statement("DROP TABLE users");
            
            // إنشاء الجدول الجديد بدون CHECK constraint
            $createTableSQL = "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT,
                phone TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'student' NOT NULL,
                avatar TEXT,
                is_active INTEGER DEFAULT 1,
                bio TEXT";
            
            // إضافة الأعمدة الاختيارية فقط إذا كانت موجودة في الجدول الأصلي
            $optionalColumns = ['parent_id', 'profile_image', 'birth_date', 'address', 'academic_year_id', 'last_login_at'];
            foreach ($optionalColumns as $col) {
                if (in_array($col, $columnNames)) {
                    $createTableSQL .= ",\n                {$col} " . ($col === 'last_login_at' ? 'TEXT' : ($col === 'academic_year_id' || $col === 'parent_id' ? 'INTEGER' : 'TEXT'));
                }
            }
            
            $createTableSQL .= ",
                remember_token TEXT,
                created_at TEXT,
                updated_at TEXT
            )";
            
            DB::statement($createTableSQL);

            // SELECT expressions must match CREATE column order; missing source cols → NULL
            $expr = static function (string $column, ?string $asSql = null) use ($columnNames): string {
                if ($asSql !== null) {
                    return $asSql;
                }

                return in_array($column, $columnNames, true)
                    ? $column
                    : "NULL AS {$column}";
            };
            
            // بناء استعلام INSERT بناءً على الأعمدة الموجودة
            $selectColumns = [
                $expr('id'),
                $expr('name'),
                $expr('email'),
                $expr('phone'),
                $expr('password'),
                "CASE 
                WHEN role = 'admin' THEN 'super_admin'
                WHEN role = 'teacher' THEN 'instructor'
                WHEN role = 'parent' THEN 'student'
                ELSE COALESCE(role, 'student')
            END as role",
                $expr('avatar'),
                $expr('is_active'),
                $expr('bio'),
            ];
            
            // إضافة الأعمدة الاختيارية إذا كانت موجودة
            foreach ($optionalColumns as $col) {
                if (in_array($col, $columnNames, true)) {
                    $selectColumns[] = $col;
                }
            }
            
            $selectColumns[] = $expr('remember_token');
            $selectColumns[] = $expr('created_at');
            $selectColumns[] = $expr('updated_at');
            
            $insertSQL = "INSERT INTO users SELECT " . implode(', ', $selectColumns) . " FROM users_backup";
            DB::statement($insertSQL);
            
            // حذف النسخة الاحتياطية
            DB::statement("DROP TABLE users_backup");
            
            DB::statement("PRAGMA foreign_keys=ON");
        } else {
            // MySQL/MariaDB: استخدام ALTER TABLE
            // أولاً: تعديل enum ليشمل القيم الجديدة
            try {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student', 'parent', 'super_admin', 'instructor') DEFAULT 'student'");
            } catch (\Exception $e) {
                // Enum قد يكون محدثاً بالفعل
            }
            
            // ثانياً: تحديث البيانات
            try {
                DB::statement("UPDATE users SET role = 'super_admin' WHERE role = 'admin'");
            } catch (\Exception $e) {
                // قد لا يوجد مستخدمون بدور admin
            }
            try {
                DB::statement("UPDATE users SET role = 'instructor' WHERE role = 'teacher'");
            } catch (\Exception $e) {
                // قد لا يوجد مستخدمون بدور teacher
            }
            try {
                DB::statement("UPDATE users SET role = 'student' WHERE role = 'parent'");
            } catch (\Exception $e) {
                // قد لا يوجد مستخدمون بدور parent
            }
            
            // ثالثاً: تعديل enum للقيم النهائية فقط
            try {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'instructor', 'student') DEFAULT 'student'");
            } catch (\Exception $e) {
                // Enum قد يكون محدثاً بالفعل
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: إعادة الأدوار القديمة
            DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin'");
            DB::statement("UPDATE users SET role = 'teacher' WHERE role = 'instructor'");
        } else {
            // MySQL/MariaDB
            DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin'");
            DB::statement("UPDATE users SET role = 'teacher' WHERE role = 'instructor'");
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student', 'parent') DEFAULT 'student'");
        }
    }
};
