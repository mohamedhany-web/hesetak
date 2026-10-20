<?php

namespace App\Console\Commands;

use App\Support\AdminArchitectureManual;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class GenerateAdminArchitecturePdfCommand extends Command
{
    protected $signature = 'hesetak:admin-architecture-pdf
                            {--out=reports/hesetak-admin-architecture-manual.pdf : مسار الملف النسبي من جذر المشروع}
                            {--base-url= : دومين الروابط في الدليل (افتراضي: الإنتاج https://hissatak.online عند localhost)}';

    protected $description = 'توليد PDF عربي عالي الجودة لدليل لوحة الإدارة (Chrome + خط عربي محلي)';

    public function handle(): int
    {
        $relative = ltrim((string) $this->option('out'), '/');
        $absolute = base_path($relative);
        $dir = dirname($absolute);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpDir = storage_path('app/admin-manual-pdf');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $baseUrl = AdminArchitectureManual::resolvePublicBaseUrl(
            $this->option('base-url') ? (string) $this->option('base-url') : null
        );

        $htmlPath = $tmpDir.'/manual.html';
        $fontCss = $this->fontFaceCss($tmpDir);
        $html = AdminArchitectureManual::toPrintHtml($fontCss, $baseUrl);
        file_put_contents($htmlPath, $html);
        $this->line('روابط الصفحات على: '.$baseUrl);
        $chrome = $this->resolveChromeBinary();
        if ($chrome === null) {
            $this->error('Google Chrome غير موجود. ثبّت Chrome أو حدّث المسار.');

            return self::FAILURE;
        }

        if (is_file($absolute)) {
            @unlink($absolute);
        }

        $this->info('جاري التصدير عبر Chrome…');

        $result = Process::timeout(120)->run([
            $chrome,
            '--headless=new',
            '--disable-gpu',
            '--allow-file-access-from-files',
            '--font-render-hinting=none',
            '--no-pdf-header-footer',
            '--print-to-pdf='.$absolute,
            '--print-to-pdf-no-header',
            'file://'.$htmlPath,
        ]);

        if ($result->failed() || ! is_file($absolute) || filesize($absolute) < 1000) {
            $this->error('فشل التصدير عبر Chrome.');
            $this->line(trim($result->errorOutput().' '.$result->output()));

            return self::FAILURE;
        }

        $this->info('تم الحفظ: '.$absolute);
        $this->line('الحجم: '.number_format((int) filesize($absolute)).' بايت');

        return self::SUCCESS;
    }

    protected function resolveChromeBinary(): ?string
    {
        $candidates = [
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * ادمج الخطوط كـ base64 داخل @font-face حتى يعمل file:// في Chrome headless.
     */
    protected function fontFaceCss(string $tmpDir): string
    {
        $fontDir = resource_path('fonts');
        $pairs = [
            'Amiri' => ['Amiri-Regular.ttf', 'Amiri-Bold.ttf'],
            'NotoNaskhArabic' => ['NotoNaskhArabic-Regular.ttf', 'NotoNaskhArabic-Bold.ttf'],
        ];

        $css = '';
        foreach ($pairs as $family => [$regular, $bold]) {
            $regSrc = $fontDir.'/'.$regular;
            $boldSrc = $fontDir.'/'.$bold;
            if (! is_file($regSrc)) {
                continue;
            }

            // نسخة محلية للمرجع / التصحيح اليدوي
            $regDst = $tmpDir.'/'.$regular;
            if (! is_file($regDst) || filemtime($regSrc) > filemtime($regDst)) {
                copy($regSrc, $regDst);
            }
            if (is_file($boldSrc)) {
                $boldDst = $tmpDir.'/'.$bold;
                if (! is_file($boldDst) || filemtime($boldSrc) > filemtime($boldDst)) {
                    copy($boldSrc, $boldDst);
                }
            }

            $css .= $this->fontFaceRule($family, 400, $regSrc);
            if (is_file($boldSrc)) {
                $css .= $this->fontFaceRule($family, 700, $boldSrc);
            }
        }

        return $css;
    }

    protected function fontFaceRule(string $family, int $weight, string $ttfPath): string
    {
        $b64 = base64_encode((string) file_get_contents($ttfPath));

        return "@font-face{font-family:'{$family}';src:url(data:font/truetype;charset=utf-8;base64,{$b64}) format('truetype');"
            ."font-weight:{$weight};font-style:normal;font-display:block;}";
    }
}
