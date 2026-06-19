<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_create_material(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('materials.create'))
            ->assertForbidden();
    }

    public function test_teacher_can_create_material_with_youtube_embed(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'Sketsa Dasar',
            'description' => 'Kelas pengantar',
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->post(route('materials.store'), [
                'title' => 'Teknik Shading',
                'content' => 'Materi shading untuk pemula.',
                'classroom_id' => $classroom->id,
                'youtube_embed_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ])
            ->assertRedirect(route('materials.index'));

        $this->assertDatabaseHas('materials', [
            'title' => 'Teknik Shading',
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);
    }

    public function test_admin_can_create_material(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'Anatomi Dasar',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($admin)
            ->post(route('materials.store'), [
                'title' => 'Proporsi Tubuh',
                'classroom_id' => $classroom->id,
                'youtube_embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertRedirect(route('materials.index'));

        $this->assertDatabaseHas('materials', [
            'title' => 'Proporsi Tubuh',
        ]);
    }

    public function test_student_can_view_video_learning_for_their_class(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
        ]);
        $otherStudent = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Advance',
            'branch' => 'Bandung',
        ]);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Garis Dasar',
            'content' => 'Latihan garis untuk SR Gold.',
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($student)
            ->get(route('materials.index'))
            ->assertOk()
            ->assertSee($material->title)
            ->assertSee($material->youtube_embed_url);

        $this->actingAs($otherStudent)
            ->get(route('materials.index'))
            ->assertOk()
            ->assertDontSee($material->title);
    }

    public function test_student_can_view_video_learning_for_their_class_from_any_branch(): void
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
            'title' => 'Garis Dasar Lintas Cabang',
            'content' => 'Video ini untuk semua siswa SR Gold.',
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($student)
            ->get(route('materials.index'))
            ->assertOk()
            ->assertSee($material->title);
    }

    public function test_student_only_sees_video_learning_for_their_program_group(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $gambarStudent = User::factory()->create([
            'role' => 'student',
            'program_type' => 'gambar',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
        ]);
        $skolastikStudent = User::factory()->create([
            'role' => 'student',
            'program_type' => 'skolastik',
            'student_class' => 'Skolastik Dasar',
            'branch' => 'Bandung',
        ]);
        $gambarClassroom = Classroom::create([
            'program_type' => 'gambar',
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $skolastikClassroom = Classroom::create([
            'program_type' => 'skolastik',
            'title' => 'Skolastik Dasar',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $gambarMaterial = Material::create([
            'classroom_id' => $gambarClassroom->id,
            'program_type' => 'gambar',
            'title' => 'Perspektif Gambar',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);
        $skolastikMaterial = Material::create([
            'classroom_id' => $skolastikClassroom->id,
            'program_type' => 'skolastik',
            'title' => 'Logika Skolastik',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($gambarStudent)
            ->get(route('materials.index'))
            ->assertOk()
            ->assertSee($gambarMaterial->title)
            ->assertDontSee($skolastikMaterial->title);

        $this->actingAs($skolastikStudent)
            ->get(route('materials.index'))
            ->assertOk()
            ->assertSee($skolastikMaterial->title)
            ->assertDontSee($gambarMaterial->title);
    }

    public function test_student_class_filter_handles_old_silver_typo(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Silver',
            'branch' => 'Jakarta Pusat',
        ]);
        $classroom = Classroom::create([
            'title' => 'SR Sirver',
            'branch' => 'Jakarta Pusat',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Komposisi Silver',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($student)
            ->get(route('materials.index'))
            ->assertOk()
            ->assertSee($material->title);
    }

    public function test_student_cannot_edit_video_learning(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'student_class' => 'SR Gold', 'branch' => 'Bandung']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Garis Dasar',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($student)
            ->get(route('materials.edit', $material))
            ->assertForbidden();

        $this->actingAs($student)
            ->put(route('materials.update', $material), [
                'title' => 'Diubah siswa',
                'classroom_id' => $classroom->id,
                'youtube_embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertForbidden();
    }

    public function test_teacher_can_edit_own_video_learning(): void
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
            ->put(route('materials.update', $material), [
                'title' => 'Garis Horizontal',
                'content' => 'Updated',
                'classroom_id' => $classroom->id,
                'youtube_embed_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ])
            ->assertRedirect(route('materials.index'));

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'title' => 'Garis Horizontal',
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);
    }

    public function test_teacher_cannot_edit_other_teacher_video_learning(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'description' => null,
            'teacher_id' => $owner->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Garis Dasar',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($otherTeacher)
            ->get(route('materials.edit', $material))
            ->assertForbidden();
    }

    public function test_teacher_can_delete_own_video_learning(): void
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
            ->delete(route('materials.destroy', $material))
            ->assertRedirect(route('materials.index'));

        $this->assertDatabaseMissing('materials', [
            'id' => $material->id,
        ]);
    }

    public function test_teacher_cannot_delete_other_teacher_video_learning(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'description' => null,
            'teacher_id' => $owner->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Garis Dasar',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($otherTeacher)
            ->delete(route('materials.destroy', $material))
            ->assertForbidden();

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
        ]);
    }

    public function test_student_cannot_delete_video_learning(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'student_class' => 'SR Gold', 'branch' => 'Bandung']);
        $classroom = Classroom::create([
            'title' => 'SR Gold',
            'branch' => 'Bandung',
            'description' => null,
            'teacher_id' => $teacher->id,
        ]);
        $material = Material::create([
            'classroom_id' => $classroom->id,
            'title' => 'Garis Dasar',
            'content' => null,
            'youtube_embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);

        $this->actingAs($student)
            ->delete(route('materials.destroy', $material))
            ->assertForbidden();

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
        ]);
    }
}
