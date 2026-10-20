<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_forgot_password_sends_notification_after_many_login_attempts(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('secret-password'),
            'role' => 'student',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 8; $i++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.request', ['sent' => 1]));
        $response->assertSessionHas('status', __('auth.reset_link_sent'));
        $response->assertSessionHas('reset_email', $user->email);

        // بعد afterResponse يُرسل الإشعار عند إنهاء الطلب في الاختبارات أيضاً.
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->get(route('password.request', ['sent' => 1]))
            ->assertOk()
            ->assertSee(__('auth.check_inbox_title'), false)
            ->assertSee(__('auth.forgot_password_sent_title'), false);
    }

    public function test_forgot_password_rejects_unknown_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'missing@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }
}
