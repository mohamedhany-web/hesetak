<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use App\Models\SiteTestimonial;
use App\Support\PlatformFaqDefaults;
use Illuminate\Http\Request;

class PageController extends Controller
{
    // Home page is handled by welcome.blade.php route

    public function about()
    {
        return redirect()->route('public.about');
    }

    public function faq()
    {
        $faqs = FAQ::active()
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('category');

        $categories = FAQ::active()
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        // عند وجود أسئلة في لوحة التحكم نعرضها فقط؛ الأسئلة الافتراضية تظهر عند عدم وجود أي سجل نشط
        $defaultFaqs = FAQ::active()->doesntExist()
            ? PlatformFaqDefaults::items()
            : [];

        return view('public.marketing.faq', [
            'faqs' => $faqs,
            'categories' => $categories,
            'defaultFaqs' => $defaultFaqs,
            'mcActive' => 'faq',
            'pageTitle' => __('hesetak_pages.faq.meta_title'),
            'pageDescription' => __('hesetak_pages.faq.meta_description'),
        ]);
    }

    public function forStudents()
    {
        return view('public.marketing.for-students', [
            'mcActive' => 'for-students',
            'pageTitle' => __('hesetak_pages.for_students.meta_title'),
            'pageDescription' => __('hesetak_pages.for_students.meta_description'),
        ]);
    }

    public function forTeachers()
    {
        return view('public.marketing.for-teachers', [
            'mcActive' => 'for-teachers',
            'pageTitle' => __('hesetak_pages.for_teachers.meta_title'),
            'pageDescription' => __('hesetak_pages.for_teachers.meta_description'),
        ]);
    }

    public function how()
    {
        return view('public.marketing.how', [
            'mcActive' => 'how',
            'pageTitle' => __('hesetak_pages.how.meta_title'),
            'pageDescription' => __('hesetak_pages.how.meta_description'),
        ]);
    }

    public function curricula()
    {
        return redirect()->route('public.curricula');
    }

    public function terms()
    {
        return view('public.terms', [
            'pageTitle' => __('public.terms_page_title').' — '.__('landing.nav.brand'),
            'pageDescription' => __('public.legal_terms_meta', ['brand' => __('landing.nav.brand')]),
            'mcActive' => '',
        ]);
    }

    public function privacy()
    {
        return view('public.privacy', [
            'pageTitle' => __('public.privacy_page_title').' — '.__('landing.nav.brand'),
            'pageDescription' => __('public.legal_privacy_meta', ['brand' => __('landing.nav.brand')]),
            'mcActive' => '',
        ]);
    }

    public function pricing()
    {
        $packages = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('service_packages')) {
            $packages = \App\Models\ServicePackage::storefrontCatalog();
        }

        return view('public.marketing.pricing', [
            'mcActive' => 'pricing',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => __('public.pricing_page_title'),
            'pageDescription' => __('public.pricing_meta_description'),
            'packages' => $packages,
        ]);
    }

    public function team()
    {
        return view('public.team', [
            'pageTitle' => (__('public.team_page_title') ?: 'الفريق').' — '.__('landing.nav.brand'),
            'pageDescription' => 'فريق حصتك يعمل على تقديم تجربة دروس خصوصية أونلاين موثوقة.',
            'mcActive' => '',
        ]);
    }

    public function certificates()
    {
        return view('public.certificates', [
            'pageTitle' => (__('public.certificates_page_title') ?: 'الشهادات').' — '.__('landing.nav.brand'),
            'pageDescription' => 'شهادات إتمام رقمية للكورسات المؤهلة مع التحقق من صحتها.',
            'mcActive' => '',
        ]);
    }

    public function help()
    {
        return view('public.help', [
            'pageTitle' => __('public.help_page_title').' — '.__('landing.nav.brand'),
            'pageDescription' => __('public.help_meta_description', ['brand' => __('landing.nav.brand')]),
            'mcActive' => '',
        ]);
    }

    public function path()
    {
        return redirect()->route('public.how');
    }

    public function refund()
    {
        return view('public.refund', [
            'pageTitle' => __('public.refund_page_title').' — '.__('landing.nav.brand'),
            'pageDescription' => __('public.refund_page_title'),
            'mcActive' => '',
        ]);
    }

    public function testimonials()
    {
        $testimonials = SiteTestimonial::query()
            ->active()
            ->ordered()
            ->get();

        return view('public.testimonials', [
            'testimonials' => $testimonials,
            'pageTitle' => (__('public.testimonials_page_title') ?: 'آراء المتعلمين').' — '.__('landing.nav.brand'),
            'pageDescription' => __('public.home_testimonials_sub'),
            'mcActive' => '',
        ]);
    }

    public function events()
    {
        return view('public.events', [
            'pageTitle' => (__('public.events_page_title') ?: 'التعلم المباشر').' — '.__('landing.nav.brand'),
            'pageDescription' => 'تعلم أونلاين طوال العام عبر حصص فردية ومناهج وكورسات.',
            'mcActive' => '',
        ]);
    }

    public function partners()
    {
        return view('public.partners', [
            'pageTitle' => (__('public.partners_page_title') ?: 'الشراكات').' — '.__('landing.nav.brand'),
            'pageDescription' => 'تعاون مع حصتك لتوسيع الوصول إلى دروس خصوصية أونلاين.',
            'mcActive' => '',
        ]);
    }
}
