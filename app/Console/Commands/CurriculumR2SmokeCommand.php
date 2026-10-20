<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CurriculumR2SmokeCommand extends Command
{
    protected $signature = 'curriculum:r2-smoke {--keep : Keep the smoke object after upload}';

    protected $description = 'Verify Cloudflare R2 credentials by uploading a tiny curriculum smoke object';

    public function handle(): int
    {
        $diskConfig = config('filesystems.disks.r2', []);
        $missing = [];
        foreach (['key', 'secret', 'bucket', 'endpoint'] as $field) {
            if (empty($diskConfig[$field])) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            $this->error('R2 is not configured. Missing: '.implode(', ', $missing));
            $this->line('Set AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_BUCKET, AWS_ENDPOINT in .env then retry.');

            return self::FAILURE;
        }

        $key = 'curriculum-library/_smoke/hesetak-'.now()->format('YmdHis').'.txt';
        $body = 'hesetak curriculum r2 smoke '.now()->toIso8601String();

        try {
            $ok = Storage::disk('r2')->put($key, $body);
            if (! $ok) {
                $this->error('Storage::put returned false for disk r2.');

                return self::FAILURE;
            }

            $exists = Storage::disk('r2')->exists($key);
            $read = $exists ? (string) Storage::disk('r2')->get($key) : '';
            if (! $exists || $read !== $body) {
                $this->error('Upload did not round-trip correctly.');

                return self::FAILURE;
            }

            $this->info('R2 smoke upload OK: '.$key);

            $corsOk = \App\Services\CloudflareR2::ensureBrowserUploadCors();
            if ($corsOk) {
                $this->info('R2 browser CORS applied for: '.implode(', ', \App\Services\CloudflareR2::browserCorsOrigins()));
            } else {
                $this->warn('Could not apply R2 browser CORS (check API token permissions for PutBucketCORS). Direct browser uploads may fail until CORS is set in the R2 dashboard.');
            }

            if (! $this->option('keep')) {
                Storage::disk('r2')->delete($key);
                $this->line('Smoke object deleted.');
            } else {
                $this->warn('Kept smoke object (--keep).');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('R2 smoke failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
