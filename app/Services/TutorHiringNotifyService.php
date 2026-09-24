<?php

namespace App\Services;

use App\Mail\TutorContractOfferedMail;
use App\Mail\TutorInterviewScheduledMail;
use App\Models\TutorApplication;
use App\Models\TutorHiringSetting;
use App\Models\TutorInterview;
use App\Models\InstructorAgreement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TutorHiringNotifyService
{
    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    public function notifyInterviewScheduled(TutorInterview $interview): void
    {
        $interview->loadMissing('application');
        $app = $interview->application;
        if (! $app) {
            return;
        }

        $vars = [
            'name' => $app->full_name ?: 'معلم',
            'datetime' => $interview->scheduled_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—',
            'join_url' => $interview->effectiveJoinUrl(),
            'app_name' => config('app.name', 'حصتك'),
        ];

        $subject = $this->render(TutorHiringSetting::get('interview_email_subject'), $vars);
        $emailBody = $this->render(TutorHiringSetting::get('interview_email_body'), $vars);
        $waBody = $this->render(TutorHiringSetting::get('interview_whatsapp_body'), $vars);

        if (filled($app->email)) {
            try {
                Mail::to($app->email)->send(new TutorInterviewScheduledMail($app, $interview, $subject, $emailBody));
            } catch (\Throwable $e) {
                Log::warning('tutor_interview_email_failed', ['error' => $e->getMessage(), 'application_id' => $app->id]);
            }
        }

        if (filled($app->phone)) {
            try {
                $this->whatsApp->sendMessage((string) $app->phone, $waBody);
            } catch (\Throwable $e) {
                Log::warning('tutor_interview_whatsapp_failed', ['error' => $e->getMessage(), 'application_id' => $app->id]);
            }
        }
    }

    public function notifyContractOffered(InstructorAgreement $agreement, TutorApplication $application, string $signUrl): void
    {
        $vars = [
            'name' => $application->full_name ?: 'معلم',
            'sign_url' => $signUrl,
            'app_name' => config('app.name', 'حصتك'),
        ];

        $subject = $this->render(TutorHiringSetting::get('contract_email_subject'), $vars);
        $emailBody = $this->render(TutorHiringSetting::get('contract_email_body'), $vars);
        $waBody = $this->render(TutorHiringSetting::get('contract_whatsapp_body'), $vars);

        if (filled($application->email)) {
            try {
                Mail::to($application->email)->send(new TutorContractOfferedMail($application, $agreement, $subject, $emailBody, $signUrl));
            } catch (\Throwable $e) {
                Log::warning('tutor_contract_email_failed', ['error' => $e->getMessage(), 'application_id' => $application->id]);
            }
        }

        if (filled($application->phone)) {
            try {
                $this->whatsApp->sendMessage((string) $application->phone, $waBody);
            } catch (\Throwable $e) {
                Log::warning('tutor_contract_whatsapp_failed', ['error' => $e->getMessage(), 'application_id' => $application->id]);
            }
        }
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function render(?string $template, array $vars): string
    {
        $template = (string) ($template ?: '');
        foreach ($vars as $key => $value) {
            $template = str_replace('{'.$key.'}', (string) $value, $template);
        }

        return $template;
    }
}
