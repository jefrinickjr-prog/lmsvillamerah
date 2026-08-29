@extends('layouts.app')

@section('title', 'Buat Kelas')

@section('content')
  <div class="mx-auto max-w-3xl">
    <div class="mb-6">
      <a href="{{ route('classrooms.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-indigo-600">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Kembali ke kelas
      </a>
      <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Buat Kelas</h2>
      <p class="mt-2 text-slate-500">Kelas diperlukan agar video pembelajaran bisa tersimpan ke ruang belajar yang tepat.</p>
    </div>

    <form method="POST" action="{{ route('classrooms.store') }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
      @endif

      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Kategori Pembelajaran</label>
          <select name="delivery_mode" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" required>
            <option value="offline" @selected(old('delivery_mode') === 'offline')>Offline / Tatap Muka</option>
            <option value="online" @selected(old('delivery_mode') === 'online')>Online / Live Streaming</option>
          </select>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Program Kelas</label>
          <select name="class_program_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" required>
            <option value="">Pilih program</option>
            @foreach($classPrograms->groupBy(fn($program) => $program->category->name) as $category => $items)
              <optgroup label="{{ $category }}">
                @foreach($items as $program)
                  <option value="{{ $program->id }}" @selected(old('class_program_id') == $program->id)>{{ $program->name }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <label class="block text-sm font-bold text-slate-700">Nama Kelompok / Sesi<input name="section_name" value="{{ old('section_name', 'Pagi A') }}" placeholder="Pagi A, Siang A, Sore A" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" required></label>
          <label class="block text-sm font-bold text-slate-700">Kapasitas<input type="number" name="capacity" value="{{ old('capacity', 20) }}" min="1" max="999" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" required></label>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Cabang</label>
          <select name="branch_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
            <option value="">Pilih cabang</option>
            @foreach($branchRecords as $branch)
              <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
          </select>
        </div>

        <div><label class="mb-2 block text-sm font-bold text-slate-700">Periode Akademik</label><select name="academic_period_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" required>@foreach($periods as $period)<option value="{{ $period->id }}" @selected(old('academic_period_id') == $period->id || (!old('academic_period_id') && $period->is_default))>{{ $period->name }}{{ $period->is_default ? ' · Aktif' : '' }}</option>@endforeach</select></div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Deskripsi</label>
          <textarea name="description" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" rows="5">{{ old('description') }}</textarea>
        </div>

        @if(in_array(auth()->user()?->role, ['admin', 'super_admin'], true))
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Pengajar</label>
            <select name="teacher_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
              <option value="">Pilih pengajar</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }} - {{ $teacher->role }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700" type="submit">
          <i class="fa-solid fa-save"></i>
          Simpan Kelas
        </button>
      </div>
    </form>
  </div>
@endsection
