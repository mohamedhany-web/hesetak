<?php

namespace Tests\Feature;

use App\Models\FreeTrialBooking;
use App\Services\FreeTrialBookingService;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class FreeTrialTimezoneTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->extendFreeTrialSchema();
        config(['app.timezone' => 'UTC', 'platform.academy_timezone' => 'Africa/Cairo']);
        Carbon::setTestNow(Carbon::parse('2026-03-10 12:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function extendFreeTrialSchema(): void
    {
        Schema::create('free_trial_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('country_code', 12)->nullable();
            $table->string('goal')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('status', 32)->default('pending');
            $table->text('notes')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('us_state', 64)->nullable();
            $table->unsignedBigInteger('recommended_academic_year_id')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_slots_without_instructor_are_empty(): void
    {
        $slots = FreeTrialBookingService::availableSlots(
            Carbon::parse('2026-03-10 00:00:00', 'UTC'),
            Carbon::parse('2026-03-17 23:59:59', 'UTC'),
            'America/New_York'
        );

        $this->assertTrue($slots->isEmpty());
    }

    public function test_http_slots_require_instructor(): void
    {
        $response = $this->getJson('/free-trial/slots?days=7&timezone=America/New_York');
        $response->assertStatus(422)
            ->assertJsonPath('mode', 'request');
    }

    public function test_book_creates_pending_request_with_timezone(): void
    {
        $starts = Carbon::parse('2026-03-12 16:00:00', 'UTC');

        $booking = FreeTrialBookingService::book([
            'name' => 'Parent Test',
            'email' => 'parent-tz@example.com',
            'phone' => '512345678',
            'country_code' => '+966',
            'goal' => 'trial',
            'starts_at' => $starts->toIso8601String(),
            'timezone' => 'America/New_York',
            'us_state' => 'نيويورك',
        ]);

        $this->assertSame(FreeTrialBooking::STATUS_PENDING, $booking->status);
        $this->assertSame('America/New_York', $booking->timezone);
        $this->assertSame('نيويورك', $booking->us_state);
        $this->assertSame('16:00', $booking->starts_at->copy()->utc()->format('H:i'));
        $this->assertSame(AppTimezone::QUALITY_GOOD, AppTimezone::slotQuality($booking->starts_at, 'America/New_York'));
    }

    public function test_http_book_request_without_slot_window(): void
    {
        $response = $this->postJson('/free-trial/book', [
            'name' => 'Parent HTTP',
            'email' => 'parent-http@example.com',
            'phone' => '512345678',
            'country_code' => '+966',
            'goal' => 'consultation',
            'starts_at' => '2026-03-15T18:00:00+02:00',
            'timezone' => 'Africa/Cairo',
            'as_request' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('booking.status', FreeTrialBooking::STATUS_PENDING)
            ->assertJsonPath('booking.is_request', true);
    }
}
