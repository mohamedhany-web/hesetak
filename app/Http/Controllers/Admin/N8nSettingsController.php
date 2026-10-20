<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class N8nSettingsController extends Controller
{
    public function index()
    {
        $n8nToken = IntegrationSetting::get('n8n_token', config('services.n8n.token'));
        $n8nWebhook = IntegrationSetting::get('n8n_live_session_report_webhook', config('services.n8n.live_session_report_webhook'));

        $n8nTokenSource = $this->resolvedSource('n8n_token', 'services.n8n.token');
        $n8nWebhookSource = $this->resolvedSource('n8n_live_session_report_webhook', 'services.n8n.live_session_report_webhook');

        $platformCallback = url('/api/n8n/live-session-reports/{report_id}');
        $classroomPlatformCallback = url('/api/n8n/classroom-meeting-reports/{report_id}');

        $examplePayload = [
            'status' => 'completed',
            'title' => 'تقرير الجلسة - مثال',
            'summary' => 'ملخص نصي للتقرير الناتج من أدوات الذكاء الاصطناعي.',
            'audio_path' => 'live-session-audio/2026/04/session-123-audio-20260416-120000-abc123.webm',
            'storage_disk' => 'live_recordings_r2',
            'n8n_execution_id' => 'your-n8n-execution-id',
        ];

        return view('admin.n8n.settings', compact(
            'n8nToken',
            'n8nWebhook',
            'n8nTokenSource',
            'n8nWebhookSource',
            'platformCallback',
            'classroomPlatformCallback',
            'examplePayload'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'n8n_token' => ['nullable', 'string', 'max:255'],
            'n8n_webhook' => ['nullable', 'string', 'max:1000'],
        ]);

        $token = trim((string) ($data['n8n_token'] ?? ''));
        $webhook = trim((string) ($data['n8n_webhook'] ?? ''));

        IntegrationSetting::set('n8n_token', $token === '' ? null : $token, 'n8n');
        IntegrationSetting::set('n8n_live_session_report_webhook', $webhook === '' ? null : $webhook, 'n8n');

        return redirect()->route('admin.n8n.settings')
            ->with('success', 'تم تحديث إعدادات n8n بنجاح.');
    }

    /**
     * طلب تجريبي من الخادم إلى webhook n8n باستخدام القيم المحفوظة (للتحقق من الإعدادات).
     */
    public function testConnection()
    {
        $webhookUrl = IntegrationSetting::get('n8n_live_session_report_webhook', config('services.n8n.live_session_report_webhook'));
        $token = IntegrationSetting::get('n8n_token', config('services.n8n.token'));

        if (! $webhookUrl || ! $token) {
            return back()->with('error', 'احفظ التوكن ورابط الـ Webhook في النموذج أولاً (أو عرّفهما في .env كاحتياطي) ثم أعد المحاولة.');
        }

        try {
            $response = Http::timeout(20)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-N8N-Token' => $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post($webhookUrl, [
                    'probe' => true,
                    'source' => 'hesetak_admin_settings_test',
                    'sent_at' => now()->toIso8601String(),
                ]);

            $status = $response->status();
            $snippet = Str::limit($response->body(), 600);

            if ($response->successful()) {
                return back()->with('success', "تم الاتصال بنجاح (HTTP {$status}). مقتطف الاستجابة: {$snippet}");
            }

            return back()->with('warning', "ردّ الخادم البعيد: HTTP {$status}. {$snippet}");
        } catch (\Throwable $e) {
            return back()->with('error', 'تعذر الاتصال بـ n8n: '.$e->getMessage());
        }
    }

    /**
     * أين تُقرأ القيمة الفعّالة: من لوحة التحكم (integration_settings) أو من .env.
     */
    private function resolvedSource(string $integrationKey, string $configDotPath): string
    {
        $row = IntegrationSetting::query()->where('key', $integrationKey)->first();
        $stored = $row?->value;
        if ($stored !== null && trim((string) $stored) !== '') {
            return 'admin';
        }

        $fallback = config($configDotPath);
        if ($fallback !== null && trim((string) $fallback) !== '') {
            return 'env';
        }

        return 'empty';
    }
}

