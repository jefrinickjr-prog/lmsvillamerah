<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function index()
    {
        abort_unless($this->canManageClassrooms(), 403);

        $classrooms = Classroom::with('teacher')
            ->when(Auth::user()?->role === 'teacher', fn ($query) => $query->where('teacher_id', Auth::id()))
            ->latest()
            ->get();

        return view('classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        abort_unless($this->canManageClassrooms(), 403);

        $teachers = User::whereIn('role', ['teacher', 'admin', 'super_admin'])
            ->orderBy('name')
            ->get();
        $programTypes = User::programTypeOptions();
        $studentClassesByProgram = User::STUDENT_CLASSES;
        $branches = User::branchOptions();

        return view('classrooms.create', compact('teachers', 'programTypes', 'studentClassesByProgram', 'branches'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageClassrooms(), 403);

        $data = $this->validatedClassroomData($request);

        Classroom::create($data);

        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil dibuat');
    }

    public function edit(Classroom $classroom)
    {
        abort_unless($this->canManageClassroom($classroom), 403);

        $teachers = User::whereIn('role', ['teacher', 'admin', 'super_admin'])
            ->orderBy('name')
            ->get();
        $programTypes = User::programTypeOptions();
        $studentClassesByProgram = User::STUDENT_CLASSES;
        $branches = User::branchOptions();

        return view('classrooms.edit', compact('classroom', 'teachers', 'programTypes', 'studentClassesByProgram', 'branches'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        abort_unless($this->canManageClassroom($classroom), 403);

        $classroom->update($this->validatedClassroomData($request, $classroom));
        $classroom->primaryMaterials()->update(['program_type' => $classroom->program_type]);

        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil diperbarui');
    }

    public function destroy(Classroom $classroom)
    {
        abort_unless($this->canManageClassroom($classroom), 403);

        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil dihapus');
    }

    private function canManageClassrooms(): bool
    {
        return in_array(Auth::user()?->role, ['teacher', 'admin', 'super_admin'], true);
    }

    private function canManageClassroom(Classroom $classroom): bool
    {
        if (! $this->canManageClassrooms()) {
            return false;
        }

        if (in_array(Auth::user()?->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return $classroom->teacher_id === Auth::id();
    }

    private function validatedClassroomData(Request $request, ?Classroom $classroom = null): array
    {
        $rules = [
            'program_type' => ['nullable', 'string', Rule::in(array_keys(User::programTypeOptions()))],
            'title' => ['required', 'string'],
            'branch' => ['required', 'string', 'in:'.implode(',', User::branchOptions())],
            'description' => ['nullable', 'string'],
        ];

        if (in_array(Auth::user()?->role, ['admin', 'super_admin'], true)) {
            $rules['teacher_id'] = ['required', 'integer', 'exists:users,id'];
        }

        $data = $request->validate($rules);
        $data['program_type'] = User::normalizeProgramType($data['program_type'] ?? null);
        validator($data, [
            'title' => [Rule::in(User::studentClassOptions($data['program_type']))],
        ])->validate();
        $data['teacher_id'] = $data['teacher_id'] ?? $classroom?->teacher_id ?? Auth::id();

        return $data;
    }
}
