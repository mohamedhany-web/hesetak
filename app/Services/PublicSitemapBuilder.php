<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Support\PublicDemoContent;
use Illuminate\Support\Facades\Schema;

final class PublicSitemapBuilder
{
    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public function urls(): array
    {
        $urls = [];
        $today = now()->toDateString();

        $urls[] = ['loc' => url('/'), 'lastmod' => $today, 'changefreq' => 'daily', 'priority' => '1.0'];

        $staticPages = [
            ['url' => '/instructors', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/curricula', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['url' => '/courses', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/pricing', 'priority' => '0.85', 'changefreq' => 'weekly'],
            ['url' => '/search', 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['url' => '/about', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => '/how-it-works', 'priority' => '0.75', 'changefreq' => 'monthly'],
            ['url' => '/for-students', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/for-teachers', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/faq', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/tutor/apply', 'priority' => '0.65', 'changefreq' => 'monthly'],
            ['url' => '/help', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => '/parent-progress', 'priority' => '0.55', 'changefreq' => 'monthly'],
            ['url' => '/terms', 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['url' => '/privacy', 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['url' => '/refund', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => url($page['url']),
                'lastmod' => $today,
                'changefreq' => $page['changefreq'],
                'priority' => $page['priority'],
            ];
        }

        if (Schema::hasTable('advanced_courses')) {
            AdvancedCourse::query()
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->get(['id', 'title', 'description', 'updated_at'])
                ->each(function (AdvancedCourse $course) use (&$urls) {
                    if (PublicDemoContent::recordLooksLikeDemo([$course->title, $course->description])) {
                        return;
                    }
                    if (trim((string) $course->title) === '') {
                        return;
                    }
                    $urls[] = [
                        'loc' => url('/course/'.$course->id),
                        'lastmod' => optional($course->updated_at)->format('Y-m-d') ?: now()->toDateString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                });
        }

        if (Schema::hasTable('instructor_profiles') && Schema::hasTable('users')) {
            PublicInstructorDirectory::approvedActiveQuery()
                ->with(['user:'.implode(',', PublicInstructorDirectory::userSelect()).',updated_at'])
                ->orderByDesc('id')
                ->limit(1000)
                ->get()
                ->each(function ($profile) use (&$urls) {
                    $user = $profile->user;
                    if (! $user || PublicInstructorDirectory::profileLooksLikeDemo($profile)) {
                        return;
                    }
                    $urls[] = [
                        'loc' => route('public.instructors.show', $user),
                        'lastmod' => optional($user->updated_at)->format('Y-m-d') ?: now()->toDateString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                });
        }

        if (Schema::hasTable('academic_years')) {
            AcademicYear::query()
                ->publicCatalog()
                ->withCount(['subjects as subjects_count' => fn ($q) => $q->active()])
                ->ordered()
                ->get()
                ->each(function (AcademicYear $year) use (&$urls) {
                    if ((int) ($year->subjects_count ?? 0) < 1) {
                        return;
                    }
                    $urls[] = [
                        'loc' => route('public.curricula.show', $year),
                        'lastmod' => optional($year->updated_at)->format('Y-m-d') ?: now()->toDateString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.75',
                    ];
                });
        }

        return $urls;
    }

    public function xml(): string
    {
        $escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;
        foreach ($this->urls() as $entry) {
            $xml .= '  <url>'.PHP_EOL;
            $xml .= '    <loc>'.$escape($entry['loc']).'</loc>'.PHP_EOL;
            $xml .= '    <lastmod>'.$escape($entry['lastmod']).'</lastmod>'.PHP_EOL;
            $xml .= '    <changefreq>'.$escape($entry['changefreq']).'</changefreq>'.PHP_EOL;
            $xml .= '    <priority>'.$escape($entry['priority']).'</priority>'.PHP_EOL;
            $xml .= '  </url>'.PHP_EOL;
        }
        $xml .= '</urlset>';

        return $xml;
    }
}
