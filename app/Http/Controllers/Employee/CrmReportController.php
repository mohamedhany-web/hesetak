<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CrmReport;
use App\Services\Crm\CrmAccessService;
use App\Services\Crm\CrmMessageService;
use App\Services\Crm\CrmReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmReportController extends Controller
{
    private function gate(): void
    {
        abort_unless(CrmAccessService::canAccessCrm(auth()->user()), 403);
    }

    public function index(Request $request): View
    {
        $this->gate();
        abort_unless(CrmAccessService::canSubmitReports($request->user()), 403);

        $reports = CrmReport::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('employee.crm.reports.index', [
            'reports' => $reports,
            'role' => CrmAccessService::crmRole($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->gate();
        abort_unless(CrmAccessService::canSubmitReports($request->user()), 403);

        $type = $request->string('type', CrmReport::TYPE_WEEKLY)->toString();
        $period = CrmReportService::suggestedPeriod($type);
        $groups = CrmMessageService::accessibleGroups($request->user());

        return view('employee.crm.reports.create', [
            'type' => $type,
            'period' => $period,
            'groups' => $groups,
            'typeLabels' => CrmReport::typeLabels(),
            'role' => CrmAccessService::crmRole($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->gate();
        abort_unless(CrmAccessService::canSubmitReports($request->user()), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(CrmReport::typeLabels()))],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:10000'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'crm_group_id' => ['nullable', 'exists:crm_groups,id'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        try {
            CrmReportService::create($data, $request->user(), $request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['title' => $e->getMessage()])->withInput();
        }

        return redirect()->route('employee.crm.reports.index')->with('success', 'تم إرسال التقرير للإدارة.');
    }

    public function download(Request $request, CrmReport $report): StreamedResponse
    {
        $this->gate();
        abort_unless($report->user_id === $request->user()->id
            || $request->user()->role === 'super_admin'
            || $request->user()->hasPermission('manage.leads'), 403);
        abort_unless($report->file_path, 404);
        $diskName = \App\Services\PublicMediaStorage::diskHolding((string) $report->file_path);
        abort_unless($diskName, 404);

        return Storage::disk($diskName)->download($report->file_path, $report->file_name ?? 'crm-report');
    }
}
