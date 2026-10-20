<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStudentRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public function __construct(
        public int $userId,
        public ?string $referralCode = null,
        public ?string $deviceFingerprint = null
    ) {}

    public function handle(): void
    {
        try {
            $user = User::findOrFail($this->userId);
            $referralService = app(ReferralService::class);

            if ($this->referralCode) {
                $referralCode = strtoupper(trim($this->referralCode));
                $referrer = User::where('referral_code', $referralCode)->first();

                if ($referrer && (int) $referrer->id !== (int) $user->id) {
                    $referralService->processReferral(
                        $referrer,
                        $user,
                        $referralCode,
                        $this->deviceFingerprint
                    );
                }
            }

            $referralService->generateReferralCode($user);

            app(\App\Services\StatisticsCacheService::class)->clearStats('user_stats');

            try {
                app(\App\Services\PlatformNotifier::class)->notify($user, [
                    'title' => 'مرحباً بك في حصتك',
                    'message' => 'تم إنشاء حسابك بنجاح. ابدأ بحجز حصة أو استكشف المناهج والكورسات من لوحة الطالب.',
                    'type' => 'system',
                    'priority' => 'normal',
                    'action_url' => '/dashboard',
                    'action_text' => 'لوحة الطالب',
                    'sender_id' => null,
                    'data' => ['source' => 'student_registration'],
                ]);
            } catch (\Throwable $notifyError) {
                Log::warning('Welcome notification failed', [
                    'user_id' => $user->id,
                    'error' => $notifyError->getMessage(),
                ]);
            }

            Log::info("تم معالجة تسجيل الطالب بنجاح: {$user->id}");
        } catch (\Exception $e) {
            Log::error("خطأ في معالجة تسجيل الطالب: {$e->getMessage()}", [
                'user_id' => $this->userId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
