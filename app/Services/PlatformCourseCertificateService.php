<?php

namespace App\Services;

use App\Models\AdvancedCourse;
use App\Models\Certificate;
use App\Models\StudentCourseEnrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Throwable;

class PlatformCourseCertificateService
{
    /**
     * إصدار شهادة المنصة (PDF) عند اكتمال التسجيل — لا يُنشئ نسخاً مكررة لنفس الطالب والكورس.
     */
    public function issueIfEligible(?StudentCourseEnrollment $enrollment): ?Certificate
    {
        if (! $enrollment || ! config('certificates.enabled', false) || ! config('certificates.platform_auto_issue', false)) {
            return null;
        }

        if ($enrollment->status !== 'completed') {
            return null;
        }

        $user = User::find($enrollment->user_id);
        $course = AdvancedCourse::find($enrollment->advanced_course_id);
        if (! $user || ! $course) {
            return null;
        }

        if ($this->platformCertificateExists((int) $user->id, (int) $course->id)) {
            return null;
        }

        try {
            return $this->createPlatformCertificate($user, $course, now(), [
                'source' => 'platform_auto',
                'design' => 'platform_academic_v1',
            ]);
        } catch (Throwable $e) {
            Log::warning('platform_certificate_issue_failed', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * إصدار شهادة تصميم المنصة من لوحة الإدارة (يختار الأدمن الطالب والكورس).
     */
    public function issueForAdmin(User $user, AdvancedCourse $course, ?Carbon $issueAt = null): ?Certificate
    {
        if ($this->platformCertificateExists((int) $user->id, (int) $course->id)) {
            return null;
        }

        return $this->createPlatformCertificate($user, $course, $issueAt ?? now(), [
            'source' => 'platform_admin',
            'design' => 'platform_academic_v1',
        ]);
    }

    /**
     * HTML للمعاينة (مع طابع معاينة اختياري).
     */
    public function renderPreviewHtml(User $user, AdvancedCourse $course, bool $watermark = false): string
    {
        $issueCarbon = now();
        $fakeCert = 'CERT-PREVIEW';
        $fakeCode = 'MX-PREVIEW-DEMO';
        $verificationUrl = url('/');

        $data = $this->pdfViewData($user, $course, $issueCarbon, $fakeCert, $fakeCode, $verificationUrl);
        $data['previewWatermark'] = $watermark;

        return view('pdf.certificates.platform-academic', $data)->render();
    }

    /**
     * @return array<string, mixed>
     */
    public function prefillPayload(User $user, AdvancedCourse $course): array
    {
        return [
            'title' => 'شهادة إتمام دورة — '.$course->title,
            'description' => 'إتمام بنجاح الدورة التدريبية: '.$course->title,
            'course_title' => $course->title,
            'student_name' => $user->name,
        ];
    }

    private function platformCertificateExists(int $userId, int $courseId): bool
    {
        return Certificate::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('template', 'platform_academic')
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $metadataExtra
     */
    private function createPlatformCertificate(User $user, AdvancedCourse $course, Carbon $issueCarbon, array $metadataExtra): Certificate
    {
        $verificationCode = $this->uniqueVerificationCode();
        $certNo = $this->uniqueCertificateNumber();
        $serial = Certificate::generateSerialNumber();

        $verificationUrl = route('public.certificates.verify.code', ['code' => $verificationCode]);

        $html = view('pdf.certificates.platform-academic', $this->pdfViewData(
            $user,
            $course,
            $issueCarbon,
            $certNo,
            $verificationCode,
            $verificationUrl
        ))->render();

        if (! class_exists(Mpdf::class)) {
            throw new \RuntimeException(
                'مكتبة PDF (mpdf/mpdf) غير موجودة في الخادم. من مجلد المشروع نفّذ: composer install --no-dev --optimize-autoloader'
            );
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $relativePath = 'certificates/'.$user->id.'/platform-'.Str::uuid().'.pdf';
        Storage::disk('public')->put($relativePath, $mpdf->Output('', 'S'));

        $academy = trim((string) config('certificates.academy_name', '')) ?: (string) config('app.name');
        $instructor = $course->instructor;
        $instructorName = $instructor?->name ?? 'المدرب المعتمد';
        $instructorTitle = 'مدرب الدورة';

        $metadata = array_merge([
            'design' => 'platform_academic_v1',
        ], $metadataExtra);

        $row = [
            'certificate_number' => $certNo,
            'serial_number' => $serial,
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_name' => $course->title,
            'certificate_type' => 'completion',
            'title' => 'شهادة إتمام دورة',
            'description' => 'إتمام بنجاح: '.$course->title,
            'issue_date' => $issueCarbon->toDateString(),
            'template' => 'platform_academic',
            'pdf_path' => $relativePath,
            'verification_code' => $verificationCode,
            'verification_url' => $verificationUrl,
            'metadata' => $metadata,
            'is_verified' => true,
            'is_public' => false,
            'status' => 'issued',
            'issued_at' => $issueCarbon->toDateString(),
            'academy_signature_name' => (string) config('certificates.director_name', 'المدير العام'),
            'academy_signature_title' => $academy,
            'instructor_signature_name' => $instructorName,
            'instructor_signature_title' => $instructorTitle,
            'instructor_id' => $course->instructor_id,
        ];

        if (Schema::hasColumn('certificates', 'certified_at')) {
            $row['certified_at'] = $issueCarbon;
        }

        $row = $this->filterCertificateColumns($row);

        $certificate = Certificate::create($row);

        if (Schema::hasColumn('certificates', 'certificate_hash')) {
            $certificate->certificate_hash = $certificate->generateHash();
            $certificate->save();
        }

        return $certificate;
    }

    /**
     * @return array<string, mixed>
     */
    private function pdfViewData(
        User $user,
        AdvancedCourse $course,
        Carbon $issueCarbon,
        string $certificateNumber,
        string $verificationCode,
        string $verificationUrl
    ): array {
        $academy = trim((string) config('certificates.academy_name', '')) ?: (string) config('app.name');
        $instructor = $course->instructor;
        $instructorName = $instructor?->name ?? 'المدرب المعتمد';
        $instructorTitle = 'مدرب الدورة';

        return [
            'academyName' => $academy,
            'studentName' => $user->name,
            'courseDisplayName' => $course->title,
            'issueDateFormatted' => $issueCarbon->format('Y/m/d'),
            'certificateNumber' => $certificateNumber,
            'verificationCode' => $verificationCode,
            'verificationUrl' => $verificationUrl,
            'directorName' => (string) config('certificates.director_name', 'المدير العام'),
            'directorTitle' => (string) config('certificates.director_title', 'الإدارة التنفيذية'),
            'instructorName' => $instructorName,
            'instructorTitle' => $instructorTitle,
            'logoDataUri' => $this->resolveLogoDataUri(),
            'primaryColor' => (string) config('certificates.primary', '#283593'),
            'secondaryColor' => (string) config('certificates.secondary', '#FB5607'),
            'creamBg' => (string) config('certificates.cream', '#FDFBF7'),
            'accentLight' => (string) config('certificates.accent_light', '#FFE5F7'),
            'previewWatermark' => false,
        ];
    }

    private function uniqueVerificationCode(): string
    {
        for ($i = 0; $i < 40; $i++) {
            $code = strtoupper('MX-'.substr(sha1(uniqid('', true)), 0, 14));
            if (! Certificate::where('verification_code', $code)->exists()) {
                return $code;
            }
        }

        return strtoupper('MX-'.str_replace('-', '', Str::uuid()->toString()));
    }

    private function uniqueCertificateNumber(): string
    {
        for ($i = 0; $i < 40; $i++) {
            $n = 'CERT-'.str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
            if (! Certificate::where('certificate_number', $n)->exists()) {
                return $n;
            }
        }

        return 'CERT-'.strtoupper(substr(sha1((string) microtime(true)), 0, 10));
    }

    private function resolveLogoDataUri(): ?string
    {
        foreach ([public_path('icons/icon-192.png'), public_path('icons/icon-512.png')] as $p) {
            if (is_file($p)) {
                return 'data:image/png;base64,'.base64_encode((string) file_get_contents($p));
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function filterCertificateColumns(array $data): array
    {
        try {
            $columns = Schema::getColumnListing((new Certificate)->getTable());

            return array_intersect_key($data, array_fill_keys($columns, true));
        } catch (Throwable) {
            return $data;
        }
    }
}
