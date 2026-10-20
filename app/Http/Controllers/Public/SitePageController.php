<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicAboutSettings;
use App\Support\TadrisPublicNav;
use Illuminate\Http\Request;

class SitePageController extends Controller
{
    public function show(string $page)
    {
        $node = TadrisPublicNav::find($page);
        abort_if(! $node || ! ($node['enabled'] ?? true), 404);

        $content = __('site.pages.'.$page);
        if (! is_array($content)) {
            $content = [
                'title' => $node['label'],
                'lead' => '',
                'sections' => [],
            ];
        }

        if ($page === 'about') {
            $about = PublicAboutSettings::payload();
            $content = array_merge($content, [
                'kicker' => $about['kicker'],
                'title' => $about['title'],
                'lead' => $about['lead'],
                'meta_title' => $about['meta_title'] ?: ($content['meta_title'] ?? null),
                'meta_description' => $about['meta_description'] ?: ($content['meta_description'] ?? null),
                'sections' => $about['sections'] !== [] ? $about['sections'] : ($content['sections'] ?? []),
            ]);
        }

        return view($node['view'] ?? 'public.site.show', [
            'pageKey' => $page,
            'node' => $node,
            'content' => $content,
            'laslesNavActive' => $page,
            'pageTitle' => match ($page) {
                'about' => __('seo.about.title'),
                'contact' => __('seo.contact.title'),
                default => ($content['meta_title'] ?? null) ?: (($content['title'] ?? $node['label']).' — '.__('common.app_name')),
            },
            'pageDescription' => match ($page) {
                'about' => __('seo.about.description'),
                'contact' => __('seo.contact.description'),
                default => $content['meta_description'] ?? ($content['lead'] ?? __('landing.meta.description')),
            },
            'bodyClass' => 'lasles-site-page lasles-site-page--'.$page,
        ]);
    }

    public function contact()
    {
        $node = TadrisPublicNav::find('contact');
        $content = __('site.pages.contact');

        return view('public.site.contact', [
            'pageKey' => 'contact',
            'node' => $node,
            'content' => is_array($content) ? $content : [],
            'laslesNavActive' => 'contact',
            'pageTitle' => __('seo.contact.title'),
            'pageDescription' => __('seo.contact.description'),
            'bodyClass' => 'mc-body--contact',
            'mcActive' => 'contact',
        ]);
    }

    public function contactStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'topic' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = \App\Models\ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['topic'] ?: __('site.nav.contact'),
            'message' => $validated['message'],
        ]);

        try {
            app(\App\Services\ContactMessageAlertService::class)->notifyAdmins($message);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('public.contact')
            ->with('status', __('site.pages.contact.success'));
    }
}
