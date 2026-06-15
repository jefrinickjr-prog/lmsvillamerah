<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_access_student_pages(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('student.grades'))->assertOk();
        $this->actingAs($student)->get(route('student.attendance'))->assertOk();
        $this->actingAs($student)->get(route('student.reports'))->assertOk();
    }

    public function test_teacher_cannot_access_student_pages(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)->get(route('student.grades'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.attendance'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.reports'))->assertForbidden();
    }
}
