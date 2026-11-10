<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ClassroomMeeting;
use App\Models\EmployeeAgreement;
use App\Models\EmployeeSalaryPayment;
use App\Models\EmployeeTask;
use App\Models\LeaveRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    /**
     * لوحة تحكم الموظف — إحصائيات عامة + لمحة حسب الوظيفة
     */
    public function dashboard()
    {
        $user = Auth::user();

        if (! $user->isEmployee() && $user->role !== 'employee') {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $user->load('employeeJob');

        // المشرف الأكاديمي ووظائف لوحة الموظف يبقون هنا — لا تحويل إلى أدمن يسبب 403
        if ($user->employeeJob?->code === 'academic_supervisor') {
            return $this->academicSupervisorDashboard($user);
        }

        if ($user->roles()->exists() && $user->userHasAdminAccessCapability()
            && $user->hasPermission('admin.access')) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user->employeeCan('dashboard')) {
            abort(403, 'لوحة التحكم غير متاحة لوظيفتك الحالية.');
        }

        $tasks = $user->employeeTasks()
            ->with(['assigner', 'deliverables'])
            ->latest()
            ->take(10)
            ->get();

        $stats = [
            'total_tasks' => $user->employeeTasks()->count(),
            'pending_tasks' => $user->employeeTasks()->where('status', 'pending')->count(),
            'in_progress_tasks' => $user->employeeTasks()->where('status', 'in_progress')->count(),
            'completed_tasks' => $user->employeeTasks()->where('status', 'completed')->count(),
            'overdue_tasks' => $user->employeeTasks()
                ->where('deadline', '<', now())
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
        ];

        $jobCode = $user->employeeJob?->code;
        $jobInsights = $this->buildJobInsights($user, $jobCode);

        return view('employee.dashboard', compact('user', 'tasks', 'stats', 'jobCode', 'jobInsights'));
    }

    /**
     * لوحة تحكم مخصصة للمشرف الأكاديمي (لا تؤثر على باقي الموظفين).
     */
    private function academicSupervisorDashboard(User $user): View
    {
        $studentIds = $user->supervisedStudentsAsAcademic()
            ->where('users.role', 'student')
            ->pluck('users.id');

        $liveMeetings = ClassroomMeeting::query()
            ->whereIn('user_id', $studentIds)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->whereNull('consultation_request_id')
            ->with(['user:id,name'])
            ->withCount('participants')
            ->orderByDesc('started_at')
            ->get();

        $inactiveThreshold = now()->subDays(14);
        $inactiveStudentsCount = $user->supervisedStudentsAsAcademic()
            ->where('users.role', 'student')
            ->where(function ($q) use ($inactiveThreshold) {
                $q->whereNull('users.last_login_at')
                    ->orWhere('users.last_login_at', '<', $inactiveThreshold);
            })
            ->count();

        $studentsWithEnrollment = $user->supervisedStudentsAsAcademic()
            ->where('users.role', 'student')
            ->whereHas('courseEnrollments')
            ->count();

        $stats = [
            'supervised_students' => $studentIds->count(),
            'live_meetings' => $liveMeetings->count(),
            'inactive_students' => $inactiveStudentsCount,
            'students_with_courses' => $studentsWithEnrollment,
            'total_tasks' => $user->employeeTasks()->count(),
            'pending_tasks' => $user->employeeTasks()->where('status', 'pending')->count(),
            'overdue_tasks' => $user->employeeTasks()
                ->where('deadline', '<', now())
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
        ];

        $studentsPreview = $user->supervisedStudentsAsAcademic()
            ->where('users.role', 'student')
            ->orderBy('users.name')
            ->limit(15)
            ->select('users.*')
            ->get();

        $liveByStudentId = $liveMeetings->keyBy('user_id');

        $tasks = $user->employeeTasks()
            ->with(['assigner', 'deliverables'])
            ->latest()
            ->take(6)
            ->get();

        return view('employee.dashboard-academic-supervisor', compact(
            'user',
            'stats',
            'liveMeetings',
            'studentsPreview',
            'liveByStudentId',
            'tasks'
        ));
    }

    /**
     * أرقام سريعة تظهر في لوحة التحكم حسب نوع الوظيفة
     */
    private function buildJobInsights($user, ?string $jobCode): array
    {
        if (!$jobCode) {
            return [];
        }

        return match ($jobCode) {
            'accountant' => [
                'label' => 'لمحة المحاسب',
                'items' => [
                    ['text' => 'طلبات دفع معلّقة', 'value' => Order::where('status', Order::STATUS_PENDING)->count(), 'color' => 'amber'],
                    ['text' => 'اتفاقيات موظفين نشطة', 'value' => EmployeeAgreement::where('status', 'active')->count(), 'color' => 'emerald'],
                    ['text' => 'دفعات رواتب بانتظار الصرف', 'value' => EmployeeSalaryPayment::where('status', 'pending')->count(), 'color' => 'sky'],
                ],
            ],
            'sales' => [
                'label' => 'لمحة المبيعات',
                'items' => [
                    ['text' => 'طلبات قيد المراجعة', 'value' => Order::where('status', Order::STATUS_PENDING)->count(), 'color' => 'amber'],
                    ['text' => 'طلبات مُعتمدة هذا الشهر', 'value' => Order::where('status', Order::STATUS_APPROVED)->where('approved_at', '>=', now()->startOfMonth())->count(), 'color' => 'emerald'],
                ],
            ],
            'hr' => [
                'label' => 'لمحة الموارد البشرية',
                'items' => [
                    ['text' => 'موظفون نشطون', 'value' => User::where('is_employee', true)->where('is_active', true)->count(), 'color' => 'indigo'],
                    ['text' => 'طلبات إجازة معلّقة', 'value' => LeaveRequest::where('status', 'pending')->count(), 'color' => 'rose'],
                ],
            ],
            'general_supervision' => $this->supervisionInsight(),
            'supervisor' => $this->supervisionInsight(),
            default => [],
        };
    }

    private function supervisionInsight(): array
    {
        return [
            'label' => 'لمحة الإشراف',
            'items' => [
                ['text' => 'مهام مفتوحة (كل الفريق)', 'value' => EmployeeTask::whereIn('status', ['pending', 'in_progress'])->count(), 'color' => 'blue'],
                ['text' => 'مهام متأخرة', 'value' => EmployeeTask::whereIn('status', ['pending', 'in_progress'])
                    ->whereNotNull('deadline')
                    ->where('deadline', '<', now()->toDateString())
                    ->count(), 'color' => 'red'],
            ],
        ];
    }
}
