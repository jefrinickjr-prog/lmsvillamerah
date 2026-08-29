<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Branch;
use App\Models\ClassProgram;
use App\Models\ProgramCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AcademicSettingController extends Controller
{
    public function index()
    {
        abort_unless($this->canView(), 403);
        return view('academic-settings.index', [
            'canManage' => $this->canManage(),
            'categories' => ProgramCategory::withCount('programs')->orderBy('sort_order')->get(),
            'programs' => ClassProgram::with('category')->withCount('classrooms')->orderBy('sort_order')->get(),
            'branches' => Branch::withCount('classrooms')->orderBy('name')->get(),
            'periods' => AcademicPeriod::withCount('classrooms')->latest('is_default')->latest('id')->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $this->authorizeManage();
        $data = $request->validate(['name'=>'required|string|max:255','code'=>'nullable|string|max:50|unique:program_categories,code','description'=>'nullable|string']);
        $data['code'] = Str::lower($data['code'] ?: Str::slug($data['name'], '-'));
        ProgramCategory::create($data + ['is_active'=>true,'sort_order'=>((int) ProgramCategory::max('sort_order'))+10]);
        return back()->with('success', 'Kategori program berhasil ditambahkan.');
    }

    public function storeProgram(Request $request)
    {
        $this->authorizeManage();
        $data = $request->validate(['program_category_id'=>'required|exists:program_categories,id','name'=>'required|string|max:255','code'=>'nullable|string|max:80|unique:class_programs,code','description'=>'nullable|string','default_capacity'=>'required|integer|min:1|max:999']);
        $data['code'] = Str::upper($data['code'] ?: Str::slug($data['name'], '-'));
        ClassProgram::create($data + ['is_active'=>true,'sort_order'=>((int) ClassProgram::max('sort_order'))+10]);
        return back()->with('success', 'Program kelas berhasil ditambahkan.');
    }

    public function storeBranch(Request $request)
    {
        $this->authorizeManage();
        $data = $request->validate(['name'=>'required|string|max:255|unique:branches,name','code'=>'required|string|max:20|unique:branches,code','address'=>'nullable|string']);
        Branch::create($data + ['code'=>Str::upper($data['code']),'is_active'=>true]);
        return back()->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function storePeriod(Request $request)
    {
        $this->authorizeManage();
        $data = $request->validate(['code'=>'required|string|max:30|unique:academic_periods,code','name'=>'required|string|max:255','starts_on'=>'nullable|date','ends_on'=>'nullable|date|after_or_equal:starts_on','is_default'=>'nullable|boolean']);
        if ($request->boolean('is_default')) AcademicPeriod::query()->update(['is_default'=>false]);
        AcademicPeriod::create($data + ['is_active'=>true,'is_default'=>$request->boolean('is_default')]);
        return back()->with('success', 'Periode akademik berhasil ditambahkan.');
    }

    public function toggle(Request $request, string $type, int $id)
    {
        $this->authorizeManage();
        $map = ['category'=>ProgramCategory::class,'program'=>ClassProgram::class,'branch'=>Branch::class,'period'=>AcademicPeriod::class];
        abort_unless(isset($map[$type]), 404);
        $record = $map[$type]::findOrFail($id);
        $record->update(['is_active'=>!$record->is_active]);
        return back()->with('success', 'Status data master berhasil diperbarui.');
    }

    private function canView(): bool { return in_array(Auth::user()?->role, ['teacher','admin','super_admin'], true); }
    private function canManage(): bool { return in_array(Auth::user()?->role, ['admin','super_admin'], true); }
    private function authorizeManage(): void { abort_unless($this->canManage(), 403); }
}
