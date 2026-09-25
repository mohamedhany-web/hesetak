<?php

namespace App\Services\Crm;

use App\Models\CrmGroup;
use App\Models\CrmMessage;
use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CrmMessageService
{
    public static function send(array $data, User $sender, ?UploadedFile $attachment = null): CrmMessage
    {
        if (! CrmAccessService::canUseMessages($sender, 'send')) {
            throw new InvalidArgumentException('غير مصرح بإرسال الرسائل.');
        }

        $recipientId = $data['recipient_id'] ?? null;
        $groupId = $data['crm_group_id'] ?? null;
        $leadId = $data['sales_lead_id'] ?? null;

        self::assertCanSendTo($sender, $recipientId, $groupId, $leadId);

        $path = null;
        $fileName = null;
        if ($attachment) {
            $fileName = $attachment->getClientOriginalName();
            $path = \App\Services\PublicMediaStorage::storeFile($attachment, 'crm/messages');
        }

        return CrmMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'crm_group_id' => $groupId,
            'sales_lead_id' => $leadId,
            'body' => $data['body'],
            'attachment_path' => $path,
            'attachment_name' => $fileName,
        ]);
    }

    public static function inboxQuery(User $user): Builder
    {
        $groupIds = self::accessibleGroupIds($user);
        $leadIds = CrmAccessService::leadsQueryFor($user)->pluck('sales_leads.id');

        return CrmMessage::query()
            ->with(['sender:id,name', 'recipient:id,name', 'group:id,name', 'lead:id,name'])
            ->where(function ($q) use ($user, $groupIds, $leadIds) {
                $q->where('recipient_id', $user->id)
                    ->orWhere('sender_id', $user->id)
                    ->orWhere(function ($gq) use ($groupIds) {
                        $gq->whereIn('crm_group_id', $groupIds)->whereNull('recipient_id');
                    })
                    ->orWhereIn('sales_lead_id', $leadIds);
            })
            ->latest();
    }

    public static function threadForLead(SalesLead $lead, User $user): Builder
    {
        abort_unless(CrmAccessService::canViewLead($user, $lead), 403);
        abort_unless(CrmAccessService::canUseMessages($user), 403);

        return CrmMessage::query()
            ->with(['sender:id,name'])
            ->where('sales_lead_id', $lead->id)
            ->oldest();
    }

    public static function markReadForUser(CrmMessage $message, User $user): void
    {
        if ($message->recipient_id === $user->id && ! $message->read_at) {
            $message->update(['read_at' => now()]);
        }
    }

    public static function unreadCount(User $user): int
    {
        return CrmMessage::query()
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, CrmGroup>
     */
    public static function accessibleGroups(User $user): Collection
    {
        $role = CrmAccessService::crmRole($user);

        if ($role === 'super_admin') {
            return CrmGroup::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }

        if ($role === 'team_leader') {
            return CrmAccessService::teamGroupsFor($user)->orderBy('name')->get(['id', 'name']);
        }

        return CrmGroup::query()
            ->where('is_active', true)
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private static function accessibleGroupIds(User $user): Collection
    {
        return self::accessibleGroups($user)->pluck('id');
    }

    private static function assertCanSendTo(User $sender, ?int $recipientId, ?int $groupId, ?int $leadId): void
    {
        $channels = (int) ($recipientId !== null) + (int) ($groupId !== null) + (int) ($leadId !== null);
        if ($channels !== 1) {
            throw new InvalidArgumentException('حدد قناة واحدة للرسالة: مباشرة، فريق، أو عميل.');
        }

        if ($recipientId) {
            $allowed = CrmAccessService::crmContactsFor($sender)->pluck('id');
            if (! $allowed->contains($recipientId)) {
                throw new InvalidArgumentException('لا يمكن مراسلة هذا المستخدم.');
            }
        }

        if ($groupId && ! self::accessibleGroupIds($sender)->contains($groupId)) {
            throw new InvalidArgumentException('غير مصرح بالكتابة في قناة هذا الفريق.');
        }

        if ($leadId) {
            $lead = SalesLead::find($leadId);
            if (! $lead || ! CrmAccessService::canViewLead($sender, $lead)) {
                throw new InvalidArgumentException('غير مصرح بالكتابة على هذا العميل.');
            }
        }
    }
}
