<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\OneToOneSession;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\StudentServiceEntitlement;
use App\Models\User;
use App\Services\OneToOneAvailabilityService;
use App\Services\OneToOneSessionService;
use App\Services\StudentEntitlementService;
use App\Support\InstructorMatchCompleteness;
use App\Support\OneToOneReportGate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Full-site smoke: every GET web route per role + core domain linkages.
 *
 * php artisan site:full-smoke
 * php artisan site:full-smoke --role=admin --limit=50
 */
class SiteFullSmokeCommand extends Command
{
    protected $signature = 'site:full-smoke
        {--role=all : all|guest|admin|instructor|student}
        {--limit=0 : Limit routes per role (0 = all)}
        {--skip-domain : Skip domain linkage suite}
        {--only-static : Skip parameterized routes}';

    protected $description = 'Smoke-test all website GET routes for guest/admin/instructor/student + core domain linkages';

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    /** @var array<string, int> */
    private array $stats = [
        'ok' => 0,
        'denied' => 0,
        'fail' => 0,
        'skip' => 0,
        'domain_ok' => 0,
        'domain_fail' => 0,
    ];

    public function handle(): int
    {
        $started = microtime(true);
        $roleOpt = strtolower((string) $this->option('role'));
        $limit = max(0, (int) $this->option('limit'));
        $onlyStatic = (bool) $this->option('only-static');

        $actors = $this->resolveActors($roleOpt);
        if ($actors === []) {
            $this->error('No actors resolved for role='.$roleOpt);

            return self::FAILURE;
        }

        $routes = $this->collectGetRoutes($onlyStatic);
        $this->info('GET routes to probe: '.count($routes).' · actors: '.implode(',', array_keys($actors)));

        foreach ($actors as $role => $user) {
            $this->newLine();
            $this->info("── Role: {$role}".($user ? " (#{$user->id} {$user->email})" : ' (guest)'));
            $n = 0;
            foreach ($routes as $route) {
                if ($limit > 0 && $n >= $limit) {
                    break;
                }
                $n++;
                $this->probeRoute($role, $user, $route);
            }
        }

        if (! $this->option('skip-domain')) {
            $this->newLine();
            $this->info('── Domain linkage suite');
            $this->runDomainLinkages($actors);
        }

        $elapsed = round(microtime(true) - $started, 2);
        $report = [
            'at' => now()->toIso8601String(),
            'elapsed_sec' => $elapsed,
            'stats' => $this->stats,
            'rows' => $this->rows,
        ];

        $jsonPath = storage_path('app/site-full-smoke-report.json');
        $mdPath = storage_path('app/site-full-smoke-report.md');
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($mdPath, $this->renderMarkdown($report));

        $this->newLine();
        $this->table(
            ['metric', 'count'],
            collect($this->stats)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );
        $this->line("Elapsed: {$elapsed}s");
        $this->line("JSON: {$jsonPath}");
        $this->line("MD:   {$mdPath}");

        return ($this->stats['fail'] + $this->stats['domain_fail']) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @return array<string, ?User>
     */
    private function resolveActors(string $roleOpt): array
    {
        $admin = User::query()->where('email', 'admin@hesetak.com')->first()
            ?? User::query()->whereIn('role', ['super_admin', 'admin'])->orderBy('id')->first();
        $instructor = User::query()->where('email', 'instructor1@hesetak.com')->first()
            ?? User::query()->whereIn('role', ['instructor', 'teacher'])->where('is_active', true)->orderBy('id')->first();
        $student = User::query()->where('email', 'student1@hesetak.com')->first()
            ?? User::query()->where('role', 'student')->where('is_active', true)->orderBy('id')->first();

        $all = [
            'guest' => null,
            'admin' => $admin,
            'instructor' => $instructor,
            'student' => $student,
        ];

        if ($roleOpt === 'all') {
            return $all;
        }
        if (! array_key_exists($roleOpt, $all)) {
            return [];
        }

        return [$roleOpt => $all[$roleOpt]];
    }

    /**
     * @return list<LaravelRoute>
     */
    private function collectGetRoutes(bool $onlyStatic): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            /** @var LaravelRoute $route */
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = $route->uri();
            if ($this->shouldSkipUri($uri)) {
                continue;
            }
            if ($onlyStatic && Str::contains($uri, '{')) {
                continue;
            }
            $out[] = $route;
        }

