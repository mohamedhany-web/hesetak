<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class PasswordStrengthRuleTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function passwords(): array
    {
        return [
            'digits only' => ['12345678', false],
            'letters only' => ['abcdefgh', false],
            'too short' => ['abc1234', false],
            'letters and digits' => ['abc12345', true],
            'arabic letters and digits' => ['حصتك2026', true],
        ];
    }

    /**
     * @dataProvider passwords
     */
    public function test_default_password_rule(string $password, bool $valid): void
    {
        $validator = Validator::make(['password' => $password], ['password' => [Password::defaults()]]);

        $this->assertSame($valid, $validator->passes());
    }

    public function test_weak_password_message_is_arabic(): void
    {
        app()->setLocale('ar');

        $validator = Validator::make(['password' => '12345678'], ['password' => [Password::defaults()]]);

        $this->assertSame('كلمة المرور يجب أن تحتوي على حرف واحد على الأقل.', $validator->errors()->first('password'));
    }

    public function test_tutor_registration_rejects_weak_password(): void
    {
        $this->post(route('public.tutor.apply.register'), [
            'full_name' => 'معلم تجريبي',
            'email' => 'weak.tutor@example.com',
            'phone' => '+966500000098',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertNull(User::query()->where('email', 'weak.tutor@example.com')->first());
    }
}
