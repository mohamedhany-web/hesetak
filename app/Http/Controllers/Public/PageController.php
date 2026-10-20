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
        $faqs = collect();
        $categories = collect();
        $defaultFaqs = \App\Support\PlatformFaqDefaults::items();
        if (\Illuminate\Support\Facades\Schema::hasTable('faqs')) {
            $faqs = FAQ::active()
                ->orderBy('order')
                ->orderBy('created_at', 'desc')
                ->get()
                ->reject(fn (FAQ $faq) => \App\Support\PublicDemoContent::recordLooksLikeDemo([$faq->question, $faq->answer]))
                ->groupBy('category');

            $categories = FAQ::active()
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values();

            $defaultFaqs = FAQ::active()->doesntExist()
                ? \App\Support\PlatformFaqDefaults::items()
                : [];
        }

        $faqCopy = __('hesetak_pages.faq');
        $visibleFaqItems = collect($faqCopy['student'] ?? [])->merge($faqCopy['teacher'] ?? [])->values();

        return view('public.marketing.faq', [
            'faqs' => $faqs,
            'categories' => $categories,
            'defaultFaqs' => $defaultFaqs,
            'mcActive' => 'faq',
            'pageTitle' => __('seo.faq.title'),
            'pageDescription' => __('seo.faq.description'),
            'jsonldType' => 'faq',
            'jsonldFaqs' => $visibleFaqItems,
        ]);
    }

    public function forStudents()
    {
        $homePackages = \Illuminate\Support\Facades\Schema::hasTable('service_packages')
            ? app(\App\Services\PackageCatalogFilterService::class)->homeShowcase(6)
            : collect();

        return view('public.marketing.for-students', [
            'mcActive' => 'for-students',
            'pageTitle' => __('seo.students.title'),
            'pageDescription' => __('seo.students.description'),
            'homePackages' => $homePackages,
        ]);
    }

    public function forTeachers()
    {
        return view('public.marketing.for-teachers', [
            'mcActive' => 'for-teachers',
            'pageTitle' => __('seo.teachers.title'),
            'pageDescription' => __('seo.teachers.description'),
        ]);
    }

    public function how()
    {
        return view('public.marketing.how', [
            'mcActive' => 'how',
            'pageTitle' => __('seo.how.title'),
            'pageDescription' => __('seo.how.description'),
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

    public function pricing(\Illuminate\Http\Request $request)
    {
        $storefront = \Illuminate\Support\Facades\Schema::hasTable('service_packages')
            ? app(\App\Services\PricingStorefrontCatalog::class)->build(
                yearId: $request->filled('year') ? $request->integer('year') : null,
                subjectId: $request->filled('subject') ? $request->integer('subject') : null,
                curriculumType: $request->query('curriculum_type'),
            )
            : [
                'catalog' => [
                    'years' => collect(),
                    'subjects' => collect(),
                    'tracks' => [],
                    'selected_year_id' => null,
                    'selected_subject_id' => null,
                    'selected_curriculum_type' => null,
                    'packages' => collect(),
                    'viewer_locked' => false,
                ],
                'stages' => collect(),
                'subjects' => collect(),
                'cards' => [],
                'price_options_template' => [],
                'custom_builder_url' => route('public.service-packages.index', ['catalog' => 1]).'#private-builder',
                'private_rule' => null,
                'urgent_rule' => null,
            ];

        $packageCatalog = $storefront['catalog'];

        return view('public.marketing.pricing', [
            'mcActive' => 'pricing',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => __('seo.pricing.title'),
            'pageDescription' => __('seo.pricing.description'),
            'packageCatalog' => $packageCatalog,
            'packages' => $packageCatalog['packages'] ?? collect(),
            'storefront' => $storefront,
            'pricingCards' => $storefront['cards'],
            'pricingStages' => $storefront['stages'],
            'pricingSubjects' => $storefront['subjects'],
            'customBuilderUrl' => $storefront['custom_builder_url'],
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
