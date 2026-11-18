<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassroomMeetingReport;
use App\Models\IntegrationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class N8nClassroomMeetingReportController extends Controller
{
    protected function ensureAuthorized(Request $request): ?JsonResponse
    {
        $token = IntegrationSetting::get('n8n_token', config('services.n8n.token'));

        if (empty($token) || $request->header('X-N8N-Token') !== $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return null;
    }

    /**
     * تحديث حالة وناتج تقرير اجتماع Classroom من n8n.
     */
    public function update(Request $request, ClassroomMeetingReport $report): JsonResponse
    {
        if ($resp = $this->ensureAuthorized($request)) {
            return $resp;
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,failed',
            'summary' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'audio_path' => 'nullable|string|max:500',
            'storage_disk' => 'nullable|string|max:100',
            'n8n_execution_id' => 'nullable|string|max:255',
        ]);

        $report->fill($validated);
        $report->save();

        if ($report->status === 'completed') {
            \App\Services\OneToOneSessionService::accrueEarningsForMeetingReport($report);
        }

        return response()->json([
            'success' => true,
            'report_id' => $report->id,
            'status' => $report->status,
        ]);
    }
}
