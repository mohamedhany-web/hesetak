<?php

if (! function_exists('platform_currency')) {
    function platform_currency(): string
    {
        return strtoupper((string) config('currency.code', 'SAR')) ?: 'SAR';
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return (string) config('currency.symbol', 'ر.س');
    }
}

if (! function_exists('currency_label')) {
    function currency_label(): string
    {
        return (string) config('currency.label', 'ريال');
    }
}

if (! function_exists('format_money')) {
    /**
     * تنسيق مبلغ بعملة المنصة (ريال سعودي افتراضياً).
     */
    function format_money(float|int|string|null $amount, int $decimals = 2): string
    {
        return number_format((float) $amount, $decimals).' '.currency_symbol();
    }
}
