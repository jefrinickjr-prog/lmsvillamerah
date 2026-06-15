<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_checklist_attendance_for_class_branch_and_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-13 09:00:00', 'Asia/Jakarta'));

        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $presentStudent = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
            'academic_year' => '2026-2027',
            'student_code' => '2627-B-GOLD-0001',
        ]);
        $absentStudent = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
            'academic_year' => '2026-2027',
            'student_code' => '2627-B-GOLD-0002',
        ]);
        $otherBranchStudent = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Jakarta Pusat',
        ]);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($admin)
            ->post(route('attendances.store'), [
                'classroom_id' => $classroom->id,
                'week' => '2026-06-13',
                'present_students' => [$presentStudent->id],
            ])
            ->assertRedirect(route('attendances.index', [
                'classroom_id' => $classroom->id,
                'week' => '2026-06-08',
            ]));

        $this->assertDatabaseHas('attendances', [
            'classroom_id' => $classroom->id,
            'student_id' => $presentStudent->id,
            'week_start' => '2026-06-08 00:00:00',
            'present' => true,
        ]);
        $this->assertDatabaseHas('attendances', [
            'classroom_id' => $classroom->id,
            'student_id' => $absentStudent->id,
            'week_start' => '2026-06-08 00:00:00',
            'present' => false,
        ]);
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $otherBranchStudent->id,
        ]);
    }

    public function test_teacher_can_view_and_update_only_own_class_attendance(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Jakarta Selatan',
            'student_code' => '2627-JS-GOLD-0001',
        ]);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Jakarta Selatan',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $otherClassroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $otherTeacher->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('attendances.index', ['classroom_id' => $classroom->id, 'week' => '2026-06-13']))
            ->assertOk()
            ->assertSee($student->student_code);

        $this->actingAs($teacher)
            ->post(route('attendances.store'), [
                'classroom_id' => $classroom->id,
                'week' => '2026-06-13',
                'present_students' => [$student->id],
            ])
            ->assertRedirect();

        $this->actingAs($teacher)
            ->get(route('attendances.index', ['classroom_id' => $otherClassroom->id]))
            ->assertForbidden();
    }

    public function test_student_cannot_view_or_store_teacher_attendance_report(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
        ]);

        $this->actingAs($student)
            ->get(route('attendances.index'))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('attendances.store'), [
                'classroom_id' => 1,
                'week' => '2026-06-13',
            ])
            ->assertForbidden();
    }

    public function test_student_attendance_page_is_read_only(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
        ]);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        Attendance::create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'date' => '2026-06-13',
            'week_start' => '2026-06-08',
            'present' => true,
        ]);

        $this->actingAs($student)
            ->get(route('student.attendance'))
            ->assertOk()
            ->assertSee('Checklist absensi dicatat oleh admin atau pengajar')
            ->assertSee('Hadir')
            ->assertDontSee('Isi Absensi Minggu Ini');
    }
}
