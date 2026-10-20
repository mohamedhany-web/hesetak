@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $shopCss = public_path('css/landing/meras-shop.css');
    $shopVer = is_file($shopCss) ? (string) filemtime($shopCss) : (string) time();
@endphp
<!DOCTYPE html>
{{-- الكابتشر لفيجما يستخدم نفس dir=rtl الحقيقي في الموقع حتى يطابق التخطيط --}}
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.seo-meta', [
        'title' => __('seo.home.title'),
        'description' => __('seo.home.description'),
        'keywords' => __('landing.meta.keywords'),
        'image' => \App\Services\SeoAssets::ogImageUrl(),
        'imageAlt' => __('seo.home.title'),
        'canonicalPath' => '/',
        'type' => 'website',
    ])
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    @include('partials.favicon-links')
    @include('partials.seo-jsonld', ['jsonldType' => 'website'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=Lato:wght@400;700&family=Rubik:wght@400;700&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#1E4E8C">
    <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
    <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'meras-shop']) }}?v={{ $shopVer }}">
    @include('partials.figma-capture-head')
</head>
<body class="mc-body mc-body--shop">
@include('partials.welcome-mycourses')
</body>
</html>
