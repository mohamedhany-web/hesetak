<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StudentCourseEnrollment;
use App\Models\EmployeeTask;
use App\Models\AdvancedCourse;
use App\Models\Lecture;
use App\Models\InstructorAgreement;
use App\Models\Assignment;
use App\Models\WithdrawalRequest;
use App\Models\InstructorRequest;
use App\Models\Certificate;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QualityControlController extends Controller
{
    /**
     * لوحة الرقابة والجودة الرئيسية
     */
    public function index()
    {
        // إحصائيات الطلاب
        $studentStats = [
            'total' => User::students()->count(),
            'active' => User::students()->where('is_active', true)->count(),
            'recent_registrations' => User::students()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'enrollments_this_month' => StudentCourseEnrollment::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // إحصائيات المدربين
        $instructorStats = [
            'total' => User::instructors()->count(),
            'active' => User::instructors()->where('is_active', true)->count(),
            'with_agreements' => \App\Models\InstructorAgreement::where('status', 'active')->distinct('instructor_id')->count(),
        ];

        // إحصائيات الموظفين
        $employeeStats = [
            'total' => User::employees()->count(),
            'active' => User::employees()->where('is_active', true)->whereNull('termination_date')->count(),
            'pending_tasks' => EmployeeTask::pending()->count(),
            'overdue_tasks' => EmployeeTask::overdue()->count(),
        ];

        // النشاطات الأخيرة
        $recentActivities = \App\Models\ActivityLog::with('user')
            ->latest()
            ->take(20)
            ->get();

        // العمليات المعلقة
        $pendingOperations = [
            'pending_enrollments' => StudentCourseEnrollment::where('status', 'pending')->count(),
            'pending_tasks' => EmployeeTask::pending()->count(),
        ];

        return view('admin.quality-control.index', compact(
            'studentStats',
            'instructorStats',
            'employeeStats',
            'recentActivities',
            'pendingOperations'
        ));
    }

    /**
     * رقابة الطلاب
     */
    public function students(Request $request)
    {
        $query = User::students()->with(['academicYear']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $students = $query->latest()->paginate(20);

        // إحصائيات لكل طالب
        $students->getCollection()->transform(function($student) {
            $student->enrollments_count = $student->courseEnrollments()->count();
            $student->completed_courses = $student->courseEnrollments()->where('status', 'completed')->count();
            $student->last_activity = $student->last_login_at;
            return $student;
        });

        return view('admin.quality-control.students', compact('students'));
    }

    /**
     * رقابة المدربين
     */
    public function instructors(Request $request)
    {
        $query = User::instructors()->with(['employeeJob']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $instructors = $query->latest()->paginate(20);

        // إحصائيات لكل مدرب
        $instructors->getCollection()->transform(function($instructor) {
            $instructor->courses_count = \App\Models\AdvancedCourse::where('instructor_id', $instructor->id)->count();
            $instructor->agreements_count = \App\Models\InstructorAgreement::where('instructor_id', $instructor->id)->count();
            $instructor->last_activity = $instructor->last_login_at;
            return $instructor;
        });

        return view('admin.quality-control.instructors', compact('instructors'));
    }

    /**
     * صفحة تفاصيل المدرب — رقابة شاملة: كل البيانات والتقارير
     */
    public function instructorShow(User $instructor)
    {
        if (!$instructor->isInstructor()) {
            abort(404);
        }

        // البيانات الشخصية (محمّلة مسبقاً)
        $instructor->load(['employeeJob']);

        // الكورسات (أونلاين)
        $advancedCourses = AdvancedCourse::where('instructor_id', $instructor->id)
            ->with(['academicYear', 'academicSubject'])
            ->orderByDesc('created_at')
            ->get();

        // المحاضرات (أونلاين) — من كورساته أو المنسوبة له
        $lectures = Lecture::where('instructor_id', $instructor->id)
            ->with(['course:id,title,instructor_id', 'lesson:id,title'])
            ->orderByDesc('scheduled_at')
            ->get();

        // الاتفاقيات
        $agreements = InstructorAgreement::where('instructor_id', $instructor->id)
            ->with(['advancedCourse:id,title'])
            ->orderByDesc('created_at')
            ->get();

        // الواجبات (teacher_id)
        $assignments = Assignment::where('teacher_id', $instructor->id)
            ->with(['course:id,title', 'group:id,name'])
            ->orderByDesc('created_at')
            ->get();

        // طلبات السحب
        $withdrawals = WithdrawalRequest::where('instructor_id', $instructor->id)
            ->orderByDesc('created_at')
            ->get();

        // طلبات الانضمام كمدرب
        $instructorRequests = InstructorRequest::where('instructor_id', $instructor->id)
            ->orderByDesc('created_at')
            ->get();

        // إحصائيات التسجيل في كورساته (أونلاين)
        $courseIds = $advancedCourses->pluck('id');

        // شهادات صادرة: إما حسب instructor_id إن وُجد العمود، وإلا حسب كورسات المدرب
        $certificates = collect();
        if (\Illuminate\Support\Facades\Schema::hasColumn((new Certificate)->getTable(), 'instructor_id')) {
            $certificates = Certificate::where('instructor_id', $instructor->id)->orderByDesc('created_at')->get();
        } elseif ($courseIds->isNotEmpty()) {
            $certificates = Certificate::whereIn('course_id', $courseIds)->orderByDesc('created_at')->get();
        }
        $enrollmentsCount = StudentCourseEnrollment::whereIn('advanced_course_id', $courseIds)->count();
        $enrollmentsActive = StudentCourseEnrollment::whereIn('advanced_course_id', $courseIds)->where('status', 'active')->count();
        $enrollmentsCompleted = StudentCourseEnrollment::whereIn('advanced_course_id', $courseIds)->where('status', 'completed')->count();

        // سجل النشاطات للمدرب
        $activityLogs = ActivityLog::where('user_id', $instructor->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('admin.quality-control.instructor-show', compact(
            'instructor',
            'advancedCourses',
            'lectures',
            'agreements',
            'assignments',
            'withdrawals',
            'instructorRequests',
            'certificates',
            'enrollmentsCount',
            'enrollmentsActive',
            'enrollmentsCompleted',
            'activityLogs'
        ));
    }

    /**
     * تصدير تقرير المدرب إلى Excel — ملف مرتب ومنظم
     */
    public function instructorExport(User $instructor)
    {
        if (!$instructor->isInstructor()) {
            abort(404);
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('حصتك')
            ->setTitle('تقرير رقابة المدرب - ' . $instructor->name)
            ->setSubject('تقرير شامل عن المدرب وأنشطته');

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ];
        $border = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]];

        $this->writeInstructorSummarySheet($spreadsheet, $instructor, $headerStyle, $border);
        $this->writeInstructorProfileSheet($spreadsheet, $instructor, $headerStyle, $border);
        $this->writeInstructorCoursesSheet($spreadsheet, $instructor, $headerStyle, $border);
        $this->writeInstructorLecturesSheet($spreadsheet, $instructor, $headerStyle, $border);
        $this->writeInstructorAgreementsSheet($spreadsheet, $instructor, $headerStyle, $border);
        $this->writeInstructorAssignmentsSheet($spreadsheet, $instructor, $headerStyle, $border);
        $this->writeInstructorWithdrawalsSheet($spreadsheet, $instructor, $headerStyle, $border);
        $this->writeInstructorActivitySheet($spreadsheet, $instructor, $headerStyle, $border);

        $safeName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '_', $instructor->name);
        $filename = 'تقرير_المدرب_' . $safeName . '_' . now()->format('Y-m-d') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function writeInstructorSummarySheet($spreadsheet, $instructor, $headerStyle, $border)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ملخص المدرب');
        $sheet->setRightToLeft(true);

        $coursesCount = AdvancedCourse::where('instructor_id', $instructor->id)->count();
        $lecturesCount = Lecture::where('instructor_id', $instructor->id)->count();
        $agreementsCount = InstructorAgreement::where('instructor_id', $instructor->id)->count();
        $assignmentsCount = Assignment::where('teacher_id', $instructor->id)->count();
        $withdrawalsCount = WithdrawalRequest::where('instructor_id', $instructor->id)->count();

        $rows = [
            ['البيان', 'القيمة'],
            ['الاسم', $instructor->name],
            ['البريد', $instructor->email ?? '—'],
            ['الهاتف', $instructor->phone ?? '—'],
            ['حالة الحساب', $instructor->is_active ? 'نشط' : 'معطّل'],
            ['آخر دخول', $instructor->last_login_at ? $instructor->last_login_at->format('Y-m-d H:i') : '—'],
            ['عدد الكورسات (أونلاين)', $coursesCount],
            ['عدد المحاضرات', $lecturesCount],
            ['عدد الاتفاقيات', $agreementsCount],
            ['عدد الواجبات', $assignmentsCount],
            ['عدد طلبات السحب', $withdrawalsCount],
        ];
        $this->writeSheetRows($sheet, $rows, $headerStyle, $border);
    }

    private function writeInstructorProfileSheet($spreadsheet, $instructor, $headerStyle, $border)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('البيانات الشخصية');
        $sheet->setRightToLeft(true);
        $rows = [
            ['الحقل', 'القيمة'],
            ['الاسم', $instructor->name],
            ['البريد الإلكتروني', $instructor->email ?? '—'],
            ['الهاتف', $instructor->phone ?? '—'],
            ['تاريخ الميلاد', $instructor->birth_date ? $instructor->birth_date->format('Y-m-d') : '—'],
            ['العنوان', $instructor->address ?? '—'],
            ['النبذة', $instructor->bio ?? '—'],
            ['نشط', $instructor->is_active ? 'نعم' : 'لا'],
            ['تاريخ التسجيل', $instructor->created_at->format('Y-m-d H:i')],
            ['آخر تحديث', $instructor->updated_at->format('Y-m-d H:i')],
            ['آخر دخول', $instructor->last_login_at ? $instructor->last_login_at->format('Y-m-d H:i') : '—'],
        ];
        $this->writeSheetRows($sheet, $rows, $headerStyle, $border);
    }

    private function writeInstructorCoursesSheet($spreadsheet, $instructor, $headerStyle, $border)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('الكورسات');
        $sheet->setRightToLeft(true);
        $courses = AdvancedCourse::where('instructor_id', $instructor->id)->orderByDesc('created_at')->get();
        $rows = [['المعرف', 'العنوان', 'السعر', 'نشط', 'تاريخ الإنشاء']];
        foreach ($courses as $c) {
            $rows[] = [$c->id, $c->title ?? '—', $c->price ?? 0, $c->is_active ? 'نعم' : 'لا', $c->created_at->format('Y-m-d')];
        }
        $this->writeSheetRows($sheet, $rows, $headerStyle, $border);
    }

    private function writeInstructorLecturesSheet($spreadsheet, $instructor, $headerStyle, $border)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('المحاضرات');
        $sheet->setRightToLeft(true);
        $lectures = Lecture::where('instructor_id', $instructor->id)->with('course:id,title')->orderByDesc('scheduled_at')->get();
        $rows = [['المعرف', 'العنوان', 'الكورس', 'مجدولة في', 'الحالة', 'المدة (د)']];
        foreach ($lectures as $l) {
            $rows[] = [
                $l->id,
                $l->title ?? '—',
                $l->course ? $l->course->title : '—',
                $l->scheduled_at ? $l->scheduled_at->format('Y-m-d H:i') : '—',
                $l->status ?? '—',
                $l->duration_minutes ?? '—',
            ];
        }
        $this->writeSheetRows($sheet, $rows, $headerStyle, $border);
    }

    private function writeInstructorAgreementsSheet($spreadsheet, $instructor, $headerStyle, $border)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('الاتفاقيات');
        $sheet->setRightToLeft(true);
        $agreements = InstructorAgreement::where('instructor_id', $instructor->id)->with('advancedCourse:id,title')->orderByDesc('created_at')->get();
        $rows = [['رقم الاتفاقية', 'العنوان', 'نوع الفوترة', 'المبلغ الإجمالي', 'الحالة', 'من', 'إلى']];
        foreach ($agreements as $a) {
            $rows[] = [
                $a->agreement_number ?? '—',
                $a->title ?? '—',
                $a->billing_type ?? '—',
                $a->total_amount ?? $a->monthly_amount ?? '—',
                $a->status ?? '—',
                $a->start_date ? $a->start_date->format('Y-m-d') : '—',
                $a->end_date ? $a->end_date->format('Y-m-d') : '—',
            ];
        }
        $this->writeSheetRows($sheet, $rows, $headerStyle, $border);
    }

    private function writeInstructorAssignmentsSheet($spreadsheet, $instructor, $headerStyle, $border)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('الواجبات');
        $sheet->setRightToLeft(true);
        $assignments = Assignment::where('teacher_id', $instructor->id)->with('course:id,title')->orderByDesc('created_at')->get();
        $rows = [['المعرف', 'العنوان', 'الكورس', 'الحد الأقصى للنقاط', 'الحالة', 'تاريخ الاستحقاق']];
        foreach ($assignments as $a) {
            $rows[] = [
                $a->id,
                $a->title ?? '—',
                $a->course ? $a->course->title : '—',
                $a->max_score ?? '—',
                $a->status ?? '—',
                $a->due_date ? $a->due_date->format('Y-m-d') : '—',
            ];
        }
        $this->writeSheetRows($sheet, $rows, $headerStyle, $border);
    }

    private function writeInstructorWithdrawalsSheet($spreadsheet, $instructor, $headerStyle, $border)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('طلبات السحب');
        $sheet->setRightToLeft(true);
        $withdrawals = WithdrawalRequest::where('instructor_id', $instructor->id)->orderByDesc('created_at')->get();
        $rows = [['المعرف', 'المبلغ', 'الحالة', 'تاريخ الطلب']];
        foreach ($withdrawals as $w) {
            $rows[] = [$w->id, $w->amount ?? 0, $w->status ?? '—', $w->created_at->format('Y-m-d H:i')];
        }
        $this->writeSheetRows($sheet, $rows, $headerStyle, $border);
    }

    private function writeInstructorActivitySheet($spreadsheet, $instructor, $headerStyle, $border)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('سجل النشاط');
        $sheet->setRightToLeft(true);
        $logs = ActivityLog::where('user_id', $instructor->id)->orderByDesc('created_at')->limit(500)->get();
        $rows = [['التاريخ', 'الإجراء', 'الوصف', 'النموذج']];
        foreach ($logs as $log) {
            $rows[] = [
                $log->created_at->format('Y-m-d H:i'),
                $log->action ?? '—',
                $log->description ?? '—',
                $log->model_type ?? '—',
            ];
        }
        $this->writeSheetRows($sheet, $rows, $headerStyle, $border);
    }

    private function writeSheetRows($sheet, $rows, $headerStyle, $border)
    {
        $row = 1;
        foreach ($rows as $index => $cells) {
            $col = 'A';
            foreach ($cells as $value) {
                $sheet->setCellValue($col . $row, $value);
                if ($index === 0) {
                    $sheet->getStyle($col . $row)->applyFromArray($headerStyle);
                }
                $sheet->getStyle($col . $row)->applyFromArray($border);
                $col++;
            }
            $row++;
        }
        foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * رقابة الموظفين
     */
    public function employees(Request $request)
    {
        $query = User::employees()->with(['employeeJob']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest('hire_date')->paginate(20);

        // إحصائيات لكل موظف
        $employees->getCollection()->transform(function($employee) {
            $employee->tasks_count = $employee->employeeTasks()->count();
            $employee->completed_tasks = $employee->employeeTasks()->where('status', 'completed')->count();
            $employee->pending_tasks = $employee->employeeTasks()->where('status', 'pending')->count();
            $employee->overdue_tasks = $employee->employeeTasks()
                ->where('deadline', '<', now())
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();
            return $employee;
        });

        return view('admin.quality-control.employees', compact('employees'));
    }

    /**
     * متابعة العمليات
     */
    public function operations()
    {
        // عمليات التسجيل
        $enrollmentOperations = [
            'online_pending' => StudentCourseEnrollment::where('status', 'pending')->count(),
            'online_active' => StudentCourseEnrollment::where('status', 'active')->count(),
        ];

        // عمليات المهام
        $taskOperations = [
            'pending' => EmployeeTask::pending()->count(),
            'in_progress' => EmployeeTask::inProgress()->count(),
            'completed_today' => EmployeeTask::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'overdue' => EmployeeTask::overdue()->count(),
        ];

        // عمليات الدفع
        $paymentOperations = [
            'pending' => \App\Models\Payment::where('status', 'pending')->count(),
            'completed_today' => \App\Models\Payment::where('status', 'completed')
                ->whereDate('paid_at', today())
                ->sum('amount'),
        ];

        // سجل النشاطات
        $activityLog = \App\Models\ActivityLog::with('user')
            ->latest()
            ->take(50)
            ->get();

        return view('admin.quality-control.operations', compact(
            'enrollmentOperations',
            'taskOperations',
            'paymentOperations',
            'activityLog'
        ));
    }
}
