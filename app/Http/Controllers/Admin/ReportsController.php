<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdvancedCourse;
use App\Models\StudentCourseEnrollment;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Expense;
use App\Models\Wallet;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Models\AdvancedExam;
use App\Models\ExamAttempt;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Lecture;
use App\Models\Certificate;
use App\Models\InstructorAgreement;
use App\Models\WithdrawalRequest;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPayment;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Referral;
use App\Models\ContactMessage;
use App\Models\Task;
use App\Models\AcademicYear;
use App\Models\AcademicSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\ExcelExportService;

class ReportsController extends Controller
{
    /**
     * عرض لوحة التقارير الرئيسية
     * محمي من: Unauthorized Access
     */
    public function index()
    {
        // التحقق من الصلاحيات
        try {
            // إحصائيات سريعة
            $quickStats = [
                'total_users' => User::count(),
                'total_students' => User::where('role', 'student')->count(),
                'total_instructors' => User::whereIn('role', ['instructor', 'teacher'])->count(),
                'total_courses' => AdvancedCourse::count(),
                'total_enrollments' => StudentCourseEnrollment::count(),
                'total_orders' => Order::count(),
                'total_invoices' => Invoice::count(),
                'total_payments' => Payment::count(),
                'total_activities' => ActivityLog::count(),
            ];

            // فئات التقارير
            $reportCategories = [
                [
                    'name' => 'تقارير المستخدمين',
                    'description' => 'تقارير شاملة عن المستخدمين، الطلاب، المدربين، والإدارة',
                    'icon' => 'fas fa-users',
                    'color' => 'blue',
                    'route' => route('admin.reports.users'),
                    'reports' => [
                        'قائمة المستخدمين الكاملة',
                        'الطلاب المسجلين',
                        'المدربين النشطين',
                        'نمو المستخدمين',
                        'توزيع المستخدمين حسب الدور',
                    ],
                ],
                [
                    'name' => 'تقارير الكورسات',
                    'description' => 'تقارير عن الكورسات، التسجيلات، التقدم، والإنجازات',
                    'icon' => 'fas fa-graduation-cap',
                    'color' => 'emerald',
                    'route' => route('admin.reports.courses'),
                    'reports' => [
                        'قائمة الكورسات الكاملة',
                        'التسجيلات النشطة',
                        'تقدم الطلاب',
                        'الكورسات الأكثر شعبية',
                        'إحصائيات الإكمال',
                    ],
                ],
                [
                    'name' => 'التقارير المالية',
                    'description' => 'تقارير شاملة عن الفواتير، المدفوعات، المعاملات، والمصروفات',
                    'icon' => 'fas fa-money-bill-wave',
                    'color' => 'amber',
                    'route' => route('admin.reports.financial'),
                    'reports' => [
                        'الفواتير',
                        'المدفوعات',
                        'المعاملات المالية',
                        'المصروفات',
                        'الربحية',
                    ],
                ],
                [
                    'name' => 'التقارير الأكاديمية',
                    'description' => 'تقارير عن الامتحانات، الواجبات، المحاضرات، والمجموعات',
                    'icon' => 'fas fa-book',
                    'color' => 'purple',
                    'route' => route('admin.reports.academic'),
                    'reports' => [
                        'الامتحانات والمحاولات',
                        'الواجبات والتسليمات',
                        'المحاضرات',
                        'المجموعات',
                        'الشهادات',
                    ],
                ],
                [
                    'name' => 'تقارير النشاطات',
                    'description' => 'سجل شامل لجميع النشاطات والتحركات في المنصة',
                    'icon' => 'fas fa-history',
                    'color' => 'indigo',
                    'route' => route('admin.reports.activities'),
                    'reports' => [
                        'سجل النشاطات',
                        'نشاط المستخدمين',
                        'نشاط الكورسات',
                        'نشاط المالية',
                        'إحصائيات النشاط',
                    ],
                ],
                [
                    'name' => 'التقرير الشامل',
                    'description' => 'تقرير شامل يغطي جميع جوانب المنصة في ملف واحد',
                    'icon' => 'fas fa-file-alt',
                    'color' => 'rose',
                    'route' => route('admin.reports.comprehensive'),
                    'reports' => [
                        'جميع البيانات',
                        'الإحصائيات العامة',
                        'التقارير التفصيلية',
                        'التحليلات',
                    ],
                ],
            ];

            return view('admin.reports.index', compact('quickStats', 'reportCategories'));
        } catch (\Exception $e) {
            Log::error('Error loading reports dashboard: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل لوحة التقارير');
        }
    }

