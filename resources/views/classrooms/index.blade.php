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

  <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse($classrooms as $classroom)
      <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-indigo-100 text-indigo-700">
          <i class="fa-solid fa-chalkboard-user"></i>
        </div>
        <h3 class="mt-5 text-lg font-black text-slate-950">{{ $classroom->title }}</h3>
        <div class="mt-2 flex flex-wrap gap-2">
          <span class="inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-violet-700">{{ \App\Models\User::programTypeLabel($classroom->program_type ?? 'gambar') }}</span>
          <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-indigo-700">{{ $classroom->branch ?? 'Cabang belum diisi' }}</span>
        </div>
        <p class="mt-2 min-h-12 text-sm leading-6 text-slate-500">{{ $classroom->description ?: 'Tidak ada deskripsi.' }}</p>
        <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500">
          Pengajar: {{ $classroom->teacher->name ?? '-' }}
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <a href="{{ route('classrooms.edit', $classroom) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit
          </a>
          <form method="POST" action="{{ route('classrooms.destroy', $classroom) }}" onsubmit="return confirm('Hapus kelas ini? Semua video pembelajaran dan tugas di kelas ini juga akan terhapus.');">
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
@endsection
