<?php

namespace App\Services;

use App\Models\Setting;

class PaymentGatewaySettings
{
    public const SETTING_KEY = 'fawaterak_gateway_enabled';

    /** نسبة عمولة بوابة الدفع على المبلغ المحصّل (0–100)، تُخزَّن في إعدادات النظام */
    public const FEE_PERCENT_SETTING_KEY = 'payment_gateway_fee_percent';

    /** السماح بالدفع اليدوي (رفع إيصال) حتى مع وجود بوابة إلكترونية مفعّلة */
    public const ALLOW_MANUAL_WITH_GATEWAYS_KEY = 'allow_manual_checkout_with_gateways';

    public static function isFawaterakEnabled(): bool
    {
        return Setting::getValue(self::SETTING_KEY) === '1';
    }

    public static function isPaypalEnabled(): bool
    {
        return PayPalSettings::isEnabled();
    }

    public static function isKashierEnabled(): bool
    {
        return KashierSettings::isEnabled();
    }

    public static function isStripeEnabled(): bool
    {
        return StripeSettings::isEnabled();
    }

    public static function allowsManualWithGateways(): bool
    {
        return Setting::getValue(self::ALLOW_MANUAL_WITH_GATEWAYS_KEY) === '1';
    }

    public static function setAllowManualWithGateways(bool $allow): void
    {
        Setting::setValue(self::ALLOW_MANUAL_WITH_GATEWAYS_KEY, $allow ? '1' : null);
    }

    /** أي بوابة أونلاين مفعّلة تمنع الدفع اليدوي — ما لم يُفعَّل خيار السماح اليدوي مع البوابات. */
    public static function blocksManualCheckout(): bool
    {
        if (self::allowsManualWithGateways()) {
            return false;
        }

        return self::hasAnyOnlineGatewayEnabled();
    }

    public static function hasAnyOnlineGatewayEnabled(): bool
    {
        return self::isFawaterakEnabled()
            || PayPalSettings::isEnabled()
            || KashierSettings::isEnabled()
            || StripeSettings::isEnabled();
    }

    /** عدد البوابات المعروضة في المركز (فواتيرك · Stripe · كاشير) — PayPal مؤرشف. */
    public static function hubEnabledCount(): int
    {
        return (int) self::isFawaterakEnabled()
            + (int) StripeSettings::isEnabled()
            + (int) KashierSettings::isEnabled();
    }

    /**
     * نسبة العمولة كنسبة مئوية (مثلاً 2.5 تساوي 2.5 بالمئة من المبلغ المحصّل).
     */
    public static function getFeePercent(): float
    {
        $raw = Setting::getValue(self::FEE_PERCENT_SETTING_KEY);
        if ($raw === null || $raw === '') {
            return 0.0;
        }
        $v = (float) str_replace(',', '.', (string) $raw);

        return max(0.0, min(100.0, $v));
    }

    /**
     * @return array{fee: float, net: float}
     */
    public static function computeFeeSplit(float $grossAmount): array
    {
        if ($grossAmount <= 0) {
            return ['fee' => 0.0, 'net' => 0.0];
        }
        $pct = self::getFeePercent();
        $fee = round($grossAmount * ($pct / 100), 2);
        if ($fee > $grossAmount) {
            $fee = $grossAmount;
        }
        $net = round($grossAmount - $fee, 2);

        return ['fee' => $fee, 'net' => $net];
    }

    public static function paidMatchesOrder(
        float $orderAmount,
        string $orderCurrency,
        float $paidAmount,
        string $paidCurrency
    ): bool {
        $expectedCurrency = strtoupper(trim($orderCurrency));
        $actualCurrency = strtoupper(trim($paidCurrency));
        if ($expectedCurrency === '' || $actualCurrency === '' || $expectedCurrency !== $actualCurrency) {
            return false;
        }

        return abs(round($orderAmount, 2) - round($paidAmount, 2)) < 0.005;
    }
}
