<?php

namespace App\Console\Commands;

use App\Models\InstructorProfile;
use App\Support\InstructorProfilePublication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AuditInstructorProfilesCommand extends Command
{
    protected $signature = 'hesetak:audit-instructor-profiles {--demote : إيقاف نشر المعتمدين الناقصين}';

    protected $description = 'يعرض (أو يوقف نشر) الملفات المعتمدة الناقصة المطابقة أو التجريبية';

    public function handle(): int
    {
        if (! Schema::hasTable('instructor_profiles')) {
            $this->warn('جدول instructor_profiles غير موجود.');

            return self::SUCCESS;
        }

        $rows = InstructorProfile::query()
            ->approved()
            ->with(['user.teachingLearningPaths'])
            ->get()
            ->map(function (InstructorProfile $profile) {
                $eval = InstructorProfilePublication::evaluate($profile);

                return [
                    'id' => $profile->id,
                    'name' => $profile->user?->name ?? '—',
                    'headline' => mb_substr((string) ($profile->headline ?? ''), 0, 40),
                    'ok' => $eval['ok'],
                    'demo' => $eval['is_demo'],
                    'missing' => implode(' · ', $eval['missing']),
                    'profile' => $profile,
                ];
            })
            ->filter(fn (array $row) => ! $row['ok'])
            ->values();

        if ($rows->isEmpty()) {
            $this->info('لا توجد ملفات معتمدة ناقصة المطابقة.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'المدرب', 'العنوان', 'تجريبي؟', 'النواقص'],
            $rows->map(fn (array $r) => [
                $r['id'],
                $r['name'],
                $r['headline'],
                $r['demo'] ? 'نعم' : 'لا',
                $r['missing'],
            ])->all()
        );

        if (! $this->option('demote')) {
            $this->comment('للإيقاف الجماعي: php artisan hesetak:audit-instructor-profiles --demote');

            return self::SUCCESS;
        }

        $n = 0;
        foreach ($rows as $row) {
            if (InstructorProfilePublication::demoteIncompleteApproved($row['profile'])) {
                $n++;
            }
        }
        $this->info("تم إيقاف نشر {$n} ملفاً.");

        return self::SUCCESS;
    }
}
