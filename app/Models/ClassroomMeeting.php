<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClassroomMeeting extends Model
{
    protected $fillable = [
        'user_id',
        'consultation_request_id',
        'one_to_one_session_id',
        'tutoring_group_booking_id',
        'code',
        'room_name',
        'title',
        'scheduled_for',
        'planned_duration_minutes',
        'max_participants',
        'participants_peak',
        'started_at',
        'ended_at',
        'recording_disk',
        'recording_path',
        'recording_audio_path',
        'recording_mime_type',
        'recording_audio_mime_type',
        'recording_size',
        'recording_audio_size',
        'recording_duration_seconds',
        'recording_audio_duration_seconds',
        'recording_uploaded_at',
        'settings',
    ];

    protected $casts = [
        'max_participants' => 'integer',
        'participants_peak' => 'integer',
        'scheduled_for' => 'datetime',
        'planned_duration_minutes' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'recording_size' => 'integer',
        'recording_audio_size' => 'integer',
        'recording_duration_seconds' => 'integer',
        'recording_audio_duration_seconds' => 'integer',
        'recording_uploaded_at' => 'datetime',
        'settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class, 'consultation_request_id');
    }

    public function oneToOneSession(): BelongsTo
    {
        return $this->belongsTo(OneToOneSession::class, 'one_to_one_session_id');
    }

    public function tutoringGroupBooking(): BelongsTo
    {
        return $this->belongsTo(TutoringGroupBooking::class, 'tutoring_group_booking_id');
    }

    public function participants()
    {
        return $this->hasMany(ClassroomMeetingParticipant::class, 'classroom_meeting_id');
    }

    /** تقارير نصية عبر n8n مرتبطة بالتسجيل/التقرير الصوتي */
    public function aiReports(): HasMany
    {
        return $this->hasMany(ClassroomMeetingReport::class, 'classroom_meeting_id');
    }

    public function isLive(): bool
    {
        return $this->started_at && ! $this->ended_at;
    }

    /** سبورة الضيوف: قلم + ممحاة عند تفعيل منظم الاجتماع */
    public function allowsParticipantWhiteboard(): bool
    {
        return (bool) data_get($this->settings, 'allow_participant_whiteboard', false);
    }

    /**
     * اسم غرفة البث الموحّد للضيف والمضيف (LiveKit).
     */
    public static function canonicalRoomName(string $code): string
    {
        return 'حصتك-'.strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    public function liveRoomName(): string
    {
        if (is_string($this->room_name) && $this->room_name !== '') {
            return $this->room_name;
        }

        return self::canonicalRoomName((string) $this->code);
    }

    /**
     * عنوان شريط الغرفة بدون أسماء طلاب (للمعلم والطالب معاً).
     */
    public function roomChromeTitle(): string
    {
        $title = trim((string) ($this->title ?: ''));
        if ($title === '') {
            return 'غرفة '.(string) $this->code;
        }

        $names = [];
        if ($this->relationLoaded('oneToOneSession') || $this->one_to_one_session_id) {
            $session = $this->relationLoaded('oneToOneSession')
                ? $this->oneToOneSession
                : $this->oneToOneSession()->with('student:id,name')->first();
            if ($session?->student?->name) {
                $names[] = (string) $session->student->name;
            }
        }
        if ($this->relationLoaded('tutoringGroupBooking') || $this->tutoring_group_booking_id) {
            $booking = $this->relationLoaded('tutoringGroupBooking')
                ? $this->tutoringGroupBooking
                : $this->tutoringGroupBooking()->with('user:id,name')->first();
            if ($booking?->user?->name) {
                $names[] = (string) $booking->user->name;
            }
            if (method_exists($booking, 'contactName') && filled($booking?->contactName())) {
                $names[] = (string) $booking->contactName();
            }
        }
        if ($this->relationLoaded('consultationRequest') || $this->consultation_request_id) {
            $consultation = $this->relationLoaded('consultationRequest')
                ? $this->consultationRequest
                : $this->consultationRequest()->with('student:id,name')->first();
            if ($consultation?->student?->name) {
                $names[] = (string) $consultation->student->name;
            }
        }

        foreach (array_unique(array_filter($names)) as $name) {
            $escaped = preg_quote($name, '/');
            $title = preg_replace('/\s*[—–-]\s*'.$escaped.'\s*$/u', '', $title) ?? $title;
            $title = preg_replace('/^'.$escaped.'\s*[—–-]\s*/u', '', $title) ?? $title;
            $title = preg_replace('/\s*'.$escaped.'\s*/u', ' ', $title) ?? $title;
        }

        // عناوين قديمة شائعة بدون ربط واضح
        if (preg_match('/^حصة\s*1\s*:\s*1\s*[—–-]\s*.+$/u', $title)) {
            $title = 'حصة 1:1';
        }
        if ($this->consultation_request_id && preg_match('/^استشارة\s*:\s*.+$/u', $title)) {
            $title = 'استشارة';
        }

        $title = trim(preg_replace('/\s{2,}/u', ' ', $title) ?? $title, " \t\n\r\0\x0B—–-");

        return $title !== '' ? $title : ('غرفة '.(string) $this->code);
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function hasBrowserRecording(): bool
    {
        return $this->hasRecordingMediaOnR2();
    }

    /** هل يوجد ملف تسجيل (فيديو و/أو صوت) مرفوع على قرص التسجيلات السحابي؟ */
    public function hasRecordingMediaOnR2(): bool
    {
        if ($this->recording_disk !== 'live_recordings_r2') {
            return false;
        }

        return ! empty($this->recording_path) || ! empty($this->recording_audio_path);
    }

    public function getRecordingDownloadUrlAttribute(): ?string
    {
        if (! $this->hasRecordingMediaOnR2() || empty($this->recording_path)) {
            return null;
        }

        try {
            return Storage::disk('live_recordings_r2')->temporaryUrl(
                $this->recording_path,
                now()->addHours(2)
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getRecordingAudioDownloadUrlAttribute(): ?string
    {
        if (! $this->hasRecordingMediaOnR2() || empty($this->recording_audio_path)) {
            return null;
        }

        try {
            return Storage::disk('live_recordings_r2')->temporaryUrl(
                $this->recording_audio_path,
                now()->addHours(2)
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}
