<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * بديل لـ storage:link على الاستضافات التي تعطّل symlink()/exec().
 * ينشئ public/storage كمجلد حقيقي ينسخ/يربط المحتوى عند الحاجة،
 * أو يكتفي بالتأكيد أن بروكسي /storage و /media يعمل بدون symlink.
 */
class EnsurePublicStorageCommand extends Command
{
    protected $signature = 'hesetak:ensure-storage
        {--copy : انسخ الملفات إلى public/storage بدل الاعتماد على البروكسي فقط}';

    protected $description = 'Ensure public media works without PHP symlink (Hostinger-safe)';

    public function handle(): int
    {
        $target = storage_path('app/public');
        $link = public_path('storage');

        if (! File::isDirectory($target)) {
            File::makeDirectory($target, 0755, true);
            $this->info("Created: {$target}");
        }

        // بروكسي Laravel يخدم الملفات بدون symlink — هذا هو المسار الأساسي على Hostinger
        $this->info('Media proxy routes: /storage/{path} and /media/{path} (no symlink required).');

        if (is_link($link)) {
            $this->info("Symlink already exists: {$link} → ".readlink($link));

            return self::SUCCESS;
        }

        if (File::exists($link) && ! File::isDirectory($link)) {
            $this->warn("{$link} exists and is not a directory/symlink — leave it alone.");

            return self::SUCCESS;
        }

        // جرّب ln من الـ shell (غالبًا يعمل على SSH حتى لو PHP symlink معطّل)
        if (! File::exists($link)) {
            $cmd = 'ln -s '.escapeshellarg($target).' '.escapeshellarg($link).' 2>&1';
            $out = [];
            $code = 1;
            if (function_exists('proc_open')) {
                $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
                $proc = @proc_open('ln -s '.escapeshellarg($target).' '.escapeshellarg($link), $descriptors, $pipes, base_path());
                if (is_resource($proc)) {
                    $out[] = stream_get_contents($pipes[1]);
                    $out[] = stream_get_contents($pipes[2]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    $code = proc_close($proc);
                }
            } elseif (function_exists('shell_exec')) {
                $out[] = (string) @shell_exec($cmd);
                $code = is_link($link) ? 0 : 1;
            }

            if (is_link($link)) {
                $this->info('Created symlink via shell: public/storage → storage/app/public');

                return self::SUCCESS;
            }

            if ($code !== 0 && $out !== []) {
                $this->line('Shell ln unavailable/disabled: '.trim(implode(' ', $out)));
            }
        }

        if ($this->option('copy')) {
            if (! File::isDirectory($link)) {
                File::makeDirectory($link, 0755, true);
            }
            File::copyDirectory($target, $link);
            $this->info('Copied storage/app/public → public/storage (--copy). Prefer /storage proxy for new uploads.');

            return self::SUCCESS;
        }

        $this->warn('Could not create symlink (normal on Hostinger). App already serves files via /storage and /media routes.');
        $this->line('Skip php artisan storage:link. Optional: php artisan hesetak:ensure-storage --copy');

        return self::SUCCESS;
    }
}
