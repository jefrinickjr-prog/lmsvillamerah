@extends('layouts.app')

@section('title', 'Edit Video Pembelajaran')

@section('content')
  <div class="mx-auto max-w-3xl">
    <div class="mb-6">
      <a href="{{ route('materials.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-indigo-600">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Kembali ke video pembelajaran
      </a>
      <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Edit Video Pembelajaran</h2>
      <p class="mt-2 text-slate-500">Perbarui video pembelajaran dan pastikan kelas programnya sesuai.</p>
    </div>

    <form method="POST" action="{{ route('materials.update', $material) }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @method('PUT')
      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
      @endif

      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Grup Video Pembelajaran</label>
          <select name="program_type" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
            @foreach($programTypes as $value => $label)
              <option value="{{ $value }}" @selected(old('program_type', $material->program_type ?? 'gambar') === $value)>{{ $label }}</option>
            @endforeach
          </select>
          <p class="mt-2 text-xs font-semibold text-slate-400">Gunakan grup ini agar tutorial gambar dan pengerjaan skolastik terpisah.</p>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Judul Video</label>
          <input name="title" value="{{ old('title', $material->title) }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Deskripsi Pembelajaran</label>
          <textarea name="content" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" rows="7">{{ old('content', $material->content) }}</textarea>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Link Video YouTube</label>
          <input name="youtube_embed_url" value="{{ old('youtube_embed_url', $material->youtube_embed_url) }}" placeholder="https://www.youtube.com/watch?v=..." class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
          <p class="mt-2 text-xs font-semibold text-slate-400">Bisa memakai link watch, youtu.be, shorts, embed URL, atau kode iframe dari YouTube.</p>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Kelas Program</label>
          <select name="classroom_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
            <option value="">Pilih kelas program</option>
            @foreach($classrooms as $classroom)
              <option value="{{ $classroom->id }}" @selected(old('classroom_id', $material->classroom_id) == $classroom->id)>{{ $classroom->title }} - {{ $classroom->teacher->name ?? 'Pengajar' }}</option>
            @endforeach
          </select>
        </div>

        <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700" type="submit">
          <i class="fa-solid fa-save"></i>
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
@endsection
