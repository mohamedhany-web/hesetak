<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessage;
use App\Models\StudentReport;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Models\AdvancedCourse;
use App\Services\WhatsAppService;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessagesController extends Controller
{
    protected $whatsappService;
    protected $emailService;

    public function __construct(WhatsAppService $whatsappService, EmailNotificationService $emailService)
    {
        $this->whatsappService = $whatsappService;
        $this->emailService = $emailService;
    }

    /**
     * عرض لوحة الرسائل الرئيسية
     */
    public function index(Request $request)
    {
        $query = WhatsAppMessage::with(['user'])
            ->orderBy('created_at', 'desc');

        // فلتر حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلتر حسب النوع
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // البحث
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('message', 'like', '%' . $request->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($userQuery) use ($request) {
                      $userQuery->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $messages = $query->paginate(20);

        // إحصائيات سريعة
        $stats = [
            'total_messages' => WhatsAppMessage::count(),
            'sent_today' => WhatsAppMessage::whereDate('sent_at', today())->count(),
            'failed_messages' => WhatsAppMessage::where('status', 'failed')->count(),
            'monthly_reports' => StudentReport::whereMonth('created_at', now()->month)->count(),
        ];

        return view('admin.messages.index', compact('messages', 'stats'));
    }

    /**
     * عرض صفحة إرسال رسالة جديدة
     */
    public function create(Request $request)
    {
        $students = User::students()->select('id', 'name', 'phone', 'email')->get();
        $employees = User::where('role', 'employee')->select('id', 'name', 'phone', 'email')->get();
        $templates = MessageTemplate::active()->get();
        $courses = AdvancedCourse::active()->select('id', 'title')->get();

        $prefillMessage = $request->input('template_content');
        $prefillTitle = $request->input('template_title');

        return view('admin.messages.create', compact('students', 'employees', 'templates', 'courses', 'prefillMessage', 'prefillTitle'));
    }

    /**
     * إرسال رسالة فردية
     */
    public function sendSingle(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string|max:4096',
            'template_id' => 'nullable|exists:message_templates,id',
            'channel' => 'nullable|in:email',
        ], [
            'user_id.required' => 'يجب اختيار الطالب',
            'message.required' => 'نص الرسالة مطلوب',
            'message.max' => 'نص الرسالة طويل جداً',
        ]);

        $student = User::findOrFail($request->user_id);
        $message = $request->message;

        // تطبيق القالب إذا تم اختياره
        if ($request->template_id) {
            $template = MessageTemplate::findOrFail($request->template_id);
            $variables = $this->getStudentVariables($student);
            $message = $template->render($variables);
        }

        $mailResult = $this->emailService->sendToUser($student, $message);

        // تسجيل الرسالة في جدول الرسائل لعرضها في لوحة الرسائل
        WhatsAppMessage::create([
            'user_id' => $student->id,
            'phone_number' => $student->phone,
            'message' => $message,
            'type' => 'email_single',
            'status' => $mailResult['success'] ? 'sent' : 'failed',
            'response_data' => null,
            'whatsapp_message_id' => null,
            'sent_at' => $mailResult['success'] ? now() : null,
            'template_name' => $request->template_id ? optional(MessageTemplate::find($request->template_id))->name : null,
            'template_params' => null,
            'error_message' => $mailResult['success'] ? null : ($mailResult['error'] ?? 'خطأ غير معروف'),
        ]);

        if ($mailResult['success']) {
            return back()->with('success', 'تم إرسال الرسالة البريدية بنجاح إلى ' . $student->name);
        }

        $error = $mailResult['error'] ?? 'خطأ غير معروف';
        return back()->with('error', 'فشل في إرسال الرسالة: ' . $error);
    }

    /**
     * إرسال رسالة جماعية
     */
    public function sendBulk(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required|in:all_students,course_students,selected_students,all_employees,all_users',
            'course_id' => 'required_if:recipient_type,course_students|exists:advanced_courses,id',
            'selected_students' => 'required_if:recipient_type,selected_students|array',
            'selected_students.*' => 'exists:users,id',
            'message' => 'required|string|max:4096',
            'template_id' => 'nullable|exists:message_templates,id',
            'channel' => 'nullable|in:email',
        ]);

        // تحديد المستلمين
        $students = $this->getRecipients($request);

        if ($students->isEmpty()) {
            return back()->with('error', 'لا توجد مستلمين لإرسال الرسالة إليهم');
        }

        $message = $request->message;

        // تطبيق القالب إذا تم اختياره
        if ($request->template_id) {
            $template = MessageTemplate::findOrFail($request->template_id);
        }

        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($students as $student) {
            $finalMessage = $message;
            
            if (isset($template)) {
                $variables = $this->getStudentVariables($student);
                $finalMessage = $template->render($variables);
            }

            $mailResult = $this->emailService->sendToUser($student, $finalMessage);

            WhatsAppMessage::create([
                'user_id' => $student->id,
                'phone_number' => $student->phone,
                'message' => $finalMessage,
                'type' => 'email_bulk',
                'status' => $mailResult['success'] ? 'sent' : 'failed',
                'response_data' => null,
                'whatsapp_message_id' => null,
                'sent_at' => $mailResult['success'] ? now() : null,
                'template_name' => $request->template_id ? optional($template)->name : null,
                'template_params' => null,
                'error_message' => $mailResult['success'] ? null : ($mailResult['error'] ?? 'خطأ غير معروف'),
            ]);

            if ($mailResult['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
            
            $results[] = [
                'student' => $student->name,
                'success' => $mailResult['success'],
                'error' => $mailResult['error'] ?? null,
            ];
        }

        return back()->with('success', 
            "تم إرسال {$successCount} رسالة بريدية بنجاح" . 
            ($failCount > 0 ? " وفشل في إرسال {$failCount} رسالة" : "")
        );
    }

    /**
     * عرض صفحة التقارير الشهرية
     */
    public function monthlyReports()
    {
        $reports = StudentReport::with(['student', 'parent'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_reports' => StudentReport::count(),
            'this_month' => StudentReport::whereMonth('created_at', now()->month)->count(),
            'pending' => StudentReport::where('status', 'pending')->count(),
            'sent' => StudentReport::where('status', 'sent')->count(),
        ];

        return view('admin.messages.monthly-reports', compact('reports', 'stats'));
    }

    /**
     * توليد وإرسال التقارير الشهرية
     */
    public function generateMonthlyReports(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'send_to_parents' => 'boolean',
        ]);

        $month = $request->month;
        $sendToParents = $request->boolean('send_to_parents');

        // الحصول على جميع الطلاب النشطين
        $students = User::students()
            ->whereHas('courseEnrollments')
            ->with(['parent'])
            ->get();

        $generated = 0;
        $sent = 0;
        $failed = 0;

        foreach ($students as $student) {
            try {
                $reportData = $this->whatsappService->generateStudentReportData($student);
                
                // إنشاء التقرير
                $report = StudentReport::create([
                    'student_id' => $student->id,
                    'parent_id' => $student->parent_id,
                    'report_month' => $month,
                    'report_type' => 'monthly',
                    'report_data' => $reportData,
                    'sent_via' => 'whatsapp',
                    'status' => 'pending',
                    'generated_by' => auth()->id(),
                ]);

                $generated++;

                // إرسال للطالب
                $studentResult = $this->whatsappService->sendMonthlyReport($student, $student, $reportData);
                
                // إرسال لولي الأمر إذا كان متاحاً ومطلوباً
                if ($sendToParents && $student->parent && $student->parent->phone) {
                    $parentResult = $this->whatsappService->sendMonthlyReport($student->parent, $student, $reportData);
                    
                    if ($parentResult['success']) {
                        $sent++;
                        $report->update([
                            'status' => 'sent',
                            'sent_at' => now()
                        ]);
                    } else {
                        $failed++;
                        $report->update([
                            'status' => 'failed',
                            'error_message' => $parentResult['error'] ?? 'خطأ غير معروف'
                        ]);
                    }
                } else {
                    // إرسال للطالب فقط
                    if ($studentResult['success']) {
                        $sent++;
                        $report->update([
                            'status' => 'sent',
                            'sent_at' => now()
                        ]);
                    } else {
                        $failed++;
                        $report->update([
                            'status' => 'failed',
                            'error_message' => $studentResult['error'] ?? 'خطأ غير معروف'
                        ]);
                    }
                }

            } catch (\Exception $e) {
                $failed++;
                \Log::error('Error generating monthly report', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return back()->with('success', 
            "تم توليد {$generated} تقرير، إرسال {$sent} بنجاح" . 
            ($failed > 0 ? " وفشل في إرسال {$failed}" : "")
        );
    }

    /**
     * عرض صفحة قوالب الرسائل
     */
    public function templates()
    {
        $templates = MessageTemplate::with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.messages.templates', compact('templates'));
    }

    /**
     * إنشاء قالب رسالة جديد
     */
    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:message_templates',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:' . implode(',', array_keys(MessageTemplate::getTypes())),
        ]);

        MessageTemplate::create([
            'name' => $request->name,
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'variables' => MessageTemplate::getAvailableVariables($request->type),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم إنشاء القالب بنجاح');
    }

    /**
     * تحديث قالب رسالة
     */
    public function updateTemplate(Request $request, MessageTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:message_templates,name,'.$template->id,
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:'.implode(',', array_keys(MessageTemplate::getTypes())),
        ]);

        $template->update([
            'name' => $request->name,
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'variables' => MessageTemplate::getAvailableVariables($request->type),
        ]);

        return back()->with('success', 'تم تحديث القالب بنجاح');
    }

    /**
     * الحصول على المستلمين حسب النوع
     */
    private function getRecipients(Request $request)
    {
        return match($request->recipient_type) {
            'all_students' => User::students()->get(),
            'course_students' => User::students()
                ->whereHas('courseEnrollments', function($q) use ($request) {
                    $q->where('advanced_course_id', $request->course_id);
                })->get(),
            'selected_students' => User::students()
                ->whereIn('id', $request->selected_students ?? [])
                ->get(),
            'all_employees' => User::where('role', 'employee')->get(),
            'all_users' => User::query()->get(),
            default => collect()
        };
    }

    /**
     * الحصول على متغيرات الطالب للقوالب
     */
    private function getStudentVariables(User $student): array
    {
        $reportData = $this->whatsappService->generateStudentReportData($student);
        $parent = $student->parent ?? null;
        
        return [
            'student_name' => $student->name,
            'student_phone' => $student->phone,
            'courses_count' => count($reportData['courses']),
            'avg_score' => $reportData['overall']['average_score'],
            'total_exams' => count($reportData['exams']),
            'month_name' => now()->locale('ar')->format('F Y'),
            'overall_grade' => $reportData['overall']['grade'],
            'parent_name' => $parent?->name,
            'platform_name' => 'منصة مستر طارق الداجن',
            'support_phone' => config('app.support_phone', '01000000000'),
            'date' => now()->format('d/m/Y'),
        ];
    }

    /**
     * عرض تفاصيل رسالة
     */
    public function show(WhatsAppMessage $message)
    {
        $message->load(['user']);
        return view('admin.messages.show', compact('message'));
    }

    /**
     * إعادة إرسال رسالة فاشلة
     */
    public function resend(WhatsAppMessage $message)
    {
        if ($message->status !== 'failed') {
            return back()->with('error', 'لا يمكن إعادة إرسال هذه الرسالة');
        }

        $result = $this->whatsappService->sendMessage(
            $message->phone_number, 
            $message->message, 
            $message->type
        );

        if ($result['success']) {
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null
            ]);
            
            return back()->with('success', 'تم إعادة إرسال الرسالة بنجاح');
        } else {
            return back()->with('error', 'فشل في إعادة إرسال الرسالة: ' . $result['error']);
        }
    }

    /**
     * حذف رسالة
     */
    public function destroy(WhatsAppMessage $message)
    {
        $message->delete();
        return back()->with('success', 'تم حذف الرسالة بنجاح');
    }

    /**
     * حذف قالب رسالة
     */
    public function destroyTemplate(MessageTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'تم حذف القالب بنجاح');
    }

    /**
     * إرسال رسالة ترحيب للطالب الجديد
     */
    public function sendWelcomeMessage(User $student)
    {
        $template = MessageTemplate::where('name', 'welcome_new_student')->first();
        
        if ($template) {
            $variables = $this->getStudentVariables($student);
            $message = $template->render($variables);
            
            return $this->whatsappService->sendStudentMessage($student, $message, 'welcome');
        }

        return ['success' => false, 'error' => 'قالب الترحيب غير موجود'];
    }

    /**
     * إرسال تذكير بالكورس
     */
    public function sendCourseReminder(User $student, AdvancedCourse $course, $lessonTitle = null)
    {
        $template = MessageTemplate::where('name', 'course_reminder')->first();
        
        if ($template) {
            $variables = array_merge($this->getStudentVariables($student), [
                'course_title' => $course->title,
                'lesson_title' => $lessonTitle ?? 'درس جديد متاح',
            ]);
            
            $message = $template->render($variables);
            return $this->whatsappService->sendStudentMessage($student, $message, 'reminder');
        }

        return ['success' => false, 'error' => 'قالب التذكير غير موجود'];
    }
}