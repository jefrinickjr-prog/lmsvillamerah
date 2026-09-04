<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\LiveStreamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
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

    public function test_student_needs_manager_approval_each_time_they_rejoin(): void
    {
        [$student, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;

        $this->actingAs($student)
            ->post(route('live-streams.join', $session))
            ->assertRedirect(route('live-streams.room', $session));

        $this->actingAs($student)
            ->get(route('live-streams.room', $session))
            ->assertOk();

        $this->actingAs($student)
            ->post(route('live-streams.rejoin.request', $session))
            ->assertRedirect();

        $this->assertDatabaseHas('live_stream_participants', [
            'live_stream_session_id' => $session->id,
            'user_id' => $student->id,
            'rejoin_status' => 'pending',
        ]);

        $this->actingAs($student)
            ->get(route('live-streams.index'))
            ->assertOk()
            ->assertSee('Pending Persetujuan Admin');

        $this->actingAs($teacher)
            ->get(route('live-streams.index'))
            ->assertOk()
            ->assertSee('Permintaan Masuk Kembali')
            ->assertSee($student->name);

        $this->actingAs($teacher)
            ->put(route('live-streams.rejoin.approve', [$session, $student]))
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('live-streams.index'))
            ->assertOk()
            ->assertSee('Masuk Kembali — Disetujui');

        $this->actingAs($student)
            ->get(route('live-streams.room', $session))
            ->assertOk();

        $this->actingAs($student)
            ->get(route('live-streams.room', $session))
            ->assertStatus(429);

        $this->actingAs($student)
            ->post(route('live-streams.rejoin.request', $session))
            ->assertRedirect();

        $this->assertDatabaseHas('live_stream_participants', [
            'live_stream_session_id' => $session->id,
            'user_id' => $student->id,
            'rejoin_status' => 'pending',
        ]);
    }

    public function test_manager_can_see_and_approve_pending_rejoin_from_meeting_room(): void
    {
        [$student, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;
        $session->participants()->attach($student->id, [
            'rejoin_status' => 'pending',
            'rejoin_requested_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get(route('live-streams.room', $session))
            ->assertOk()
            ->assertViewHas('isManager', true)
            ->assertSee('Permintaan Masuk')
            ->assertSee('rejoinRequests', false);

        $this->actingAs($teacher)
            ->getJson(route('live-streams.status', $session))
            ->assertOk()
            ->assertJsonPath('pending_rejoin_count', 1)
            ->assertJsonPath('pending_rejoins.0.id', $student->id)
            ->assertJsonPath('pending_rejoins.0.name', $student->name)
            ->assertJsonPath('pending_rejoins.0.approve_url', route('live-streams.rejoin.approve', [$session, $student]));

        $this->actingAs($teacher)
            ->putJson(route('live-streams.rejoin.approve', [$session, $student]))
            ->assertOk()
            ->assertJsonFragment(['message' => 'Permintaan masuk kembali '.$student->name.' telah disetujui.']);

        $this->assertDatabaseHas('live_stream_participants', [
            'live_stream_session_id' => $session->id,
            'user_id' => $student->id,
            'rejoin_status' => 'approved',
        ]);
    }

    public function test_student_status_does_not_expose_pending_rejoin_requests(): void
    {
        [$student, $session] = $this->makeSession();
        $otherStudent = User::factory()->create(['role' => 'student']);
        $session->participants()->attach($student->id, ['rejoin_status' => 'approved']);
        $session->participants()->attach($otherStudent->id, [
            'rejoin_status' => 'pending',
            'rejoin_requested_at' => now(),
        ]);

        $this->actingAs($student)
            ->getJson(route('live-streams.status', $session))
            ->assertOk()
            ->assertJsonPath('pending_rejoin_count', 0)
            ->assertJsonPath('pending_rejoins', []);
    }

    public function test_student_status_immediately_provides_room_access_after_rejoin_is_approved(): void
    {
        [$student, $session] = $this->makeSession();
        $session->participants()->attach($student->id, [
            'entered_at' => null,
            'rejoin_status' => 'approved',
            'rejoin_approved_at' => now(),
        ]);

        $this->actingAs($student)
            ->getJson(route('live-streams.status', $session))
            ->assertOk()
            ->assertJsonPath('rejoin_status', 'approved')
            ->assertJsonPath('can_rejoin', true)
            ->assertJsonPath('room_url', route('live-streams.room', $session));
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
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('live-streams.start', $session))
            ->assertRedirect(route('live-streams.room', $session));

        $session->refresh();
        $this->assertSame($admin->id, $session->started_by);

        $this->actingAs($admin)
            ->get(route('live-streams.room', $session))
            ->assertOk()
            ->assertViewHas('isHost', true)
            ->assertSee('meeting-shell', false)
            ->assertViewHas('jitsiDomain', 'meet.jit.si')
            ->assertViewHas('jitsiRoomName')
            ->assertSee('JitsiMeetExternalAPI', false)
            ->assertSee('meet.jit.si/external_api.js', false)
            ->assertSee("onload: () => loading.classList.add('is-hidden')", false)
            ->assertSee('Pembelajaran Online Bimbel Gambar Villa Merah')
            ->assertSee('Akhiri Live untuk Semua')
            ->assertDontSee('whereby.com', false);
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

    public function test_admin_can_schedule_an_offline_class_and_activate_matching_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approved_at' => now()]);
        $student = User::factory()->create([
            'role' => 'student',
            'program_type' => 'gambar',
            'delivery_mode' => 'offline',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
        ]);
        $classroom = Classroom::create([
            'program_type' => 'gambar',
            'delivery_mode' => 'offline',
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'teacher_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('live-streams.index'))
            ->assertOk()
            ->assertSee('Offline — akan diaktifkan')
            ->assertSee('Buat Jadwal');

        $this->actingAs($admin)
            ->post(route('live-streams.store'), [
                'classroom_id' => $classroom->id,
                'title' => 'Kelas Gold Online',
                'starts_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertSame('online', $classroom->fresh()->delivery_mode);
        $this->assertSame('online', $student->fresh()->delivery_mode);
        $this->assertDatabaseHas('live_stream_sessions', [
            'classroom_id' => $classroom->id,
            'title' => 'Kelas Gold Online',
        ]);
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

    public function test_host_and_student_receive_the_same_private_jitsi_room_name(): void
    {
        [$student, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;

        $this->actingAs($student)
            ->post(route('live-streams.join', $session))
            ->assertRedirect(route('live-streams.room', $session));

        $hostRoom = $this->actingAs($teacher)
            ->get(route('live-streams.room', $session))
            ->assertOk()
            ->viewData('jitsiRoomName');
        $studentRoom = $this->actingAs($student)
            ->get(route('live-streams.room', $session))
            ->assertOk()
            ->viewData('jitsiRoomName');

        $outsider = User::factory()->create(['role' => 'student']);
        $this->actingAs($outsider)
            ->get(route('live-streams.room', $session))
            ->assertForbidden();

        $this->assertSame($hostRoom, $studentRoom);
        $this->assertMatchesRegularExpression('/^VillaMerahBeta-\d+-[a-f0-9]{24}$/', $hostRoom);
    }

    public function test_jaas_uses_app_namespace_and_signed_user_token(): void
    {
        [, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;
        $privateKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($privateKey === false) {
            $this->markTestSkipped('OpenSSL pada environment ini tidak dapat membuat RSA test key.');
        }
        openssl_pkey_export($privateKey, $privateKeyPem);

        Config::set('jitsi', [
            'domain' => '8x8.vc',
            'room_prefix' => 'VillaMerahBeta',
            'app_id' => 'vpaas-magic-cookie-test',
            'key_id' => 'vpaas-magic-cookie-test/key-id',
            'private_key_path' => null,
            'private_key_base64' => base64_encode($privateKeyPem),
            'max_token_hours' => 6,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('live-streams.room', $session))
            ->assertOk()
            ->assertViewHas('usingJaas', true)
            ->assertViewHas('jitsiDomain', '8x8.vc')
            ->assertSee('https://8x8.vc/vpaas-magic-cookie-test/external_api.js', false);

        $this->assertStringStartsWith(
            'vpaas-magic-cookie-test/VillaMerahBeta-',
            $response->viewData('jitsiRoomName')
        );

        [$encodedHeader, $encodedPayload] = explode('.', $response->viewData('jitsiJwt'));
        $header = json_decode(base64_decode(strtr($encodedHeader, '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($encodedPayload, '-_', '+/')), true);

        $this->assertSame('RS256', $header['alg']);
        $this->assertSame('vpaas-magic-cookie-test/key-id', $header['kid']);
        $this->assertSame('vpaas-magic-cookie-test', $payload['sub']);
        $this->assertSame((string) $teacher->id, $payload['context']['user']['id']);
        $this->assertTrue($payload['context']['user']['moderator']);
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

    public function test_teacher_can_end_live_stream_for_all_participants(): void
    {
        [$student, $session] = $this->makeSession();
        $teacher = $session->classroom->teacher;
        $session->participants()->attach($student->id);

        $this->actingAs($teacher)
            ->post(route('live-streams.end', $session))
            ->assertRedirect(route('live-streams.index'))
            ->assertSessionHas('success');

        $session->refresh();
        $this->assertTrue($session->ends_at->lte(now()));
        $this->assertSame(0, $session->participants()->count());

        $this->actingAs($student)
            ->getJson(route('live-streams.status', $session))
            ->assertOk()
            ->assertJson(['ended' => true]);
    }

    private function makeSession(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'program_type' => 'gambar', 'delivery_mode' => 'online', 'student_class' => 'SR Gold', 'branch' => 'Bandung']);
        $classroom = Classroom::create(['program_type' => 'gambar', 'delivery_mode' => 'online', 'title' => 'SR Gold', 'branch' => 'Bandung', 'teacher_id' => $teacher->id]);
        $session = LiveStreamSession::create([
            'classroom_id' => $classroom->id,
            'title' => 'Live',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'started_at' => now(),
            'started_by' => $teacher->id,
        ]);

        return [$student, $session];
    }
}
