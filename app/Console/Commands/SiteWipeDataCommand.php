<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SiteDataWipeService;
use Illuminate\Console\Command;

class SiteWipeDataCommand extends Command
{
    protected $signature = 'site:wipe-data
        {--keep-email= : بريد الأدمن المراد الإبقاء عليه (افتراضي: المستخدم الحالي غير متاح — مرّر البريد)}
        {--keep-id= : معرّف مستخدم للإبقاء عليه (يمكن تكراره عبر فاصلة)}
        {--force : تأكيد التنفيذ دون سؤال}';

    protected $description = 'مسح كل بيانات الموقع مع الإبقاء على حساب أدمن واحد (أو أكثر)، ثم بذر الصلاحيات';

    public function handle(SiteDataWipeService $wipe): int
    {
        if (! $this->option('force') && ! $this->confirm('هذا سيمسح كل البيانات ما عدا الأدمن المحدد. متأكد؟')) {
            $this->warn('تم الإلغاء.');

            return self::FAILURE;
        }

        $keepIds = [];
        if ($email = trim((string) $this->option('keep-email'))) {
            $id = User::query()->where('email', $email)->value('id');
            if (! $id) {
                $this->error("لا يوجد مستخدم بالبريد: {$email}");

                return self::FAILURE;
            }
            $keepIds[] = (int) $id;
        }

        if ($raw = trim((string) $this->option('keep-id'))) {
            foreach (explode(',', $raw) as $part) {
                $part = (int) trim($part);
                if ($part > 0) {
                    $keepIds[] = $part;
                }
            }
        }

        if ($keepIds === []) {
            $admin = User::query()
                ->whereIn('role', ['super_admin', 'admin'])
                ->orderByRaw("CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->first();
            if (! $admin) {
                $this->error('لم يُعثر على أدمن للإبقاء عليه. مرّر --keep-email أو --keep-id');

                return self::FAILURE;
            }
            $keepIds[] = (int) $admin->id;
            $this->info("سيتم الإبقاء على: {$admin->email} (#{$admin->id})");
        }

        $result = $wipe->wipeExceptAdmins($keepIds, true);
        $this->info(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
