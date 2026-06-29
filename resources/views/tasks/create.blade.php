@extends('layouts.app')

@php
  $isEdit = isset($task) && $task;
  $existingQuestions = old('questions', $isEdit ? ($task->questions ?? []) : []);
  $selectedClassroomIds = array_map('intval', old('classroom_ids', $isEdit ? $task->classrooms->pluck('id')->all() : []));
@endphp

@section('title', $isEdit ? 'Edit Tugas' : 'Buat Tugas')

@section('content')
  <div class="mx-auto max-w-3xl">
    <div class="mb-6">
      <a href="{{ route('tasks.index') }}" class="btn-link-strong inline-flex items-center gap-2 text-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Kembali ke tugas
      </a>
      <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">{{ $isEdit ? 'Edit Tugas' : 'Buat Tugas' }}</h2>
      <p class="mt-2 text-slate-500">Tambahkan instruksi, PDF, target kelas, latihan soal, dan timer pengerjaan siswa.</p>
    </div>

    <form method="POST" action="{{ $isEdit ? route('tasks.update', $task) : route('tasks.store') }}" enctype="multipart/form-data" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @if($isEdit)
        @method('PUT')
      @endif
      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
      @endif
      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Judul</label>
          <input name="title" value="{{ old('title', $task->title ?? '') }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
        </div>
        <div class="grid gap-5 sm:grid-cols-3">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Jenis Tugas</label>
            <select name="task_type" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
              @foreach($taskTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('task_type', $task->task_type ?? 'assignment') === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Deadline</label>
            <input name="due_at" value="{{ old('due_at', optional($task->due_at ?? null)->format('Y-m-d\TH:i')) }}" type="datetime-local" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
          </div>
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Timer Latihan</label>
            <input name="duration_minutes" value="{{ old('duration_minutes', $task->duration_minutes ?? '') }}" type="number" min="1" max="600" placeholder="Menit" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
          </div>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Deskripsi</label>
          <textarea name="description" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" rows="6">{{ old('description', $task->description ?? '') }}</textarea>
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Lampiran PDF</label>
          <input name="attachment" type="file" accept="application/pdf,.pdf" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition file:mr-4 file:rounded-xl file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-black file:text-white focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
          <p class="mt-2 text-xs font-semibold text-slate-400">Opsional, maksimal 10MB. Gunakan untuk modul, lembar soal, atau referensi.</p>
          @if($isEdit && $task->attachment_path)
            <label class="mt-3 flex items-center gap-3 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
              <input type="checkbox" name="remove_attachment" value="1" class="h-4 w-4 rounded border-rose-300 text-rose-600">
              Hapus lampiran PDF saat ini
            </label>
          @endif
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Video Pembelajaran <span class="font-semibold text-slate-400">(opsional)</span></label>
          @if($videos->isNotEmpty())
            <select name="material_id" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
              <option value="">Tidak dikaitkan dengan video</option>
              @foreach($videos as $video)
                @php
                  $videoClassrooms = $video->classrooms->isNotEmpty()
                    ? $video->classrooms->pluck('title')->implode(', ')
                    : ($video->classroom->title ?? 'Kelas');
                @endphp
                <option value="{{ $video->id }}" @selected(old('material_id', $task->material_id ?? '') == $video->id)>{{ \App\Models\User::programTypeLabel($video->program_type ?? 'gambar') }} - {{ $video->title }} - {{ $videoClassrooms }}</option>
              @endforeach
            </select>
          @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">
              Belum ada video pembelajaran. Tugas tetap bisa dibuat tanpa video.
            </div>
          @endif
        </div>
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Target Kelas</label>
          @if($classrooms->isNotEmpty())
            <div class="grid gap-3 sm:grid-cols-2">
              @foreach($classrooms as $classroom)
                <label class="flex min-h-14 cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold text-slate-700">
                  <input type="checkbox" name="classroom_ids[]" value="{{ $classroom->id }}" class="mt-1 h-5 w-5 rounded border-slate-300 text-indigo-600" @checked(in_array((int) $classroom->id, $selectedClassroomIds, true))>
                  <span>
                    <span class="block">{{ $classroom->title }}</span>
                    <span class="mt-1 block text-xs font-semibold text-slate-400">{{ $classroom->teacher->name ?? 'Pengajar' }}</span>
                  </span>
                </label>
              @endforeach
            </div>
          @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">Belum ada kelas tersedia.</div>
          @endif
        </div>
        <section class="rounded-3xl border border-slate-100 bg-slate-50 p-4 sm:p-5">
          <div>
            <h3 class="font-black text-slate-900">Soal Tugas</h3>
            <p class="mt-1 text-sm text-slate-500">Isi sampai 20 soal. Kosongkan slot yang tidak dipakai.</p>
          </div>
          <div class="mt-4 space-y-4">
            @for($index = 0; $index < 20; $index++)
              @php
                $question = $existingQuestions[$index] ?? [];
                $questionType = $question['type'] ?? 'essay';
                $questionPrompt = $question['prompt'] ?? '';
                $questionOptions = $question['options'] ?? '';
                if (is_array($questionOptions)) {
                  $questionOptions = implode("\n", $questionOptions);
                }
              @endphp
              <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="mb-3 text-sm font-black text-indigo-600">Soal {{ $index + 1 }}</div>
              <div class="grid gap-3 sm:grid-cols-3">
                <div>
                  <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Tipe</label>
                  <select name="questions[{{ $index }}][type]" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none">
                    <option value="essay" @selected($questionType === 'essay')>Esai</option>
                    <option value="multiple_choice" @selected($questionType === 'multiple_choice')>Pilihan Ganda</option>
                    <option value="questionnaire" @selected($questionType === 'questionnaire')>Kuesioner</option>
                  </select>
                </div>
                <div class="sm:col-span-2">
                  <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Pertanyaan</label>
                  <input name="questions[{{ $index }}][prompt]" value="{{ $questionPrompt }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none" placeholder="Tulis pertanyaan...">
                </div>
              </div>
              <div class="mt-3">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-400">Pilihan Jawaban</label>
                <textarea name="questions[{{ $index }}][options]" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none" rows="3" placeholder="Untuk pilihan ganda saja. Satu pilihan per baris.">{{ $questionOptions }}</textarea>
              </div>
            </div>
            @endfor
          </div>
        </section>
        <button class="btn-action btn-primary-solid rounded-2xl px-5 py-3 text-sm disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none" type="submit" @disabled($classrooms->isEmpty())>
          <i class="fa-solid fa-save"></i>
          {{ $isEdit ? 'Update Tugas' : 'Simpan Tugas' }}
        </button>
      </div>
    </form>
  </div>
@endsection
