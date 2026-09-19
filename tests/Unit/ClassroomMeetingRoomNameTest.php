<?php

namespace Tests\Unit;

use App\Models\ClassroomMeeting;
use PHPUnit\Framework\TestCase;

class ClassroomMeetingRoomNameTest extends TestCase
{
    public function test_canonical_room_name_uses_glottical_prefix(): void
    {
        $this->assertSame('حصتك-ABC12345', ClassroomMeeting::canonicalRoomName('abc12345'));
    }

    public function test_live_room_name_prefers_stored_room_name(): void
    {
        $meeting = new ClassroomMeeting([
            'code' => 'ABCDEFGH',
            'room_name' => 'tutoring-9-legacy',
        ]);

        $this->assertSame('tutoring-9-legacy', $meeting->liveRoomName());
    }

    public function test_live_room_name_falls_back_to_canonical(): void
    {
        $meeting = new ClassroomMeeting([
            'code' => 'ABCDEFGH',
            'room_name' => null,
        ]);

        $this->assertSame('حصتك-ABCDEFGH', $meeting->liveRoomName());
    }
}
