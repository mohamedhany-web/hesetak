<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\PayPalService;
use App\Services\PayPalSettings;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class PayPalGatewaySettingsTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
    }

    public function test_paypal_settings_save_credentials_but_stay_soft_archived(): void
    {
        PayPalSettings::save([
            'enabled' => true,
            'mode' => 'sandbox',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-secret-value',
            'webhook_id' => 'WH-123',
            'currency' => 'USD',
        ]);

        $this->assertTrue(PayPalSettings::isSoftArchived());
        $this->assertFalse(PayPalSettings::isEnabled());
        $this->assertFalse(PayPalSettings::isReady());
        $this->assertTrue(PayPalSettings::isConfigured());
        $this->assertSame('sandbox', PayPalSettings::mode());
        $this->assertSame('test-client-id', PayPalSettings::clientId());
        $this->assertSame('test-secret-value', PayPalSettings::clientSecret());
        $this->assertSame('WH-123', PayPalSettings::webhookId());
        $this->assertSame('USD', PayPalSettings::currency());
        $this->assertNotSame('test-secret-value', Setting::getValue(PayPalSettings::SECRET_KEY));
        $this->assertNull(Setting::getValue(PayPalSettings::ENABLED_KEY));
    }

    public function test_paypal_rejects_amount_or_currency_mismatch(): void
    {
        $paypal = app(PayPalService::class);
        $paid = [
            'purchase_units' => [[
                'payments' => [
                    'captures' => [[
                        'amount' => ['value' => '1.00', 'currency_code' => 'USD'],
                    ]],
                ],
            ]],
        ];

        $this->expectException(\RuntimeException::class);
        $paypal->assertMatchesOrder($paid, 25.50, 'USD');
    }

    public function test_paypal_webhook_rejects_missing_signature(): void
    {
        PayPalSettings::save([
            'enabled' => true,
            'mode' => 'sandbox',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'webhook_id' => 'WH-123',
            'currency' => 'USD',
        ]);

        $this->postJson(route('webhooks.paypal'), [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'PAYPAL-ORDER-1'],
        ])->assertStatus(400);
    }

    public function test_paypal_webhook_accepts_verified_signature_without_local_order(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('orders', 'paypal_order_id')) {
            \Illuminate\Support\Facades\Schema::table('orders', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('paypal_order_id')->nullable();
            });
        }

        PayPalSettings::save([
            'enabled' => true,
            'mode' => 'sandbox',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'webhook_id' => 'WH-123',
            'currency' => 'USD',
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'tok_test',
                'expires_in' => 300,
            ], 200),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ], 200),
        ]);

        $this->call(
            'POST',
            route('webhooks.paypal'),
            [],
            [],
            [],
            [
                'HTTP_PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
                'HTTP_PAYPAL-CERT-URL' => 'https://api.sandbox.paypal.com/v1/notifications/certs/cert',
                'HTTP_PAYPAL-TRANSMISSION-ID' => 'trx-1',
                'HTTP_PAYPAL-TRANSMISSION-SIG' => 'sig',
                'HTTP_PAYPAL-TRANSMISSION-TIME' => '2026-08-17T00:00:00Z',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'event_type' => 'CHECKOUT.ORDER.APPROVED',
                'resource' => ['id' => 'PAYPAL-ORDER-MISSING'],
            ])
        )->assertOk()->assertJson(['ok' => true, 'message' => 'no matching order']);
    }

    public function test_paypal_create_order_posts_to_sandbox_api(): void
    {
        PayPalSettings::save([
            'enabled' => true,
            'mode' => 'sandbox',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'currency' => 'USD',
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'tok_test',
                'expires_in' => 300,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'status' => 'CREATED',
                'links' => [
                    ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-1'],
                ],
            ], 201),
        ]);

        $created = app(PayPalService::class)->createOrder(
            25.5,
            'USD',
            'https://hesetak.test/checkout/paypal/return',
            'https://hesetak.test/checkout/paypal/cancel',
            '99',
            'Test course'
        );

        $this->assertSame('PAYPAL-ORDER-1', $created['id']);
        $this->assertStringContainsString('checkoutnow', $created['approve_url']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-m.sandbox.paypal.com/v2/checkout/orders'
                && ($request['purchase_units'][0]['custom_id'] ?? null) === '99'
                && ($request['purchase_units'][0]['amount']['value'] ?? null) === '25.50';
        });
    }
}