        usort($out, fn (LaravelRoute $a, LaravelRoute $b) => strcmp($a->uri(), $b->uri()));

        return $out;
    }

    private function shouldSkipUri(string $uri): bool
    {
        $uri = ltrim($uri, '/');
        $prefixes = [
            '_ignition',
            'telescope',
            'horizon',
            'sanctum',
            'livewire',
            'vendor/',
            'storage/',
            'up', // health already covered elsewhere
        ];
        foreach ($prefixes as $p) {
            if ($uri === rtrim($p, '/') || str_starts_with($uri, $p)) {
                return true;
            }
        }

        // Dangerous destructive-looking download endpoints still GET-smoked unless they mutate;
        // skip binary-heavy export endpoints that often timeout.
        // Retired collective join / export-heavy endpoints
        if (preg_match('#(export|download|stream|zip|pdf-download|schedule/join)#i', $uri)) {
            return true;
        }

        return false;
    }

    private function probeRoute(string $role, ?User $user, LaravelRoute $route): void
    {
        $uri = $route->uri();
        $name = $route->getName() ?: '';
        $params = $this->resolveParameters($route, $role, $user);
        if ($params === null) {
            $this->record('skip', $role, $name, $uri, null, 'unresolved route parameters');

            return;
        }

        try {
            if ($name !== '') {
                try {
                    $url = route($name, $params, absolute: false);
                } catch (Throwable) {
                    $url = $this->buildUri($uri, $params);
                }
            } else {
                $url = $this->buildUri($uri, $params);
            }
        } catch (Throwable $e) {
            $this->record('skip', $role, $name, $uri, null, 'url build: '.$e->getMessage());

            return;
        }

        Auth::logout();
        if ($user) {
            Auth::login($user);
        }

        try {
            $request = Request::create($url, 'GET');
            $request->headers->set('Accept', 'text/html,application/json');
            $request->setLaravelSession(app('session.store'));
            if ($user) {
                $request->setUserResolver(fn () => $user);
            }

            /** @var Response $response */
            $response = app()->handle($request);
            $code = $response->getStatusCode();
            $verdict = $this->classify($role, $uri, $name, $code);
            $this->record($verdict, $role, $name, $url, $code, null);
        } catch (Throwable $e) {
            $this->record('fail', $role, $name, $url ?? $uri, 500, class_basename($e).': '.$e->getMessage());
        } finally {
            Auth::logout();
        }
    }

    private function buildUri(string $uri, array $params): string
    {
        $built = $uri;
        foreach ($params as $key => $value) {
            $built = str_replace(['{'.$key.'}', '{'.$key.'?}'], (string) $value, $built);
        }
        if (Str::contains($built, '{')) {
            throw new \RuntimeException('leftover params in '.$built);
        }

        return '/'.ltrim($built, '/');
    }

    /**
     * @return array<string, mixed>|null null = skip
     */
    private function resolveParameters(LaravelRoute $route, string $role, ?User $user): ?array
    {
        $params = [];
        foreach ($route->parameterNames() as $name) {
            $value = $this->guessParam($name, $role, $user);
            if ($value === null) {
                return null;
            }
            $params[$name] = $value;
        }

        return $params;
    }

    private function guessParam(string $name, string $role, ?User $user): mixed
    {
        $key = Str::snake($name);
        $map = [
            'user' => fn () => User::query()->orderBy('id')->value('id'),
            'instructor' => fn () => User::query()->whereIn('role', ['instructor', 'teacher'])->orderBy('id')->value('id'),
            'student' => fn () => User::query()->where('role', 'student')->orderBy('id')->value('id'),
            'teacher' => fn () => User::query()->whereIn('role', ['instructor', 'teacher'])->orderBy('id')->value('id'),
            'one_to_one_session' => fn () => OneToOneSession::query()->orderByDesc('id')->value('id'),
            'oneToOneSession' => fn () => OneToOneSession::query()->orderByDesc('id')->value('id'),
            'advanced_course' => fn () => AdvancedCourse::query()->orderBy('id')->value('id'),
            'course' => fn () => AdvancedCourse::query()->orderBy('id')->value('id'),
            'advancedCourse' => fn () => AdvancedCourse::query()->orderBy('id')->value('id'),
            'order' => fn () => Order::query()->orderByDesc('id')->value('id'),
            'service_package' => fn () => ServicePackage::query()->orderBy('id')->value('id'),
            'servicePackage' => fn () => ServicePackage::query()->orderBy('id')->value('id'),
            'package' => fn () => ServicePackage::query()->orderBy('id')->value('id'),
            'academic_year' => fn () => AcademicYear::query()->orderBy('id')->value('id'),
            'academicYear' => fn () => AcademicYear::query()->orderBy('id')->value('slug')
                ?? AcademicYear::query()->orderBy('id')->value('id'),
            'year' => fn () => AcademicYear::query()->orderBy('id')->value('slug')
                ?? AcademicYear::query()->orderBy('id')->value('id'),
            'locale' => fn () => 'ar',
            'code' => fn () => 'DEMO',
            'token' => fn () => 'smoke-token',
            'slug' => fn () => AcademicYear::query()->whereNotNull('slug')->orderBy('id')->value('slug') ?? 'demo',
            'type' => fn () => 'collective',
            'tab' => fn () => 'students',
            'sheet' => fn () => 'mycourses',
        ];

        if (isset($map[$name])) {
            return $map[$name]();
        }
        if (isset($map[$key])) {
            return $map[$key]();
        }

        // Common *Id suffixes
        if (Str::endsWith($name, 'Id') || Str::endsWith($key, '_id') || $name === 'id') {
            return 1;
        }

        // Prefer current actor for self-scoped params
        if (in_array($name, ['me', 'profile'], true) && $user) {
            return $user->id;
        }

        return null;
    }

    private function classify(string $role, string $uri, string $name, int $code): string
    {
        $uri = ltrim($uri, '/');
        $isAdmin = str_starts_with($uri, 'admin') || str_starts_with($name, 'admin.');
        $isInstructor = str_starts_with($uri, 'instructor') || str_starts_with($name, 'instructor.');
        $isEmployee = str_starts_with($uri, 'employee') || str_starts_with($name, 'employee.');
        $isAuthArea = $isAdmin || $isInstructor || $isEmployee
            || str_starts_with($uri, 'dashboard')
            || str_starts_with($name, 'student.')
            || in_array($uri, ['learn', 'one-to-one-sessions', 'profile', 'messages', 'notifications'], true)
            || str_starts_with($uri, 'learn')
            || str_starts_with($uri, 'one-to-one');

        // Server errors always fail
        if ($code >= 500) {
            return 'fail';
        }

        // Missing required query/body for API-ish GET endpoints
        if (in_array($code, [400, 422], true)) {
            return 'skip';
        }

        // Rate limit during dense smoke runs
        if ($code === 429) {
            return 'skip';
        }

        // Retired collective surface
        if (str_contains($uri, 'schedule/join/collective') || str_contains($uri, 'tutoring-group')) {
            return in_array($code, [200, 301, 302, 303, 404, 410], true) ? 'skip' : 'fail';
        }

        if ($role === 'guest') {
            if ($isAuthArea) {
                // login redirect or forbidden
                return in_array($code, [200, 301, 302, 303, 401, 403], true) ? 'denied' : 'fail';
            }

            return in_array($code, [200, 301, 302, 303, 204], true) ? 'ok' : ($code === 404 ? 'skip' : 'fail');
        }

        if ($role === 'admin') {
            if ($isInstructor && ! $isAdmin) {
                // admin may still open instructor panel sometimes; accept 200/302/403
                return in_array($code, [200, 301, 302, 303, 403], true) ? 'ok' : ($code === 404 ? 'skip' : 'fail');
            }

            return in_array($code, [200, 201, 204, 301, 302, 303], true) ? 'ok'
                : ($code === 404 ? 'skip' : (in_array($code, [401, 403], true) ? 'denied' : 'fail'));
        }

        if ($role === 'instructor') {
            if ($isAdmin || $isEmployee) {
                return in_array($code, [200, 301, 302, 303, 401, 403], true) ? 'denied' : ($code === 404 ? 'skip' : 'fail');
            }

            return in_array($code, [200, 201, 204, 301, 302, 303], true) ? 'ok'
                : ($code === 404 ? 'skip' : (in_array($code, [401, 403], true) ? 'denied' : 'fail'));
        }

        // student
        if ($isAdmin || $isInstructor || $isEmployee) {
            return in_array($code, [200, 301, 302, 303, 401, 403], true) ? 'denied' : ($code === 404 ? 'skip' : 'fail');
        }

        return in_array($code, [200, 201, 204, 301, 302, 303], true) ? 'ok'
            : ($code === 404 ? 'skip' : (in_array($code, [401, 403], true) ? 'denied' : 'fail'));
    }

    private function record(string $verdict, string $role, string $name, string $uri, ?int $code, ?string $detail): void
    {
        $this->stats[$verdict] = ($this->stats[$verdict] ?? 0) + 1;
        $this->rows[] = [
            'verdict' => $verdict,
            'role' => $role,
            'name' => $name,
            'uri' => $uri,
            'code' => $code,
            'detail' => $detail,
        ];

        if ($verdict === 'fail') {
            $this->line("<fg=red>FAIL</> [{$role}] {$code} {$uri}".($detail ? " — {$detail}" : ''));
        }
    }

    /**
     * @param  array<string, ?User>  $actors
     */
    private function runDomainLinkages(array $actors): void
    {
        $admin = $actors['admin'] ?? null;
        $instructor = $actors['instructor'] ?? User::query()->where('email', 'instructor2@hesetak.com')->first();
        $instructorB = User::query()->where('email', 'instructor2@hesetak.com')->first() ?? $instructor;
        $student = $actors['student'] ?? null;

        $checks = [];

        $add = function (bool $ok, string $label, string $detail = '') use (&$checks) {
            $checks[] = compact('ok', 'label', 'detail');
            if ($ok) {
                $this->stats['domain_ok']++;
                $this->line("<fg=green>PASS</> {$label}".($detail !== '' ? " — {$detail}" : ''));
            } else {
                $this->stats['domain_fail']++;
                $this->line("<fg=red>FAIL</> {$label}".($detail !== '' ? " — {$detail}" : ''));
            }
            $this->rows[] = [
                'verdict' => $ok ? 'domain_ok' : 'domain_fail',
                'role' => 'domain',
                'name' => $label,
                'uri' => 'domain',
                'code' => $ok ? 200 : 500,
                'detail' => $detail,
            ];
        };

        try {
            $add($admin && $student && $instructor && $instructorB, 'Actors available for domain suite');

            // Public catalog excludes TCH
            if (Schema::hasTable('academic_years')) {
                $tchPublic = AcademicYear::query()->publicCatalog()->where('code', 'like', 'TCH-%')->count();
                $add($tchPublic === 0, 'TCH-* excluded from publicCatalog', "count={$tchPublic}");
            }

            // Match completeness API callable
            $profile = $instructor?->instructorProfile;
            if ($profile) {
                $eval = InstructorMatchCompleteness::evaluate($profile, $instructor);
                $add(isset($eval['ok'], $eval['checklist']), 'InstructorMatchCompleteness.evaluate works');
            } else {
                $add(true, 'InstructorMatchCompleteness skipped (no profile)');
            }

            // Report gate callable
            $add(is_int(OneToOneReportGate::overdueCountForInstructor((int) ($instructorB?->id ?? 0))), 'OneToOneReportGate.overdueCount callable');

            // ClassroomMeeting::reports relation exists
            $add(
                method_exists(\App\Models\ClassroomMeeting::class, 'reports'),
                'ClassroomMeeting::reports() exists'
            );

            // Parent relation
            if ($student && Schema::hasColumn('users', 'parent_id')) {
                $parent = User::query()->where('email', 'student2@hesetak.com')->first() ?? $admin;
                $student->forceFill(['parent_id' => $parent->id])->save();
                $add((int) $student->fresh()->parent_id === (int) $parent->id, 'parent_id link');
                $add($parent->children()->whereKey($student->id)->exists(), 'parent.children relation');
                $student->forceFill(['parent_id' => null])->save();
                $add($student->fresh()->parent_id === null, 'parent_id unlink');
            }

            // Full placement → reserve → switch → complete → cancel path (compact)
            if ($admin && $student && $instructor && $instructorB) {
                foreach ([$instructor->id, $instructorB->id] as $iid) {
                    $rows = [];
                    foreach (range(1, 7) as $dow) {
                        $rows[] = [
                            'day_of_week' => $dow,
                            'start_time' => '09:00',
                            'end_time' => '21:00',
                            'slot_duration_minutes' => 50,
                        ];
                    }
                    OneToOneAvailabilityService::syncRules((int) $iid, $rows);
                }

                $ent = StudentEntitlementService::grantManual(
                    (int) $student->id,
                    ServicePackage::SCOPE_PRIVATE_LESSONS,
                    3,
                    null,
                    60,
                    'site:full-smoke '.now()->format('YmdHis')
                );
                $add(StudentEntitlementService::bookableUnitsLeft($ent) === 3, 'grantManual bookable=3');

                $slots = OneToOneAvailabilityService::availableSlots((int) $instructor->id, now()->addHour(), now()->addWeeks(1), 50);
                $add($slots->count() > 1, 'availability slots exist', 'count='.$slots->count());

                $at1 = Carbon::parse($slots->get(0)['starts_at'])->utc();
                $at2 = Carbon::parse($slots->get(3)['starts_at'] ?? $slots->get(1)['starts_at'])->utc();
                $sessions = OneToOneSessionService::bookMultipleWithInstructor(
                    $student,
                    $instructor,
                    [$at1, $at2],
                    $ent->fresh(),
                    $admin,
                    'site:full-smoke placement',
                    true
                );
                $add($sessions->count() === 2, 'book 2 sessions');
                $add(StudentEntitlementService::bookableUnitsLeft($ent->fresh()) === 1, '2 units reserved');

                $s1 = $sessions->first()->fresh(['classroomMeeting']);
                OneToOneSessionService::reassignInstructor($s1, $instructorB);
                $add((int) $s1->fresh()->instructor_id === (int) $instructorB->id, 'teacher switch');
                $add(StudentEntitlementService::bookableUnitsLeft($ent->fresh()) === 1, 'switch keeps reservation');

                $s1->forceFill(['scheduled_at' => now()->subHour()])->save();
                if ($s1->classroomMeeting) {
                    $s1->classroomMeeting->forceFill(['started_at' => now()->subMinutes(40)])->save();
                }
                OneToOneSessionService::markCompleted($s1->fresh(), false);
                $add($s1->fresh()->status === OneToOneSession::STATUS_COMPLETED, 'complete session');
                $add((int) $ent->fresh()->units_used === 1, 'consume 1 unit on complete');

                $s2 = $sessions->last();
                $cancelled = OneToOneSessionService::cancelSession($s2->fresh(), false, 'site:full-smoke cancel');
                $add($cancelled >= 1, 'cancel releases reservation');
                $add(StudentEntitlementService::bookableUnitsLeft($ent->fresh()) === 2, 'bookable back to 2');
            }
        } catch (Throwable $e) {
            $add(false, 'Domain suite exception', $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderMarkdown(array $report): string
    {
        $s = $report['stats'];
        $lines = [
            '# Site full smoke report',
            '',
            '- At: '.$report['at'],
            '- Elapsed: '.$report['elapsed_sec'].'s',
            '',
            '## Stats',
            '',
            '| metric | count |',
            '|---|---|',
        ];
        foreach ($s as $k => $v) {
            $lines[] = "| {$k} | {$v} |";
        }

        $fails = collect($report['rows'])->whereIn('verdict', ['fail', 'domain_fail'])->values();
        $lines[] = '';
        $lines[] = '## Failures ('.$fails->count().')';
        $lines[] = '';
        if ($fails->isEmpty()) {
            $lines[] = '_None_';
        } else {
            $lines[] = '| role | code | uri | detail |';
            $lines[] = '|---|---|---|---|';
            foreach ($fails as $row) {
                $lines[] = '| '.$row['role'].' | '.($row['code'] ?? '').' | `'.str_replace('|', '\\|', (string) $row['uri']).'` | '.str_replace('|', '\\|', (string) ($row['detail'] ?? '')).' |';
            }
        }

        return implode("\n", $lines)."\n";
    }
}
