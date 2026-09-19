@extends('layouts.public')

@section('title', __('public.events_page_title') . ' - ' . __('public.site_suffix'))
@section('meta_description', 'فعاليات ' . config('app.name') . ' — ورش لغوية ومهنية، ندوات، وأيام توظيف.')
@section('meta_keywords', 'فعاليات, حصتك, ورش عمل, كول سنتر, ألمانيا')
@section('canonical_url', url('/events'))

@section('content')
<!-- Hero Section -->
<section class="hero-gradient min-h-[50vh] flex items-center relative overflow-hidden pt-28" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.85) 25%, rgba(14, 165, 233, 0.7) 50%, rgba(14, 165, 233, 0.75) 75%, rgba(2, 132, 199, 0.8) 100%);">
    <div class="container mx-auto px-4 text-center relative z-10">
        <h1 class="text-5xl md:text-6xl font-black text-white leading-tight mb-6 fade-in" style="text-shadow: 0 4px 16px rgba(0,0,0,0.8), 0 2px 8px rgba(0,0,0,0.6), 0 0 12px rgba(14, 165, 233, 0.4);">
            الفعاليات والندوات
        </h1>
        <p class="text-xl md:text-2xl text-white mb-10 fade-in font-semibold" style="text-shadow: 0 3px 12px rgba(0,0,0,0.7), 0 1px 6px rgba(0,0,0,0.5), 0 0 8px rgba(14, 165, 233, 0.3);">
            انضم إلى فعالياتنا القادمة
        </p>
    </div>
</section>

<!-- Events -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto space-y-6">
            @for($i = 1; $i <= 5; $i++)
            <div class="bg-gradient-to-br from-white to-sky-50 rounded-xl shadow-lg p-6 border-r-4 border-sky-500 card-hover">
                <div class="flex items-start justify-between flex-wrap gap-4">
                    <div class="flex-1">
                        <div class="flex items-center mb-2 flex-wrap gap-3">
                            <div class="bg-sky-100 text-sky-600 px-4 py-2 rounded-full text-sm font-bold">
                                {{ \Carbon\Carbon::now()->addDays($i * 7)->format('d M') }}
                            </div>
                            <span class="text-gray-500 text-sm flex items-center">
                                <i class="fas fa-clock ml-1"></i>
                                {{ 10 + $i * 2 }}:00 صباحاً
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                            فعالية {{ $i }}: عنوان الفعالية
                        </h3>
                        <p class="text-gray-600 mb-4">
                            وصف تفصيلي للفعالية وما سيتم تغطيته في هذه الندوة أو الورشة التعليمية.
                        </p>
                        <div class="flex items-center space-x-4 space-x-reverse flex-wrap gap-2">
                            <span class="text-sm text-gray-500 flex items-center">
                                <i class="fas fa-users ml-1"></i>
                                {{ 20 + $i * 5 }} مشارك
                            </span>
                            <span class="text-sm text-gray-500 flex items-center">
                                <i class="fas fa-map-marker-alt ml-1"></i>
                                أونلاين
                            </span>
                        </div>
                    </div>
                    <a href="#" class="btn-primary whitespace-nowrap">
                        سجل الآن
                    </a>
                </div>
            </div>
            @endfor
        </div>
    </div>
</section>
@endsection

