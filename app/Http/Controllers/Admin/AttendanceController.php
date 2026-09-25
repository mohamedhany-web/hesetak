<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Lecture;
use App\Models\TeamsAttendanceFile;
use App\Services\PublicMediaStorage;
use App\Services\TeamsAttendanceImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRecord::with(['lecture', 'student']);

        if ($request->filled('lecture_id')) {
            $query->where('lecture_id', $request->lecture_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(20);
        $lectures = Lecture::with('course')->orderBy('scheduled_at', 'desc')->get();

        return view('admin.attendance.index', compact('records', 'lectures'));
    }

    public function uploadTeamsFile(Request $request, Lecture $lecture)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:'.config('upload_limits.max_upload_kb'),
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $localImportPath = $file->getRealPath();
        $filePath = PublicMediaStorage::storeFileAs($file, 'attendance/teams', $fileName);

        $teamsFile = TeamsAttendanceFile::create([
            'lecture_id' => $lecture->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'status' => 'uploaded',
            'uploaded_by' => auth()->id(),
        ]);

        try {
            $teamsFile->update(['status' => 'processing']);
            $importer = new TeamsAttendanceImportService();
            $result = $importer->importFromFile($lecture, $localImportPath);
            $teamsFile->update([
                'status' => 'completed',
                'total_records' => (int) ($result['total'] ?? 0),
                'processed_records' => (int) ($result['processed'] ?? 0),
                'error_message' => !empty($result['errors'])
                    ? implode(' | ', array_slice($result['errors'], 0, 5))
                    : null,
            ]);

            $summary = "تمت معالجة الملف. الإجمالي: {$result['total']} | مطابق: {$result['matched']} | غير مطابق: {$result['unmatched']}";
            return redirect()->back()->with('success', $summary);
        } catch (\Throwable $e) {
            Log::error('Attendance import failed', [
                'lecture_id' => $lecture->id,
                'teams_file_id' => $teamsFile->id,
                'error' => $e->getMessage(),
            ]);
            $teamsFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'فشل معالجة ملف الحضور. تأكد من تنسيق الأعمدة ثم أعد المحاولة.');
        }
    }

    public function showLectureAttendance(Lecture $lecture)
    {
        $lecture->load(['attendanceRecords.student', 'course']);
        $attendanceRecords = $lecture->attendanceRecords;

        return view('admin.attendance.lecture', compact('lecture', 'attendanceRecords'));
    }
}
