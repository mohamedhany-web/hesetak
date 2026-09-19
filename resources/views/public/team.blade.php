@extends('layouts.public')

@section('title', __('public.team_page_title') . ' - ' . __('public.site_suffix'))
@section('meta_description', 'تعرف على فريق ' . config('app.name') . ' — خبراء اللغة والتوجيه المهني لربط التعليم بفرص العمل.')
@section('meta_keywords', 'فريق العمل, حصتك, فريق المنصة, من نحن')
@section('canonical_url', url('/team'))

@section('content')
<!-- Hero Section -->
<section class="hero-gradient min-h-[50vh] flex items-center relative overflow-hidden pt-28" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.85) 25%, rgba(14, 165, 233, 0.7) 50%, rgba(14, 165, 233, 0.75) 75%, rgba(2, 132, 199, 0.8) 100%);">
    <div class="container mx-auto px-4 text-center relative z-10">
        <h1 class="text-5xl md:text-6xl font-black text-white leading-tight mb-6 fade-in" style="text-shadow: 0 4px 16px rgba(0,0,0,0.8), 0 2px 8px rgba(0,0,0,0.6), 0 0 12px rgba(14, 165, 233, 0.4);">
            فريقنا
        </h1>
        <p class="text-xl md:text-2xl text-white mb-10 fade-in font-semibold" style="text-shadow: 0 3px 12px rgba(0,0,0,0.7), 0 1px 6px rgba(0,0,0,0.5), 0 0 8px rgba(14, 165, 233, 0.3);">
            تعرف على الفريق المتميز وراء {{ config('app.name') }}
        </p>
    </div>
</section>

<!-- Team Members -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @for($i = 1; $i <= 6; $i++)
            <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg overflow-hidden card-hover">
                <div class="h-64 bg-gradient-to-br from-sky-500 via-sky-600 to-sky-700 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-32 h-32 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-user text-white text-5xl"></i>
                        </div>
                    </div>
                </div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">اسم المدرب {{ $i }}</h3>
                    <p class="text-sky-600 font-semibold mb-4">فريق المنصة</p>
                    <p class="text-gray-600 text-sm mb-4">
                        نعمل على تطوير تجربة المعلّم والمتعلّم على {{ config('app.name') }} باستمرار
                    </p>
                    <div class="flex justify-center space-x-4 space-x-reverse">
                        <a href="#" class="w-10 h-10 bg-sky-100 rounded-full flex items-center justify-center text-sky-600 hover:bg-sky-600 hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-colors">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endfor
        </div>
    </div>
</section>
@endsection

