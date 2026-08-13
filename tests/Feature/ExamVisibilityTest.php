<?php

namespace Tests\Feature;

use App\Models\Exam;
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
