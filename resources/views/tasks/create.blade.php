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

    <form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
      @endif
      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Judul</label>
          <input name="title" value="{{ old('title') }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Jenis Tugas</label>
            <select name="task_type" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
              @foreach($taskTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('task_type', 'assignment') === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Deadline</label>
            <input name="due_at" value="{{ old('due_at') }}" type="datetime-local" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
          </div>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Deskripsi</label>
          <textarea name="description" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" rows="6">{{ old('description') }}</textarea>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Lampiran PDF</label>
          <input name="attachment" type="file" accept="application/pdf,.pdf" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition file:mr-4 file:rounded-xl file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-black file:text-white focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
          <p class="mt-2 text-xs font-semibold text-slate-400">Opsional, maksimal 10MB. Gunakan untuk modul, lembar soal, atau referensi.</p>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Video Pembelajaran</label>
          @if($videos->isNotEmpty())
            <select name="material_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
              <option value="">Pilih video pembelajaran</option>
              @foreach($videos as $video)
                @php
                  $videoClassrooms = $video->classrooms->isNotEmpty()
                    ? $video->classrooms->pluck('title')->implode(', ')
                    : ($video->classroom->title ?? 'Kelas');
                @endphp
                <option value="{{ $video->id }}" @selected(old('material_id') == $video->id)>{{ \App\Models\User::programTypeLabel($video->program_type ?? 'gambar') }} - {{ $video->title }} - {{ $videoClassrooms }}</option>
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
        <section class="rounded-3xl border border-slate-100 bg-slate-50 p-4 sm:p-5">
          <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
              <h3 class="font-black text-slate-900">Soal Tugas</h3>
              <p class="mt-1 text-sm text-slate-500">Tambahkan soal esai, pilihan ganda, atau kuesioner pembelajaran.</p>
            </div>
            <button id="addQuestion" type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-black text-white hover:bg-slate-800">
              <i class="fa-solid fa-plus"></i>
              Tambah Soal
            </button>
          </div>
          <div id="questionsList" class="mt-4 space-y-4"></div>
          <template id="questionTemplate">
            <div class="question-item rounded-2xl border border-slate-200 bg-white p-4">
              <div class="mb-3 flex items-center justify-between gap-3">
                <span class="question-title text-sm font-black text-indigo-600">Soal</span>
                <button type="button" class="remove-question rounded-xl bg-rose-50 px-3 py-2 text-xs font-black text-rose-600">Hapus</button>
              </div>
              <div class="grid gap-3 sm:grid-cols-3">
                <div>
                  <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Tipe</label>
                  <select data-name="type" class="question-type block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none">
                    <option value="essay">Esai</option>
                    <option value="multiple_choice">Pilihan Ganda</option>
                    <option value="questionnaire">Kuesioner</option>
                  </select>
                </div>
                <div class="sm:col-span-2">
                  <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Pertanyaan</label>
                  <input data-name="prompt" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none" placeholder="Tulis pertanyaan...">
                </div>
              </div>
              <div class="question-options mt-3 hidden">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Pilihan Jawaban</label>
                <textarea data-name="options" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none" rows="4" placeholder="Satu pilihan per baris"></textarea>
              </div>
            </div>
          </template>
        </section>
        <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none" type="submit" @disabled($videos->isEmpty())>
          <i class="fa-solid fa-save"></i>
          Simpan Tugas
        </button>
      </div>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const list = document.getElementById('questionsList');
      const template = document.getElementById('questionTemplate');
      const addButton = document.getElementById('addQuestion');

      const renumberQuestions = () => {
        list.querySelectorAll('.question-item').forEach((item, index) => {
          item.querySelector('.question-title').textContent = `Soal ${index + 1}`;
          item.querySelectorAll('[data-name]').forEach((field) => {
            field.name = `questions[${index}][${field.dataset.name}]`;
          });
        });
      };

      const addQuestion = () => {
        const item = template.content.firstElementChild.cloneNode(true);
        const typeSelect = item.querySelector('.question-type');
        const options = item.querySelector('.question-options');

        typeSelect.addEventListener('change', () => {
          options.classList.toggle('hidden', typeSelect.value !== 'multiple_choice');
        });
        item.querySelector('.remove-question').addEventListener('click', () => {
          item.remove();
          renumberQuestions();
        });

        list.appendChild(item);
        renumberQuestions();
      };

      addButton?.addEventListener('click', addQuestion);
      addQuestion();
    });
  </script>
@endsection
