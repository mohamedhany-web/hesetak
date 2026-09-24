<?php

namespace App\Services;

use App\Models\InstructorAgreement;
use App\Models\TutorApplication;
use App\Models\TutorHiringSetting;
use App\Models\TutorInterview;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mpdf\Mpdf;

class TutorContractService
{
    public function __construct(
        protected TutorHiringNotifyService $notify,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function offer(TutorApplication $application, User $admin, array $payload): InstructorAgreement
    {
        TeacherSpecialtyMatcher::assertComplete($application);

        if (TutorHiringSetting::bool('require_interview', true)
            && ! in_array($application->status, [
                TutorApplication::STATUS_INTERVIEW_PASSED,
                TutorApplication::STATUS_CONTRACT_PENDING,
                TutorApplication::STATUS_CONTRACT_SIGNED,
                TutorApplication::STATUS_APPROVED,
            ], true)) {
            throw ValidationException::withMessages([
                'status' => 'أكمل المقابلة التقنية قبل إرسال عرض العقد.',
            ]);
        }

        $allowedStatuses = [
            TutorApplication::STATUS_INTERVIEW_PASSED,
            TutorApplication::STATUS_CONTRACT_PENDING,
            TutorApplication::STATUS_CONTRACT_SIGNED,
            TutorApplication::STATUS_PENDING,
            TutorApplication::STATUS_APPROVED,
        ];

        if (! TutorHiringSetting::bool('require_interview', true)) {
            $allowedStatuses[] = TutorApplication::STATUS_PENDING;
        }

        if (! in_array($application->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن إرسال عقد لهذا الطلب في حالته الحالية.',
            ]);
        }

        $instructorId = $application->user_id;
        if (! $instructorId) {
            throw ValidationException::withMessages([
                'user' => 'لا يوجد حساب مرتبط بالطلب.',
            ]);
        }

        return DB::transaction(function () use ($application, $admin, $payload, $instructorId) {
            $token = Str::random(48);

            $agreement = InstructorAgreement::create([
                'instructor_id' => $instructorId,
                'tutor_application_id' => $application->id,
                'billing_type' => $payload['billing_type'] ?? InstructorAgreement::BILLING_PER_SESSION,
                'type' => $payload['billing_type'] ?? InstructorAgreement::BILLING_PER_SESSION,
                'rate' => $payload['rate'] ?? $payload['salary_per_session'] ?? null,
                'salary_per_session' => $payload['salary_per_session'] ?? $payload['rate'] ?? null,
                'monthly_amount' => $payload['monthly_amount'] ?? null,
                'agreement_number' => InstructorAgreement::generateAgreementNumber(),
                'title' => $payload['title'] ?? ('عقد تعاون — '.$application->full_name),
                'description' => $payload['description'] ?? null,
                'terms' => $payload['terms'] ?? '',
                'notes' => $payload['notes'] ?? null,
                'start_date' => $payload['start_date'] ?? now()->toDateString(),
                'end_date' => $payload['end_date'] ?? null,
                'status' => InstructorAgreement::STATUS_DRAFT,
                'created_by' => $admin->id,
                'offered_at' => now(),
                'offer_token' => $token,
            ]);

            $application->update([
                'status' => TutorApplication::STATUS_CONTRACT_PENDING,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            $signUrl = route('tutor.contract.sign', ['token' => $token]);
            $this->notify->notifyContractOffered($agreement, $application->fresh(), $signUrl);

            return $agreement->fresh();
        });
    }

    public function sign(InstructorAgreement $agreement, string $signerName, UploadedFile|string $signatureData, string $ip, string $userAgent): InstructorAgreement
    {
        if ($agreement->isSigned()) {
            throw ValidationException::withMessages([
                'contract' => 'تم توقيع هذا العقد مسبقاً.',
            ]);
        }

        $application = $agreement->tutorApplication;
        if (! $application) {
            throw ValidationException::withMessages([
                'contract' => 'العقد غير مرتبط بطلب توظيف.',
            ]);
        }

        return DB::transaction(function () use ($agreement, $signerName, $signatureData, $ip, $userAgent, $application) {
            $signaturePath = $this->storeSignature($agreement, $signatureData);
            $pdfPath = $this->generatePdf($agreement, $signerName, $signaturePath);

            $agreement->update([
                'signer_name' => $signerName,
                'signature_path' => $signaturePath,
                'pdf_path' => $pdfPath,
                'signed_at' => now(),
                'candidate_ip' => $ip,
                'candidate_user_agent' => Str::limit($userAgent, 480),
                'status' => InstructorAgreement::STATUS_ACTIVE,
            ]);

            $application->update([
                'status' => TutorApplication::STATUS_CONTRACT_SIGNED,
            ]);

            return $agreement->fresh();
        });
    }

    public function assertReadyForActivation(TutorApplication $application): void
    {
        TeacherSpecialtyMatcher::assertComplete($application);

        if (TutorHiringSetting::bool('require_interview', true)) {
            $passed = $application->interviews()
                ->where('result', TutorInterview::RESULT_PASS)
                ->exists()
                || in_array($application->status, [
                    TutorApplication::STATUS_INTERVIEW_PASSED,
                    TutorApplication::STATUS_CONTRACT_PENDING,
                    TutorApplication::STATUS_CONTRACT_SIGNED,
                    TutorApplication::STATUS_APPROVED,
                    TutorApplication::STATUS_ACTIVATED,
                ], true);

            if (! $passed) {
                throw ValidationException::withMessages([
                    'interview' => 'المقابلة التقنية إلزامية قبل التفعيل.',
                ]);
            }
        }

        if (TutorHiringSetting::bool('require_contract', true)) {
            $signed = InstructorAgreement::query()
                ->where('tutor_application_id', $application->id)
                ->whereNotNull('signed_at')
                ->exists();

            if (! $signed && $application->status !== TutorApplication::STATUS_CONTRACT_SIGNED) {
                throw ValidationException::withMessages([
                    'contract' => 'توقيع العقد إلزامي قبل التفعيل.',
                ]);
            }
        }
    }

    protected function storeSignature(InstructorAgreement $agreement, UploadedFile|string $signatureData): string
    {
        $disk = TutorApplicationStorage::resolvedDisk();
        $dir = 'tutor-applications/signatures';
        $filename = 'agr-'.$agreement->id.'-'.Str::random(10).'.png';

        if ($signatureData instanceof UploadedFile) {
            return (string) $signatureData->storeAs($dir, $filename, $disk);
        }

        $raw = $signatureData;
        if (str_contains($raw, 'base64,')) {
            $raw = substr($raw, strpos($raw, 'base64,') + 7);
        }
        $binary = base64_decode($raw, true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                'signature' => 'توقيع غير صالح.',
            ]);
        }

        $path = $dir.'/'.$filename;
        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    protected function generatePdf(InstructorAgreement $agreement, string $signerName, string $signaturePath): string
    {
        $agreement->loadMissing(['instructor', 'tutorApplication']);
        $disk = TutorApplicationStorage::resolvedDisk();

        $signatureDataUri = null;
        if (Storage::disk($disk)->exists($signaturePath)) {
            $bytes = Storage::disk($disk)->get($signaturePath);
            $signatureDataUri = 'data:image/png;base64,'.base64_encode($bytes);
        }

        $html = view('pdf.tutor-instructor-agreement', [
            'agreement' => $agreement,
            'signerName' => $signerName,
            'signatureDataUri' => $signatureDataUri,
            'appName' => config('app.name', 'حصتك'),
        ])->render();

        if (! class_exists(Mpdf::class)) {
            throw new \RuntimeException('مكتبة PDF غير متوفرة.');
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 14,
            'margin_bottom' => 14,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $relativePath = 'tutor-applications/contracts/agr-'.$agreement->id.'-'.Str::random(8).'.pdf';
        Storage::disk($disk)->put($relativePath, $mpdf->Output('', 'S'));

        return $relativePath;
    }
}
