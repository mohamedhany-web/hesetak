@extends('layouts.public')

@section('title', __('public.certificates_page_title') . ' - ' . __('public.site_suffix'))
@section('meta_description', 'شهادات ' . config('app.name') . ' — وثّق مهاراتك اللغوية والمهنية وتحقق من صحة شهادتك.')
@section('meta_keywords', 'شهادات, حصتك, شهادة إتمام, سوق العمل')
@section('canonical_url', url('/certificates'))

@section('content')
<!-- Hero Section -->
<section class="hero-gradient min-h-[50vh] flex items-center relative overflow-hidden pt-28" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.85) 25%, rgba(14, 165, 233, 0.7) 50%, rgba(14, 165, 233, 0.75) 75%, rgba(2, 132, 199, 0.8) 100%);">
    <div class="container mx-auto px-4 text-center relative z-10">
        <h1 class="text-5xl md:text-6xl font-black text-white leading-tight mb-6 fade-in" style="text-shadow: 0 4px 16px rgba(0,0,0,0.8), 0 2px 8px rgba(0,0,0,0.6), 0 0 12px rgba(14, 165, 233, 0.4);">
            الشهادات المعتمدة
        </h1>
        <p class="text-xl md:text-2xl text-white mb-10 fade-in font-semibold" style="text-shadow: 0 3px 12px rgba(0,0,0,0.7), 0 1px 6px rgba(0,0,0,0.5), 0 0 8px rgba(14, 165, 233, 0.3);">
            احصل على شهادات إتمام برامج التأهيل والتطوير المهني للمعلّمين
        </p>
    </div>
</section>

<!-- Certificates Info -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <div class="bg-gradient-to-br from-sky-50 to-blue-50 rounded-xl shadow-lg p-8 card-hover border-r-4 border-sky-500">
                <div class="text-6xl text-sky-500 mb-4 text-center">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4 text-center">شهادة إتمام الكورس</h3>
                <p class="text-gray-700 mb-6 text-center">
                    احصل على شهادة معتمدة عند إتمام أي كورس بنجاح. الشهادة قابلة للتحقق ومقبولة في سوق العمل.
                </p>
                <ul class="space-y-3 text-gray-700">
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 ml-3"></i>
                        شهادة رقمية قابلة للتحقق
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 ml-3"></i>
                        يمكن مشاركتها على LinkedIn
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 ml-3"></i>
                        معترف بها في سوق العمل
                    </li>
                </ul>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-lg p-8 card-hover border-r-4 border-blue-500">
                <div class="text-6xl text-blue-500 mb-4 text-center">
                    <i class="fas fa-medal"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4 text-center">شهادة احترافية</h3>
                <p class="text-gray-700 mb-6 text-center">
                    شهادة احترافية للمعلّمين الذين يكملون متطلبات المسارات التدريبية المتقدمة على المنصة.
                </p>
                <ul class="space-y-3 text-gray-700">
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 ml-3"></i>
                        شهادة متقدمة معتمدة
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 ml-3"></i>
                        إثبات الكفاءة المهنية
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 ml-3"></i>
                        ميزة تنافسية في السوق
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection

