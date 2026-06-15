<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_edit_own_classroom(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => 'Deskripsi lama',
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('classrooms.edit', $classroom))
            ->assertOk()
            ->assertSee('Edit Kelas');

        $this->actingAs($teacher)
            ->put(route('classrooms.update', $classroom), [
                'title' => 'SR Advance',
                'branch' => 'Bandung',
                'description' => 'Deskripsi baru',
            ])
            ->assertRedirect(route('classrooms.index'));

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'title' => 'SR Advance',
            'branch' => 'Bandung',
            'description' => 'Deskripsi baru',
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_admin_can_edit_any_classroom_and_change_teacher(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $oldTeacher = User::factory()->create(['role' => 'teacher']);
        $newTeacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Jakarta Pusat',
            'description' => null,
            'teacher_id' => $oldTeacher->id,
        ]);

        $this->actingAs($admin)
            ->put(route('classrooms.update', $classroom), [
                'title' => 'SR SMP',
                'branch' => 'Jakarta Selatan',
                'description' => 'Kelas SMP',
                'teacher_id' => $newTeacher->id,
            ])
            ->assertRedirect(route('classrooms.index'));

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'title' => 'SR SMP',
            'branch' => 'Jakarta Selatan',
            'description' => 'Kelas SMP',
            'teacher_id' => $newTeacher->id,
        ]);
    }

    public function test_teacher_cannot_edit_other_teacher_classroom(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $owner->id,
        ]);

        $this->actingAs($otherTeacher)
            ->get(route('classrooms.edit', $classroom))
            ->assertForbidden();

        $this->actingAs($otherTeacher)
            ->put(route('classrooms.update', $classroom), [
                'title' => 'SR Advance',
                'branch' => 'Bandung',
                'description' => null,
            ])
            ->assertForbidden();
    }

    public function test_student_cannot_edit_classroom(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'student_class' => 'SR Gold']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($student)
            ->get(route('classrooms.edit', $classroom))
            ->assertForbidden();

        $this->actingAs($student)
            ->put(route('classrooms.update', $classroom), [
                'title' => 'SR Advance',
                'branch' => 'Bandung',
                'description' => null,
            ])
            ->assertForbidden();
    }

    public function test_teacher_can_delete_own_classroom_and_its_videos(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Garis Dasar',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($teacher)
            ->delete(route('classrooms.destroy', $classroom))
            ->assertRedirect(route('classrooms.index'));

        $this->assertDatabaseMissing('classrooms', [
            'id' => $classroom->id,
        ]);
        $this->assertDatabaseMissing('materials', [
            'id' => $material->id,
        ]);
    }

    public function test_teacher_cannot_delete_other_teacher_classroom(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'description' => null,
            'teacher_id' => $owner->id,
        ]);

        $this->actingAs($otherTeacher)
            ->delete(route('classrooms.destroy', $classroom))
            ->assertForbidden();

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
        ]);
    }

    public function test_admin_can_delete_any_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('classrooms.destroy', $classroom))
            ->assertRedirect(route('classrooms.index'));

        $this->assertDatabaseMissing('classrooms', [
            'id' => $classroom->id,
        ]);
    }

    public function test_student_cannot_delete_classroom(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'student_class' => 'SR Gold']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($student)
            ->delete(route('classrooms.destroy', $classroom))
            ->assertForbidden();

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
        ]);
    }
}
