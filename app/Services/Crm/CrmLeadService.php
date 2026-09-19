<?php

namespace App\Services\Crm;

use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CrmLeadService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function createLead(array $data, User $creator): SalesLead
    {
        if (! CrmAccessService::hasCrmPermission($creator, 'crm_create_leads')) {
            throw new InvalidArgumentException('غير مصرح بإنشاء Lead.');
        }

        $lead = SalesLead::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'source' => $data['source'] ?? SalesLead::SOURCE_OTHER,
            'status' => SalesLead::STATUS_NEW,
            'notes' => $data['notes'] ?? null,
            'interested_advanced_course_id' => $data['interested_advanced_course_id'] ?? null,
            'created_by' => $creator->id,
            'marketing_owner_id' => $data['marketing_owner_id'] ?? $creator->id,
            'crm_group_id' => $data['crm_group_id'] ?? null,
        ]);

        CrmAuditService::log('lead_created', $lead, $creator, null, $lead->only([
            'name', 'email', 'phone', 'source', 'status', 'marketing_owner_id',
        ]));

        return $lead;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function updateLead(SalesLead $lead, array $data, User $actor, bool $adminOverride = false): SalesLead
    {
        if (! $adminOverride && ! CrmAccessService::canEditLead($actor, $lead)) {
            throw new InvalidArgumentException('لا يمكن تعديل هذا الـ Lead بعد التحويل أو التعيين.');
        }

        if ($adminOverride && ! ($actor->role === 'super_admin' || $actor->hasPermission('manage.leads'))) {
            throw new InvalidArgumentException('غير مصرح بتعديل الإدارة لهذا الـ Lead.');
        }

        $old = $lead->only(['name', 'email', 'phone', 'company', 'source', 'notes', 'interested_advanced_course_id', 'crm_group_id', 'assigned_to']);
        $lead->fill(collect($data)->only([
            'name', 'email', 'phone', 'company', 'source', 'notes', 'interested_advanced_course_id', 'crm_group_id',
        ])->all());
        $lead->save();

        CrmAuditService::log('lead_updated', $lead, $actor, $old, $lead->only(array_keys($old)));

        return $lead->fresh();
    }

    public static function deleteLead(SalesLead $lead, User $actor): void
    {
        if (! ($actor->role === 'super_admin' || $actor->hasPermission('manage.leads'))) {
            throw new InvalidArgumentException('غير مصرح بحذف الـ Lead.');
        }

        $snapshot = $lead->only(['id', 'name', 'email', 'phone', 'status', 'marketing_owner_id', 'assigned_to']);
        CrmAuditService::log('lead_deleted', $lead, $actor, $snapshot, null);
        $lead->delete();
    }

    public static function assignToSales(SalesLead $lead, User $salesUser, User $actor, ?int $groupId = null): SalesLead
    {
        if (! CrmAccessService::canAssignLeadTo($actor, $lead, $salesUser)) {
            throw new InvalidArgumentException('غير مصرح بتعيين الـ Lead.');
        }
        if ($lead->isClosed()) {
            throw new InvalidArgumentException('الـ Lead مغلق.');
        }

        $old = $lead->only(['assigned_to', 'status', 'crm_group_id', 'assigned_at']);

        $lead->update([
            'assigned_to' => $salesUser->id,
            'assigned_at' => now(),
            'status' => SalesLead::STATUS_ASSIGNED,
            'crm_group_id' => $groupId ?? $lead->crm_group_id,
            'submitted_to_sales_at' => $lead->submitted_to_sales_at ?? now(),
            'submitted_to_sales_by' => $lead->submitted_to_sales_by ?? $actor->id,
        ]);

        CrmAuditService::log('lead_assigned', $lead, $actor, $old, [
            'assigned_to' => $salesUser->id,
            'assigned_to_name' => $salesUser->name,
            'status' => SalesLead::STATUS_ASSIGNED,
        ]);

        return $lead->fresh();
    }

    /**
     * المسوق يرسل الـ Lead لصندوق المبيعات للاستلام والمتابعة.
     */
    public static function submitToSales(SalesLead $lead, User $actor, ?string $note = null): SalesLead
    {
        if (! CrmAccessService::canSubmitLeadToSales($actor, $lead)) {
            throw new InvalidArgumentException('غير مصرح بإرسال هذا العميل للمبيعات.');
        }
        if ($lead->isClosed()) {
            throw new InvalidArgumentException('لا يمكن إرسال عميل مغلق.');
        }
        if ($lead->assigned_to) {
            throw new InvalidArgumentException('هذا العميل مسند بالفعل لمندوب مبيعات.');
        }
        if ($lead->submitted_to_sales_at) {
            throw new InvalidArgumentException('تم إرسال هذا العميل للمبيعات مسبقاً.');
        }

        $old = $lead->only(['submitted_to_sales_at', 'submitted_to_sales_by', 'notes']);
        $updates = [
            'submitted_to_sales_at' => now(),
            'submitted_to_sales_by' => $actor->id,
        ];
        if ($note) {
            $prefix = '['.now()->toDateTimeString().'] إرسال للمبيعات: ';
            $updates['notes'] = trim(($lead->notes ?? '')."\n\n".$prefix.$note);
        }

        $lead->update($updates);

        CrmAuditService::log('lead_submitted_to_sales', $lead, $actor, $old, [
            'submitted_to_sales_at' => $lead->submitted_to_sales_at?->toDateTimeString(),
            'note' => $note,
        ]);

        return $lead->fresh();
    }

    /**
     * مندوب المبيعات يستلم Lead من صندوق المسوقين.
     */
    public static function claimFromMarketingInbox(SalesLead $lead, User $salesUser): SalesLead
    {
        if (! CrmAccessService::canClaimMarketingLead($salesUser, $lead)) {
            throw new InvalidArgumentException('غير مصرح باستلام هذا العميل.');
        }
        if ($lead->isClosed()) {
            throw new InvalidArgumentException('الـ Lead مغلق.');
        }
        if ($lead->assigned_to && (int) $lead->assigned_to !== (int) $salesUser->id) {
            throw new InvalidArgumentException('هذا العميل مسند بالفعل إلى مندوب آخر.');
        }

        $old = $lead->only(['assigned_to', 'status', 'assigned_at']);

        $lead->update([
            'assigned_to' => $salesUser->id,
            'assigned_at' => now(),
            'status' => $lead->status === SalesLead::STATUS_NEW
                ? SalesLead::STATUS_ASSIGNED
                : $lead->status,
            'submitted_to_sales_at' => $lead->submitted_to_sales_at ?? now(),
        ]);

        CrmAuditService::log('lead_claimed_from_marketing', $lead, $salesUser, $old, [
            'assigned_to' => $salesUser->id,
            'status' => $lead->status,
        ]);

        return $lead->fresh();
    }

    public static function transitionStatus(SalesLead $lead, string $toStatus, User $actor, ?string $note = null): SalesLead
    {
        if (! CrmAccessService::canTransitionStatus($actor, $lead, $toStatus)) {
            throw new InvalidArgumentException('انتقال الحالة غير مسموح.');
        }

        return self::applyStatusChange($lead, $toStatus, $actor, $note, false);
    }

    /**
     * فرض حالة من إدارة CRM حصتك (تخطي القيود بما فيها إعادة فتح مغلق).
     */
    public static function forceStatus(SalesLead $lead, string $toStatus, User $actor, ?string $note = null): SalesLead
    {
        if (! CrmAccessService::canForceLeadStatus($actor)) {
            throw new InvalidArgumentException('فرض الحالة متاح للإدارة فقط.');
        }

        if (! array_key_exists($toStatus, SalesLead::statusLabels())) {
            throw new InvalidArgumentException('حالة غير صالحة.');
        }

        if ($toStatus === $lead->status) {
            throw new InvalidArgumentException('العميل بالفعل في هذه الحالة.');
        }

        return self::applyStatusChange($lead, $toStatus, $actor, $note, true);
    }

    private static function applyStatusChange(
        SalesLead $lead,
        string $toStatus,
        User $actor,
        ?string $note,
        bool $forced
    ): SalesLead {
        return DB::transaction(function () use ($lead, $toStatus, $actor, $note, $forced) {
            $oldStatus = $lead->status;
            $updates = ['status' => $toStatus];

            if ($toStatus === SalesLead::STATUS_CLOSED_LOST && $note) {
                $updates['lost_reason'] = $note;
            }
            if ($toStatus === SalesLead::STATUS_CLOSED_WON) {
                $updates['converted_at'] = now();
            }
            if (in_array($toStatus, [SalesLead::STATUS_NEW, SalesLead::STATUS_ASSIGNED, SalesLead::STATUS_CONTACTED], true)
                && $oldStatus === SalesLead::STATUS_CLOSED_LOST) {
                $updates['lost_reason'] = null;
                $updates['converted_at'] = null;
            }

            if ($note && $toStatus !== SalesLead::STATUS_CLOSED_LOST) {
                $prefix = '['.now()->toDateTimeString().'] ';
                if ($forced) {
                    $prefix .= '[إدارة — فرض حالة] ';
                }
                $updates['notes'] = trim(($lead->notes ?? '')."\n\n".$prefix.$note);
            } elseif ($forced && $note && $toStatus === SalesLead::STATUS_CLOSED_LOST) {
                // lost_reason already handled
            }

            $lead->update($updates);

            $action = match (true) {
                $forced => 'lead_status_forced',
                $toStatus === SalesLead::STATUS_CLOSED_WON => 'lead_closed_won',
                $toStatus === SalesLead::STATUS_CLOSED_LOST => 'lead_closed_lost',
                default => 'lead_status_changed',
            };

            CrmAuditService::log($action, $lead, $actor, ['status' => $oldStatus], [
                'status' => $toStatus,
                'note' => $note,
                'forced' => $forced,
            ]);

            return $lead->fresh();
        });
    }

    public static function linkOrder(SalesLead $lead, int $orderId, User $actor): SalesLead
    {
        $lead->update([
            'order_id' => $orderId,
            'converted_order_id' => $orderId,
        ]);

        CrmAuditService::log('lead_updated', $lead, $actor, null, ['order_id' => $orderId]);

        return $lead->fresh();
    }

    public static function addNote(SalesLead $lead, string $note, User $actor): SalesLead
    {
        if (! CrmAccessService::canViewLead($actor, $lead)) {
            throw new InvalidArgumentException('غير مصرح.');
        }

        $prefix = '['.now()->toDateTimeString().'] '.$actor->name.': ';
        $lead->update([
            'notes' => trim(($lead->notes ?? '')."\n\n".$prefix.$note),
        ]);

        CrmAuditService::log('lead_note_added', $lead, $actor, null, ['note' => $note]);

        return $lead->fresh();
    }
}
