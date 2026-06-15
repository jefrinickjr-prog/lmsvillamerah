<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_create_task(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('tasks.create'))
            ->assertForbidden();
    }

    public function test_teacher_can_create_task(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'Sketsa Dasar',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Shading',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($teacher)
            ->post(route('tasks.store'), [
                'title' => 'Latihan gradasi',
                'description' => 'Buat 5 contoh gradasi.',
                'material_id' => $material->id,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Latihan gradasi',
            'material_id' => $material->id,
        ]);
    }

    public function test_student_can_view_tasks_for_their_class_from_any_branch(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
        ]);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Jakarta Selatan',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Shading SR Gold',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);
        $task = \App\Models\Task::create([
            'title' => 'Latihan lintas cabang',
            'description' => 'Tugas untuk semua siswa SR Gold.',
            'material_id' => $material->id,
        ]);

        $this->actingAs($student)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee($task->title);
    }
}
