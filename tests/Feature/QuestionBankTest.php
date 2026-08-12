<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_save_multiple_choice_question_and_is_redirected_to_bank(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::firstOrCreate(['name' => 'Penalaran Matematika']);

        $response = $this->actingAs($teacher)->post(route('questions.store'), [
            'subject_id' => $subject->id,
            'type' => 'multiple_choice',
            'question' => 'Berapakah $\\frac{150}{7}$?',
            'difficulty' => 'medium',
            'score' => 10,
            'status' => 'active',
            'options' => ['A' => '20', 'B' => '21', 'C' => '22', 'D' => '23', 'E' => null],
            'correct_answer' => 'B',
            'explanation' => '$\\frac{150}{7}=21$ sisa 3.',
        ]);

        $response->assertRedirect(route('questions.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('questions', ['question' => 'Berapakah $\\frac{150}{7}$?']);
        $this->assertDatabaseHas('question_options', ['option_label' => 'B', 'is_correct' => true]);
    }

    public function test_question_is_not_partially_saved_when_correct_answer_is_missing(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::firstOrCreate(['name' => 'Penalaran Umum']);

        $this->actingAs($teacher)->from(route('questions.create'))->post(route('questions.store'), [
            'subject_id' => $subject->id,
            'type' => 'multiple_choice',
            'question' => 'Contoh soal',
            'difficulty' => 'easy',
            'score' => 10,
            'status' => 'active',
            'options' => ['A' => 'Satu', 'B' => 'Dua', 'C' => 'Tiga', 'D' => 'Empat'],
        ])->assertRedirect(route('questions.create'))->assertSessionHasErrors('correct_answer');

        $this->assertDatabaseMissing('questions', ['question' => 'Contoh soal']);
    }
}
