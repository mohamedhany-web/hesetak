<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\PublicSeo;
use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $root = PublicSeo::siteRoot();
        $sitemap = $root.'/sitemap.xml';
        $llms = $root.'/llms.txt';

        $private = [
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /dashboard',
            'Disallow: /admin',
            'Disallow: /checkout',
            'Disallow: /service-packages/*/checkout',
            'Disallow: /account',
            'Disallow: /teacher/dashboard',
            'Disallow: /student/dashboard',
            'Disallow: /employee',
            'Disallow: /instructor/',
            'Disallow: /student/',
        ];

        $aiAgents = [
            'GPTBot',
            'ChatGPT-User',
            'Google-Extended',
            'anthropic-ai',
            'ClaudeBot',
            'PerplexityBot',
            'Bytespider',
            'CCBot',
        ];

        $lines = [
            '# Hesetak / حصتك — https://hissatak.online',
            'User-agent: *',
            ...$private,
            '',
            'Allow: /',
            '',
        ];

        foreach ($aiAgents as $agent) {
            $lines[] = 'User-agent: '.$agent;
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /checkout';
            $lines[] = 'Disallow: /login';
            $lines[] = '';
        }

        $lines[] = 'Sitemap: '.$sitemap;
        $lines[] = '# GEO / AI context';
        $lines[] = '# llms: '.$llms;
        $lines[] = '';

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
