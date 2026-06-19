@extends('layouts.app')

@section('title', 'Tambah Video Pembelajaran')

@section('content')
  <div class="mx-auto max-w-3xl">
    <div class="mb-6">
      <a href="{{ route('materials.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-indigo-600">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Kembali ke video pembelajaran
      </a>
      <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Tambah Video Pembelajaran</h2>
      <p class="mt-2 text-slate-500">Pengajar/admin dapat menambahkan video pembelajaran sesuai kelas program.</p>
    </div>

    <form method="POST" action="{{ route('materials.store') }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
      @endif
      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Grup Video Pembelajaran</label>
          <select name="program_type" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
            @foreach($programTypes as $value => $label)
              <option value="{{ $value }}" @selected(old('program_type', 'gambar') === $value)>Video Pembelajaran {{ $label }}</option>
            @endforeach
          </select>
          <p class="mt-2 text-xs font-semibold text-slate-400">Video gambar hanya tampil untuk siswa program gambar, begitu juga video skolastik.</p>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Judul Video</label>
          <input name="title" value="{{ old('title') }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Deskripsi Pembelajaran</label>
          <textarea name="content" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" rows="7">{{ old('content') }}</textarea>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Link Video YouTube</label>
          <input name="youtube_embed_url" value="{{ old('youtube_embed_url') }}" placeholder="https://www.youtube.com/watch?v=..." class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
          <p class="mt-2 text-xs font-semibold text-slate-400">Bisa memakai link watch, youtu.be, shorts, embed URL, atau kode iframe dari YouTube.</p>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Kelas Program</label>
          @if($classrooms->isNotEmpty())
            <select name="classroom_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
              <option value="">Pilih kelas program</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ \App\Models\User::programTypeLabel($classroom->program_type ?? 'gambar') }} - {{ $classroom->title }} - {{ $classroom->teacher->name ?? 'Pengajar' }}</option>
              @endforeach
            </select>
          @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">
              Belum ada kelas program yang tersedia. Buat kelas dulu sebelum menambahkan video pembelajaran.
            </div>
            <a href="{{ route('classrooms.create') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-black text-indigo-600">
              <i class="fa-solid fa-plus text-xs"></i>
              Buat kelas program sekarang
            </a>
          @endif
        </div>
        <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none" type="submit" @disabled($classrooms->isEmpty())>
          <i class="fa-solid fa-save"></i>
          Simpan Video
        </button>
      </div>
    </form>
  </div>
@endsection
