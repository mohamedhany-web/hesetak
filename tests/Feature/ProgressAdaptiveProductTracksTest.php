<?php

namespace Tests\Feature;

use App\Models\AdvancedCourse;
use App\Models\AdaptiveLearningSuggestion;
use App\Models\FamilyProgressReport;
use App\Models\User;
use App\Services\AdaptiveLearningService;
use App\Services\FamilyProgressReportService;
use App\Services\StudentProgressAnalyticsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class ProgressAdaptiveProductTracksTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
    }

    public function test_student_progress_hub_renders_comparison(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('password'),
            'progress_share_token' => 'tok_'.str_repeat('c', 44),
        ]);

        $this->actingAs($student)
            ->get(route('student.progress.index'))
            ->assertOk()
            ->assertSee(__('student_timeline.nav_progress_hub'), false);
    }

    public function test_analytics_persists_monthly_snapshot(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $payload = app(StudentProgressAnalyticsService::class)->comparePeriods($student);

        $this->assertArrayHasKey('current', $payload);
        $this->assertArrayHasKey('strengths', $payload);
        $this->assertDatabaseHas('student_progress_snapshots', [
            'user_id' => $student->id,
            'period_key' => $payload['period']['key'],
        ]);
    }

    public function test_adaptive_suggestions_include_product_tracks(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        AdvancedCourse::query()->create([
            'title' => 'شرح فيديو تجريبي',
            'is_active' => true,
            'is_featured' => true,
            'product_track' => AdaptiveLearningService::TRACK_RECORDED,
            'price' => 100,
        ]);

        AdvancedCourse::query()->create([
            'title' => 'كتاب مراجعة تجريبي',
            'is_active' => true,
            'is_featured' => true,
            'product_track' => AdaptiveLearningService::TRACK_BOOK,
            'price' => 50,
        ]);

        $suggestions = app(AdaptiveLearningService::class)->refreshSuggestionsFor($student);

        $this->assertTrue($suggestions->contains(fn ($s) => $s->kind === AdaptiveLearningSuggestion::KIND_RECORDED_COURSE));
        $this->assertTrue($suggestions->contains(fn ($s) => $s->kind === AdaptiveLearningSuggestion::KIND_BOOK_MATERIAL));
    }

    public function test_recorded_and_books_catalog_routes(): void
    {
        AdvancedCourse::query()->create([
            'title' => 'كورس مسجل ظاهر',
            'is_active' => true,
            'product_track' => AdaptiveLearningService::TRACK_RECORDED,
            'price' => 120,
        ]);

        $this->get(route('public.recorded-courses'))
            ->assertOk()
            ->assertSee('كورس مسجل ظاهر', false);

        $this->get(route('public.books'))
            ->assertOk();
    }

    public function test_family_progress_report_records_send_attempt(): void
    {
        Mail::fake();

        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'email' => 'student-axis3@example.com',
            'password' => Hash::make('password'),
        ]);

        $report = app(FamilyProgressReportService::class)->sendForStudent($student, 'email');

        $this->assertNotNull($report);
        $this->assertSame(FamilyProgressReport::STATUS_SENT, $report->status);
        $this->assertDatabaseHas('family_progress_reports', [
            'student_id' => $student->id,
            'status' => FamilyProgressReport::STATUS_SENT,
        ]);
    }

    public function test_student_can_refresh_adaptive_from_hub(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($student)
            ->post(route('student.progress.refresh-adaptive'))
            ->assertRedirect();
    }
}
