@extends('layouts.app')

@section('title', 'Kelas')

@section('content')
  <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
    <div>
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Manajemen Kelas</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Daftar Kelas</h2>
      <p class="mt-2 text-slate-500">Buat kelas program terlebih dahulu sebelum menambahkan video pembelajaran.</p>
    </div>
    <a href="{{ route('classrooms.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700">
      <i class="fa-solid fa-plus"></i>
      Buat Kelas
    </a>
  </div>

  <form method="GET" action="{{ route('classrooms.index') }}" class="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div class="xl:col-span-2">
        <label for="search" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-500">Cari kelas</label>
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input id="search" name="search" value="{{ request('search') }}" placeholder="Nama kelas atau bagian..." class="w-full rounded-2xl border-slate-200 py-3 pl-11 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
      </div>
      <div>
        <label for="class_program_id" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-500">Program kelas</label>
        <select id="class_program_id" name="class_program_id" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
          <option value="">Semua program</option>
          @foreach($classPrograms as $program)
            <option value="{{ $program->id }}" @selected(request('class_program_id') == $program->id)>{{ $program->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="branch_id" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-500">Cabang</label>
        <select id="branch_id" name="branch_id" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
          <option value="">Semua cabang</option>
          @foreach($branches as $branch)
            <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="academic_period_id" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-500">Periode akademik</label>
        <select id="academic_period_id" name="academic_period_id" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
          <option value="">Semua periode</option>
          @foreach($periods as $period)
            <option value="{{ $period->id }}" @selected(request('academic_period_id') == $period->id)>{{ $period->name }}</option>
          @endforeach
        </select>
      </div>
      @if(in_array(auth()->user()->role, ['admin', 'super_admin'], true))
        <div>
          <label for="teacher_id" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-500">Pengajar</label>
          <select id="teacher_id" name="teacher_id" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Semua pengajar</option>
            @foreach($teachers as $teacher)
              <option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
            @endforeach
          </select>
        </div>
      @endif
      <div>
        <label for="delivery_mode" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-500">Mode belajar</label>
        <select id="delivery_mode" name="delivery_mode" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
          <option value="">Semua mode</option>
          <option value="online" @selected(request('delivery_mode') === 'online')>Online</option>
          <option value="offline" @selected(request('delivery_mode') === 'offline')>Offline</option>
        </select>
      </div>
      <div>
        <label for="status" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-500">Status</label>
        <select id="status" name="status" class="w-full rounded-2xl border-slate-200 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
          <option value="">Semua status</option>
          <option value="active" @selected(request('status') === 'active')>Aktif</option>
          <option value="inactive" @selected(request('status') === 'inactive')>Tidak aktif</option>
        </select>
      </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-3">
      <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white hover:bg-indigo-700"><i class="fa-solid fa-filter"></i>Terapkan Filter</button>
      @if(request()->hasAny(['search', 'class_program_id', 'branch_id', 'academic_period_id', 'teacher_id', 'delivery_mode', 'status']))
        <a href="{{ route('classrooms.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200"><i class="fa-solid fa-rotate-left"></i>Reset</a>
      @endif
    </div>
  </form>

  <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse($classrooms as $classroom)
      <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-indigo-100 text-indigo-700">
          <i class="fa-solid fa-chalkboard-user"></i>
        </div>
        <h3 class="mt-5 text-lg font-black text-slate-950">{{ $classroom->display_name }}</h3>
        <div class="mt-2 flex flex-wrap gap-2">
          <span class="inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide {{ ($classroom->delivery_mode ?? 'offline') === 'online' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ ($classroom->delivery_mode ?? 'offline') === 'online' ? 'Online' : 'Offline' }}</span>
          <span class="inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-violet-700">{{ \App\Models\User::programTypeLabel($classroom->program_type ?? 'gambar') }}</span>
          <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-indigo-700">{{ $classroom->branch ?? 'Cabang belum diisi' }}</span>
        </div>
        <p class="mt-2 min-h-12 text-sm leading-6 text-slate-500">{{ $classroom->description ?: 'Tidak ada deskripsi.' }}</p>
        <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">
          Pengajar: {{ $classroom->teacher->name ?? '-' }}
          <div class="mt-2 text-slate-700"><i class="fa-solid fa-users mr-2 text-indigo-500"></i>{{ $classroom->active_enrollments_count }} / {{ $classroom->capacity }} siswa</div>
          <div class="mt-2 text-xs">{{ $classroom->academicPeriod->name ?? 'Periode belum diatur' }}</div>
        </div>
        <a href="{{ route('classrooms.roster', $classroom) }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-black text-white hover:bg-indigo-700"><i class="fa-solid fa-users-gear"></i>Anggota & Jadwal</a>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
          <a href="{{ route('classrooms.edit', $classroom) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit
          </a>
          <form method="POST" action="{{ route('classrooms.destroy', $classroom) }}" onsubmit="return confirm('Hapus kelas ini? Video pembelajaran tetap tersimpan dan akan muncul kembali jika kelas dibuat lagi.');">
            @csrf
            @method('DELETE')
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-black text-rose-600 hover:bg-rose-100" type="submit">
              <i class="fa-solid fa-trash"></i>
              Hapus
            </button>
          </form>
        </div>
      </article>
    @empty
      <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center md:col-span-2 xl:col-span-3">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400">
          <i class="fa-solid fa-chalkboard"></i>
        </div>
        <h3 class="mt-4 font-black text-slate-900">Belum ada kelas</h3>
        <p class="mt-2 text-sm text-slate-500">Buat kelas dulu agar video pembelajaran bisa disimpan ke kelas yang valid.</p>
      </div>
    @endforelse
  </div>

  @if($classrooms->hasPages())
    <div class="mt-6">{{ $classrooms->links() }}</div>
  @endif
@endsection
