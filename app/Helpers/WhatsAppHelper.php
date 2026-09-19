<?php

if (!function_exists('sendWhatsAppMessage')) {
    /**
     * إرسال رسالة واتساب سريعة
     */
    function sendWhatsAppMessage(string $phoneNumber, string $message, string $type = 'text'): array
    {
        $whatsappService = app(\App\Services\WhatsAppService::class);
        return $whatsappService->sendMessage($phoneNumber, $message, $type);
    }
}

if (!function_exists('sendExamResultToParent')) {
    /**
     * إرسال نتيجة امتحان لولي الأمر
     */
    function sendExamResultToParent(\App\Models\ExamAttempt $attempt): bool
    {
        try {
            $student = $attempt->user;
            $parent = $student->parent;
            
            if (!$parent || !$parent->phone) {
                return false;
            }

            $whatsappService = app(\App\Services\WhatsAppService::class);
            $result = $whatsappService->sendExamResult($parent, $attempt);
            
            return $result['success'];
        } catch (\Exception $e) {
            \Log::error('Failed to send exam result to parent', [
                'attempt_id' => $attempt->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

if (!function_exists('generateStudentMonthlyReport')) {
    /**
     * توليد وإرسال تقرير شهري لطالب
     */
    function generateStudentMonthlyReport(\App\Models\User $student, ?string $month = null): array
    {
        try {
            $month = $month ?? now()->subMonth()->format('Y-m');
            $whatsappService = app(\App\Services\WhatsAppService::class);
            
            $reportData = $whatsappService->generateStudentReportData($student);
            
            // إرسال للطالب
            $studentResult = $whatsappService->sendStudentProgress($student);
            
            // إرسال لولي الأمر إذا متاح
            $parentResult = null;
            if ($student->parent && $student->parent->phone) {
                $parentResult = $whatsappService->sendMonthlyReport($student->parent, $student, $reportData);
            }
            
            return [
                'success' => true,
                'student_sent' => $studentResult['success'] ?? false,
                'parent_sent' => $parentResult['success'] ?? false,
                'report_data' => $reportData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
