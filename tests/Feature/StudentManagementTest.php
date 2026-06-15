<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_edit_registered_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Siswa Lama',
            'email' => 'lama@example.com',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
            'academic_year' => '2026-2027',
            'student_code' => '2627-B-GOLD-0001',
        ]);

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Daftar Siswa')
            ->assertSee('Siswa Lama');

        $this->actingAs($admin)
            ->put(route('students.update', $student), [
                'name' => 'Siswa Baru',
                'email' => 'baru@example.com',
                'student_class' => 'SR Silver',
                'branch' => 'Jakarta Pusat',
                'academic_year' => '2026-2027',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('students.index'));

        $student->refresh();

        $this->assertSame('Siswa Baru', $student->name);
        $this->assertSame('baru@example.com', $student->email);
        $this->assertSame('SR Silver', $student->student_class);
        $this->assertSame('Jakarta Pusat', $student->branch);
        $this->assertSame('2627-JP-SILVER-0001', $student->student_code);
        $this->assertTrue(Hash::check('secret123', $student->password));
    }

    public function test_teacher_can_view_and_edit_registered_students(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Siswa Terdaftar',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
            'academic_year' => '2026-2027',
        ]);
        User::factory()->create([
            'role' => 'student',
            'name' => 'Siswa Lain',
            'student_class' => 'SR Silver',
            'branch' => 'Bandung',
            'academic_year' => '2026-2027',
        ]);

        $this->actingAs($teacher)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Siswa Terdaftar')
            ->assertSee('Siswa Lain');

        $this->actingAs($teacher)
            ->get(route('students.edit', $student))
            ->assertOk();

        $this->actingAs($teacher)
            ->put(route('students.update', $student), [
                'name' => 'Siswa Terdaftar',
                'email' => $student->email,
                'student_class' => 'SR Advance',
                'branch' => 'Jakarta Selatan',
                'academic_year' => '2027-2028',
            ])
            ->assertRedirect(route('students.index'));

        $student->refresh();

        $this->assertSame('SR Advance', $student->student_class);
        $this->assertSame('Jakarta Selatan', $student->branch);
        $this->assertSame('2027-2028', $student->academic_year);
        $this->assertSame('2728-JS-ADVANCE-0001', $student->student_code);
    }

    public function test_student_cannot_access_student_management(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('students.index'))
            ->assertForbidden();
    }
}
