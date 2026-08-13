<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_exam_form_defaults_to_published_status(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('exams.create'))
            ->assertOk()
            ->assertViewHas('exam', fn (Exam $exam) => $exam->status === 'published');
    }

    public function test_student_can_see_published_exam_but_not_draft_exam(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        Exam::create($this->examData($teacher, 'Ujian Terbit', 'published'));
        Exam::create($this->examData($teacher, 'Ujian Draft', 'draft'));

        $this->actingAs($student)
            ->get(route('exams.index'))
            ->assertOk()
            ->assertSee('Ujian Terbit')
            ->assertDontSee('Ujian Draft');
    }

    public function test_completed_exam_shows_finished_notice_instead_of_start_button(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create($this->examData($teacher, 'Ujian Selesai', 'published'));
        Attempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'status' => 'graded',
            'score' => 80,
        ]);

        $this->actingAs($student)
            ->get(route('exams.index'))
            ->assertOk()
            ->assertSee('Ujian selesai')
            ->assertSee('Anda sudah mengikuti ujian ini')
            ->assertDontSee('Mulai Ujian');
    }

    public function test_student_can_continue_active_attempt_while_time_remains(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create($this->examData($teacher, 'Ujian Berjalan', 'published'));
        $attempt = Attempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(5),
            'status' => 'in_progress',
        ]);

        $this->actingAs($student)
            ->post(route('exams.start', $exam))
            ->assertRedirect(route('attempts.show', $attempt));
    }

    public function test_starting_completed_exam_returns_friendly_notice_instead_of_422(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create($this->examData($teacher, 'Ujian Selesai', 'published'));
        Attempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'status' => 'graded',
        ]);

        $this->actingAs($student)
            ->post(route('exams.start', $exam))
            ->assertRedirect(route('exams.index'))
            ->assertSessionHas('success', 'Ujian ini sudah selesai Anda kerjakan dan tidak dapat dimasuki kembali.');
    }

    public function test_completed_attempt_cannot_reopen_question_page(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create($this->examData($teacher, 'Ujian Selesai', 'published'));
        $attempt = Attempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'status' => 'graded',
        ]);

        $this->actingAs($student)
            ->get(route('attempts.show', $attempt))
            ->assertRedirect(route('attempts.result', $attempt));
    }

    private function examData(User $teacher, string $title, string $status): array
    {
        return [
            'title' => $title,
            'duration' => 60,
            'passing_grade' => 75,
            'status' => $status,
            'show_result' => 'immediately',
            'created_by' => $teacher->id,
        ];
    }
}
