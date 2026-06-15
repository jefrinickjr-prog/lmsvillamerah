<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalStudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_student_registration_form(): void
    {
        $this->get(route('register'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_register_other_students(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
        ]);

        $this->actingAs($student)
            ->get(route('register'))
            ->assertForbidden();
    }

    public function test_teacher_can_register_verified_student_with_class(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->post(route('register.post'), [
                'name' => 'Siswa Baru',
                'email' => 'siswa@example.com',
                'student_class' => 'SR Gold',
                'branch' => 'Bandung',
                'academic_year' => '2026-2027',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('register'));

        $this->assertDatabaseHas('users', [
            'name' => 'Siswa Baru',
            'email' => 'siswa@example.com',
            'role' => 'student',
            'student_class' => 'SR Gold',
            'branch' => 'Bandung',
            'academic_year' => '2026-2027',
            'student_code' => '2627-B-GOLD-0001',
        ]);

        $this->assertNotNull(User::where('email', 'siswa@example.com')->first()?->email_verified_at);
    }
}
