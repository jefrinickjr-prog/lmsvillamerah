<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\LiveStreamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_online_student_can_join_live_stream(): void
    {
        [$student, $session] = $this->makeSession();

        $this->actingAs($student)->post(route('live-streams.join', $session))
            ->assertRedirect('https://meet.google.com/abc-defg-hij');

        $this->assertDatabaseHas('live_stream_participants', ['live_stream_session_id' => $session->id, 'user_id' => $student->id]);
    }

    public function test_offline_or_different_class_student_cannot_join(): void
    {
        [$student, $session] = $this->makeSession();
        $student->update(['student_class' => 'SR Silver']);

        $this->actingAs($student)->post(route('live-streams.join', $session))->assertForbidden();
    }

    public function test_live_stream_rejects_participant_number_twenty_one(): void
    {
        [$student, $session] = $this->makeSession();
        $participants = User::factory()->count(20)->create(['role' => 'student']);
        $session->participants()->attach($participants->pluck('id'));

        $this->actingAs($student)->post(route('live-streams.join', $session))
            ->assertSessionHasErrors('live_stream');
    }

    private function makeSession(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'program_type' => 'gambar', 'student_class' => 'SR Gold', 'branch' => 'Bandung']);
        $classroom = Classroom::create(['program_type' => 'gambar', 'delivery_mode' => 'online', 'title' => 'SR Gold', 'branch' => 'Bandung', 'teacher_id' => $teacher->id]);
        $session = LiveStreamSession::create(['classroom_id' => $classroom->id, 'title' => 'Live', 'meeting_url' => 'https://meet.google.com/abc-defg-hij', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addHour()]);

        return [$student, $session];
    }
}
