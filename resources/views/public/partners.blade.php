@extends('layouts.public')

@section('title', __('public.partners_page_title') . ' - ' . __('public.site_suffix'))
@section('meta_description', 'شركاء ' . config('app.name') . ' — مؤسسات وشركات تدعم ربط التعليم اللغوي بسوق العمل.')
@section('meta_keywords', 'شركاء, حصتك, شراكات, تعليم ألماني, سوق العمل')
@section('canonical_url', url('/partners'))

@section('content')
<!-- Hero Section -->
<section class="hero-gradient min-h-[50vh] flex items-center relative overflow-hidden pt-28" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.85) 25%, rgba(14, 165, 233, 0.7) 50%, rgba(14, 165, 233, 0.75) 75%, rgba(2, 132, 199, 0.8) 100%);">
    <div class="container mx-auto px-4 text-center relative z-10">
        <h1 class="text-5xl md:text-6xl font-black text-white leading-tight mb-6 fade-in" style="text-shadow: 0 4px 16px rgba(0,0,0,0.8), 0 2px 8px rgba(0,0,0,0.6), 0 0 12px rgba(14, 165, 233, 0.4);">
            شركاؤنا
        </h1>
        <p class="text-xl md:text-2xl text-white mb-10 fade-in font-semibold" style="text-shadow: 0 3px 12px rgba(0,0,0,0.7), 0 1px 6px rgba(0,0,0,0.5), 0 0 8px rgba(14, 165, 233, 0.3);">
            نفتخر بشراكتنا مع المؤسسات الرائدة
        </p>
    </div>
</section>

<!-- Partners -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-6xl mx-auto mb-12">
            @for($i = 1; $i <= 8; $i++)
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow flex items-center justify-center h-32 card-hover">
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-sky-500 to-sky-700 rounded-lg mx-auto mb-2 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                        {{ $i }}
                    </div>
                    <p class="text-sm font-semibold text-gray-700">شريك {{ $i }}</p>
                </div>
            </div>
            @endfor
        </div>

        <div class="mt-12 bg-gradient-to-br from-sky-50 to-blue-50 rounded-xl shadow-lg p-8 max-w-4xl mx-auto text-center card-hover">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">هل تريد أن تكون شريكاً معنا؟</h2>
            <p class="text-gray-600 mb-6">
                نبحث عن شركاء استراتيجيين لتعزيز تأهيل المعلّمين والتعليم أونلاين. تواصل معنا لمناقشة فرص الشراكة.
            </p>
            <a href="{{ route('public.contact') }}" class="btn-primary">
                <i class="fas fa-handshake ml-2"></i>
                تواصل معنا
            </a>
        </div>
    </div>
</section>
@endsection

