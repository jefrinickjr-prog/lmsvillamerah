<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Branch;
use App\Models\ClassProgram;
use App\Models\Classroom;
use App\Models\ProgramCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_academic_master_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approved_at' => now()]);

        $this->actingAs($admin)->post(route('academic-settings.categories.store'), [
            'name' => 'Minat Seni', 'code' => 'minat-seni',
        ])->assertRedirect();

        $category = ProgramCategory::where('code', 'minat-seni')->firstOrFail();
        $this->actingAs($admin)->post(route('academic-settings.programs.store'), [
            'program_category_id' => $category->id, 'name' => 'SR Minat Seni',
            'code' => 'SR-MS', 'default_capacity' => 16,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('academic-settings.branches.store'), [
            'name' => 'Bandung Timur', 'code' => 'BDGT',
        ])->assertRedirect();

        $this->assertDatabaseHas('class_programs', ['code' => 'SR-MS', 'default_capacity' => 16]);
        $this->assertDatabaseHas('branches', ['code' => 'BDGT']);
    }

    public function test_teacher_can_view_settings_but_cannot_change_master_data(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher)->get(route('academic-settings.index'))->assertOk();
        $this->actingAs($teacher)->post(route('academic-settings.categories.store'), ['name' => 'Baru'])->assertForbidden();
    }

    public function test_teacher_can_see_and_manage_real_class_members(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'program_type' => 'gambar']);
        $category = ProgramCategory::firstOrCreate(['code' => 'gambar'], ['name' => 'Gambar']);
        $program = ClassProgram::firstOrCreate(['code' => 'TEST-PROGRAM'], ['program_category_id' => $category->id, 'name' => 'Program Uji Anggota', 'default_capacity' => 20]);
        $branch = Branch::firstOrCreate(['code' => 'TEST'], ['name' => 'Cabang Test']);
        $period = AcademicPeriod::firstOrCreate(['code' => 'TEST'], ['name' => 'Periode Test']);
        $classroom = Classroom::create(['class_program_id' => $program->id, 'branch_id' => $branch->id, 'academic_period_id' => $period->id, 'program_type' => 'gambar', 'title' => $program->name, 'section_name' => 'Pagi A', 'capacity' => 1, 'branch' => $branch->name, 'teacher_id' => $teacher->id]);

        $this->actingAs($teacher)->post(route('classrooms.enroll', $classroom), ['student_id' => $student->id])->assertRedirect();
        $this->assertDatabaseHas('classroom_enrollments', ['classroom_id' => $classroom->id, 'student_id' => $student->id, 'status' => 'active']);
        $this->actingAs($teacher)->get(route('classrooms.roster', $classroom))->assertOk()->assertSee($student->name)->assertSee('1 / 1');

        $this->actingAs($teacher)->post(route('classrooms.schedules.store', $classroom), [
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'room' => 'Studio 1',
        ])->assertRedirect();
        $this->assertDatabaseHas('classroom_schedules', [
            'classroom_id' => $classroom->id,
            'day_of_week' => 1,
            'room' => 'Studio 1',
        ]);
    }

    public function test_registration_places_student_directly_into_selected_classroom(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $category = ProgramCategory::where('code', 'gambar')->firstOrFail();
        $program = ClassProgram::where('program_category_id', $category->id)->firstOrFail();
        $branch = Branch::firstOrFail();
        $period = AcademicPeriod::where('is_default', true)->firstOrFail();
        $classroom = Classroom::create([
            'class_program_id' => $program->id, 'branch_id' => $branch->id,
            'academic_period_id' => $period->id, 'program_type' => $category->code,
            'title' => $program->name, 'section_name' => 'Sore A', 'capacity' => 16,
            'branch' => $branch->name, 'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)->post(route('register.post'), [
            'name' => 'Siswa Otomatis', 'email' => 'otomatis@example.com',
            'classroom_id' => $classroom->id, 'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('register'));

        $student = User::where('email', 'otomatis@example.com')->firstOrFail();
        $this->assertNotNull($student->student_code);
        $this->assertDatabaseHas('classroom_enrollments', [
            'classroom_id' => $classroom->id, 'student_id' => $student->id, 'status' => 'active',
        ]);
    }
}
