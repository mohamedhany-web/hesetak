<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * نقطة مركزية لإشعارات المنصة: داخل التطبيق + بريد (عبر Notification::dispatchEmailCopy).
 */
class PlatformNotifier
{
    /**
     * @param  array{
     *   title: string,
     *   message: string,
     *   type?: string,
     *   priority?: string,
     *   action_url?: ?string,
     *   action_text?: ?string,
     *   sender_id?: ?int,
     *   data?: array<string, mixed>,
     *   skip_email?: bool
     * }  $payload
     */
    public function notify(User|int $user, array $payload): ?Notification
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;
        if ($userId <= 0) {
            return null;
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        if ($title === '' || $message === '') {
            Log::warning('PlatformNotifier skipped empty notification', ['user_id' => $userId]);

            return null;
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        if (! empty($payload['skip_email'])) {
            $data['skip_email'] = true;
        }

        try {
            return Notification::create([
                'user_id' => $userId,
                'sender_id' => $payload['sender_id'] ?? auth()->id(),
                'title' => mb_substr($title, 0, 190),
                'message' => $message,
                'type' => $payload['type'] ?? 'system',
                'priority' => $payload['priority'] ?? 'normal',
                'action_url' => $payload['action_url'] ?? null,
                'action_text' => $payload['action_text'] ?? null,
                'data' => $data,
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('PlatformNotifier failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function notifyMany(iterable $users, array $payload): int
    {
        $count = 0;
        foreach ($users as $user) {
            if ($this->notify($user, $payload)) {
                $count++;
            }
        }

        return $count;
    }

    public function notifyStudents(array $payload): int
    {
        $ids = User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->pluck('id');

        return $this->notifyMany($ids, $payload);
    }
}
