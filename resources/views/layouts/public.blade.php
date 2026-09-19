@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $brand = __('landing.nav.brand');
    $brandAr = 'حصتك';
    $langSwitch = fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
    $mcActive = $mcActive ?? ($laslesNavActive ?? '');
    $pageTitle = trim($__env->yieldContent('title')) ?: $brand;
    $pageDescription = trim($__env->yieldContent('meta_description')) ?: __('landing.meta.description');
    $pageKeywords = trim($__env->yieldContent('meta_keywords')) ?: __('landing.meta.keywords');
    $pageImage = trim($__env->yieldContent('meta_image')) ?: \App\Services\SeoAssets::ogImageUrl();
    $pageType = trim($__env->yieldContent('meta_type')) ?: 'website';
    $pageUrl = trim($__env->yieldContent('canonical_url')) ?: url()->current();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ request()->boolean('figma') ? 'ltr' : ($isRtl ? 'rtl' : 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.seo-meta', [
        'title' => $pageTitle,
        'description' => $pageDescription,
        'keywords' => $pageKeywords,
        'image' => $pageImage,
        'imageAlt' => $pageTitle,
        'url' => $pageUrl,
        'type' => $pageType,
    ])
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#1E4E8C">
    <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
    @stack('head')
    @include('partials.figma-capture-head')
</head>
<body class="mc-body {{ $bodyClass ?? '' }}">
@include('partials.landing.mycourses.nav')
<main>
    @yield('content')
</main>
@include('partials.landing.mycourses.footer')
@stack('scripts')
</body>
</html>
