<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\LiveStreamSession;
use App\Models\LiveStreamSignal;
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
            ->assertSee('Join Live Streaming')
            ->assertSee('btn-download-solid', false);
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

    public function test_manager_who_enters_an_existing_session_becomes_the_active_host(): void
    {
        [, $session] = $this->makeSession();
        $previousHost = $session->started_by;
        $admin = User::factory()->create(['role' => 'super_admin']);

        LiveStreamSignal::create([
            'live_stream_session_id' => $session->id,
            'from_user_id' => $previousHost,
            'to_user_id' => $admin->id,
            'type' => 'offer',
            'payload' => ['type' => 'offer', 'sdp' => 'stale'],
        ]);

        $this->actingAs($admin)
            ->post(route('live-streams.start', $session))
            ->assertRedirect(route('live-streams.room', $session));

        $session->refresh();
        $this->assertSame($admin->id, $session->started_by);
        $this->assertDatabaseMissing('live_stream_signals', [
            'live_stream_session_id' => $session->id,
        ]);

        $this->actingAs($admin)
            ->get(route('live-streams.room', $session))
            ->assertOk()
            ->assertViewHas('isHost', true)
            ->assertSee('meet-shell', false)
            ->assertSee('Mikrofon')
            ->assertSee('Kamera')
            ->assertSee('Bagikan Layar')
            ->assertSee('Keluar dari Ruang')
            ->assertSee('Anda sudah masuk sebagai host')
            ->assertSee('kamera dan mikrofon nonaktif')
            ->assertDontSee('await startCamera();', false)
            ->assertDontSee("createDataChannel('presence')", false)
            ->assertSee('max-message-size', false)
            ->assertSee('repair-window', false)
            ->assertSee('extmap-allow-mixed', false)
            ->assertSee('transport-cc', false)
            ->assertSee('transport-wide-cc', false)
            ->assertSee('rtcp-rsize', false)
            ->assertSee('simulcast', false);
    }

    public function test_assigned_teacher_can_create_schedule_and_start_as_host(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'program_type' => 'gambar',
            'delivery_mode' => 'online',
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)->post(route('live-streams.store'), [
            'classroom_id' => $classroom->id,
            'title' => 'Live buatan pengajar',
            'starts_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $session = LiveStreamSession::where('title', 'Live buatan pengajar')->firstOrFail();

        $this->actingAs($teacher)
            ->post(route('live-streams.start', $session))
            ->assertRedirect(route('live-streams.room', $session));

        $this->assertSame($teacher->id, $session->fresh()->started_by);
    }

    public function test_teacher_sees_a_visible_start_live_button(): void
    {
        [, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;
        $session->update(['started_at' => null, 'started_by' => null]);

        $this->actingAs($teacher)
            ->get(route('live-streams.index'))
            ->assertOk()
            ->assertSee('Mulai Live sebagai Host')
            ->assertSee('btn-approve-solid', false)
            ->assertSee(route('live-streams.start', $session), false);
    }

    public function test_signaling_payload_is_returned_as_a_json_object(): void
    {
        [$student, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;

        $this->actingAs($student)
            ->post(route('live-streams.join', $session))
            ->assertRedirect(route('live-streams.room', $session));

        $this->actingAs($student)
            ->postJson(route('live-streams.signal', $session), [
                'to_user_id' => $teacher->id,
                'type' => 'offer',
                'payload' => ['type' => 'offer', 'sdp' => 'test-sdp'],
            ])
            ->assertOk();

        $this->actingAs($teacher)
            ->getJson(route('live-streams.signals', ['liveStream' => $session, 'after' => 0]))
            ->assertOk()
            ->assertJsonPath('0.payload.type', 'offer')
            ->assertJsonPath('0.payload.sdp', 'test-sdp');
    }

    public function test_same_host_reentering_clears_stale_signals(): void
    {
        [, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;
        $recipient = User::factory()->create(['role' => 'student']);

        LiveStreamSignal::create([
            'live_stream_session_id' => $session->id,
            'from_user_id' => $teacher->id,
            'to_user_id' => $recipient->id,
            'type' => 'offer',
            'payload' => ['type' => 'offer', 'sdp' => 'stale'],
        ]);

        $this->actingAs($teacher)
            ->post(route('live-streams.start', $session))
            ->assertRedirect(route('live-streams.room', $session));

        $this->assertDatabaseMissing('live_stream_signals', [
            'live_stream_session_id' => $session->id,
        ]);
    }

    public function test_host_can_restart_an_ended_session_for_sixty_minutes(): void
    {
        [$student, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;
        $session->participants()->attach($student->id);
        $session->update([
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subMinute(),
        ]);

        $this->actingAs($teacher)
            ->get(route('live-streams.index'))
            ->assertOk()
            ->assertSee('Mulai Ulang 60 Menit');

        $this->actingAs($teacher)
            ->post(route('live-streams.start', $session))
            ->assertRedirect(route('live-streams.room', $session));

        $session->refresh();
        $this->assertTrue($session->ends_at->between(now()->addMinutes(59), now()->addMinutes(61)));
        $this->assertSame(0, $session->participants()->count());
    }

    public function test_opening_an_ended_room_redirects_without_exception_page(): void
    {
        [, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;
        $session->update(['ends_at' => now()->subMinute()]);

        $this->actingAs($teacher)
            ->get(route('live-streams.room', $session))
            ->assertRedirect(route('live-streams.index'))
            ->assertSessionHasErrors('live_stream');
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
