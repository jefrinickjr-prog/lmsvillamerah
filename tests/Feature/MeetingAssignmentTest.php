<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\MeetingAssignment;
use App\Models\MeetingSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MeetingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_assignment_and_initial_absence_for_class_students(): void
    {
        [$teacher, $student, $classroom] = $this->classSetup();

        $this->actingAs($teacher)->post(route('meeting-assignments.store'), [
            'classroom_id' => $classroom->id,
            'title' => 'Karya Pertemuan Pertama',
            'meeting_date' => '2026-08-22',
            'due_at' => '2026-08-23 20:00:00',
            'max_score' => 100,
        ])->assertRedirect(route('meeting-assignments.index'));

        $this->assertDatabaseHas('attendances', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'week_start' => '2026-08-17 00:00:00',
            'present' => false,
        ]);
    }

    public function test_uploading_work_automatically_marks_student_present(): void
    {
        Storage::fake('local');
        [$teacher, $student, $classroom] = $this->classSetup();
        $assignment = MeetingAssignment::create([
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'Karya Mingguan',
            'meeting_date' => now()->toDateString(),
            'due_at' => now()->addDay(),
            'max_score' => 100,
        ]);

        $this->actingAs($student)->post(route('meeting-assignments.submit', $assignment), [
            'work' => UploadedFile::fake()->image('karya.jpg'),
            'note' => 'Karya saya.',
        ])->assertRedirect();

        $submission = MeetingSubmission::firstOrFail();
        Storage::disk('local')->assertExists($submission->work_path);
        $this->assertDatabaseHas('attendances', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'present' => true,
        ]);
    }

    public function test_teacher_can_grade_work_and_student_sees_feedback(): void
    {
        [$teacher, $student, $classroom] = $this->classSetup();
        $assignment = MeetingAssignment::create([
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'Latihan Komposisi',
            'meeting_date' => now()->toDateString(),
            'due_at' => now()->addDay(),
            'max_score' => 100,
        ]);
        $submission = MeetingSubmission::create([
            'meeting_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'work_path' => 'meeting-works/test.jpg',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)->put(route('meeting-submissions.grade', $submission), [
            'score' => 88,
            'feedback' => 'Komposisi sudah kuat.',
        ])->assertRedirect();

        $this->actingAs($student)->get(route('student.grades'))
            ->assertOk()
            ->assertSee('88/100')
            ->assertSee('Komposisi sudah kuat.');
    }

    private function classSetup(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'program_type' => 'gambar',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
        ]);
        $classroom = Classroom::create([
            'program_type' => 'gambar',
            'delivery_mode' => 'offline',
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'teacher_id' => $teacher->id,
        ]);

        return [$teacher, $student, $classroom];
    }
}
