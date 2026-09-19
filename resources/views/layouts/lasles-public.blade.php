@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $brand = 'حصتك';
    $brandAr = 'حصتك';
    $langSwitch = fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
    $mcActive = $mcActive ?? ($laslesNavActive ?? '');
    $pageTitle = $pageTitle ?? $brand;
    $pageDescription = $pageDescription ?? __('landing.meta.description');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.seo-meta', [
        'title' => $pageTitle,
        'description' => $pageDescription,
        'keywords' => __('landing.meta.keywords'),
        'image' => \App\Services\SeoAssets::ogImageUrl(),
        'imageAlt' => $pageTitle,
        'url' => url()->current(),
        'type' => 'website',
    ])
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#00C2A8">
    <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
    @stack('head')
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

{{-- Alias of mycourses-public: keep name for legacy @extends without loading Lasles CSS. --}}
