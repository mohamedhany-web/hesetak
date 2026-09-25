<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\PublicMediaStorage;
use App\Services\SecurityService;

class SecureFileUploadController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * رفع ملف آمن
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'type' => 'required|in:image,video,document',
        ]);

        $file = $request->file('file');
        
        // التحقق من صحة الملف
        $allowedMimes = config("security.file_upload.allowed_mimes.{$request->type}", []);
        $maxSize = config('security.file_upload.max_size', 10485760);
        
        $validation = $this->securityService->validateUploadedFile($file, $allowedMimes, $maxSize);

        if (!$validation['valid']) {
            $this->securityService->logSuspiciousActivity(
                'Invalid File Upload Attempt',
                $request,
                'Errors: ' . implode(', ', $validation['errors'])
            );

            return response()->json([
                'success' => false,
                'errors' => $validation['errors']
            ], 422);
        }

        try {
            // إنشاء اسم ملف آمن
            $safeFileName = $this->securityService->generateSafeFileName($file->getClientOriginalName());
            
            // حفظ الملف
            $path = PublicMediaStorage::storeFileAs(
                $file,
                "secure/{$request->type}",
                $safeFileName
            );

            // تسجيل رفع الملف
            if (config('security.logging.log_file_uploads', true)) {
                Log::info('File Uploaded Successfully', [
                    'user_id' => auth()->id(),
                    'file_name' => $safeFileName,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }

            return response()->json([
                'success' => true,
                'path' => PublicMediaStorage::publicUrl($path) ?? $path,
                'file_name' => $safeFileName,
            ]);

        } catch (\Exception $e) {
            Log::error('File Upload Error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع الملف'
            ], 500);
        }
    }
}
