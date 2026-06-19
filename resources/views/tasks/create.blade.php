@extends('layouts.app')

@section('title', 'Buat Tugas')

@section('content')
  <div class="mx-auto max-w-3xl">
    <div class="mb-6">
      <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-indigo-600">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Kembali ke tugas
      </a>
      <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Buat Tugas</h2>
      <p class="mt-2 text-slate-500">Tambahkan instruksi tugas dan hubungkan ke video pembelajaran sesuai kelas program.</p>
    </div>

    <form method="POST" action="{{ route('tasks.store') }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Judul</label>
          <input name="title" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Deskripsi</label>
          <textarea name="description" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" rows="6"></textarea>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Video Pembelajaran</label>
          @if($videos->isNotEmpty())
            <select name="material_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
              <option value="">Pilih video pembelajaran</option>
              @foreach($videos as $video)
                <option value="{{ $video->id }}" @selected(old('material_id') == $video->id)>{{ \App\Models\User::programTypeLabel($video->program_type ?? 'gambar') }} - {{ $video->title }} - {{ $video->classroom->title ?? 'Kelas' }}</option>
              @endforeach
            </select>
          @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">
              Belum ada video pembelajaran yang tersedia. Tambahkan video terlebih dahulu sebelum membuat tugas.
            </div>
            <a href="{{ route('materials.create') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-black text-indigo-600">
              <i class="fa-solid fa-plus text-xs"></i>
              Tambah video pembelajaran
            </a>
          @endif
        </div>
        <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none" type="submit" @disabled($videos->isEmpty())>
          <i class="fa-solid fa-save"></i>
          Simpan Tugas
        </button>
      </div>
    </form>
  </div>
@endsection
