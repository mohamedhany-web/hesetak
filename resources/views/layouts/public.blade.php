@php
    $publicLocale = app()->getLocale();
    $publicRtl = $publicLocale === 'ar';
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $brand = config('app.name', 'حصتك');
    $langSwitch = fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
@endphp
<!DOCTYPE html>
<html lang="{{ $publicLocale }}" dir="{{ $publicRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoTitle = trim($__env->yieldContent('title')) ?: ($brand.' — حصتك');
        $seoDescription = trim($__env->yieldContent('meta_description')) ?: __('landing.meta.description');
        $seoKeywords = trim($__env->yieldContent('meta_keywords')) ?: __('landing.meta.keywords');
        $seoImage = trim($__env->yieldContent('meta_image')) ?: \App\Services\SeoAssets::ogImageUrl();
        $seoType = trim($__env->yieldContent('meta_type')) ?: 'website';
        $seoCanonical = trim($__env->yieldContent('canonical_url')) ?: url()->current();
        $seoAltBase = url()->current();
    @endphp
    @include('components.seo-meta', [
        'title' => $seoTitle,
        'description' => $seoDescription,
        'keywords' => $seoKeywords,
        'image' => $seoImage,
        'type' => $seoType,
        'url' => $seoCanonical,
    ])
    <link rel="alternate" hreflang="ar" href="{{ $seoAltBase }}?lang=ar">
    <link rel="alternate" hreflang="en" href="{{ $seoAltBase }}?lang=en">
    <link rel="alternate" hreflang="x-default" href="{{ $seoAltBase }}">
    <meta name="theme-color" content="#00C2A8">
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
      .mc-public-legacy { min-height: 50vh; padding: 1.5rem 0 3rem; }
    </style>
    @stack('styles')
    @stack('head')
    @include('partials.seo-jsonld', ['jsonldType' => 'website'])
</head>
<body class="mc-body page-academy font-sans antialiased"
      x-data="{ mobileMenu: false, searchQuery: '' }"
      :class="{ 'overflow-hidden': mobileMenu }">

    @include('partials.landing.mycourses.nav')

    <main class="flex-1 w-full mc-public-legacy">
        <div class="mc-container">
            @yield('content')
        </div>
    </main>

    @include('partials.landing.mycourses.footer')
    @stack('scripts')
</body>
</html>
