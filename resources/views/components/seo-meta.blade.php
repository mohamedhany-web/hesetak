{{-- SEO Meta Tags Component — حصتك / Hesetak --}}
@php
    $siteName    = \App\Support\PublicSeo::brandPrimaryName();
    $latinBrand  = \App\Support\PublicSeo::brandLatinName();
    $title       = $title       ?? $siteName . ' — ' . __('landing.hero.headline');
    $description = $description ?? __('landing.meta.description');
    $keywords    = $keywords    ?? (__('landing.meta.keywords') ?: ('حصتك, Hesetak, Hissatak, دروس خصوصية, معلم خصوصي, مناهج, كورسات أونلاين'));
    $image       = \App\Support\PublicSeo::forceAbsoluteUrl($image ?? \App\Services\SeoAssets::ogImageUrl());
    $imageAlt    = $imageAlt    ?? ($siteName.' | '.$latinBrand);
    $canonicalPath = $canonicalPath ?? null;
    $url         = \App\Support\PublicSeo::forceAbsoluteUrl($url ?? \App\Support\PublicSeo::canonicalUrl($canonicalPath));
    $type        = $type        ?? 'website';
    $indexable   = $indexable ?? ! \App\Support\PublicSeo::isPrivatePath();
    $robots      = $robots ?? \App\Support\PublicSeo::robots((bool) $indexable);
    $hreflangs   = $hreflangs ?? \App\Support\PublicSeo::hreflangHrefs($canonicalPath);
    $locale      = app()->getLocale();
    $ogLocale    = $locale === 'ar' ? 'ar_SA' : 'en_US';
    $ogLocaleAlt = $locale === 'ar' ? 'en_US' : 'ar_SA';
    $langCode    = $locale === 'ar' ? 'Arabic' : 'English';
    $twitterHandle = trim((string) config('platform.twitter_handle', '@hesetak'));
    if ($twitterHandle !== '' && ! str_starts_with($twitterHandle, '@')) {
        $twitterHandle = '@'.$twitterHandle;
    }
    $googleVerify = trim((string) config('services.google.site_verification', env('GOOGLE_SITE_VERIFICATION', '')));
    $bingVerify = trim((string) env('BING_SITE_VERIFICATION', ''));
@endphp

<!-- ═══ Primary Meta Tags ═══ -->
<title>{{ $title }}</title>
<meta name="title"          content="{{ $title }}">
<meta name="description"    content="{{ $description }}">
<meta name="keywords"       content="{{ $keywords }}">
<meta name="author"         content="{{ $siteName }}">
<meta name="application-name" content="{{ $siteName }}">
<meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
<meta name="robots"         content="{{ $robots }}">
<meta name="googlebot"      content="{{ $robots }}">
<meta name="language"       content="{{ $langCode }}">
<meta name="revisit-after"  content="3 days">
<meta name="rating"         content="general">
<meta name="geo.region"     content="SA">
<meta name="geo.placename"  content="Saudi Arabia, Egypt, United States">
@if($googleVerify !== '')
<meta name="google-site-verification" content="{{ $googleVerify }}">
@endif
@if($bingVerify !== '')
<meta name="msvalidate.01" content="{{ $bingVerify }}">
@endif

<!-- ═══ Canonical URL ═══ -->
<link rel="canonical" href="{{ $url }}">
@foreach($hreflangs as $hreflang => $href)
<link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach
<link rel="alternate" type="text/plain" title="llms.txt" href="{{ \App\Support\PublicSeo::absoluteUrl('/llms.txt') }}">

<!-- ═══ Open Graph / Facebook ═══ -->
<meta property="og:type"              content="{{ $type }}">
<meta property="og:url"               content="{{ $url }}">
<meta property="og:title"             content="{{ $title }}">
<meta property="og:description"       content="{{ $description }}">
<meta property="og:image"             content="{{ $image }}">
<meta property="og:image:secure_url"  content="{{ $image }}">
<meta property="og:image:alt"         content="{{ $imageAlt }}">
<meta property="og:image:width"       content="1200">
<meta property="og:image:height"      content="630">
<meta property="og:image:type"        content="image/png">
<meta property="og:locale"            content="{{ $ogLocale }}">
<meta property="og:locale:alternate"  content="{{ $ogLocaleAlt }}">
<meta property="og:site_name"         content="{{ $siteName }}">

<!-- ═══ Twitter / X Card ═══ -->
<meta name="twitter:card"        content="summary_large_image">
@if($twitterHandle !== '')
<meta name="twitter:site"        content="{{ $twitterHandle }}">
<meta name="twitter:creator"     content="{{ $twitterHandle }}">
@endif
<meta name="twitter:url"         content="{{ $url }}">
<meta name="twitter:title"       content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image"       content="{{ $image }}">
<meta name="twitter:image:alt"   content="{{ $imageAlt }}">
