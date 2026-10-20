<?php

namespace App\Console\Commands;

use App\Models\FAQ;
use App\Models\InstructorProfile;
use App\Models\PopupAd;
use App\Models\ServicePackage;
use App\Models\SiteTestimonial;
use App\Services\PublicInstructorDirectory;
use App\Support\PublicDemoContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class UnpublishDemoPublicContent extends Command
{
    protected $signature = 'hesetak:unpublish-demo-content';

    protected $description = 'إيقاف ظهور السجلات التجريبية (test / تجريبي / website) على الواجهة العامة';

    public function handle(): int
    {
        $hidden = 0;

        if (Schema::hasTable('site_testimonials')) {
            foreach (SiteTestimonial::query()->where('is_active', true)->get() as $row) {
                if (PublicDemoContent::recordLooksLikeDemo([$row->author_name, $row->role_label, $row->body])) {
                    $row->update(['is_active' => false]);
                    $hidden++;
                }
            }
        }

        if (Schema::hasTable('faqs')) {
            foreach (FAQ::query()->where('is_active', true)->get() as $row) {
                if (PublicDemoContent::recordLooksLikeDemo([$row->question, $row->answer, $row->category])) {
                    $row->update(['is_active' => false]);
                    $hidden++;
                }
            }
        }

        if (Schema::hasTable('popup_ads')) {
            foreach (PopupAd::query()->where('is_active', true)->get() as $row) {
                if (PublicDemoContent::recordLooksLikeDemo([$row->title, $row->body, $row->cta_text])) {
                    $row->update(['is_active' => false]);
                    $hidden++;
                }
            }
        }

        if (Schema::hasTable('service_packages')) {
            foreach (ServicePackage::query()->where('is_active', true)->get() as $row) {
                if (PublicDemoContent::recordLooksLikeDemo([$row->name, $row->tagline, $row->description, $row->badge])) {
                    $row->update(['is_active' => false, 'is_featured' => false]);
                    $hidden++;
                }
            }
        }

        if (Schema::hasTable('instructor_profiles')) {
            foreach (InstructorProfile::query()->approved()->with('user')->get() as $profile) {
                if (PublicInstructorDirectory::profileLooksLikeDemo($profile)) {
                    $profile->update(['status' => InstructorProfile::STATUS_DRAFT]);
                    $hidden++;
                }
            }
        }

        $this->info("تم إخفاء {$hidden} سجل تجريبي عن الواجهة العامة.");

        return self::SUCCESS;
    }
}
