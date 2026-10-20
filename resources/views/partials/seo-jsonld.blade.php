@php
/*
 * حصتك — JSON-LD Structured Data (+ GEO brand signals)
 * Usage: @include('partials.seo-jsonld', ['jsonldType' => 'website|course|instructor|about'])
 */
$_jldType    = $jsonldType ?? 'website';
$_siteUrl    = rtrim(\App\Support\PublicSeo::absoluteUrl('/'), '/');
$_logoUrl    = \App\Services\SeoAssets::ogImageUrl();
$_siteName   = \App\Support\PublicSeo::brandPrimaryName();
$_latinBrand = \App\Support\PublicSeo::brandLatinName();
$_altNames   = \App\Support\PublicSeo::brandAlternateNames();
$_metaDesc   = __('landing.meta.description');
$_supportEmail = (string) (config('mail.from.address') ?: 'info@hissatak.online');
$_phone = preg_replace('/\s+/', '', (string) (config('platform.support_phone') ?: '+966573211242'));

// ── Base: WebSite + EducationalOrganization ──────────────────────────────
$_baseGraph = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'         => 'WebSite',
            '@id'           => $_siteUrl . '/#website',
            'url'           => $_siteUrl,
            'name'          => $_siteName,
            'alternateName' => $_altNames,
            'description'   => $_metaDesc,
            'inLanguage'    => [app()->getLocale() === 'en' ? 'en' : 'ar', 'en', 'ar'],
            'publisher'     => ['@id' => $_siteUrl . '/#organization'],
            'potentialAction' => [
                '@type'        => 'SearchAction',
                'target'       => ['@type' => 'EntryPoint', 'urlTemplate' => \App\Support\PublicSeo::absoluteUrl('/instructors') . '?q={search_term_string}'],
                'query-input'  => 'required name=search_term_string',
            ],
        ],
        [
            '@type'  => ['EducationalOrganization', 'Organization', 'OnlineBusiness'],
            '@id'    => $_siteUrl . '/#organization',
            'name'   => $_siteName,
            'legalName' => $_siteName,
            'alternateName' => $_altNames,
            'brand'  => [
                '@type' => 'Brand',
                'name' => $_siteName,
                'alternateName' => [$_latinBrand, 'Hissatak'],
                'logo' => $_logoUrl,
            ],
            'url'    => $_siteUrl,
            'description' => $_metaDesc,
            'slogan' => app()->getLocale() === 'en'
                ? 'Online private tutoring with accredited teachers'
                : 'دروس خصوصية أونلاين مع معلمين معتمدين',
            'logo'   => ['@type' => 'ImageObject', 'url' => $_logoUrl, 'width' => 1200, 'height' => 630],
            'image'  => $_logoUrl,
            'sameAs' => array_values(array_filter([
                config('services.social.twitter'),
                config('services.social.facebook'),
                config('services.social.linkedin'),
                config('services.social.instagram'),
                config('services.social.youtube'),
                config('services.social.tiktok'),
            ])),
            'email' => $_supportEmail,
            'contactPoint' => array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $_supportEmail,
                'telephone' => $_phone !== '' ? $_phone : null,
                'availableLanguage' => ['Arabic', 'English'],
                'areaServed' => ['SA', 'EG', 'US'],
            ], static fn ($v) => $v !== null),
            'areaServed' => [
                ['@type' => 'Country', 'name' => 'Saudi Arabia'],
                ['@type' => 'Country', 'name' => 'Egypt'],
                ['@type' => 'Country', 'name' => 'United States'],
            ],
            'knowsAbout' => app()->getLocale() === 'en'
                ? ['Online private tutoring', '1:1 live lessons', 'School curriculum support', 'Independent courses', 'Hesetak', 'Hissatak']
                : ['دروس خصوصية أونلاين', 'حصص فردية 1:1', 'متابعة المناهج المدرسية', 'كورسات مستقلة', 'حصتك', 'Hesetak', 'Hissatak'],
            'foundingDate' => '2020',
            'parentOrganization' => [
                '@type' => 'Organization',
                'name' => 'IKONIX360',
            ],
        ],
        [
            '@type' => 'WebPage',
            '@id' => $_siteUrl . '/#webpage',
            'url' => $_siteUrl.'/',
            'name' => __('landing.meta.title'),
            'description' => $_metaDesc,
            'isPartOf' => ['@id' => $_siteUrl . '/#website'],
            'about' => ['@id' => $_siteUrl . '/#organization'],
            'inLanguage' => app()->getLocale() === 'en' ? 'en' : 'ar',
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $_logoUrl],
            'speakable' => [
                '@type' => 'SpeakableSpecification',
                'cssSelector' => ['h1', '.mc-hero__title', 'meta[name=description]'],
            ],
        ],
    ],
];
$_outputScripts = '<script type="application/ld+json">' . json_encode($_baseGraph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

// ── Course Page ───────────────────────────────────────────────────────────
if ($_jldType === 'course' && isset($course)) {
    $_thumbnail = !empty($course->thumbnail)
        ? storage_asset(str_replace('\\', '/', $course->thumbnail))
        : $_logoUrl;

    $_courseData = [
        '@context'  => 'https://schema.org',
        '@type'     => 'Course',
        '@id'       => url('/course/' . ($course->id ?? 0)) . '#course',
        'name'      => (string)($course->title ?? ''),
        'description' => \Illuminate\Support\Str::limit(strip_tags((string)($course->description ?? '')), 300),
        'url'       => url('/course/' . ($course->id ?? 0)),
        'image'     => $_thumbnail,
        'inLanguage'=> app()->getLocale() === 'en' ? 'en' : 'ar',
        'courseLanguage' => app()->getLocale() === 'en' ? 'English' : 'Arabic',
        'provider'  => ['@type' => 'EducationalOrganization', '@id' => $_siteUrl . '/#organization', 'name' => $_siteName],
        'hasCourseInstance' => ['@type' => 'CourseInstance', 'courseMode' => 'online', 'inLanguage' => 'ar'],
    ];
    $catName = $course->courseCategory?->name ?? null;
    if (! empty($catName)) {
        $_courseData['about'] = ['@type' => 'Thing', 'name' => (string) $catName];
    }
    if (isset($course->price) && $course->effectivePurchasePrice() > 0) {
        $_courseData['offers'] = [
            '@type' => 'Offer', 'price' => (string) $course->effectivePurchasePrice(),
            'priceCurrency' => platform_currency(), 'availability' => 'https://schema.org/InStock',
        ];
    }
    if (!empty($course->instructor) && !empty($course->instructor->name)) {
        $_courseData['instructor'] = ['@type' => 'Person', 'name' => $course->instructor->name];
    }

    $_breadcrumbCourse = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('landing.mc.nav.home'), 'item' => $_siteUrl],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('landing.mc.nav.courses'),  'item' => url('/courses')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => (string)($course->title ?? __('landing.mc.nav.courses')), 'item' => url('/course/' . ($course->id ?? 0))],
        ],
    ];

    $_outputScripts .= '<script type="application/ld+json">' . json_encode($_courseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    $_outputScripts .= '<script type="application/ld+json">' . json_encode($_breadcrumbCourse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

// ── Instructor Profile Page ───────────────────────────────────────────────
if ($_jldType === 'instructor' && isset($profile)) {
    $_instrName = (string)($profile->user->name ?? 'مدرب');
    $_instrBio  = \Illuminate\Support\Str::limit(strip_tags((string)($profile->bio ?? $profile->headline ?? '')), 300);
    $_instrImg  = !empty($profile->user->profile_image ?? null) ? ($profile->user->profile_image_url ?? $_logoUrl) : $_logoUrl;

    try {
        $_instrUrl = route('public.instructors.show', $profile->user ?? $profile);
    } catch (\Exception $_e) {
        $_instrUrl = url('/instructors');
    }

    $_personData = [
        '@type'       => 'Person',
        '@id'         => $_instrUrl . '#person',
        'name'        => $_instrName,
        'description' => $_instrBio,
        'image'       => $_instrImg,
        'url'         => $_instrUrl,
        'jobTitle'    => (string)($profile->headline ?? 'مدرب'),
        'worksFor'    => ['@type' => 'EducationalOrganization', '@id' => $_siteUrl . '/#organization', 'name' => $_siteName],
    ];

    $_instrPageData = [
        '@context'   => 'https://schema.org',
        '@type'      => 'ProfilePage',
        '@id'        => $_instrUrl . '#profile',
        'url'        => $_instrUrl,
        'name'       => $_instrName.' — '.$_siteName,
        'mainEntity' => $_personData,
    ];

    $_breadcrumbInstr = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('landing.mc.nav.home'), 'item' => $_siteUrl],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('landing.mc.nav.instructors'),  'item' => url('/instructors')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $_instrName,  'item' => $_instrUrl],
        ],
    ];

    $_outputScripts .= '<script type="application/ld+json">' . json_encode($_instrPageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    $_outputScripts .= '<script type="application/ld+json">' . json_encode($_breadcrumbInstr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

// ── FAQ Page ──────────────────────────────────────────────────────────────
if ($_jldType === 'faq' && isset($faqs) && (is_countable($faqs) ? count($faqs) : 0) > 0) {
    $_faqItems = [];
    foreach ($faqs as $_faqItem) {
        $q = is_array($_faqItem) ? ($_faqItem['q'] ?? $_faqItem['question'] ?? '') : ($_faqItem->question ?? $_faqItem->q ?? '');
        $a = is_array($_faqItem) ? ($_faqItem['a'] ?? $_faqItem['answer'] ?? '') : ($_faqItem->answer ?? $_faqItem->a ?? '');
        if (trim((string) $q) === '' || \App\Support\PublicDemoContent::isPlaceholder((string) $q)) {
            continue;
        }
        $_faqItems[] = [
            '@type' => 'Question',
            'name'  => strip_tags((string) $q),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => \Illuminate\Support\Str::limit(strip_tags((string) $a), 400),
            ],
        ];
    }
    if ($_faqItems !== []) {
        $_faqData = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $_faqItems];
        $_outputScripts .= '<script type="application/ld+json">' . json_encode($_faqData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}

// ── About Page ────────────────────────────────────────────────────────────
if ($_jldType === 'about') {
    $_aboutData = [
        '@context'    => 'https://schema.org',
        '@type'       => 'AboutPage',
        'url'         => url('/about'),
        'name'        => __('landing.mc.nav.about').' — '.$_siteName,
        'description' => __('public.about_intro', ['brand' => $_siteName]),
        'mainEntity'  => [
            '@type'       => 'EducationalOrganization',
            '@id'         => $_siteUrl . '/#organization',
            'name'        => $_siteName,
            'url'         => $_siteUrl,
            'foundingDate'=> '2023',
            'description' => __('landing.meta.description'),
            'areaServed'  => ['@type' => 'Place', 'name' => app()->getLocale() === 'en' ? 'United States, Egypt, and Saudi Arabia' : 'الولايات المتحدة ومصر والسعودية'],
            'knowsAbout'  => app()->getLocale() === 'en'
                ? ['Online private tutoring', 'Curriculum support', '1:1 lessons']
                : ['دروس خصوصية أونلاين', 'متابعة المناهج', 'حصص فردية'],
        ],
    ];
    $_outputScripts .= '<script type="application/ld+json">' . json_encode($_aboutData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}
@endphp
{!! $_outputScripts !!}
