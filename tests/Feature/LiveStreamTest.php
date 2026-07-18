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
            ->assertRedirect(route('live-streams.room', $session));

        $this->assertDatabaseHas('live_stream_participants', ['live_stream_session_id' => $session->id, 'user_id' => $student->id]);
    }

    public function test_offline_or_different_class_student_cannot_join(): void
    {
        [$student, $session] = $this->makeSession();
        $student->update(['student_class' => 'SR Silver']);

        $this->actingAs($student)->post(route('live-streams.join', $session))->assertForbidden();
    }

    public function test_legacy_siswa_role_sees_join_button_and_can_join(): void
    {
        [$student, $session] = $this->makeSession();
        $student->forceFill(['role' => 'siswa'])->save();

        $this->actingAs($student)->get(route('live-streams.index'))
            ->assertOk()
            ->assertSee('Join Live Streaming');
        $this->actingAs($student)->post(route('live-streams.join', $session))
            ->assertRedirect(route('live-streams.room', $session));
    }

    public function test_live_stream_rejects_participant_number_twenty_one(): void
    {
        [$student, $session] = $this->makeSession();
        $participants = User::factory()->count(20)->create(['role' => 'student']);
        $session->participants()->attach($participants->pluck('id'));

        $this->actingAs($student)->post(route('live-streams.join', $session))
            ->assertSessionHasErrors('live_stream');
    }

    public function test_super_admin_can_edit_and_start_live_stream(): void
    {
        [, $session] = $this->makeSession();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->put(route('live-streams.update', $session), [
            'classroom_id' => $session->classroom_id,
            'title' => 'Live yang diperbarui',
            'meeting_url' => 'https://zoom.us/j/123456789',
            'starts_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('live-streams.index'));

        $session->refresh();
        $this->assertSame('Live yang diperbarui', $session->title);
        $session->update(['started_at' => null, 'started_by' => null]);
        $this->actingAs($admin)->post(route('live-streams.start', $session))
            ->assertRedirect(route('live-streams.room', $session));
        $this->assertTrue($session->fresh()->started_at->lte(now()));
        $this->assertSame($admin->id, $session->fresh()->started_by);
    }

    private function makeSession(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'program_type' => 'gambar', 'delivery_mode' => 'online', 'student_class' => 'SR Gold', 'branch' => 'Bandung']);
        $classroom = Classroom::create(['program_type' => 'gambar', 'delivery_mode' => 'online', 'title' => 'SR Gold', 'branch' => 'Bandung', 'teacher_id' => $teacher->id]);
        $session = LiveStreamSession::create(['classroom_id' => $classroom->id, 'title' => 'Live', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addHour(), 'started_at' => now(), 'started_by' => $teacher->id]);

        return [$student, $session];
    }
}
