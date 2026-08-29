<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        abort_unless($this->canRegisterStudents(), 403);

        $programTypes = User::programTypeOptions();
        $videoAccessOptions = User::videoAccessOptions();
        $studentClassesByProgram = User::STUDENT_CLASSES;
        $branches = User::branchOptions();
        $defaultAcademicYear = User::currentAcademicYear();
        $classrooms = Classroom::with(['program.category', 'branchMaster', 'academicPeriod', 'teacher'])
            ->withCount('activeEnrollments')->where('is_active', true)
            ->when(Auth::user()?->role === 'teacher', fn ($query) => $query->where('teacher_id', Auth::id()))
            ->orderBy('title')->orderBy('section_name')->get();

        return view('auth.register', compact('programTypes', 'videoAccessOptions', 'studentClassesByProgram', 'branches', 'defaultAcademicYear', 'classrooms'));
    }

    public function register(Request $request)
    {
        abort_unless($this->canRegisterStudents(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'program_type' => ['nullable', 'string', Rule::in(array_keys(User::programTypeOptions()))],
            'delivery_mode' => ['nullable', 'string', Rule::in(['online', 'offline'])],
            'video_accesses' => ['nullable', 'array'],
            'video_accesses.*' => ['string', Rule::in(array_keys(User::videoAccessOptions()))],
            'student_class' => ['required_without:classroom_id', 'nullable', 'string'],
            'branch' => ['required_without:classroom_id', 'nullable', 'string', 'in:'.implode(',', User::branchOptions())],
            'academic_year' => ['required_without:classroom_id', 'nullable', 'string', 'regex:/^\d{4}-\d{4}$/'],
        ]);
        $classroom = null;
        if (! empty($data['classroom_id'])) {
            $classroom = Classroom::with(['program.category', 'branchMaster', 'academicPeriod'])->findOrFail($data['classroom_id']);
            abort_unless($classroom->is_active && (in_array(Auth::user()?->role, ['admin', 'super_admin'], true) || $classroom->teacher_id === Auth::id()), 403);
            if ($classroom->activeEnrollments()->count() >= $classroom->capacity) {
                return back()->withErrors(['classroom_id' => 'Kelas yang dipilih sudah penuh.'])->withInput();
            }
            $data['program_type'] = $classroom->program?->category?->code ?: $classroom->program_type;
            $data['student_class'] = $classroom->program?->name ?: $classroom->title;
            $data['branch'] = $classroom->branchMaster?->name ?: $classroom->branch;
            $data['academic_year'] = str_replace('/', '-', $classroom->academicPeriod?->code ?: User::currentAcademicYear());
            $data['delivery_mode'] = $classroom->delivery_mode;
        }
        $data['program_type'] = $classroom ? $data['program_type'] : User::normalizeProgramType($data['program_type'] ?? null);
        if (! $classroom) {
            validator($data, ['student_class' => [Rule::in(User::studentClassOptions($data['program_type']))]])->validate();
        }
        $data['video_accesses'] = User::normalizeVideoAccesses($data['video_accesses'] ?? null, $data['program_type'], $data['student_class']);

        $sequence = User::where('role', 'student')
            ->where('academic_year', $data['academic_year'])
            ->where('program_type', $data['program_type'])
            ->where('branch', $data['branch'])
            ->where('student_class', $data['student_class'])
            ->count() + 1;

        $student = DB::transaction(function () use ($data, $sequence, $classroom): User {
            $student = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'program_type' => $data['program_type'],
            'delivery_mode' => $data['delivery_mode'] ?? 'offline',
            'video_accesses' => $data['video_accesses'],
            'student_class' => $data['student_class'],
            'branch' => $data['branch'],
            'academic_year' => $data['academic_year'],
            'student_code' => User::makeStudentCode($data['academic_year'], $data['branch'], $data['student_class'], $sequence),
            'email_verified_at' => now(),
            ]);
            if ($classroom) {
                ClassroomEnrollment::create(['classroom_id' => $classroom->id, 'student_id' => $student->id, 'status' => 'active', 'joined_at' => now(), 'assigned_by' => Auth::id()]);
            }
            return $student;
        });

        return redirect()->route('register')->with('success', 'Akun siswa berhasil dibuat'.($classroom ? ' dan langsung masuk ke '.$classroom->display_name : '').'. Kode siswa: '.$student->student_code);
    }

    private function canRegisterStudents(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }
}
