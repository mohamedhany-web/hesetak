<?php

namespace App\Services;

use App\Mail\EmployeeAgreementAcceptanceCodeMail;
use App\Mail\EmployeeAgreementSignedMail;
use App\Models\EmployeeAgreement;
use App\Models\EmployeeAgreementVersion;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mpdf\Mpdf;

class EmployeeAgreementService
{
    /** @return list<string> */
    public static function statusesRequiringCompleteContract(): array
    {
        return ['active'];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(string $status): array
    {
        $requiresComplete = in_array($status, self::statusesRequiringCompleteContract(), true);

        return [
            'employee_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'salary' => $requiresComplete ? 'required|numeric|min:0.01' : 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:draft,active,suspended,terminated,completed',
            'contract_terms' => $requiresComplete ? 'required|string|min:40' : 'nullable|string',
            'agreement_terms' => $requiresComplete ? 'required|string|min:40' : 'nullable|string',
            'notes' => 'nullable|string|max:5000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationMessages(): array
    {
        return [
            'salary.min' => 'عند تفعيل الاتفاقية يجب أن يكون الراتب أكبر من صفر.',
            'contract_terms.required' => 'شروط العقد مطلوبة عند نقل الحالة إلى سارية/نشط.',
            'contract_terms.min' => 'شروط العقد قصيرة جداً؛ أضف بنوداً تشغيلية واضحة.',
            'agreement_terms.required' => 'بنود الاتفاقية مطلوبة عند نقل الحالة إلى سارية/نشط.',
            'agreement_terms.min' => 'بنود الاتفاقية قصيرة جداً؛ أضف بنوداً قانونية واضحة.',
            'title.required' => 'عنوان الاتفاقية مطلوب.',
            'start_date.required' => 'تاريخ البدء مطلوب.',
        ];
    }

    public function assertEmployeeIsStaff(int $employeeId): void
    {
        $ok = User::query()
            ->whereKey($employeeId)
            ->where('is_employee', true)
            ->where('is_active', true)
            ->exists();

        if (! $ok) {
            throw ValidationException::withMessages([
                'employee_id' => 'المستخدم المحدد ليس موظفاً نشطاً في النظام.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function applyUpdate(EmployeeAgreement $agreement, array $validated, ?User $actor = null): EmployeeAgreement
    {
        if ($agreement->isSigned() && $this->isMaterialChange($agreement, $validated)) {
            $this->archiveSignedVersion($agreement, $actor);
            $validated['version'] = ((int) $agreement->version) + 1;
            $validated['signed_at'] = null;
            $validated['signer_name'] = null;
            $validated['signature_path'] = null;
            $validated['signed_ip'] = null;
            $validated['signed_user_agent'] = null;
            $validated['pdf_path'] = null;
            $validated['acceptance_otp_hash'] = null;
            $validated['acceptance_otp_expires_at'] = null;
            $validated['acceptance_sent_at'] = null;

            if (($validated['status'] ?? $agreement->status) === 'active') {
                // إصدار جديد بعد التوقيع يتطلب إعادة موافقة قبل اعتباره سارياً قانونياً
                $validated['status'] = 'draft';
            }
        }

        $agreement->update($validated);

        return $agreement->fresh();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function isMaterialChange(EmployeeAgreement $agreement, array $validated): bool
    {
        $fields = ['title', 'description', 'salary', 'start_date', 'end_date', 'contract_terms', 'agreement_terms', 'employee_id'];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $old = $agreement->{$field};
            $new = $validated[$field];

            if ($field === 'salary') {
                if (round((float) $old, 2) !== round((float) $new, 2)) {
                    return true;
                }
                continue;
            }

            if (in_array($field, ['start_date', 'end_date'], true)) {
                $oldStr = $old ? (string) $old->format('Y-m-d') : '';
                $newStr = $new ? (string) $new : '';
                if ($oldStr !== $newStr) {
                    return true;
                }
                continue;
            }

            if ((string) ($old ?? '') !== (string) ($new ?? '')) {
                return true;
            }
        }

        return false;
    }

    protected function archiveSignedVersion(EmployeeAgreement $agreement, ?User $actor): void
    {
        EmployeeAgreementVersion::create([
            'employee_agreement_id' => $agreement->id,
            'version' => (int) $agreement->version,
            'title' => $agreement->title,
            'description' => $agreement->description,
            'salary' => $agreement->salary,
            'start_date' => $agreement->start_date,
            'end_date' => $agreement->end_date,
            'status' => $agreement->status,
            'contract_terms' => $agreement->contract_terms,
            'agreement_terms' => $agreement->agreement_terms,
            'notes' => $agreement->notes,
            'pdf_path' => $agreement->pdf_path,
            'signed_at' => $agreement->signed_at,
            'signer_name' => $agreement->signer_name,
            'signature_path' => $agreement->signature_path,
            'archived_by' => $actor?->id,
        ]);
    }

    public function sendAcceptanceOtp(EmployeeAgreement $agreement): string
    {
        $agreement->loadMissing('employee');

        if ($agreement->isSigned()) {
            throw ValidationException::withMessages([
                'agreement' => 'هذه الاتفاقية موقّعة مسبقاً.',
            ]);
        }

        $this->assertReadyForAcceptance($agreement);

        $email = trim((string) ($agreement->employee?->email ?? ''));
        if ($email === '') {
            throw ValidationException::withMessages([
                'agreement' => 'لا يوجد بريد إلكتروني للموظف؛ لا يمكن إرسال رمز التأكيد.',
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $agreement->update([
            'acceptance_otp_hash' => Hash::make($code),
            'acceptance_otp_expires_at' => now()->addMinutes(30),
            'acceptance_sent_at' => now(),
        ]);

        Mail::to($email)->send(new EmployeeAgreementAcceptanceCodeMail($agreement->fresh(), $code));

        return $code;
    }

    public function acceptWithOtp(
        EmployeeAgreement $agreement,
        string $otp,
        string $signerName,
        string $ip,
        string $userAgent,
    ): EmployeeAgreement {
        if ($agreement->isSigned()) {
            throw ValidationException::withMessages([
                'otp' => 'تم توقيع هذه الاتفاقية مسبقاً.',
            ]);
        }

        $this->assertReadyForAcceptance($agreement);

        if (! $agreement->acceptance_otp_hash || ! $agreement->acceptance_otp_expires_at) {
            throw ValidationException::withMessages([
                'otp' => 'لم يُرسل رمز تأكيد بعد. اطلب إرسال الرمز من الإدارة أو أعد طلبه.',
            ]);
        }

        if ($agreement->acceptance_otp_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => 'انتهت صلاحية رمز التأكيد. اطلب رمزاً جديداً.',
            ]);
        }

        if (! Hash::check(trim($otp), $agreement->acceptance_otp_hash)) {
            throw ValidationException::withMessages([
                'otp' => 'رمز التأكيد غير صحيح.',
            ]);
        }

        $signerName = trim($signerName);
        if ($signerName === '') {
            throw ValidationException::withMessages([
                'signer_name' => 'الاسم عند الموافقة مطلوب.',
            ]);
        }

        $pdfPath = $this->generateAndStorePdf($agreement, $signerName);

        $agreement->update([
            'signed_at' => now(),
            'signer_name' => $signerName,
            'pdf_path' => $pdfPath,
            'signed_ip' => mb_substr($ip, 0, 45),
            'signed_user_agent' => mb_substr($userAgent, 0, 512),
            'acceptance_otp_hash' => null,
            'acceptance_otp_expires_at' => null,
            'status' => $agreement->status === 'draft' ? 'active' : $agreement->status,
        ]);

        $fresh = $agreement->fresh(['employee', 'creator']);
        $this->emailSignedCopies($fresh);

        return $fresh;
    }

    public function assertReadyForAcceptance(EmployeeAgreement $agreement): void
    {
        $errors = [];
        if (trim((string) $agreement->title) === '') {
            $errors['title'] = 'عنوان الاتفاقية ناقص.';
        }
        if (! $agreement->start_date) {
            $errors['start_date'] = 'تاريخ البدء ناقص.';
        }
        if ((float) $agreement->salary <= 0) {
            $errors['salary'] = 'يجب أن يكون الراتب أكبر من صفر قبل الموافقة.';
        }
        if (mb_strlen(trim((string) $agreement->contract_terms)) < 40) {
            $errors['contract_terms'] = 'شروط العقد ناقصة.';
        }
        if (mb_strlen(trim((string) $agreement->agreement_terms)) < 40) {
            $errors['agreement_terms'] = 'بنود الاتفاقية ناقصة.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function generateAndStorePdf(EmployeeAgreement $agreement, ?string $signerName = null): string
    {
        $agreement->loadMissing(['employee.employeeJob', 'creator']);

        $html = view('pdf.employee-agreement', [
            'agreement' => $agreement,
            'signerName' => $signerName ?? $agreement->signer_name,
            'appName' => config('app.name', 'حصتك'),
            'currency' => function_exists('currency_unit') ? currency_unit() : 'ر.س',
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

        $relativePath = 'employee-agreements/contracts/emp-agr-'.$agreement->id.'-v'.$agreement->version.'-'.Str::random(8).'.pdf';
        $disk = PublicMediaStorage::resolvedDisk();
        Storage::disk($disk)->put($relativePath, $mpdf->Output('', 'S'));

        return $relativePath;
    }

    public function pdfBinary(EmployeeAgreement $agreement, bool $regenerateIfMissing = true): string
    {
        $path = $agreement->pdf_path;
        $disk = $path ? PublicMediaStorage::diskHolding($path) : null;

        if ($path && $disk) {
            return Storage::disk($disk)->get($path);
        }

        if (! $regenerateIfMissing) {
            throw new \RuntimeException('ملف PDF غير موجود.');
        }

        $newPath = $this->generateAndStorePdf($agreement, $agreement->signer_name);
        $agreement->update(['pdf_path' => $newPath]);

        $disk = PublicMediaStorage::diskHolding($newPath) ?? PublicMediaStorage::resolvedDisk();

        return Storage::disk($disk)->get($newPath);
    }

    protected function emailSignedCopies(EmployeeAgreement $agreement): void
    {
        $recipients = collect([
            $agreement->employee?->email,
            $agreement->creator?->email,
        ])->filter()->unique()->values();

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new EmployeeAgreementSignedMail($agreement));
            } catch (\Throwable $e) {
                Log::warning('Employee agreement signed mail failed', [
                    'agreement_id' => $agreement->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
