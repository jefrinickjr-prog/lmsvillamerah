<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentManagementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->canManageStudents(), 403);

        $programTypes = User::programTypeOptions();
        $studentClasses = $this->manageableStudentClasses();
        $branches = $this->manageableBranches();

        $students = $this->manageableStudentsQuery()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('student_code', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('program_type'), fn ($query) => $query->where('program_type', $request->program_type))
            ->when($request->filled('student_class'), fn ($query) => $query->where('student_class', $request->student_class))
            ->when($request->filled('branch'), fn ($query) => $query->where('branch', $request->branch))
            ->orderBy('program_type')
            ->orderBy('student_class')
            ->orderBy('branch')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('students.index', compact('students', 'programTypes', 'studentClasses', 'branches'));
    }

    public function edit(User $student)
    {
        abort_unless($this->canManageStudent($student), 403);

        $programTypes = User::programTypeOptions();
        $studentClassesByProgram = User::STUDENT_CLASSES;
        $branches = $this->manageableBranches();

        return view('students.edit', compact('student', 'programTypes', 'studentClassesByProgram', 'branches'));
    }

    public function update(Request $request, User $student)
    {
        abort_unless($this->canManageStudent($student), 403);

        $branches = $this->manageableBranches();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->id)],
            'program_type' => ['nullable', 'string', Rule::in(array_keys(User::programTypeOptions()))],
            'student_class' => ['required', 'string'],
            'branch' => ['required', 'string', Rule::in($branches)],
            'academic_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'password' => ['nullable', 'confirmed', 'min:6'],
        ]);
        $data['program_type'] = User::normalizeProgramType($data['program_type'] ?? null);
        validator($data, [
            'student_class' => [Rule::in(User::studentClassOptions($data['program_type']))],
        ])->validate();

        $identityChanged = $student->academic_year !== $data['academic_year']
            || $student->program_type !== $data['program_type']
            || $student->branch !== $data['branch']
            || $student->student_class !== $data['student_class']
            || ! $student->student_code;

        $student->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'program_type' => $data['program_type'],
            'student_class' => $data['student_class'],
            'branch' => $data['branch'],
            'academic_year' => $data['academic_year'],
        ]);

        if ($identityChanged) {
            $student->student_code = $this->nextStudentCode($data, $student);
        }

        if (! empty($data['password'])) {
            $student->password = Hash::make($data['password']);
        }

        $student->save();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    private function canManageStudents(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }

    private function canManageStudent(User $student): bool
    {
        return $this->canManageStudents() && $student->role === 'student';
    }

    private function manageableStudentsQuery()
    {
        return User::where('role', 'student');
    }

    private function manageableStudentClasses(): array
    {
        return User::studentClassOptions();
    }

    private function manageableBranches(): array
    {
        return User::branchOptions();
    }

    private function nextStudentCode(array $data, User $student): string
    {
        $sequence = User::where('role', 'student')
            ->where('academic_year', $data['academic_year'])
            ->where('program_type', $data['program_type'])
            ->where('branch', $data['branch'])
            ->where('student_class', $data['student_class'])
            ->whereKeyNot($student->id)
            ->count() + 1;

        do {
            $code = User::makeStudentCode($data['academic_year'], $data['branch'], $data['student_class'], $sequence);
            $sequence++;
        } while (User::where('student_code', $code)->whereKeyNot($student->id)->exists());

        return $code;
    }
}
