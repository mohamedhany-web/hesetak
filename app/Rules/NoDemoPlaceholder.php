<?php

namespace App\Rules;

use App\Support\PublicDemoContent;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoDemoPlaceholder implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if (PublicDemoContent::isPlaceholder($value)) {
            $fail('لا يمكن نشر بيانات تجريبية (test / TEST / تجريبي / website) على الواجهة العامة.');
        }
    }
}
