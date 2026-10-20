<?php

namespace App\Models;

use App\Mail\InAppNotificationMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sender_id',
        'title',
        'message',
        'type',
        'action_url',
        'action_text',
        'priority',
        'target_type',
        'target_id',
        'audience',
        'is_read',
        'read_at',
        'expires_at',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'data' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (self $notification) {
            $notification->dispatchEmailCopy();
        });
    }

    /**
     * إرسال نسخة بريدية إلى بريد المستخدم (Gmail وغيره).
     * يُفضَّل الإرسال الفوري لضمان الوصول حتى بدون queue worker.
     */
    public function dispatchEmailCopy(): void
    {
        if (! filter_var(config('mail.notify_in_app', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $data = is_array($this->data) ? $this->data : [];
        if (! empty($data['skip_email'])) {
            return;
        }

        try {
            $user = $this->relationLoaded('user') ? $this->user : $this->user()->first();
            if (! $user || empty($user->email) || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $mailable = new InAppNotificationMail($this, (string) ($user->name ?? ''));

            try {
                Mail::to($user->email)->send($mailable);
            } catch (\Throwable $e) {
                Log::warning('In-app notification email failed', [
                    'notification_id' => $this->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('In-app notification email dispatch failed', [
                'notification_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * علاقة مع المستخدم المستقبل
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة مع المرسل
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * أنواع الإشعارات المتاحة
     */
    public static function getTypes()
    {
        return [
            'message' => 'رسالة',
            'general' => 'عام',
            'course' => 'كورس',
            'exam' => 'امتحان',
            'assignment' => 'واجب',
            'grade' => 'درجة',
            'announcement' => 'إعلان',
            'reminder' => 'تذكير',
            'warning' => 'تحذير',
            'system' => 'نظام',
            'employee' => 'موظف',
        ];
    }

    /**
     * مستويات الأولوية
     */
    public static function getPriorities()
    {
        return [
            'low' => 'منخفضة',
            'normal' => 'عادية',
            'high' => 'عالية',
            'urgent' => 'عاجلة',
        ];
    }

    /**
     * أهداف الإشعارات
     */
    public static function getTargetTypes()
    {
        return [
            'all_students' => 'جميع الطلاب',
            'course_students' => 'طلاب كورس معين',
            'year_students' => 'طلاب سنة دراسية',
            'subject_students' => 'طلاب مادة معينة',
            'individual' => 'طالب محدد',
        ];
    }

    /**
     * scope للإشعارات غير المقروءة
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * scope للإشعارات المقروءة
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * scope للإشعارات حسب النوع
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * scope للإشعارات حسب الأولوية
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * scope للإشعارات غير المنتهية الصلاحية
     */
    public function scopeValid($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * تحديد الإشعار كمقروء
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * التحقق من انتهاء صلاحية الإشعار
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * الحصول على لون الأولوية
     */
    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'yellow',
            'urgent' => 'red',
        ];

        return $colors[$this->priority] ?? 'blue';
    }

    /**
     * الحصول على أيقونة النوع
     */
    public function getTypeIconAttribute()
    {
        $icons = [
            'general' => 'fas fa-info-circle',
            'message' => 'fas fa-comment',
            'course' => 'fas fa-graduation-cap',
            'exam' => 'fas fa-clipboard-check',
            'assignment' => 'fas fa-tasks',
            'grade' => 'fas fa-star',
            'announcement' => 'fas fa-bullhorn',
            'reminder' => 'fas fa-bell',
            'warning' => 'fas fa-exclamation-triangle',
            'system' => 'fas fa-cog',
        ];

        return $icons[$this->type] ?? 'fas fa-info-circle';
    }

    /**
     * الحصول على لون النوع
     */
    public function getTypeColorAttribute()
    {
        $colors = [
            'general' => 'blue',
            'course' => 'green',
            'exam' => 'purple',
            'assignment' => 'orange',
            'grade' => 'yellow',
            'announcement' => 'red',
            'reminder' => 'blue',
            'warning' => 'red',
            'system' => 'gray',
        ];

        return $colors[$this->type] ?? 'blue';
    }

    /**
     * إرسال إشعار لمستخدم واحد
     */
    public static function sendToUser($userId, $data)
    {
        return self::create(array_merge($data, ['user_id' => $userId]));
    }

    /**
     * إرسال إشعار لمجموعة مستخدمين
     */
    public static function sendToUsers($userIds, $data)
    {
        $notifications = [];
        $now = now();
        foreach ($userIds as $userId) {
            $notifications[] = array_merge($data, [
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $ok = self::insert($notifications);

        // insert() لا يطلق created — نرسل البريد يدوياً
        if ($ok && filter_var(config('mail.notify_in_app', true), FILTER_VALIDATE_BOOLEAN)) {
            $skip = is_array($data['data'] ?? null) && ! empty($data['data']['skip_email']);
            if (! $skip) {
                self::query()
                    ->whereIn('user_id', $userIds)
                    ->where('title', $data['title'] ?? '')
                    ->where('created_at', '>=', $now->copy()->subMinute())
                    ->latest('id')
                    ->limit(count($notifications))
                    ->get()
                    ->each(fn (self $n) => $n->dispatchEmailCopy());
            }
        }

        return $ok;
    }

    /**
     * إرسال إشعار لجميع الطلاب
     */
    public static function sendToAllStudents($data)
    {
        $studentIds = User::where('role', 'student')->where('is_active', true)->pluck('id');
        return self::sendToUsers($studentIds, $data);
    }

    /**
     * إرسال إشعار لطلاب كورس معين
     */
    public static function sendToCourseStudents($courseId, $data)
    {
        $studentIds = \App\Models\StudentCourseEnrollment::where('advanced_course_id', $courseId)
                                                        ->where('status', 'active')
                                                        ->pluck('user_id');
        return self::sendToUsers($studentIds, $data);
    }

    /**
     * إرسال إشعار لطلاب سنة دراسية
     */
    public static function sendToYearStudents($yearId, $data)
    {
        $courseIds = \App\Models\AdvancedCourse::where('academic_year_id', $yearId)->pluck('id');
        $studentIds = \App\Models\StudentCourseEnrollment::whereIn('advanced_course_id', $courseIds)
                                                        ->where('status', 'active')
                                                        ->pluck('user_id')
                                                        ->unique();
        return self::sendToUsers($studentIds, $data);
    }

    /**
     * إرسال إشعار لطلاب مادة معينة
     */
    public static function sendToSubjectStudents($subjectId, $data)
    {
        $courseIds = \App\Models\AdvancedCourse::where('academic_subject_id', $subjectId)->pluck('id');
        $studentIds = \App\Models\StudentCourseEnrollment::whereIn('advanced_course_id', $courseIds)
                                                        ->where('status', 'active')
                                                        ->pluck('user_id')
                                                        ->unique();
        return self::sendToUsers($studentIds, $data);
    }

    /**
     * إرسال إشعار لموظف معين
     */
    public static function sendToEmployee($employeeId, $data)
    {
        $user = User::find($employeeId);
        if (!$user || !$user->is_employee) {
            return 0;
        }

        $notification = self::create(array_merge($data, [
            'user_id' => $employeeId,
        ]));

        return $notification ? 1 : 0;
    }

    /**
     * إرسال إشعار لمجموعة موظفين
     */
    public static function sendToEmployees($employeeIds, $data)
    {
        $employeeIds = collect($employeeIds)->filter()->values()->all();
        if ($employeeIds === []) {
            return 0;
        }

        // create() يطلق البريد؛ insert() لا يفعل ذلك
        $count = 0;
        foreach ($employeeIds as $employeeId) {
            try {
                if (self::create(array_merge($data, ['user_id' => $employeeId]))) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::error('Error sending notification to employee: '.$e->getMessage(), [
                    'employee_id' => $employeeId,
                ]);
            }
        }

        return $count;
    }

    /**
     * إرسال إشعار لجميع الموظفين
     */
    public static function sendToAllEmployees($data)
    {
        $employeeIds = User::where('is_employee', true)
                          ->where('is_active', true)
                          ->pluck('id');
        return self::sendToEmployees($employeeIds, $data);
    }
}
