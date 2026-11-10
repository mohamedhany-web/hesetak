<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\CrmGroup;
use App\Models\SalesLead;
use App\Models\User;
use App\Services\Crm\CrmAccessService;
use App\Services\Crm\CrmLeadService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmLeadController extends Controller
{
    private function authorizeLeads(Request $request): void
    {
        abort_unless($request->user()->hasPermission('manage.leads') || $request->user()->role === 'super_admin', 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLeadPayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::in(array_keys(SalesLead::sourceLabels()))],
            'notes' => ['nullable', 'string', 'max:10000'],
            'interested_advanced_course_id' => ['nullable', 'exists:advanced_courses,id'],
            'crm_group_id' => ['nullable', 'exists:crm_groups,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'marketing_owner_id' => ['nullable', 'exists:users,id'],
        ]);
    }

    public function index(Request $request): View
    {
        $this->authorizeLeads($request);

        $audience = (string) $request->query('audience', 'all');
        if (! in_array($audience, ['leads', 'registered', 'all'], true)) {
            $audience = 'all';
        }

        $leads = null;
        $registeredUsers = null;
        $recentUnlinkedUsers = collect();

        if ($audience !== 'registered') {
            $query = SalesLead::query()
                ->with(['marketingOwner:id,name', 'assignedTo:id,name', 'interestedCourse:id,title', 'crmGroup:id,name']);

            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }
            if ($request->filled('search')) {
                $s = '%'.trim($request->string('search')).'%';
                $query->where(fn ($q) => $q->where('name', 'like', $s)->orWhere('email', 'like', $s)->orWhere('phone', 'like', $s));
            }

            $leads = $query->latest()->paginate(25)->withQueryString();
        }

        if ($audience === 'registered' || $audience === 'all') {
            $userQuery = $this->registeredUsersQuery($request);
            if ($audience === 'registered') {
                $registeredUsers = $userQuery->latest()->paginate(25)->withQueryString();
            } else {
                $linkedIds = SalesLead::query()
                    ->whereNotNull('linked_user_id')
                    ->pluck('linked_user_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $recentUnlinkedUsers = (clone $userQuery)
                    ->when($linkedIds !== [], fn ($q) => $q->whereNotIn('id', $linkedIds))
                    ->latest()
                    ->limit(20)
                    ->get(['id', 'name', 'email', 'phone', 'role', 'created_at']);
            }
        }

        $leadLinksByUserId = SalesLead::query()
            ->whereNotNull('linked_user_id')
            ->pluck('id', 'linked_user_id');

        $stats = [
            'total' => SalesLead::count(),
            'open' => SalesLead::open()->count(),
            'filtered' => $audience === 'registered'
                ? ($registeredUsers?->total() ?? 0)
                : ($leads?->total() ?? 0),
            'closed_won' => SalesLead::where('status', SalesLead::STATUS_CLOSED_WON)->count(),
            'registered_total' => User::query()->whereIn('role', ['student', 'parent'])->count(),
        ];

        return view('admin.crm.leads.index', [
            'leads' => $leads,
            'registeredUsers' => $registeredUsers,
            'recentUnlinkedUsers' => $recentUnlinkedUsers,
            'leadLinksByUserId' => $leadLinksByUserId,
            'audience' => $audience,
            'statusLabels' => SalesLead::statusLabels(),
            'stats' => $stats,
            'recentRegistrationCutoff' => Carbon::now()->subDays(14),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeLeads($request);

        return view('admin.crm.leads.create', $this->formLookups());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeLeads($request);

        $data = $this->validatedLeadPayload($request);

        try {
            $lead = CrmLeadService::createLead($data, $request->user());

            if (! empty($data['assigned_to'])) {
                $salesUser = User::findOrFail($data['assigned_to']);
                CrmLeadService::assignToSales($lead, $salesUser, $request->user(), $data['crm_group_id'] ?? null);
                $lead->refresh();
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.crm.leads.show', $lead)
            ->with('success', 'تم إنشاء العميل المحتمل بنجاح.');
    }

    public function show(Request $request, SalesLead $salesLead): View
    {
        $this->authorizeLeads($request);

        $salesLead->load([
            'marketingOwner', 'assignedTo', 'creator', 'linkedUser',
            'order', 'convertedOrder.course', 'interestedCourse', 'crmGroup.teamLeader',
            'commissions.user', 'auditLogs.user',
        ]);

        $salesUsers = User::employees()
            ->whereHas('employeeJob', fn ($q) => $q->whereIn('code', ['sales', config('crm.employee_job_codes.sales')]))
            ->orderBy('name')
            ->get(['id', 'name']);

        $groups = CrmGroup::where('is_active', true)->orderBy('name')->get();
        $nextStatuses = CrmAccessService::selectableStatusesFor($request->user(), $salesLead);

        return view('admin.crm.leads.show', compact('salesLead', 'salesUsers', 'groups', 'nextStatuses'));
    }

    public function edit(Request $request, SalesLead $salesLead): View
    {
        $this->authorizeLeads($request);

        return view('admin.crm.leads.edit', array_merge($this->formLookups(), [
            'salesLead' => $salesLead,
        ]));
    }

    public function update(Request $request, SalesLead $salesLead): RedirectResponse
    {
        $this->authorizeLeads($request);

        $data = $this->validatedLeadPayload($request);

        try {
            CrmLeadService::updateLead($salesLead, $data, $request->user(), true);

            if (array_key_exists('assigned_to', $data) && $data['assigned_to']) {
                $currentAssigned = (int) ($salesLead->fresh()->assigned_to ?? 0);
                if ($currentAssigned !== (int) $data['assigned_to'] && ! $salesLead->fresh()->isClosed()) {
                    $salesUser = User::findOrFail($data['assigned_to']);
                    CrmLeadService::assignToSales($salesLead->fresh(), $salesUser, $request->user(), $data['crm_group_id'] ?? null);
                } elseif (! empty($data['crm_group_id'])) {
                    $salesLead->fresh()->update(['crm_group_id' => $data['crm_group_id']]);
                }
            } elseif (! empty($data['crm_group_id'])) {
                $salesLead->fresh()->update(['crm_group_id' => $data['crm_group_id']]);
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.crm.leads.show', $salesLead)
            ->with('success', 'تم تحديث بيانات العميل المحتمل.');
    }

    public function destroy(Request $request, SalesLead $salesLead): RedirectResponse
    {
        $this->authorizeLeads($request);

        try {
            CrmLeadService::deleteLead($salesLead, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.crm.leads.index')
            ->with('success', 'تم حذف العميل المحتمل.');
    }

    public function assign(Request $request, SalesLead $salesLead): RedirectResponse
    {
        abort_unless(CrmAccessService::canAssignLead($request->user()), 403);

        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'crm_group_id' => ['nullable', 'exists:crm_groups,id'],
        ]);

        $salesUser = User::findOrFail($data['assigned_to']);
        CrmLeadService::assignToSales($salesLead, $salesUser, $request->user(), $data['crm_group_id'] ?? null);

        return back()->with('success', 'تم تعيين الـ Lead لموظف المبيعات.');
    }

    public function transition(Request $request, SalesLead $salesLead): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(SalesLead::statusLabels()))],
            'note' => ['nullable', 'string', 'max:5000'],
            'force' => ['nullable', 'boolean'],
        ]);

        try {
            if ($request->boolean('force')) {
                CrmLeadService::forceStatus($salesLead, $data['status'], $request->user(), $data['note'] ?? null);
            } else {
                CrmLeadService::transitionStatus($salesLead, $data['status'], $request->user(), $data['note'] ?? null);
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'تم تحديث حالة الـ Lead.');
    }

    public function addNote(Request $request, SalesLead $salesLead): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);

        CrmLeadService::addNote($salesLead, $data['note'], $request->user());

        return back()->with('success', 'تمت إضافة الملاحظة.');
    }

    private function registeredUsersQuery(Request $request)
    {
        $query = User::query()->whereIn('role', ['student', 'parent']);

        if ($request->filled('search')) {
            $s = '%'.trim($request->string('search')).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $s)->orWhere('email', 'like', $s)->orWhere('phone', 'like', $s));
        }

        return $query;
    }

    /**
     * @return array{courses: \Illuminate\Support\Collection, groups: \Illuminate\Support\Collection, salesUsers: \Illuminate\Support\Collection, marketingUsers: \Illuminate\Support\Collection}
     */
    private function formLookups(): array
    {
        $salesCode = config('crm.employee_job_codes.sales', 'sales');
        $marketingCode = config('crm.employee_job_codes.marketing', 'marketing');

        return [
            'courses' => AdvancedCourse::query()->orderBy('title')->get(['id', 'title']),
            'groups' => CrmGroup::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'salesUsers' => User::employees()
                ->whereHas('employeeJob', fn ($q) => $q->whereIn('code', ['sales', $salesCode]))
                ->orderBy('name')
                ->get(['id', 'name']),
            'marketingUsers' => User::employees()
                ->whereHas('employeeJob', fn ($q) => $q->whereIn('code', ['marketing', $marketingCode]))
                ->orderBy('name')
                ->get(['id', 'name']),
            'sourceLabels' => SalesLead::sourceLabels(),
        ];
    }
}
