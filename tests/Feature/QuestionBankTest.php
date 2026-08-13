<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Question;
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
            'story' => 'Sebuah cerita dipakai untuk beberapa pertanyaan.',
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
        $this->assertDatabaseHas('questions', [
            'story' => 'Sebuah cerita dipakai untuk beberapa pertanyaan.',
            'question' => 'Berapakah $\\frac{150}{7}$?',
        ]);
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

    public function test_question_bank_filters_by_type_difficulty_and_status(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::firstOrCreate(['name' => 'Penalaran Umum']);
        Question::create([
            'subject_id' => $subject->id,
            'type' => 'multiple_choice',
            'question' => 'Soal yang sesuai filter',
            'difficulty' => 'hard',
            'status' => 'active',
            'created_by' => $teacher->id,
        ]);
        Question::create([
            'subject_id' => $subject->id,
            'type' => 'essay',
            'question' => 'Soal yang tidak sesuai filter',
            'difficulty' => 'easy',
            'status' => 'inactive',
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('questions.index', [
                'subject_id' => $subject->id,
                'type' => 'multiple_choice',
                'difficulty' => 'hard',
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSee('Soal yang sesuai filter')
            ->assertDontSee('Soal yang tidak sesuai filter');
    }
}
