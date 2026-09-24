<?php

namespace App\Services;

use App\Mail\PackageGiftPurchasedMail;
use App\Mail\PackageGiftReceivedMail;
use App\Models\Order;
use App\Models\PackageGift;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PackageGiftService
{
    public function __construct(
        protected ServiceSessionRateService $rates,
    ) {}

    /**
     * @param  array{recipient_email:string,recipient_name?:string,recipient_phone?:string,message?:string,academic_year_id?:int,academic_subject_id?:int,curriculum_type?:string}  $giftInput
     * @return array{order: Order, gift: PackageGift, quote: array}
     */
    public function createPendingGiftOrder(
        User $buyer,
        ServicePackage $package,
        array $giftInput,
        string $paymentMethod = 'online',
    ): array {
        $email = strtolower(trim((string) ($giftInput['recipient_email'] ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('بريد المستلم غير صالح.');
        }

        $yearId = isset($giftInput['academic_year_id']) ? (int) $giftInput['academic_year_id'] : null;
        $subjectId = isset($giftInput['academic_subject_id']) ? (int) $giftInput['academic_subject_id'] : null;
        $track = $this->rates->normalizeCurriculumType($giftInput['curriculum_type'] ?? null) ?: 'saudi';
        $quote = $this->rates->quotePackage($package, $yearId, $track);

        $order = StudentEntitlementService::createOrder($buyer, $package, $paymentMethod, null, [
            'academic_year_id' => $yearId,
            'academic_subject_id' => $subjectId,
            'curriculum_type' => $track,
            'quoted_unit_price' => $quote['unit'],
            'quoted_total' => $quote['total'],
            'is_gift' => true,
            'gift_recipient_email' => $email,
            'gift_recipient_name' => $giftInput['recipient_name'] ?? null,
            'gift_recipient_phone' => $giftInput['recipient_phone'] ?? null,
            'gift_message' => $giftInput['message'] ?? null,
        ]);

        $gift = PackageGift::create([
            'order_id' => $order->id,
            'service_package_id' => $package->id,
            'buyer_user_id' => $buyer->id,
            'recipient_email' => $email,
            'recipient_name' => $giftInput['recipient_name'] ?? null,
            'recipient_phone' => $giftInput['recipient_phone'] ?? null,
            'message' => $giftInput['message'] ?? null,
            'status' => PackageGift::STATUS_PENDING_PAYMENT,
            'academic_year_id' => $yearId,
            'academic_subject_id' => $subjectId,
            'curriculum_type' => $track,
            'quoted_unit_price' => $quote['unit'],
            'quoted_total' => $quote['total'],
            'currency' => $quote['currency'],
        ]);

        return ['order' => $order, 'gift' => $gift, 'quote' => $quote];
    }

    public function fulfillAfterPayment(Order $order): ?PackageGift
    {
        $gift = PackageGift::query()->where('order_id', $order->id)->first();
        $meta = is_array($order->custom_package_data) ? $order->custom_package_data : [];

        if (! $gift && empty($meta['is_gift'])) {
            return null;
        }

        if (! $gift && ! empty($meta['is_gift'])) {
            $gift = PackageGift::create([
                'order_id' => $order->id,
                'service_package_id' => $order->service_package_id,
                'buyer_user_id' => $order->user_id,
                'recipient_email' => strtolower((string) ($meta['gift_recipient_email'] ?? '')),
                'recipient_name' => $meta['gift_recipient_name'] ?? null,
                'recipient_phone' => $meta['gift_recipient_phone'] ?? null,
                'message' => $meta['gift_message'] ?? null,
                'status' => PackageGift::STATUS_PENDING_PAYMENT,
                'academic_year_id' => $meta['academic_year_id'] ?? $order->academic_year_id,
                'academic_subject_id' => $meta['academic_subject_id'] ?? null,
                'curriculum_type' => $meta['curriculum_type'] ?? null,
                'quoted_unit_price' => $meta['quoted_unit_price'] ?? null,
                'quoted_total' => $meta['quoted_total'] ?? $order->amount,
                'currency' => $order->currency,
            ]);
        }

        if (! $gift || $gift->isGranted()) {
            return $gift;
        }

        $isNew = false;

        try {
            $gift = DB::transaction(function () use ($order, $gift, &$isNew) {
                $package = $order->servicePackage ?: ServicePackage::find($order->service_package_id);
                if (! $package) {
                    $gift->update(['status' => PackageGift::STATUS_FAILED]);

                    return $gift;
                }

                [$recipient, $created] = $this->resolveOrCreateRecipient($gift);
                $isNew = $created;
                $gift->recipient_user_id = $recipient->id;
                $gift->save();

                $buyerEntitlement = \App\Models\StudentServiceEntitlement::query()
                    ->where('order_id', $order->id)
                    ->where('user_id', $order->user_id)
                    ->orderBy('id')
                    ->get();

                if ($buyerEntitlement->isNotEmpty()) {
                    foreach ($buyerEntitlement as $row) {
                        $row->update([
                            'user_id' => $recipient->id,
                            'academic_year_id' => $gift->academic_year_id ?: $row->academic_year_id,
                            'academic_subject_id' => $gift->academic_subject_id ?: $row->academic_subject_id,
                            'notes' => trim(($row->notes ? $row->notes.' | ' : '').'gift_to:'.$recipient->id),
                        ]);
                    }
                } else {
                    StudentEntitlementService::grant(
                        userId: (int) $recipient->id,
                        package: $package,
                        orderId: (int) $order->id,
                        notes: 'gift_from:'.$order->user_id,
                    );
                }

                $gift->forceFill([
                    'status' => PackageGift::STATUS_GRANTED,
                    'granted_at' => now(),
                ])->save();

                return $gift->fresh(['buyer', 'recipient', 'servicePackage', 'order']);
            });
        } catch (\Throwable $e) {
            Log::error('Package gift fulfill failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            $gift->update(['status' => PackageGift::STATUS_FAILED]);

            return $gift;
        }

        if ($gift && $gift->isGranted()) {
            $resetUrl = $isNew && $gift->recipient
                ? $this->passwordResetUrl($gift->recipient)
                : null;
            $this->notifyParties($gift, $isNew, $resetUrl);
        }

        return $gift;
    }

    /**
     * @return array{0: User, 1: bool}
     */
    protected function resolveOrCreateRecipient(PackageGift $gift): array
    {
        $email = strtolower(trim((string) $gift->recipient_email));
        $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existing) {
            return [$existing, false];
        }

        $name = trim((string) ($gift->recipient_name ?: 'طالب حصتك'));
        $attrs = [
            'name' => $name !== '' ? $name : 'طالب حصتك',
            'email' => $email,
            'phone' => $gift->recipient_phone,
            'password' => Hash::make(Str::random(32)),
            'role' => 'student',
            'is_active' => true,
        ];
        if (Schema::hasColumn('users', 'academic_year_id') && $gift->academic_year_id) {
            $attrs['academic_year_id'] = $gift->academic_year_id;
        }

        return [User::create($attrs), true];
    }

    protected function passwordResetUrl(User $user): ?string
    {
        try {
            $token = Password::broker()->createToken($user);

            return url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));
        } catch (\Throwable $e) {
            Log::warning('Gift password reset token failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return route('password.request');
        }
    }

    protected function notifyParties(PackageGift $gift, bool $recipientIsNew, ?string $resetUrl): void
    {
        try {
            if ($gift->buyer?->email) {
                Mail::to($gift->buyer->email)->send(new PackageGiftPurchasedMail($gift));
            }
        } catch (\Throwable $e) {
            Log::warning('Gift buyer mail failed', ['gift_id' => $gift->id, 'error' => $e->getMessage()]);
        }

        try {
            Mail::to($gift->recipient_email)->send(new PackageGiftReceivedMail($gift, $recipientIsNew, $resetUrl));
        } catch (\Throwable $e) {
            Log::warning('Gift recipient mail failed', ['gift_id' => $gift->id, 'error' => $e->getMessage()]);
        }

        if (filled($gift->recipient_phone) && class_exists(WhatsAppService::class)) {
            try {
                $wa = app(WhatsAppService::class);
                if (method_exists($wa, 'sendMessage')) {
                    $wa->sendMessage(
                        (string) $gift->recipient_phone,
                        'وصلك هدية باقة على حصتك. راجع بريدك: '.$gift->recipient_email
                    );
                }
            } catch (\Throwable $e) {
                Log::debug('Gift WhatsApp skipped', ['error' => $e->getMessage()]);
            }
        }
    }
}
