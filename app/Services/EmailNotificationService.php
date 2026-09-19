<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * إرسال رسالة بريد إلكتروني لمستخدم واحد.
     */
    public function sendToUser(User $user, string $message, ?string $subject = null): array
    {
        if (empty($user->email)) {
            return [
                'success' => false,
                'error' => 'لا يوجد بريد إلكتروني مرفوع لهذا المستخدم.',
            ];
        }

        $subject = $subject ?: 'رسالة من منصة حصتك';

        try {
            Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)
                    ->subject($subject);
            });

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('Email send failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'تعذر إرسال البريد الإلكتروني: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * إرسال رسالة بريدية جماعية.
     *
     * @param iterable<User> $users
     */
    public function sendBulk(iterable $users, string $message, ?string $subject = null): array
    {
        $success = 0;
        $failed = 0;

        foreach ($users as $user) {
            $result = $this->sendToUser($user, $message, $subject);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
        ];
    }
}

