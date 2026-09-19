<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AdvancedCourse;
use App\Models\AssignmentSubmission;
use App\Models\ConsultationRequest;
use App\Models\InstructorProfile;
use App\Models\LiveSession;
use App\Models\Order;
use App\Models\SalesLead;
use App\Models\StudentCourseEnrollment;
use App\Models\SupportTicket;
use App\Models\TutoringGroupBooking;
use App\Models\TutoringGroupCohort;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AcademyInsightsService
{
    /**
     * لقطة تحليلات الأكاديمية مع رؤى واقتراحات قابلة للتنفيذ.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->subDays(6)->startOfDay();
        $monthStart = $now->copy()->startOfMonth();

        $studentsTotal = User::students()->count();
        $instructorsTotal = User::whereIn('role', ['instructor', 'teacher'])->count();
        $studentsToday = User::students()->where('created_at', '>=', $todayStart)->count();
        $studentsWeek = User::students()->where('created_at', '>=', $weekStart)->count();

        $activeEnrollments = StudentCourseEnrollment::where('status', 'active')->count();
        $enrollmentsWeek = StudentCourseEnrollment::where('created_at', '>=', $weekStart)->count();
        $coursesActive = AdvancedCourse::where('is_active', true)->count();

        $ordersPending = Order::where('status', Order::STATUS_PENDING)->count();
        $ordersToday = Order::where('created_at', '>=', $todayStart)->count();
        $revenueMonth = (float) Order::where('status', Order::STATUS_APPROVED)
            ->where('updated_at', '>=', $monthStart)
            ->sum('amount');
        $revenueToday = (float) Order::where('status', Order::STATUS_APPROVED)
            ->where('updated_at', '>=', $todayStart)
            ->sum('amount');

        $tutoring = $this->tutoringMetrics($now);
        $liveNow = $this->safeCount(fn () => LiveSession::where('status', 'live')->count());
        $pendingWithdrawals = $this->safeCount(fn () => WithdrawalRequest::where('status', WithdrawalRequest::STATUS_PENDING)->count());
        $openTickets = $this->safeCount(fn () => SupportTicket::whereIn('status', ['open', 'pending', 'in_progress', 'waiting'])->count());
        $pendingConsultations = $this->safeCount(fn () => ConsultationRequest::where('status', ConsultationRequest::STATUS_PENDING)->count());
        $pendingInstructorProfiles = $this->safeCount(fn () => InstructorProfile::where('status', InstructorProfile::STATUS_PENDING_REVIEW)->count());
        $pendingSubmissions = AssignmentSubmission::whereNull('graded_at')->count();
        $openLeads = $this->safeCount(function () {
            if (! Schema::hasTable('sales_leads')) {
                return 0;
            }

            return SalesLead::query()
                ->when(Schema::hasColumn('sales_leads', 'status'), function ($q) {
                    $q->whereNotIn('status', ['won', 'lost', 'closed', 'converted']);
                })
                ->count();
        });

        $kpis = [
            [
                'key' => 'students',
                'label' => 'الطلاب',
                'value' => $studentsTotal,
                'delta' => '+'.$studentsToday.' اليوم',
                'hint' => $studentsWeek.' خلال 7 أيام',
                'tone' => 'accent',
            ],
            [
                'key' => 'instructors',
                'label' => 'المدربون',
                'value' => $instructorsTotal,
                'delta' => null,
                'hint' => 'طاقم التدريس',
                'tone' => 'ink',
            ],
            [
                'key' => 'enrollments',
                'label' => 'تسجيلات نشطة',
                'value' => $activeEnrollments,
                'delta' => '+'.$enrollmentsWeek.' أسبوعياً',
                'hint' => $coursesActive.' كورس نشط',
                'tone' => 'accent',
            ],
            [
                'key' => 'revenue_month',
                'label' => 'إيراد الشهر',
                'value' => round($revenueMonth, 2),
                'formatted' => number_format($revenueMonth, 2) . ' ' . currency_symbol(),
                'delta' => number_format($revenueToday, 2).' $ اليوم',
                'hint' => 'طلبات معتمدة',
                'tone' => 'gold',
            ],
            [
                'key' => 'orders_pending',
                'label' => 'طلبات معلّقة',
                'value' => $ordersPending,
                'delta' => $ordersToday.' طلب اليوم',
                'hint' => 'تحتاج مراجعة',
                'tone' => $ordersPending > 0 ? 'warn' : 'ok',
            ],
            [
                'key' => 'tutoring_upcoming',
                'label' => 'حصص مجموعات قادمة',
                'value' => $tutoring['upcoming'],
                'delta' => $tutoring['pending'].' بانتظار التأكيد',
                'hint' => $tutoring['today'].' اليوم',
                'tone' => $tutoring['pending'] > 0 ? 'warn' : 'accent',
            ],
            [
                'key' => 'live_now',
                'label' => 'بث مباشر الآن',
                'value' => $liveNow,
                'delta' => null,
                'hint' => 'جلسات live',
                'tone' => $liveNow > 0 ? 'live' : 'ink',
            ],
            [
                'key' => 'support',
                'label' => 'تذاكر مفتوحة',
                'value' => $openTickets,
                'delta' => $pendingConsultations.' استشارة معلّقة',
                'hint' => 'دعم وجودة',
                'tone' => $openTickets > 5 ? 'warn' : 'ink',
            ],
        ];

        $trends = [
            'students_7d' => $this->dailySeries(User::students(), $weekStart, 7),
            'orders_7d' => $this->dailySeries(Order::query(), $weekStart, 7),
            'enrollments_7d' => $this->dailySeries(StudentCourseEnrollment::query(), $weekStart, 7),
            'revenue_7d' => $this->dailySumSeries(
                Order::where('status', Order::STATUS_APPROVED),
                $weekStart,
                7,
                'amount',
                'updated_at'
            ),
        ];

        $attention = [
            'orders_pending' => $ordersPending,
            'tutoring_pending' => $tutoring['pending'],
            'withdrawals_pending' => $pendingWithdrawals,
            'tickets_open' => $openTickets,
            'consultations_pending' => $pendingConsultations,
            'instructor_profiles_pending' => $pendingInstructorProfiles,
            'ungraded_submissions' => $pendingSubmissions,
            'leads_open' => $openLeads,
            'cohorts_at_risk' => $tutoring['cohorts_at_risk'],
            'cohorts_near_full' => $tutoring['cohorts_near_full'],
        ];

        $actions = $this->buildActions($attention, [
            'students_week' => $studentsWeek,
            'enrollments_week' => $enrollmentsWeek,
            'revenue_today' => $revenueToday,
            'live_now' => $liveNow,
        ]);

        $insights = $this->buildInsights($kpis, $attention, $trends, $tutoring);

        $pulse = [
            'generated_at' => $now->toIso8601String(),
            'generated_at_human' => $now->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'server_time' => $now->timestamp,
        ];

        $recent = [
            'orders' => Order::with('user:id,name')
                ->latest()
                ->take(6)
                ->get(['id', 'user_id', 'amount', 'status', 'created_at'])
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'title' => $o->user?->name ?? 'طلب #'.$o->id,
                    'meta' => number_format((float) $o->amount, 2).' $ · '.$o->status,
                    'when' => optional($o->created_at)->diffForHumans(),
                    'url' => Route::has('admin.orders.show') ? route('admin.orders.show', $o) : (Route::has('admin.orders.index') ? route('admin.orders.index') : null),
                ]),
            'students' => User::students()->latest()->take(6)->get(['id', 'name', 'email', 'created_at'])->map(fn ($u) => [
                'id' => $u->id,
                'title' => $u->name,
                'meta' => $u->email,
                'when' => optional($u->created_at)->diffForHumans(),
                'url' => Route::has('admin.users.show') ? route('admin.users.show', $u->id) : null,
            ]),
            'activity' => ActivityLog::with('user:id,name')->latest()->take(8)->get()->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->user?->name ?? 'النظام',
                'meta' => Str::limit((string) ($a->description ?? $a->action ?? 'نشاط'), 80),
                'when' => optional($a->created_at)->diffForHumans(),
                'url' => null,
            ]),
        ];

        return compact('kpis', 'trends', 'attention', 'actions', 'insights', 'pulse', 'recent', 'tutoring');
    }

    /**
     * @return array<string, int>
     */
    private function tutoringMetrics($now): array
    {
        $defaults = [
            'upcoming' => 0,
            'pending' => 0,
            'today' => 0,
            'cohorts_open' => 0,
            'cohorts_at_risk' => 0,
            'cohorts_near_full' => 0,
        ];

        if (! Schema::hasTable('tutoring_group_bookings')) {
            return $defaults;
        }

        $defaults['upcoming'] = TutoringGroupBooking::query()
            ->where('status', TutoringGroupBooking::STATUS_CONFIRMED)
            ->where('starts_at', '>=', $now)
            ->count();
        $defaults['pending'] = TutoringGroupBooking::query()
            ->where('status', TutoringGroupBooking::STATUS_PENDING)
            ->count();
        $defaults['today'] = TutoringGroupBooking::query()
            ->where('status', TutoringGroupBooking::STATUS_CONFIRMED)
            ->whereBetween('starts_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->count();

        if (Schema::hasTable('tutoring_group_cohorts')) {
            $open = TutoringGroupCohort::query()
                ->where('status', TutoringGroupCohort::STATUS_OPEN)
                ->where('is_visible', true)
                ->get(['id', 'capacity', 'enrolled_count', 'min_enrollment', 'starts_at']);

            $defaults['cohorts_open'] = $open->count();
            $defaults['cohorts_at_risk'] = $open->filter(function ($c) use ($now) {
                $min = max(1, (int) ($c->min_enrollment ?? 1));
                $startsSoon = $c->starts_at && $c->starts_at->lte($now->copy()->addDays(3));

                return $startsSoon && (int) $c->enrolled_count < $min;
            })->count();
            $defaults['cohorts_near_full'] = $open->filter(function ($c) {
                $cap = max(1, (int) $c->capacity);

                return ((int) $c->enrolled_count / $cap) >= 0.85;
            })->count();
        }

        return $defaults;
    }

    /**
     * @param  array<string, int>  $attention
     * @param  array<string, mixed>  $ctx
     * @return list<array<string, mixed>>
     */
    private function buildActions(array $attention, array $ctx): array
    {
        $actions = [];

        if ($attention['orders_pending'] > 0) {
            $actions[] = [
                'priority' => 'high',
                'title' => 'مراجعة الطلبات المعلّقة',
                'body' => $attention['orders_pending'].' طلب بانتظار الموافقة — تأخيرها يؤثر على التحويل والإيراد.',
                'cta' => 'فتح الطلبات',
                'url' => Route::has('admin.orders.index') ? route('admin.orders.index', ['status' => 'pending']) : null,
            ];
        }

        if ($attention['tutoring_pending'] > 0) {
            $actions[] = [
                'priority' => 'high',
                'title' => 'تأكيد حجوزات المجموعات',
                'body' => $attention['tutoring_pending'].' حجز معلّق — أكّدها لإنشاء جلسات Live وربط الطلاب.',
                'cta' => 'الحجوزات',
                'url' => Route::has('admin.tutoring-group-bookings.index') ? route('admin.tutoring-group-bookings.index') : null,
            ];
        }

        if ($attention['cohorts_at_risk'] > 0) {
            $actions[] = [
                'priority' => 'high',
                'title' => 'دفعات جماعية مهددة بالتأجيل',
                'body' => $attention['cohorts_at_risk'].' دفعة تبدأ خلال 3 أيام دون الحد الأدنى — راجع التسويق أو أجّل الموعد.',
                'cta' => 'المجموعات',
                'url' => Route::has('admin.tutoring-groups.index') ? route('admin.tutoring-groups.index', ['type' => 'collective']) : null,
            ];
        }

        if ($attention['withdrawals_pending'] > 0) {
            $actions[] = [
                'priority' => 'medium',
                'title' => 'صرف طلبات سحب المدربين',
                'body' => $attention['withdrawals_pending'].' طلب سحب معلّق — التأخير يقلل رضا المدربين.',
                'cta' => 'السحوبات',
                'url' => Route::has('admin.withdrawals.index') ? route('admin.withdrawals.index') : null,
            ];
        }

        if ($attention['tickets_open'] > 0) {
            $actions[] = [
                'priority' => $attention['tickets_open'] > 5 ? 'high' : 'medium',
                'title' => 'معالجة تذاكر الدعم',
                'body' => $attention['tickets_open'].' تذكرة مفتوحة — أغلق الأقدم أولاً للحفاظ على جودة الخدمة.',
                'cta' => 'الدعم',
                'url' => Route::has('admin.support-tickets.index') ? route('admin.support-tickets.index') : null,
            ];
        }

        if ($attention['instructor_profiles_pending'] > 0) {
            $actions[] = [
                'priority' => 'medium',
                'title' => 'اعتماد ملفات المدربين',
                'body' => $attention['instructor_profiles_pending'].' ملف بانتظار المراجعة قبل الظهور العام.',
                'cta' => 'الملفات',
                'url' => Route::has('admin.academy-instructors.index') ? route('admin.academy-instructors.index') : null,
            ];
        }

        if ($attention['ungraded_submissions'] > 15) {
            $actions[] = [
                'priority' => 'medium',
                'title' => 'ضغط تقييم الواجبات',
                'body' => $attention['ungraded_submissions'].' تسليم بلا تقييم — ذكّر المدربين أو وزّع الحمل.',
                'cta' => 'التقارير',
                'url' => Route::has('admin.reports.academic') ? route('admin.reports.academic') : null,
            ];
        }

        if ($attention['leads_open'] > 0) {
            $actions[] = [
                'priority' => 'medium',
                'title' => 'متابعة ليادات المبيعات',
                'body' => $attention['leads_open'].' ليد مفتوح في CRM — ركّز على الأعلى احتمالاً اليوم.',
                'cta' => 'CRM',
                'url' => Route::has('admin.crm.dashboard') ? route('admin.crm.dashboard') : null,
            ];
        }

        if ($attention['cohorts_near_full'] > 0) {
            $actions[] = [
                'priority' => 'low',
                'title' => 'افتح دفعة جديدة للمجموعات الممتلئة',
                'body' => $attention['cohorts_near_full'].' دفعة قاربت الامتلاء (≥85%) — جهّز دفعة تالية قبل نفاد المقاعد.',
                'cta' => 'الدفعات',
                'url' => Route::has('admin.tutoring-groups.index') ? route('admin.tutoring-groups.index', ['type' => 'collective']) : null,
            ];
        }

        if (($ctx['students_week'] ?? 0) === 0 && ($ctx['enrollments_week'] ?? 0) === 0) {
            $actions[] = [
                'priority' => 'medium',
                'title' => 'ضعف اكتساب هذا الأسبوع',
                'body' => 'لا تسجيل طلاب/كورسات ملحوظ خلال 7 أيام — راجع الحملات وصفحة المجموعات.',
                'cta' => 'الموقع',
                'url' => Route::has('public.groups') ? route('public.groups') : url('/'),
            ];
        }

        if (empty($actions)) {
            $actions[] = [
                'priority' => 'low',
                'title' => 'الوضع التشغيلي مستقر',
                'body' => 'لا توجد اختناقات حرجة الآن. راقب الإيراد اليومي والحصص المباشرة.',
                'cta' => null,
                'url' => null,
            ];
        }

        usort($actions, function ($a, $b) {
            $rank = ['high' => 0, 'medium' => 1, 'low' => 2];

            return ($rank[$a['priority']] ?? 9) <=> ($rank[$b['priority']] ?? 9);
        });

        return $actions;
    }

    /**
     * @param  list<array<string, mixed>>  $kpis
     * @param  array<string, int>  $attention
     * @param  array<string, list<array<string, mixed>>>  $trends
     * @param  array<string, int>  $tutoring
     * @return list<array<string, string>>
     */
    private function buildInsights(array $kpis, array $attention, array $trends, array $tutoring): array
    {
        $insights = [];

        $ordersSeries = collect($trends['orders_7d'] ?? []);
        $last3 = $ordersSeries->slice(-3)->sum('count');
        $prev3 = $ordersSeries->slice(0, 3)->sum('count');
        if ($prev3 > 0 && $last3 > $prev3 * 1.25) {
            $insights[] = [
                'tone' => 'up',
                'title' => 'تسارع في الطلبات',
                'body' => 'آخر 3 أيام أعلى من بداية الأسبوع بنسبة ملحوظة — جهّز فريق المبيعات/الموافقة.',
            ];
        } elseif ($prev3 > 2 && $last3 < $prev3 * 0.6) {
            $insights[] = [
                'tone' => 'down',
                'title' => 'تباطؤ الطلبات',
                'body' => 'حجم الطلبات انخفض مؤخراً مقارنة ببداية الأسبوع — راجع العروض وصفحات الهبوط.',
            ];
        }

        $studentsKpi = collect($kpis)->firstWhere('key', 'students');
        if ($studentsKpi && (int) $studentsKpi['value'] > 0) {
            $insights[] = [
                'tone' => 'info',
                'title' => 'قاعدة الطلاب',
                'body' => number_format((int) $studentsKpi['value']).' طالب مسجّل · '.($studentsKpi['hint'] ?? ''),
            ];
        }

        if ($tutoring['today'] > 0) {
            $insights[] = [
                'tone' => 'live',
                'title' => 'يوم حصص مجموعات مزدحم',
                'body' => $tutoring['today'].' حصة مؤكدة اليوم — تأكد من جاهزية المدربين وغرف Live.',
            ];
        }

        if ($attention['orders_pending'] + $attention['tutoring_pending'] + $attention['tickets_open'] >= 8) {
            $insights[] = [
                'tone' => 'warn',
                'title' => 'تراكم تشغيلي',
                'body' => 'مجموع العناصر المعلّقة مرتفع. أعطِ الأولوية للطلبات والحجوزات قبل المهام الإدارية.',
            ];
        }

        if (count($insights) < 3) {
            $insights[] = [
                'tone' => 'info',
                'title' => 'توجه الأكاديمية',
                'body' => 'اربط قراراتك اليومية بالإيراد المعتمد، امتلاء الدفعات، وسرعة إغلاق الدعم — هذه الثلاثية تحرك النمو.',
            ];
        }

        return array_slice($insights, 0, 6);
    }

    /**
     * @return list<array{date:string,label:string,count:int}>
     */
    private function dailySeries($query, $from, int $days): array
    {
        $rows = (clone $query)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();
            $out[] = [
                'date' => $key,
                'label' => $day->translatedFormat('D'),
                'count' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{date:string,label:string,count:float}>
     */
    private function dailySumSeries($query, $from, int $days, string $column, string $dateColumn = 'created_at'): array
    {
        $rows = (clone $query)
            ->where($dateColumn, '>=', $from)
            ->selectRaw("DATE({$dateColumn}) as d, SUM({$column}) as c")
            ->groupBy('d')
            ->pluck('c', 'd');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();
            $out[] = [
                'date' => $key,
                'label' => $day->translatedFormat('D'),
                'count' => round((float) ($rows[$key] ?? 0), 2),
            ];
        }

        return $out;
    }

    private function safeCount(callable $fn): int
    {
        try {
            return (int) $fn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