    /**
     * تقارير المستخدمين
     * محمي من: Unauthorized Access, SQL Injection
     */
    public function users(Request $request)
    {
        // التحقق من الصلاحيات
        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $role = strip_tags(trim($request->input('role', '')));
            $status = strip_tags(trim($request->input('status', '')));

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // Query بناءً على الفلاتر
            $query = User::query();

            if ($role && in_array($role, ['student', 'instructor', 'teacher', 'admin', 'super_admin'])) {
                if ($role === 'instructor' || $role === 'teacher') {
                    $query->whereIn('role', ['instructor', 'teacher']);
                } else {
                    $query->where('role', $role);
                }
            }

            if ($status && in_array($status, ['active', 'inactive'])) {
                $query->where('is_active', $status === 'active');
            }

            // إحصائيات
            $stats = [
                'total' => User::count(),
                'students' => User::where('role', 'student')->count(),
                'instructors' => User::whereIn('role', ['instructor', 'teacher'])->count(),
                'admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'new_this_month' => User::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
                'new_this_year' => User::where('created_at', '>=', Carbon::now()->startOfYear())->count(),
            ];

            // البيانات حسب التاريخ
            $users = $query->whereBetween('created_at', [$startDate, $endDate])
                          ->orderBy('created_at', 'desc')
                          ->paginate(50);

            // إحصائيات حسب الدور
            $usersByRole = User::select('role', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('role')
                ->get();

            // نمو المستخدمين حسب الشهر
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                $usersPerMonth = User::select(
                        DB::raw("CAST(strftime('%Y', created_at) AS INTEGER) as year"),
                        DB::raw("CAST(strftime('%m', created_at) AS INTEGER) as month"),
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get();
            } else {
                $usersPerMonth = User::select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get();
            }

            return view('admin.reports.users', compact(
                'stats',
                'users',
                'usersByRole',
                'usersPerMonth',
                'startDate',
                'endDate',
                'role',
                'status',
                'period'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading user reports: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل تقارير المستخدمين');
        }
    }

    /**
     * تقارير الكورسات
     * محمي من: Unauthorized Access, SQL Injection
     */
    public function courses(Request $request)
    {
        // التحقق من الصلاحيات
        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $status = strip_tags(trim($request->input('status', '')));

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // Query بناءً على الفلاتر
            $query = AdvancedCourse::with(['academicSubject', 'academicYear']);

            if ($status && in_array($status, ['active', 'inactive'])) {
                $query->where('is_active', $status === 'active');
            }

            // إحصائيات
            $stats = [
                'total' => AdvancedCourse::count(),
                'active' => AdvancedCourse::where('is_active', true)->count(),
                'inactive' => AdvancedCourse::where('is_active', false)->count(),
                'total_enrollments' => StudentCourseEnrollment::count(),
                'active_enrollments' => StudentCourseEnrollment::where('status', 'active')->count(),
                'completed_enrollments' => StudentCourseEnrollment::where('status', 'completed')->count(),
                'new_this_month' => AdvancedCourse::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            ];

            // الكورسات
            $courses = $query->whereBetween('created_at', [$startDate, $endDate])
                            ->withCount('enrollments')
                            ->orderBy('created_at', 'desc')
                            ->paginate(50);

            // الكورسات الأكثر شعبية
            $popularCourses = AdvancedCourse::withCount('enrollments')
                ->orderBy('enrollments_count', 'desc')
                ->limit(10)
                ->get();

            // التسجيلات حسب الشهر
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                $enrollmentsPerMonth = StudentCourseEnrollment::select(
                        DB::raw("CAST(strftime('%Y', created_at) AS INTEGER) as year"),
                        DB::raw("CAST(strftime('%m', created_at) AS INTEGER) as month"),
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get();
            } else {
                $enrollmentsPerMonth = StudentCourseEnrollment::select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get();
            }

            return view('admin.reports.courses', compact(
                'stats',
                'courses',
                'popularCourses',
                'enrollmentsPerMonth',
                'startDate',
                'endDate',
                'status',
                'period'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading course reports: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل تقارير الكورسات');
        }
    }

    /**
     * التقارير المالية
     * محمي من: Unauthorized Access, SQL Injection
     */
    public function financial(Request $request)
    {
        // التحقق من الصلاحيات
        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $status = strip_tags(trim($request->input('status', '')));
            $type = strip_tags(trim($request->input('type', 'all')));

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // إحصائيات عامة
            $stats = [
                'total_revenue' => Payment::where('status', 'completed')
                    ->whereBetween('paid_at', [$startDate, $endDate])
                    ->sum('amount'),
                'total_expenses' => Expense::whereBetween('expense_date', [$startDate, $endDate])
                    ->sum('amount'),
                'total_invoices' => Invoice::whereBetween('created_at', [$startDate, $endDate])->count(),
                'paid_invoices' => Invoice::where('status', 'paid')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
                'pending_invoices' => Invoice::where('status', 'pending')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
                'total_transactions' => Transaction::whereBetween('created_at', [$startDate, $endDate])->count(),
            ];
            $stats['net_profit'] = $stats['total_revenue'] - $stats['total_expenses'];

            // الفواتير
            $invoices = Invoice::with('user')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            // المدفوعات
            $payments = Payment::with(['invoice', 'user'])
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->orderBy('paid_at', 'desc')
                ->paginate(50);

            // المعاملات
            $transactions = Transaction::with(['user', 'payment'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            // المصروفات
            $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
                ->orderBy('expense_date', 'desc')
                ->paginate(50);

            // الإيرادات حسب طريقة الدفع
            $revenueByMethod = Payment::select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->groupBy('payment_method')
                ->get();

            return view('admin.reports.financial', compact(
                'stats',
                'invoices',
                'payments',
                'transactions',
                'expenses',
                'revenueByMethod',
                'startDate',
                'endDate',
                'type',
                'period'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading financial reports: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل التقارير المالية');
        }
    }

    /**
     * التقارير الأكاديمية
     * محمي من: Unauthorized Access, SQL Injection
     */
    public function academic(Request $request)
    {
        // التحقق من الصلاحيات
        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $status = strip_tags(trim($request->input('status', '')));

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // إحصائيات
            $stats = [
                'total_exams' => AdvancedExam::count(),
                'total_attempts' => ExamAttempt::count(),
                'passed_attempts' => ExamAttempt::where('status', 'passed')->count(),
                'failed_attempts' => ExamAttempt::where('status', 'failed')->count(),
                'total_assignments' => Assignment::count(),
                'total_submissions' => AssignmentSubmission::count(),
                'total_lectures' => Lecture::count(),
                'total_certificates' => Certificate::count(),
            ];

            // الامتحانات والمحاولات
            $exams = AdvancedExam::withCount('attempts')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->paginate(30);

            $examAttempts = ExamAttempt::with(['exam', 'user'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            // الواجبات
            $assignments = Assignment::with(['course', 'lesson'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->paginate(30);

            $submissions = AssignmentSubmission::with(['assignment', 'student'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            // المحاضرات
            $lectures = Lecture::with(['course', 'instructor'])
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->orderBy('scheduled_at', 'desc')
                ->paginate(50);

            // الشهادات
            $certificates = Certificate::with(['user', 'course'])
                ->whereBetween('issued_at', [$startDate, $endDate])
                ->orderBy('issued_at', 'desc')
                ->paginate(30);

            return view('admin.reports.academic', compact(
                'stats',
                'exams',
                'examAttempts',
                'assignments',
                'submissions',
                'lectures',
                'certificates',
                'startDate',
                'endDate',
                'period'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading academic reports: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل التقارير الأكاديمية');
        }
    }

    /**
     * تقارير النشاطات
     * محمي من: Unauthorized Access, SQL Injection
     */
    public function activities(Request $request)
    {
        // التحقق من الصلاحيات
        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $status = strip_tags(trim($request->input('status', '')));
            $action = strip_tags(trim($request->input('action', '')));
            $user_id = filter_var($request->input('user_id'), FILTER_VALIDATE_INT);

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // Query
            $query = ActivityLog::with('user')
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($action && strlen($action) <= 100) {
                $query->where('action', 'like', '%' . $action . '%');
            }

            if ($user_id && $user_id > 0) {
                $query->where('user_id', $user_id);
            }

            // إحصائيات
            $stats = [
                'total' => ActivityLog::whereBetween('created_at', [$startDate, $endDate])->count(),
                'today' => ActivityLog::whereDate('created_at', today())->count(),
                'this_week' => ActivityLog::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
                'this_month' => ActivityLog::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            ];

            // النشاطات
            $activities = $query->orderBy('created_at', 'desc')
                               ->paginate(100);

            // النشاطات حسب النوع
            $activitiesByAction = ActivityLog::select('action', DB::raw('COUNT(*) as count'))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get();

            // النشاطات حسب المستخدم
            $activitiesByUser = ActivityLog::select('user_id', DB::raw('COUNT(*) as count'))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->with('user')
                ->get();

            // النشاطات اليومية
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                $dailyActivities = ActivityLog::select(
                        DB::raw("strftime('%Y-%m-%d', created_at) as date"),
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->limit(30)
                    ->get();
            } else {
                $dailyActivities = ActivityLog::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->limit(30)
                    ->get();
            }

            return view('admin.reports.activities', compact(
                'stats',
                'activities',
                'activitiesByAction',
                'activitiesByUser',
                'dailyActivities',
                'startDate',
                'endDate',
                'action',
                'user_id',
                'period'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading activity reports: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل تقارير النشاطات');
        }
    }

    /**
     * التقرير الشامل
     * محمي من: Unauthorized Access, SQL Injection
     */
    public function comprehensive(Request $request)
    {
        // التحقق من الصلاحيات
        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'month')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // إحصائيات شاملة
            $stats = [
                // المستخدمين
                'users' => [
                    'total' => User::count(),
                    'students' => User::where('role', 'student')->count(),
                    'instructors' => User::whereIn('role', ['instructor', 'teacher'])->count(),
                    'new_this_period' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
                ],
                // الكورسات
                'courses' => [
                    'total' => AdvancedCourse::count(),
                    'active' => AdvancedCourse::where('is_active', true)->count(),
                    'total_enrollments' => StudentCourseEnrollment::count(),
                    'active_enrollments' => StudentCourseEnrollment::where('status', 'active')->count(),
                ],
                // المالية
                'financial' => [
                    'total_revenue' => Payment::where('status', 'completed')
                        ->whereBetween('paid_at', [$startDate, $endDate])
                        ->sum('amount'),
                    'total_expenses' => Expense::whereBetween('expense_date', [$startDate, $endDate])
                        ->sum('amount'),
                    'total_invoices' => Invoice::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'total_transactions' => Transaction::whereBetween('created_at', [$startDate, $endDate])->count(),
                ],
                // الأكاديمية
                'academic' => [
                    'total_exams' => AdvancedExam::count(),
                    'total_attempts' => ExamAttempt::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'total_assignments' => Assignment::count(),
                    'total_submissions' => AssignmentSubmission::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'total_lectures' => Lecture::count(),
                    'total_certificates' => Certificate::count(),
                ],
                // أخرى
                'other' => [
                    'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'total_notifications' => Notification::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'total_activities' => ActivityLog::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'total_contacts' => ContactMessage::whereBetween('created_at', [$startDate, $endDate])->count(),
                ],
            ];

            $stats['financial']['net_profit'] = $stats['financial']['total_revenue'] - $stats['financial']['total_expenses'];

            return view('admin.reports.comprehensive', compact(
                'stats',
                'startDate',
                'endDate',
                'period'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading comprehensive report: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل التقرير الشامل');
        }
    }

    /**
     * تصدير تقرير المستخدمين إلى Excel
     * محمي من: Unauthorized Access, SQL Injection, Brute Force
     */
    public function exportUsers(Request $request)
    {
        // التحقق من الصلاحيات
        // Rate Limiting
        $key = 'report_export_' . Auth::id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->with('error', "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.");
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 300); // 5 دقائق

        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $role = strip_tags(trim($request->input('role', '')));
            $status = strip_tags(trim($request->input('status', '')));

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // Query
            $query = User::query();

            if ($role && in_array($role, ['student', 'instructor', 'teacher', 'admin', 'super_admin'])) {
                if ($role === 'instructor' || $role === 'teacher') {
                    $query->whereIn('role', ['instructor', 'teacher']);
                } else {
                    $query->where('role', $role);
                }
            }

            if ($status && in_array($status, ['active', 'inactive'])) {
                $query->where('is_active', $status === 'active');
            }

            $users = $query->whereBetween('created_at', [$startDate, $endDate])
                          ->orderBy('created_at', 'desc')
                          ->get();

            // إنشاء ملف Excel مع Service
            $excelService = new ExcelExportService();

            $roleLabel = match($role) {
                'student' => 'الطلاب',
                'instructor', 'teacher' => 'المدربين',
                'admin', 'super_admin' => 'الإدارة',
                default => 'كل الأدوار',
            };
            $statusLabel = match($status) {
                'active' => 'نشط',
                'inactive' => 'غير نشط',
                default => 'كل الحالات',
            };

            $excelService->addHeader(
                'تقرير المستخدمين الشامل - حصتك',
                'من تاريخ: ' . $startDate->format('Y-m-d')
                . ' إلى تاريخ: ' . $endDate->format('Y-m-d')
                . ' | الدور: ' . $roleLabel
                . ' | الحالة: ' . $statusLabel
            );

            // إضافة إحصائيات
            $stats = [
                'إجمالي المستخدمين (بعد الفلترة)' => $users->count(),
                'الطلاب' => $users->where('role', 'student')->count(),
                'المدربين' => $users->whereIn('role', ['instructor', 'teacher'])->count(),
                'الإدارة' => $users->whereIn('role', ['admin', 'super_admin'])->count(),
                'المستخدمين النشطين' => $users->where('is_active', true)->count(),
                'المستخدمين غير النشطين' => $users->where('is_active', false)->count(),
            ];
            $excelService->addSectionTitle('الإحصائيات');
            $excelService->addStatistics($stats);
            $excelService->addEmptyRow(2);

            // إضافة بيانات الجدول
            $excelService->addSectionTitle('قائمة المستخدمين');
            $headers = ['ID', 'الاسم', 'البريد الإلكتروني', 'رقم الهاتف', 'الدور', 'الحالة', 'تاريخ الإنشاء', 'آخر تحديث'];
            $rows = [];
            
            foreach ($users as $user) {
                $roleLabel = match($user->role) {
                    'student' => 'طالب',
                    'instructor', 'teacher' => 'مدرب',
                    'admin', 'super_admin' => 'إدارة',
                    default => $user->role,
                };
                
                $rows[] = [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? '-',
                    $roleLabel,
                    $user->is_active ? 'نشط' : 'غير نشط',
                    $user->created_at->format('Y-m-d H:i:s'),
                    $user->updated_at->format('Y-m-d H:i:s'),
                ];
            }
            
            $excelService->addTableData($headers, $rows);

            // تنزيل الملف
            $filename = 'تقارير_المستخدمين_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');
            return $excelService->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            Log::error('Error exporting user report: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تصدير التقرير');
        }
    }

    /**
     * تصدير تقرير الكورسات إلى Excel
     * محمي من: Unauthorized Access, SQL Injection, Brute Force
     */
    public function exportCourses(Request $request)
    {
        // التحقق من الصلاحيات
        // Rate Limiting
        $key = 'report_export_' . Auth::id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->with('error', "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.");
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 300);

        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $status = strip_tags(trim($request->input('status', '')));

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // Query
            $coursesQuery = AdvancedCourse::with(['academicSubject', 'academicYear']);
            if ($status && in_array($status, ['active', 'inactive'], true)) {
                $coursesQuery->where('is_active', $status === 'active');
            }

            $courses = $coursesQuery
                ->whereBetween('created_at', [$startDate, $endDate])
                ->withCount('enrollments')
                ->orderBy('created_at', 'desc')
                ->get();

            // إنشاء ملف Excel مع Service
            $excelService = new ExcelExportService();

            $excelService->addHeader(
                'تقرير الكورسات الشامل - حصتك',
                'من تاريخ: ' . $startDate->format('Y-m-d')
                . ' إلى تاريخ: ' . $endDate->format('Y-m-d')
                . ' | الحالة: ' . ($status === 'active' ? 'نشط' : ($status === 'inactive' ? 'غير نشط' : 'كل الحالات'))
            );

            // إضافة إحصائيات
            $stats = [
                'إجمالي الكورسات' => $courses->count(),
                'الكورسات النشطة' => $courses->where('is_active', true)->count(),
                'إجمالي التسجيلات' => $courses->sum('enrollments_count'),
                'متوسط التسجيلات لكل كورس' => $courses->count() > 0 ? round($courses->sum('enrollments_count') / $courses->count(), 2) : 0,
            ];
            $excelService->addSectionTitle('الإحصائيات');
            $excelService->addStatistics($stats);
            $excelService->addEmptyRow(2);

            // إضافة بيانات الجدول
            $excelService->addSectionTitle('قائمة الكورسات');
            $headers = ['ID', 'العنوان', 'المسار التعليمي', 'مجموعة المهارات', 'الحالة', 'عدد التسجيلات', 'السعر', 'تاريخ الإنشاء'];
            $rows = [];
            
            foreach ($courses as $course) {
                $rows[] = [
                    $course->id,
                    $course->title,
                    $course->academicYear->name ?? 'غير محدد',
                    $course->academicSubject->name ?? 'غير محدد',
                    $course->is_active ? 'نشط' : 'غير نشط',
                    number_format($course->enrollments_count),
                    number_format($course->price ?? 0, 2) . ' ' . currency_symbol(),
                    $course->created_at->format('Y-m-d H:i:s'),
                ];
            }
            
            $excelService->addTableData($headers, $rows);

            // تنزيل الملف
            $filename = 'تقارير_الكورسات_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');
            return $excelService->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            Log::error('Error exporting course report: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تصدير التقرير');
        }
    }

    /**
     * تصدير التقارير المالية إلى Excel
     * محمي من: Unauthorized Access, SQL Injection, Brute Force
     */
    public function exportFinancial(Request $request)
    {
        // التحقق من الصلاحيات
        // Rate Limiting
        $key = 'report_export_' . Auth::id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->with('error', "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.");
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 300);

        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $type = strip_tags(trim($request->input('type', 'all')));

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // إنشاء ملف Excel مع Service
            $excelService = new ExcelExportService();

            $excelService->addHeader(
                'التقارير المالية الشاملة - حصتك',
                'من تاريخ: ' . $startDate->format('Y-m-d')
                . ' إلى تاريخ: ' . $endDate->format('Y-m-d')
                . ' | النوع: ' . match($type) {
                    'summary' => 'الملخص',
                    'invoices' => 'الفواتير',
                    'payments' => 'المدفوعات',
                    'transactions' => 'المعاملات',
                    'expenses' => 'المصروفات',
                    default => 'الكل',
                }
            );

            // الملخص المالي
            if ($type === 'all' || $type === 'summary') {
                $totalRevenue = Payment::where('status', 'completed')
                    ->whereBetween('paid_at', [$startDate, $endDate])
                    ->sum('amount');
                $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
                    ->sum('amount');
                $netProfit = $totalRevenue - $totalExpenses;
                
                $stats = [
                    'إجمالي الإيرادات' => number_format($totalRevenue, 2) . ' ' . currency_symbol(),
                    'إجمالي المصروفات' => number_format($totalExpenses, 2) . ' ' . currency_symbol(),
                    'الربح الصافي' => number_format($netProfit, 2) . ' ' . currency_symbol(),
                    'نسبة الربحية' => $totalRevenue > 0 ? number_format(($netProfit / $totalRevenue) * 100, 2) . '%' : '0%',
                ];
                
                $excelService->addSectionTitle('الملخص المالي');
                $excelService->addStatistics($stats);
                $excelService->addEmptyRow(2);
            }

            // الفواتير
            if ($type === 'all' || $type === 'invoices') {
                $invoices = Invoice::with('user')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc')
                    ->get();

                $excelService->addSectionTitle('الفواتير');
                $headers = ['رقم الفاتورة', 'المستخدم', 'النوع', 'المبلغ الإجمالي', 'الحالة', 'تاريخ الاستحقاق', 'تاريخ الدفع', 'تاريخ الإنشاء'];
                $rows = [];
                
                foreach ($invoices as $invoice) {
                    $rows[] = [
                        $invoice->invoice_number ?? 'N/A',
                        $invoice->user->name ?? 'غير محدد',
                        $invoice->type ?? '-',
                        number_format($invoice->total_amount ?? 0, 2) . ' ' . currency_symbol(),
                        $invoice->status,
                        $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-',
                        $invoice->paid_at ? $invoice->paid_at->format('Y-m-d') : '-',
                        $invoice->created_at->format('Y-m-d H:i:s'),
                    ];
                }
                
                $excelService->addTableData($headers, $rows);
                $excelService->addEmptyRow(2);
            }

            // المدفوعات
            if ($type === 'all' || $type === 'payments') {
                $payments = Payment::with(['user', 'invoice'])
                    ->whereBetween('paid_at', [$startDate, $endDate])
                    ->orderBy('paid_at', 'desc')
                    ->get();

                $excelService->addSectionTitle('المدفوعات');
                $headers = ['ID', 'المستخدم', 'المبلغ', 'طريقة الدفع', 'الحالة', 'تاريخ الدفع', 'رقم الفاتورة'];
                $rows = [];
                
                foreach ($payments as $payment) {
                    $rows[] = [
                        $payment->id,
                        $payment->user->name ?? 'غير محدد',
                        number_format($payment->amount, 2) . ' ' . currency_symbol(),
                        $payment->payment_method ?? '-',
                        $payment->status,
                        $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : '-',
                        $payment->invoice->invoice_number ?? '-',
                    ];
                }
                
                $excelService->addTableData($headers, $rows);
                $excelService->addEmptyRow(2);
            }

            // المصروفات
            if ($type === 'all' || $type === 'expenses') {
                $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
                    ->orderBy('expense_date', 'desc')
                    ->get();

                $excelService->addSectionTitle('المصروفات');
                $headers = ['رقم المصروف', 'العنوان', 'الفئة', 'المبلغ', 'طريقة الدفع', 'الحالة', 'تاريخ المصروف', 'تاريخ الإنشاء'];
                $rows = [];
                
                foreach ($expenses as $expense) {
                    $rows[] = [
                        $expense->expense_number ?? 'N/A',
                        $expense->title ?? '-',
                        $expense->category ?? '-',
                        number_format($expense->amount, 2) . ' ' . currency_symbol(),
                        $expense->payment_method ?? '-',
                        $expense->status ?? '-',
                        $expense->expense_date ? $expense->expense_date->format('Y-m-d') : '-',
                        $expense->created_at->format('Y-m-d H:i:s'),
                    ];
                }
                
                $excelService->addTableData($headers, $rows);
            }

            // المعاملات
            if ($type === 'all' || $type === 'transactions') {
                $transactions = Transaction::with(['user', 'payment'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc')
                    ->get();

                $excelService->addSectionTitle('المعاملات');
                $headers = ['ID', 'المستخدم', 'النوع', 'المبلغ', 'الحالة', 'رقم الدفعة', 'تاريخ الإنشاء'];
                $rows = [];

                foreach ($transactions as $transaction) {
                    $rows[] = [
                        $transaction->id,
                        $transaction->user->name ?? 'غير محدد',
                        $transaction->type ?? '-',
                        number_format((float) ($transaction->amount ?? 0), 2) . ' ' . currency_symbol(),
                        $transaction->status ?? '-',
                        $transaction->payment->id ?? '-',
                        $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : '-',
                    ];
                }

                $excelService->addTableData($headers, $rows);
            }

            // تنزيل الملف
            $filename = 'التقارير_المالية_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');
            return $excelService->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            Log::error('Error exporting financial report: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تصدير التقرير');
        }
    }

    /**
     * تصدير التقارير الأكاديمية إلى Excel
     * محمي من: Unauthorized Access, SQL Injection, Brute Force
     */
    public function exportAcademic(Request $request)
    {
        $key = 'report_export_' . Auth::id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->with('error', "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.");
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 300);

        try {
            $period = strip_tags(trim($request->input('period', 'all')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            $excelService = new ExcelExportService();
            $excelService->addHeader(
                'التقرير الأكاديمي الشامل - حصتك',
                'من تاريخ: ' . $startDate->format('Y-m-d') . ' إلى تاريخ: ' . $endDate->format('Y-m-d')
            );

            $stats = [
                'إجمالي الامتحانات' => AdvancedExam::whereBetween('created_at', [$startDate, $endDate])->count(),
                'إجمالي محاولات الامتحانات' => ExamAttempt::whereBetween('created_at', [$startDate, $endDate])->count(),
                'المحاولات الناجحة' => ExamAttempt::where('status', 'passed')->whereBetween('created_at', [$startDate, $endDate])->count(),
                'المحاولات غير الناجحة' => ExamAttempt::where('status', 'failed')->whereBetween('created_at', [$startDate, $endDate])->count(),
                'إجمالي الواجبات' => Assignment::whereBetween('created_at', [$startDate, $endDate])->count(),
                'إجمالي التسليمات' => AssignmentSubmission::whereBetween('created_at', [$startDate, $endDate])->count(),
                'إجمالي المحاضرات' => Lecture::whereBetween('created_at', [$startDate, $endDate])->count(),
                'إجمالي الشهادات' => Certificate::whereBetween('created_at', [$startDate, $endDate])->count(),
            ];
            $excelService->addSectionTitle('الإحصائيات الأكاديمية');
            $excelService->addStatistics($stats);
            $excelService->addEmptyRow(2);

            $exams = AdvancedExam::withCount('attempts')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->get();
            $excelService->addSectionTitle('الامتحانات');
            $excelService->addTableData(
                ['ID', 'العنوان', 'عدد المحاولات', 'تاريخ الإنشاء'],
                $exams->map(fn($exam) => [
                    $exam->id,
                    $exam->title ?? '-',
                    (int) ($exam->attempts_count ?? 0),
                    optional($exam->created_at)->format('Y-m-d H:i:s') ?? '-',
                ])->toArray()
            );
            $excelService->addEmptyRow(2);

            $submissions = AssignmentSubmission::with(['assignment', 'student'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->limit(5000)
                ->get();
            $excelService->addSectionTitle('تسليمات الواجبات');
            $excelService->addTableData(
                ['ID', 'الواجب', 'الطالب', 'الدرجة', 'الحالة', 'تاريخ التسليم'],
                $submissions->map(fn($s) => [
                    $s->id,
                    $s->assignment->title ?? '-',
                    $s->student->name ?? '-',
                    $s->grade ?? '-',
                    $s->status ?? '-',
                    optional($s->created_at)->format('Y-m-d H:i:s') ?? '-',
                ])->toArray()
            );
            $excelService->addEmptyRow(2);

            $filename = 'التقارير_الأكاديمية_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');
            return $excelService->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            Log::error('Error exporting academic report: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تصدير التقرير الأكاديمي');
        }
    }

    /**
     * تصدير التقرير الشامل إلى Excel
     * محمي من: Unauthorized Access, SQL Injection, Brute Force
     */
    public function exportComprehensive(Request $request)
    {
        // التحقق من الصلاحيات
        // Rate Limiting
        $key = 'report_export_' . Auth::id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->with('error', "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.");
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 600); // 10 دقائق

        try {
            // Sanitization
            $period = strip_tags(trim($request->input('period', 'month')));
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // حساب التواريخ
            $dates = $this->calculateDateRange($period, $startDate, $endDate);
            $startDate = $dates['start'];
            $endDate = $dates['end'];

            // إنشاء ملف Excel مع Service
            $excelService = new ExcelExportService();

            $excelService->addHeader(
                'التقرير الشامل - حصتك',
                'من تاريخ: ' . $startDate->format('Y-m-d') . ' إلى تاريخ: ' . $endDate->format('Y-m-d')
            );

            // المستخدمين
            $excelService->addSectionTitle('تقرير المستخدمين');
            $stats = [
                'إجمالي المستخدمين' => number_format(User::count()),
                'الطلاب' => number_format(User::where('role', 'student')->count()),
                'المدربين' => number_format(User::whereIn('role', ['instructor', 'teacher'])->count()),
                'الإدارة' => number_format(User::whereIn('role', ['admin', 'super_admin'])->count()),
                'جدد هذا الفترة' => number_format(User::whereBetween('created_at', [$startDate, $endDate])->count()),
            ];
            $excelService->addStatistics($stats);
            $excelService->addEmptyRow(2);

            // الكورسات
            $excelService->addSectionTitle('تقرير الكورسات');
            $stats = [
                'إجمالي الكورسات' => number_format(AdvancedCourse::count()),
                'الكورسات النشطة' => number_format(AdvancedCourse::where('is_active', true)->count()),
                'إجمالي التسجيلات' => number_format(StudentCourseEnrollment::count()),
                'التسجيلات النشطة' => number_format(StudentCourseEnrollment::where('status', 'active')->count()),
            ];
            $excelService->addStatistics($stats);
            $excelService->addEmptyRow(2);

            // المالية
            $excelService->addSectionTitle('التقرير المالي');
            $totalRevenue = Payment::where('status', 'completed')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount');
            $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
                ->sum('amount');
            $stats = [
                'إجمالي الإيرادات' => number_format($totalRevenue, 2) . ' ' . currency_symbol(),
                'إجمالي المصروفات' => number_format($totalExpenses, 2) . ' ' . currency_symbol(),
                'الربح الصافي' => number_format($totalRevenue - $totalExpenses, 2) . ' ' . currency_symbol(),
                'عدد الفواتير' => number_format(Invoice::whereBetween('created_at', [$startDate, $endDate])->count()),
                'عدد المدفوعات' => number_format(Payment::whereBetween('paid_at', [$startDate, $endDate])->count()),
            ];
            $excelService->addStatistics($stats);
            $excelService->addEmptyRow(2);

            // المحتوى الدراسي
            $excelService->addSectionTitle('التقرير الدراسي');
            $stats = [
                'إجمالي الامتحانات' => number_format(AdvancedExam::count()),
                'إجمالي المحاولات' => number_format(ExamAttempt::whereBetween('created_at', [$startDate, $endDate])->count()),
                'إجمالي الواجبات' => number_format(Assignment::count()),
                'إجمالي التسليمات' => number_format(AssignmentSubmission::whereBetween('created_at', [$startDate, $endDate])->count()),
                'إجمالي المحاضرات' => number_format(Lecture::count()),
                'إجمالي الشهادات' => number_format(Certificate::count()),
            ];
            $excelService->addStatistics($stats);
            $excelService->addEmptyRow(2);

            // أخرى
            $excelService->addSectionTitle('تقارير أخرى');
            $stats = [
                'إجمالي الطلبات' => number_format(Order::whereBetween('created_at', [$startDate, $endDate])->count()),
                'إجمالي الإشعارات' => number_format(Notification::whereBetween('created_at', [$startDate, $endDate])->count()),
                'إجمالي النشاطات' => number_format(ActivityLog::whereBetween('created_at', [$startDate, $endDate])->count()),
                'رسائل التواصل' => number_format(ContactMessage::whereBetween('created_at', [$startDate, $endDate])->count()),
            ];
            $excelService->addStatistics($stats);

            // تنزيل الملف
            $filename = 'التقرير_الشامل_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');
            return $excelService->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            Log::error('Error exporting comprehensive report: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تصدير التقرير');
        }
    }

    /**
     * حساب نطاق التاريخ
     */
    private function calculateDateRange($period, $startDate, $endDate)
    {
        $now = Carbon::now();

        if ($startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        return match($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'all' => [
                'start' => Carbon::parse('2020-01-01'),
                'end' => $now->copy()->endOfDay(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }
}
