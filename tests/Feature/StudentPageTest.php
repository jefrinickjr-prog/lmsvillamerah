<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MeetingAssignment;
use App\Models\MeetingSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_access_student_pages(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('student.grades'))->assertOk();
        $this->actingAs($student)->get(route('student.attendance'))->assertOk();
        $this->actingAs($student)->get(route('student.reports'))->assertOk();
    }

    public function test_teacher_cannot_access_student_pages(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)->get(route('student.grades'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.attendance'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.reports'))->assertForbidden();
    }

    public function test_report_uses_meeting_assignments_and_normalizes_graded_scores(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $classroom = Classroom::create([
            'program_type' => 'gambar',
            'delivery_mode' => 'offline',
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'teacher_id' => $teacher->id,
        ]);
        $classroom->students()->attach($student->id, [
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $submittedAssignment = MeetingAssignment::create([
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'Latihan Perspektif',
            'meeting_date' => now()->toDateString(),
            'due_at' => now()->addDay(),
            'max_score' => 50,
        ]);
        MeetingAssignment::create([
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'Latihan Warna',
            'meeting_date' => now()->addWeek()->toDateString(),
            'due_at' => now()->addWeek(),
            'max_score' => 100,
        ]);
        MeetingSubmission::create([
            'meeting_assignment_id' => $submittedAssignment->id,
            'student_id' => $student->id,
            'work_path' => 'meeting-submissions/test.jpg',
            'submitted_at' => now(),
            'score' => 40,
            'graded_by' => $teacher->id,
            'graded_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.reports'))
            ->assertOk()
            ->assertViewHas('tasksCount', 2)
            ->assertViewHas('submittedCount', 1)
            ->assertViewHas('pendingTasksCount', 1)
            ->assertViewHas('averageScore', 80.0)
            ->assertSee('1/2')
            ->assertSee('80.0')
            ->assertSee('Latihan Perspektif')
            ->assertSee('Latihan Warna');
    }
}
