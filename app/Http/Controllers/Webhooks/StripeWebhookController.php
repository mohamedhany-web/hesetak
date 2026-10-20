<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\StripeCheckoutController;
use App\Models\Order;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = app(StripeService::class)->assertValidWebhook($request);
        } catch (RuntimeException $e) {
            Log::warning('Stripe webhook signature rejected', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'invalid signature'], 400);
        }

        $event = (string) ($payload['type'] ?? '');
        $object = is_array($payload['data']['object'] ?? null) ? $payload['data']['object'] : [];

        Log::info('Stripe webhook received', [
            'event' => $event,
            'id' => $payload['id'] ?? null,
        ]);

        if (! in_array($event, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return response()->json(['ok' => true, 'message' => 'ignored event']);
        }

        $sessionId = (string) ($object['id'] ?? '');
        if ($sessionId === '') {
            return response()->json(['ok' => true, 'message' => 'no session id']);
        }

        $order = Order::query()->where('stripe_checkout_session_id', $sessionId)->first();
        if (! $order) {
            $ref = $object['client_reference_id'] ?? ($object['metadata']['order_id'] ?? null);
            if (is_numeric($ref)) {
                $order = Order::query()->whereKey((int) $ref)->first();
            }
        }

        if (! $order) {
            return response()->json(['ok' => true, 'message' => 'no matching order']);
        }
        if ($order->status === Order::STATUS_APPROVED) {
            return response()->json(['ok' => true, 'message' => 'already approved']);
        }

        try {
            app(StripeCheckoutController::class)->captureAndApprove($order, $sessionId);
        } catch (Throwable $e) {
            Log::error('Stripe webhook approve failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => 'approve failed'], 500);
        }

        return response()->json(['ok' => true]);
    }
}
