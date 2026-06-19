<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        abort_unless($this->canRegisterStudents(), 403);

        $programTypes = User::programTypeOptions();
        $studentClassesByProgram = User::STUDENT_CLASSES;
        $branches = User::branchOptions();
        $defaultAcademicYear = User::currentAcademicYear();

        return view('auth.register', compact('programTypes', 'studentClassesByProgram', 'branches', 'defaultAcademicYear'));
    }

    public function register(Request $request)
    {
        abort_unless($this->canRegisterStudents(), 403);

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','confirmed','min:6'],
            'program_type' => ['nullable', 'string', Rule::in(array_keys(User::programTypeOptions()))],
            'student_class' => ['required', 'string'],
            'branch' => ['required', 'string', 'in:'.implode(',', User::branchOptions())],
            'academic_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
        ]);
        $data['program_type'] = User::normalizeProgramType($data['program_type'] ?? null);
        validator($data, [
            'student_class' => [Rule::in(User::studentClassOptions($data['program_type']))],
        ])->validate();

        $sequence = User::where('role', 'student')
            ->where('academic_year', $data['academic_year'])
            ->where('program_type', $data['program_type'])
            ->where('branch', $data['branch'])
            ->where('student_class', $data['student_class'])
            ->count() + 1;

        $student = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'program_type' => $data['program_type'],
            'student_class' => $data['student_class'],
            'branch' => $data['branch'],
            'academic_year' => $data['academic_year'],
            'student_code' => User::makeStudentCode($data['academic_year'], $data['branch'], $data['student_class'], $sequence),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('register')->with('success', 'Akun siswa berhasil dibuat. Kode siswa: '.$student->student_code);
    }

    private function canRegisterStudents(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }
}
