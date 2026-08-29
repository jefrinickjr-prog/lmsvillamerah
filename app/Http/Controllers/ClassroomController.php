<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\AcademicPeriod;
use App\Models\Branch;
use App\Models\ClassProgram;
use App\Models\ProgramCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function index()
    {
        abort_unless($this->canManageClassrooms(), 403);

        $classrooms = Classroom::with(['teacher','program.category','branchMaster','academicPeriod','schedules'])->withCount('activeEnrollments')
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
        [$programTypes,$studentClassesByProgram,$branches,$classPrograms,$branchRecords,$periods] = $this->academicOptions();

        return view('classrooms.create', compact('teachers', 'programTypes', 'studentClassesByProgram', 'branches','classPrograms','branchRecords','periods'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageClassrooms(), 403);

        $data = $this->validatedClassroomData($request);

        $classroom = Classroom::onlyTrashed()
            ->where('program_type', $data['program_type'])
            ->whereRaw('LOWER(TRIM(title)) = ?', [User::normalizeStudentClass($data['title'])])
            ->whereRaw('LOWER(TRIM(branch)) = ?', [User::normalizeBranch($data['branch'])])
            ->whereRaw('LOWER(TRIM(section_name)) = ?', [strtolower(trim($data['section_name']))])
            ->when(Auth::user()?->role === 'teacher', fn ($query) => $query->where('teacher_id', Auth::id()))
            ->first();

        if ($classroom) {
            $classroom->restore();
            $classroom->update($data);

            return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil dibuat kembali. Video pembelajaran lama tetap tersedia.');
        }

        Classroom::create($data);

        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil dibuat');
    }

    public function edit(Classroom $classroom)
    {
        abort_unless($this->canManageClassroom($classroom), 403);

        $teachers = User::whereIn('role', ['teacher', 'admin', 'super_admin'])
            ->orderBy('name')
            ->get();
        [$programTypes,$studentClassesByProgram,$branches,$classPrograms,$branchRecords,$periods] = $this->academicOptions();

        return view('classrooms.edit', compact('classroom', 'teachers', 'programTypes', 'studentClassesByProgram', 'branches','classPrograms','branchRecords','periods'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        abort_unless($this->canManageClassroom($classroom), 403);

        $classroom->update($this->validatedClassroomData($request, $classroom));

        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil diperbarui');
    }

    public function destroy(Classroom $classroom)
    {
        abort_unless($this->canManageClassroom($classroom), 403);

        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil dihapus. Video pembelajaran tetap tersimpan dan akan muncul kembali saat kelas dibuat lagi.');
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
        // Keep old forms and existing integrations compatible while the new
        // structured master data is rolled out.
        if (! $request->filled('class_program_id') && $request->filled('title')) {
            $program = ClassProgram::whereRaw('LOWER(TRIM(name)) = ?', [User::normalizeStudentClass($request->string('title'))])->first();
            $branch = Branch::whereRaw('LOWER(TRIM(name)) = ?', [User::normalizeBranch($request->string('branch'))])->first();
            $period = AcademicPeriod::where('is_default', true)->first() ?: AcademicPeriod::where('is_active', true)->first();
            $request->merge([
                'class_program_id' => $program?->id,
                'branch_id' => $branch?->id,
                'academic_period_id' => $period?->id,
                'section_name' => $request->input('section_name', $classroom?->section_name ?: 'Umum'),
                'capacity' => $request->input('capacity', $classroom?->capacity ?: $program?->default_capacity ?: 20),
            ]);
        }

        $rules = [
            'class_program_id' => ['required', 'integer', 'exists:class_programs,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'delivery_mode' => ['nullable', Rule::in(['online', 'offline'])],
            'section_name' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:999'],
            'description' => ['nullable', 'string'],
        ];

        if (in_array(Auth::user()?->role, ['admin', 'super_admin'], true)) {
            $rules['teacher_id'] = ['required', 'integer', 'exists:users,id'];
        }

        $data = $request->validate($rules);
        $program = ClassProgram::with('category')->findOrFail($data['class_program_id']);
        $branch = Branch::findOrFail($data['branch_id']);
        $data['title'] = $program->name;
        $data['program_type'] = $program->category->code;
        $data['branch'] = $branch->name;
        $data['delivery_mode'] = $data['delivery_mode'] ?? $classroom?->delivery_mode ?? 'offline';
        $data['is_active'] = true;
        $data['teacher_id'] = $data['teacher_id'] ?? $classroom?->teacher_id ?? Auth::id();

        return $data;
    }

    private function academicOptions(): array
    {
        $categories = ProgramCategory::where('is_active',true)->orderBy('sort_order')->get();
        $classPrograms = ClassProgram::with('category')->where('is_active',true)->orderBy('sort_order')->get();
        $branchRecords = Branch::where('is_active',true)->orderBy('name')->get();
        $periods = AcademicPeriod::where('is_active',true)->orderByDesc('is_default')->orderByDesc('id')->get();
        $programTypes = $categories->pluck('name','code')->all();
        $studentClassesByProgram = $classPrograms->groupBy(fn($program)=>$program->category->code)->map(fn($items)=>$items->pluck('name')->all())->all();
        return [$programTypes,$studentClassesByProgram,$branchRecords->pluck('name')->all(),$classPrograms,$branchRecords,$periods];
    }
}
