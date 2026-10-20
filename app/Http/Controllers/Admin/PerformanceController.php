<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class PerformanceController extends Controller
{
    /**
     * عرض صفحة الأداء
     * محمي من: Unauthorized Access
     */
    public function index()
    {
        try {
            $systemInfo = $this->getSystemInfo();
            $performanceInfo = $this->getPerformanceInfo();
            $cacheSizes = $this->getCacheSizes();
            $databaseInfo = $this->safeDatabaseInfo();
            $lastDbOptimize = Cache::get('admin.performance.db_optimize_last');

            return view('admin.performance.index', compact(
                'systemInfo',
                'performanceInfo',
                'cacheSizes',
                'databaseInfo',
                'lastDbOptimize'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading performance page: '.$e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل صفحة الأداء');
        }
    }

    /**
     * مسح جميع أنواع الكاش
     * محمي من: Unauthorized Access, Brute Force
     */
    public function clearCache(Request $request)
    {
        // Rate Limiting
        $key = 'clear_cache_' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية."
            ], 429);
        }
        RateLimiter::hit($key, 60); // دقيقة واحدة

        // Sanitization
        $type = strip_tags(trim($request->input('type', 'all')));
        if (!in_array($type, ['config', 'route', 'view', 'application', 'compiled', 'all'])) {
            $type = 'all';
        }
        $results = [];

        try {
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    $results['config'] = 'تم مسح كاش الإعدادات بنجاح';
                    break;
                    
                case 'route':
                    Artisan::call('route:clear');
                    $results['route'] = 'تم مسح كاش المسارات بنجاح';
                    break;
                    
                case 'view':
                    Artisan::call('view:clear');
                    $results['view'] = 'تم مسح كاش العروض بنجاح';
                    break;
                    
                case 'application':
                    Artisan::call('cache:clear');
                    $results['application'] = 'تم مسح كاش التطبيق بنجاح';
                    break;
                    
                case 'compiled':
                    Artisan::call('clear-compiled');
                    $results['compiled'] = 'تم مسح الملفات المترجمة بنجاح';
                    break;
                    
                case 'all':
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');
                    Artisan::call('cache:clear');
                    Artisan::call('clear-compiled');
                    $results['all'] = 'تم مسح جميع أنواع الكاش بنجاح';
                    break;
            }
            
            return response()->json([
                'success' => true,
                'message' => $results[$type] ?? 'تم المسح بنجاح',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء الكاش لتحسين الأداء
     * محمي من: Unauthorized Access, Brute Force
     */
    public function optimizeCache()
    {
        $key = 'optimize_cache_'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.",
            ], 429);
        }
        RateLimiter::hit($key, 300);

        try {
            $results = [];
            $warnings = [];

            Artisan::call('config:cache');
            $results['config'] = 'تم إنشاء كاش الإعدادات بنجاح';

            try {
                Artisan::call('route:cache');
                $results['route'] = 'تم إنشاء كاش المسارات بنجاح';
            } catch (\Throwable $e) {
                $warnings['route'] = 'تعذّر كاش المسارات: '.$e->getMessage();
                Log::warning('route:cache failed during optimizeCache', ['error' => $e->getMessage()]);
                try {
                    Artisan::call('route:clear');
                } catch (\Throwable $ignored) {
                }
            }

            Artisan::call('view:cache');
            $results['view'] = 'تم إنشاء كاش العروض بنجاح';

            $ok = $warnings === [];

            return response()->json([
                'success' => $ok || $results !== [],
                'message' => $ok
                    ? 'تم تحسين الأداء بنجاح'
                    : 'اكتمل جزئياً — راجع تحذيرات كاش المسارات',
                'results' => $results,
                'warnings' => $warnings,
            ], $ok ? 200 : 207);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * تنظيف الملفات المؤقتة
     * محمي من: Unauthorized Access, Brute Force
     */
    public function clearTempFiles()
    {
        // Rate Limiting
        $key = 'clear_temp_' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية."
            ], 429);
        }
        RateLimiter::hit($key, 300); // 5 دقائق

        try {
            $cleared = 0;
            $paths = [
                storage_path('logs'),
                storage_path('framework/cache/data'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
            ];

            foreach ($paths as $path) {
                if (File::exists($path)) {
                    $files = File::allFiles($path);
                    foreach ($files as $file) {
                        if ($file->getMTime() < (time() - 3600)) { // أقدم من ساعة
                            File::delete($file);
                            $cleared++;
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$cleared} ملف مؤقت بنجاح",
                'cleared' => $cleared
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحسين قاعدة البيانات (OPTIMIZE TABLE) مع تقرير قبل/بعد.
     */
    public function optimizeDatabase()
    {
        $key = 'optimize_db_'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.",
            ], 429);
        }
        RateLimiter::hit($key, 600);

        if (DB::getDriverName() !== 'mysql') {
            return response()->json([
                'success' => false,
                'message' => 'تحسين الجداول متاح لـ MySQL/MariaDB فقط.',
            ], 422);
        }

        try {
            $startedAt = microtime(true);
            $before = $this->databaseFootprint();

            $optimized = 0;
            $failed = [];
            $dbName = DB::getDatabaseName();
            $tableKey = 'Tables_in_'.$dbName;
            $tables = DB::select('SHOW TABLES');

            foreach ($tables as $table) {
                $tableName = (string) ($table->{$tableKey} ?? '');
                if ($tableName === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
                    continue;
                }
                try {
                    DB::statement("OPTIMIZE TABLE `{$tableName}`");
                    $optimized++;
                } catch (\Throwable $e) {
                    $failed[] = $tableName;
                    Log::warning('OPTIMIZE TABLE failed: '.$tableName, ['error' => $e->getMessage()]);
                }
            }

            // إعادة قراءة الإحصاءات بعد التحسين
            $after = $this->databaseFootprint();
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $reclaimedBytes = max(0, (int) $before['data_bytes'] - (int) $after['data_bytes']);

            $summary = [
                'before' => [
                    'size_bytes' => $before['data_bytes'],
                    'size_human' => $this->formatBytes($before['data_bytes']),
                    'tables' => $before['tables'],
                    'rows_approx' => $before['rows_approx'],
                    'free_bytes' => $before['data_free_bytes'],
                    'free_human' => $this->formatBytes($before['data_free_bytes']),
                ],
                'after' => [
                    'size_bytes' => $after['data_bytes'],
                    'size_human' => $this->formatBytes($after['data_bytes']),
                    'tables' => $after['tables'],
                    'rows_approx' => $after['rows_approx'],
                    'free_bytes' => $after['data_free_bytes'],
                    'free_human' => $this->formatBytes($after['data_free_bytes']),
                ],
                'reclaimed_bytes' => $reclaimedBytes,
                'reclaimed_human' => $this->formatBytes($reclaimedBytes),
                'tables_optimized' => $optimized,
                'tables_failed' => $failed,
                'duration_ms' => $durationMs,
                'duration_human' => $this->formatDurationMs($durationMs),
                'ran_at' => now()->toDateTimeString(),
                'ran_at_human' => now()->format('Y-m-d H:i'),
                'note' => $reclaimedBytes < 1024
                    ? 'لا فرق ملحوظ في الحجم — طبيعي على قاعدة صغيرة/جديدة. التسريع الحقيقي من الفهارس والكاش وQueue وليس من OPTIMIZE وحده.'
                    : 'تم استرجاع مساحة من الصفوف المحذوفة وإعادة ترتيب الجداول.',
            ];

            Cache::forever('admin.performance.db_optimize_last', $summary);

            return response()->json([
                'success' => true,
                'message' => 'اكتمل تحسين قاعدة البيانات',
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('optimizeDatabase failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array{data_bytes:int,data_free_bytes:int,tables:int,rows_approx:int}
     */
    private function databaseFootprint(): array
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT
                COUNT(*) AS tables_count,
                COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH), 0) AS data_bytes,
                COALESCE(SUM(DATA_FREE), 0) AS data_free_bytes,
                COALESCE(SUM(TABLE_ROWS), 0) AS rows_approx
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?',
            [$db]
        );

        return [
            'tables' => (int) ($row->tables_count ?? 0),
            'data_bytes' => (int) ($row->data_bytes ?? 0),
            'data_free_bytes' => (int) ($row->data_free_bytes ?? 0),
            'rows_approx' => (int) ($row->rows_approx ?? 0),
        ];
    }

    private function formatDurationMs(int $ms): string
    {
        if ($ms < 1000) {
            return $ms.' مللي ثانية';
        }

        $seconds = round($ms / 1000, 1);

        return $seconds.' ثانية';
    }

    /**
     * الحصول على معلومات النظام
     */
    private function getSystemInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    /**
     * @return array{size_human:string,tables:int,free_human:string,rows_approx:int,driver:string}|null
     */
    private function safeDatabaseInfo(): ?array
    {
        try {
            if (DB::getDriverName() !== 'mysql') {
                return [
                    'driver' => DB::getDriverName(),
                    'size_human' => '—',
                    'tables' => 0,
                    'free_human' => '—',
                    'rows_approx' => 0,
                ];
            }

            $foot = $this->databaseFootprint();

            return [
                'driver' => 'mysql',
                'size_human' => $this->formatBytes($foot['data_bytes']),
                'tables' => $foot['tables'],
                'free_human' => $this->formatBytes($foot['data_free_bytes']),
                'rows_approx' => $foot['rows_approx'],
            ];
        } catch (\Throwable $e) {
            Log::warning('safeDatabaseInfo failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * الحصول على معلومات الأداء
     */
    private function getPerformanceInfo()
    {
        return [
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'disk_free_space' => $this->formatBytes(disk_free_space('/')),
            'disk_total_space' => $this->formatBytes(disk_total_space('/')),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ];
    }

    /**
     * الحصول على أحجام الكاش
     */
    private function getCacheSizes()
    {
        $sizes = [];
        
        // كاش الإعدادات
        $configPath = base_path('bootstrap/cache/config.php');
        if (File::exists($configPath)) {
            try {
                $sizes['config'] = $this->formatBytes(File::size($configPath));
            } catch (\Exception $e) {
                $sizes['config'] = '0 B';
            }
        } else {
            $sizes['config'] = '0 B';
        }
        
        // كاش المسارات
        $routesPath = base_path('bootstrap/cache/routes-v7.php');
        if (File::exists($routesPath)) {
            try {
                $sizes['route'] = $this->formatBytes(File::size($routesPath));
            } catch (\Exception $e) {
                $sizes['route'] = '0 B';
            }
        } else {
            $sizes['route'] = '0 B';
        }
        
        // كاش العروض
        $viewCachePath = storage_path('framework/views');
        if (File::exists($viewCachePath) && File::isDirectory($viewCachePath)) {
            try {
                $totalSize = 0;
                $files = File::allFiles($viewCachePath);
                foreach ($files as $file) {
                    $totalSize += $file->getSize();
                }
                $sizes['view'] = $this->formatBytes($totalSize);
            } catch (\Exception $e) {
                $sizes['view'] = '0 B';
            }
        } else {
            $sizes['view'] = '0 B';
        }
        
        // كاش التطبيق
        $appCachePath = storage_path('framework/cache/data');
        if (File::exists($appCachePath) && File::isDirectory($appCachePath)) {
            try {
                $totalSize = 0;
                $files = File::allFiles($appCachePath);
                foreach ($files as $file) {
                    $totalSize += $file->getSize();
                }
                $sizes['application'] = $this->formatBytes($totalSize);
            } catch (\Exception $e) {
                $sizes['application'] = '0 B';
            }
        } else {
            $sizes['application'] = '0 B';
        }
        
        return $sizes;
    }

    /**
     * تنسيق البايتات
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

