<?php

namespace App\Console\Commands;

use App\Models\LiveServer;
use App\Models\LiveSetting;
use App\Services\LiveKitTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ProvisionHesetakLiveKitCommand extends Command
{
    protected $signature = 'livekit:provision-hesetak
                            {--domain=live.glottical.com : Public LiveKit host for حصتك}
                            {--ip=187.124.36.228 : VPS IP}
                            {--set-default : Mark LiveKit server as default provider}';

    protected $description = 'Register حصتك LiveKit live server and set platform defaults';

    public function handle(LiveKitTokenService $tokens): int
    {
        $domain = LiveSetting::normalizeLiveHost((string) $this->option('domain'));
        $ip = (string) $this->option('ip');

        $this->info("VPS target: {$ip}");
        $this->info("حصتك LiveKit host: {$domain}");

        $health = null;
        try {
            $health = Http::timeout(8)->get("http://{$ip}:7880/");
            $this->line('LiveKit :7880 => HTTP '.$health->status().' '.$health->body());
        } catch (\Throwable $e) {
            $this->warn('LiveKit :7880 unreachable: '.$e->getMessage());
        }

        $server = LiveServer::query()->firstOrNew([
            'domain' => $domain,
            'provider' => 'livekit',
        ]);
        $server->fill([
            'name' => $server->exists ? $server->name : 'حصتك LiveKit',
            'ip_address' => $ip,
            'status' => 'active',
            'max_participants' => $server->max_participants ?: 200,
            'notes' => 'حصتك LiveKit on shared VPS',
        ]);
        $server->save();

        if ($this->option('set-default') || true) {
            LiveSetting::set('live_provider', 'livekit');
            LiveSetting::set('livekit_host', $domain);
            $this->info('Default live provider => livekit / '.$domain);
        }

        $this->table(['id', 'name', 'provider', 'domain', 'ip', 'status'], LiveServer::query()
            ->orderBy('id')
            ->get(['id', 'name', 'provider', 'domain', 'ip_address', 'status'])
            ->map(fn (LiveServer $s) => [
                $s->id, $s->name, $s->provider, $s->domain, $s->ip_address, $s->status,
            ])->all());

        if ($tokens->isConfigured()) {
            $this->info('LIVEKIT_API_KEY is set in .env');
            $this->line('WS URL: '.$tokens->wsUrl($server));
        } else {
            $this->warn('Set LIVEKIT_API_KEY / LIVEKIT_API_SECRET in .env (same keys as VPS livekit.yaml)');
        }

        $this->newLine();
        $this->warn('DNS required at Hostinger for glottical.com:');
        $this->line("  A  live  ->  {$ip}   (= {$domain})");
        $this->line('Then on VPS run: sudo bash scripts/setup-live-hesetak-livekit.sh');

        return self::SUCCESS;
    }
}
