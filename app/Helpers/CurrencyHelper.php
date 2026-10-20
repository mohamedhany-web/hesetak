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

if (! function_exists('currency_unit')) {
    /**
     * رمز العملة بين أقواس للتسميات في النماذج (مثل: الحد الأدنى للطلب (ر.س)).
     */
    function currency_unit(): string
    {
        return '('.currency_symbol().')';
    }
}

if (! function_exists('currency_icon_class')) {
    /**
     * أيقونة محايدة للعملة — لا تستخدم fa-dollar-sign.
     */
    function currency_icon_class(): string
    {
        return (string) config('currency.icon', 'fas fa-coins');
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

if (! function_exists('supported_platform_currencies')) {
    /**
     * عملات مدعومة للبوابات/التسعير لاحقاً (العرض الحالي = عملة المنصة فقط).
     *
     * @return list<string>
     */
    function supported_platform_currencies(): array
    {
        return array_values(array_unique(array_merge(
            [platform_currency()],
            config('currency.supported', ['SAR', 'QAR', 'KWD', 'BHD', 'JOD', 'AED', 'EGP', 'USD'])
        )));
    }
}
