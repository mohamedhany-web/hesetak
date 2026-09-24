<?php

namespace App\Services;

use App\Mail\ManualInstructorHiredMail;
use App\Models\InstructorProfile;
use App\Models\Notification;
use App\Models\TutorApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TutorApplicationActivationService
{
    /**
     * تفعيل حساب المعلم بعد قبول الطلب: يفتح لوحة التحكم ويظهر الملف للعامة.
     *
     * @return array{user: User, profile: InstructorProfile, password: ?string}
     */
    public static function activate(TutorApplication $application, User $admin, ?string $password = null): array
    {
        if ($application->status === TutorApplication::STATUS_ACTIVATED && $application->user_id) {
            throw new InvalidArgumentException('تم تفعيل هذا الطلب مسبقاً.');
        }

        if ($application->status === TutorApplication::STATUS_REJECTED) {
            throw new InvalidArgumentException('لا يمكن تفعيل طلب مرفوض.');
        }

        if ($application->status === TutorApplication::STATUS_DRAFT) {
            throw new InvalidArgumentException('المتقدم لم يُكمل بياناته بعد.');
        }

        if ($application->status === TutorApplication::STATUS_PENDING) {
            throw new InvalidArgumentException('راجع الطلب واقبله أولاً ثم فعّل الملف العام.');
        }

        $user = $application->user_id
            ? User::query()->find($application->user_id)
            : null;

        if (! $user) {
            throw new InvalidArgumentException('لا يوجد حساب مرتبط بهذا الطلب. يجب أن يسجّل المتقدم أولاً بالإيميل وكلمة المرور.');
        }

        return DB::transaction(function () use ($application, $admin, $user) {
            $introVideoUrl = filled($application->intro_video_url)
                ? trim((string) $application->intro_video_url)
                : TutorApplicationStorage::publicUrl($application->intro_video_path);

            $user->forceFill([
                'name' => trim((string) $application->full_name) ?: $user->name,
                'phone' => self::normalizePhone($application->phone) ?: $user->phone,
                'role' => 'instructor',
                'is_active' => true,
                'gender' => in_array($application->gender, ['male', 'female'], true) ? $application->gender : $user->gender,
                'bio' => $application->bio ?: $user->bio,
                'portfolio_intro_video_url' => $introVideoUrl ?: $user->portfolio_intro_video_url,
                'profile_image' => $application->photo_path ?: $user->profile_image,
            ])->save();

            $profile = InstructorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'headline' => $application->headline,
                    'bio' => $application->bio,
                    'experience' => $application->experience,
                    'skills' => self::skillsFromExperience($application),
                    'photo_path' => $application->photo_path,
                    'teaching_subject_ids' => $application->teaching_subject_ids ?? [],
                    'curriculum_types' => $application->curriculum_types ?? [],
                    'status' => InstructorProfile::STATUS_APPROVED,
                    'submitted_at' => $application->created_at ?? now(),
                    'reviewed_at' => now(),
                    'reviewed_by' => $admin->id,
                    'rejection_reason' => null,
                    'social_links' => [],
                ]
            );

            $application->update([
                'status' => TutorApplication::STATUS_ACTIVATED,
                'user_id' => $user->id,
                'activated_at' => now(),
                'activated_by' => $admin->id,
                'reviewed_at' => $application->reviewed_at ?? now(),
                'reviewed_by' => $application->reviewed_by ?? $admin->id,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'sender_id' => $admin->id,
                'title' => 'تم تفعيل حسابك كمعلّم',
                'message' => 'مرحباً '.$user->name.' — تم تفعيل حسابك. يمكنك الآن تسجيل الدخول واستخدام لوحة المعلم.',
                'type' => 'general',
                'priority' => 'high',
                'audience' => 'instructor',
                'action_url' => route('dashboard'),
                'action_text' => 'لوحة المعلم',
            ]);

            return [
                'user' => $user->fresh(),
                'profile' => $profile->fresh(),
                'password' => null,
            ];
        });
    }

    /**
     * توظيف معلم مباشرة بالإيميل دون مسار التقديم العام.
     *
     * @return array{user: User, application: TutorApplication, password: ?string, created: bool, mail_sent: bool}
     */
    public static function hireManuallyByEmail(User $admin, string $email, string $name, ?string $phone = null): array
    {
        $email = strtolower(trim($email));
        $name = trim($name);
        $phone = self::normalizePhone($phone);

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('أدخل بريداً إلكترونياً صحيحاً.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('الاسم الكامل مطلوب.');
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing) {
            if (! $existing->isInstructor()) {
                throw new InvalidArgumentException('هذا البريد مرتبط بحساب قائم ('.$existing->role.'). لا يمكن تحويله من هنا.');
            }

            $latest = $existing->latestTutorApplication();
            if ($latest && $latest->isActivated()) {
                throw new InvalidArgumentException('هذا المعلم مفعّل مسبقاً.');
            }
        }

        if ($phone) {
            $phoneTaken = User::query()
                ->where('phone', $phone)
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->exists();
            if ($phoneTaken) {
                throw new InvalidArgumentException('رقم الجوال مسجّل مسبقاً على حساب آخر.');
            }
        }

        $plainPassword = $existing ? null : Str::password(12);

        $result = DB::transaction(function () use ($admin, $email, $name, $phone, $existing, $plainPassword) {
            if ($existing) {
                $user = $existing;
                $user->forceFill([
                    'name' => $name ?: $user->name,
                    'phone' => $phone ?: $user->phone,
                    'role' => 'instructor',
                    'is_active' => true,
                ])->save();
            } else {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $plainPassword,
                    'role' => 'instructor',
                    'is_active' => true,
                ]);
            }

            $application = $user->latestTutorApplication();
            if (! $application) {
                $application = TutorApplication::create([
                    'user_id' => $user->id,
                    'full_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'headline' => 'معلم معيَّن يدوياً',
                    'status' => TutorApplication::STATUS_APPROVED,
                    'admin_notes' => 'توظيف يدوي بالإيميل',
                    'reviewed_at' => now(),
                    'reviewed_by' => $admin->id,
                ]);
            } else {
                $application->update([
                    'full_name' => $name,
                    'email' => $email,
                    'phone' => $phone ?: $application->phone,
                    'status' => TutorApplication::STATUS_APPROVED,
                    'admin_notes' => trim((string) $application->admin_notes."\nتوظيف يدوي بالإيميل"),
                    'reviewed_at' => $application->reviewed_at ?? now(),
                    'reviewed_by' => $application->reviewed_by ?? $admin->id,
                ]);
            }

            $activated = self::activate($application->fresh(), $admin);

            return [
                'user' => $activated['user'],
                'created' => $existing === null,
            ];
        });

        $application = $result['user']->latestTutorApplication();
        $mailSent = self::sendHireMail($result['user'], $plainPassword);

        return [
            'user' => $result['user'],
            'application' => $application,
            'password' => $plainPassword,
            'created' => $result['created'],
            'mail_sent' => $mailSent,
        ];
    }

    private static function sendHireMail(User $user, ?string $password): bool
    {
        try {
            Mail::to($user->email)->send(new ManualInstructorHiredMail($user, $password));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Manual instructor hire email failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private static function normalizePhone(?string $phone): ?string
    {
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', trim($phone));

        return $digits !== '' ? $digits : null;
    }

    private static function skillsFromExperience(TutorApplication $application): ?string
    {
        $parts = array_filter([
            $application->education,
            $application->headline,
            $application->years_experience !== null ? ($application->years_experience.' سنوات خبرة') : null,
            $application->nationality,
            $application->city,
        ]);

        return $parts === [] ? null : implode("\n", $parts);
    }
}
