<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\SalesLead;
use App\Services\Crm\CrmAccessService;
use App\Services\Crm\CrmMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmMessageController extends Controller
{
    private function gate(): void
    {
        abort_unless(CrmAccessService::canAccessCrm(auth()->user()), 403);
    }

    public function index(Request $request): View
    {
        $this->gate();
        abort_unless(CrmAccessService::canUseMessages($request->user()), 403);

        $messages = CrmMessageService::inboxQuery($request->user())->paginate(30);
        $contacts = CrmAccessService::crmContactsFor($request->user());
        $groups = CrmMessageService::accessibleGroups($request->user());
        $leads = CrmAccessService::leadsQueryFor($request->user())
            ->open()
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name']);

        return view('employee.crm.messages.index', [
            'messages' => $messages,
            'contacts' => $contacts,
            'groups' => $groups,
            'leads' => $leads,
            'unread' => CrmMessageService::unreadCount($request->user()),
            'role' => CrmAccessService::crmRole($request->user()),
            'canSend' => CrmAccessService::canUseMessages($request->user(), 'send'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->gate();
        abort_unless(CrmAccessService::canUseMessages($request->user(), 'send'), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'recipient_id' => ['nullable', 'exists:users,id'],
            'crm_group_id' => ['nullable', 'exists:crm_groups,id'],
            'sales_lead_id' => ['nullable', 'exists:sales_leads,id'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        try {
            CrmMessageService::send($data, $request->user(), $request->file('attachment'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['body' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'تم إرسال الرسالة.');
    }

    public function leadThread(Request $request, SalesLead $salesLead): View
    {
        $this->gate();
        abort_unless(CrmAccessService::canViewLead($request->user(), $salesLead), 403);
        abort_unless(CrmAccessService::canUseMessages($request->user()), 403);

        $messages = CrmMessageService::threadForLead($salesLead, $request->user())->get();

        return view('employee.crm.messages.lead-thread', [
            'lead' => $salesLead,
            'messages' => $messages,
            'role' => CrmAccessService::crmRole($request->user()),
            'canSend' => CrmAccessService::canUseMessages($request->user(), 'send'),
        ]);
    }

    public function downloadAttachment(Request $request, int $messageId): StreamedResponse
    {
        $this->gate();
        $message = \App\Models\CrmMessage::findOrFail($messageId);
        abort_unless(
            $message->sender_id === $request->user()->id
            || $message->recipient_id === $request->user()->id
            || CrmMessageService::inboxQuery($request->user())->where('id', $message->id)->exists(),
            403
        );
        abort_unless($message->attachment_path, 404);
        $diskName = \App\Services\PublicMediaStorage::diskHolding((string) $message->attachment_path);
        abort_unless($diskName, 404);

        return Storage::disk($diskName)->download($message->attachment_path, $message->attachment_name ?? 'attachment');
    }
}
