<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmReport;
use App\Services\Crm\CrmReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasPermission('manage.leads') || auth()->user()->role === 'super_admin', 403);

        $query = CrmReport::with(['user:id,name', 'group:id,name', 'reviewer:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $reports = $query->latest()->paginate(25)->withQueryString();

        return view('admin.crm.reports.index', [
            'reports' => $reports,
            'typeLabels' => CrmReport::typeLabels(),
            'statusLabels' => CrmReport::statusLabels(),
        ]);
    }

    public function show(CrmReport $report): View
    {
        abort_unless(auth()->user()->hasPermission('manage.leads') || auth()->user()->role === 'super_admin', 403);

        $report->load(['user', 'group', 'reviewer']);

        return view('admin.crm.reports.show', compact('report'));
    }

    public function review(Request $request, CrmReport $report): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('manage.leads') || auth()->user()->role === 'super_admin', 403);

        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        CrmReportService::markReviewed($report, $request->user(), $data['admin_notes'] ?? null);

        return back()->with('success', 'تمت مراجعة التقرير.');
    }

    public function download(CrmReport $report): StreamedResponse
    {
        abort_unless(auth()->user()->hasPermission('manage.leads') || auth()->user()->role === 'super_admin', 403);
        abort_unless($report->file_path, 404);
        $diskName = \App\Services\PublicMediaStorage::diskHolding((string) $report->file_path);
        abort_unless($diskName, 404);

        return Storage::disk($diskName)->download($report->file_path, $report->file_name ?? 'crm-report');
    }
}
