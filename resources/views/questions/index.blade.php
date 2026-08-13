@extends('layouts.app')

@section('title', 'Bank Soal')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
  <div>
    <h2 class="text-3xl font-black text-slate-900">Bank Soal</h2>
    <p class="mt-1 text-slate-500">Kelola, cari, dan kelompokkan soal berdasarkan subtest.</p>
  </div>
  <a href="{{ route('questions.create') }}" class="btn-action btn-primary-solid rounded-2xl px-5">+ Buat Soal</a>
</div>

<form method="get" action="{{ route('questions.index') }}" class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-2">
    <div>
      <h3 class="font-black text-slate-900">Filter Bank Soal</h3>
      <p class="text-sm text-slate-500">Pilih kriteria, kemudian tekan Terapkan Filter.</p>
    </div>
    @if(request()->hasAny(['subject_id', 'topic_id', 'type', 'difficulty', 'status']))
      <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">Filter aktif</span>
    @endif
  </div>

  <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <label class="text-sm font-bold text-slate-700">Subtest
      <select id="subjectFilter" name="subject_id" class="mt-2 w-full cursor-pointer rounded-xl border-slate-300 bg-white py-3 pl-3 pr-9 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Semua subtest</option>
        @foreach($subjects as $subject)
          <option value="{{ $subject->id }}" @selected((string) request('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>
        @endforeach
      </select>
    </label>

    <label class="text-sm font-bold text-slate-700">Materi
      <select id="topicFilter" name="topic_id" class="mt-2 w-full cursor-pointer rounded-xl border-slate-300 bg-white py-3 pl-3 pr-9 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Semua materi</option>
        @foreach($subjects as $subject)
          @foreach($subject->topics as $topic)
            <option value="{{ $topic->id }}" data-subject="{{ $subject->id }}" @selected((string) request('topic_id') === (string) $topic->id)>{{ $topic->name }}</option>
          @endforeach
        @endforeach
      </select>
    </label>

    <label class="text-sm font-bold text-slate-700">Jenis Soal
      <select name="type" class="mt-2 w-full cursor-pointer rounded-xl border-slate-300 bg-white py-3 pl-3 pr-9 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Semua jenis</option>
        <option value="multiple_choice" @selected(request('type') === 'multiple_choice')>Pilihan Ganda</option>
        <option value="essay" @selected(request('type') === 'essay')>Esai</option>
      </select>
    </label>

    <label class="text-sm font-bold text-slate-700">Kesulitan
      <select name="difficulty" class="mt-2 w-full cursor-pointer rounded-xl border-slate-300 bg-white py-3 pl-3 pr-9 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Semua tingkat</option>
        <option value="easy" @selected(request('difficulty') === 'easy')>Mudah</option>
        <option value="medium" @selected(request('difficulty') === 'medium')>Sedang</option>
        <option value="hard" @selected(request('difficulty') === 'hard')>Sulit</option>
      </select>
    </label>

    <label class="text-sm font-bold text-slate-700">Status
      <select name="status" class="mt-2 w-full cursor-pointer rounded-xl border-slate-300 bg-white py-3 pl-3 pr-9 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Semua status</option>
        <option value="active" @selected(request('status') === 'active')>Aktif</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
      </select>
    </label>
  </div>

  <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
    <a href="{{ route('questions.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-bold text-slate-700 hover:bg-slate-50">Reset</a>
    <button type="submit" class="rounded-xl px-6 py-3 font-black shadow-sm" style="background:#0f172a;color:#fff">Terapkan Filter</button>
  </div>
</form>

<div class="mt-5 overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
  <table class="w-full min-w-[850px] text-left text-sm">
    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
      <tr><th class="p-4">Soal</th><th class="p-4">Subtest / Materi</th><th class="p-4">Jenis</th><th class="p-4">Bobot</th><th class="p-4">Aksi</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      @forelse($questions as $question)
        <tr class="hover:bg-slate-50/70">
          <td class="max-w-xl p-4 font-semibold text-slate-800">
            <span class="math-content">{!! strip_tags($question->question, '<p><br><strong><em>') !!}</span>
            <div class="mt-2 text-xs font-semibold text-slate-400">{{ ['easy'=>'Mudah','medium'=>'Sedang','hard'=>'Sulit'][$question->difficulty] ?? $question->difficulty }} · {{ $question->class_level ?: 'Semua kelas' }}</div>
          </td>
          <td class="p-4"><div class="font-bold text-slate-800">{{ $question->subject->name }}</div><div class="mt-1 text-xs text-slate-400">{{ $question->topic?->name ?: 'Tanpa materi' }}</div></td>
          <td class="p-4">{{ $question->type === 'essay' ? 'Esai' : 'Pilihan Ganda' }}</td>
          <td class="p-4 font-bold">{{ number_format($question->score, 0) }}</td>
          <td class="p-4"><div class="flex flex-wrap gap-3"><a class="font-bold text-indigo-600" href="{{ route('questions.edit', $question) }}">Edit</a><form method="post" action="{{ route('questions.duplicate', $question) }}">@csrf<button class="font-bold text-emerald-600">Duplikat</button></form><form method="post" action="{{ route('questions.destroy', $question) }}" onsubmit="return confirm('Hapus soal?')">@csrf @method('DELETE')<button class="font-bold text-rose-600">Hapus</button></form></div></td>
        </tr>
      @empty
        <tr><td colspan="5" class="p-12 text-center text-slate-500">Tidak ada soal yang sesuai dengan filter.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-5">{{ $questions->links() }}</div>

@include('questions.math')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const subject = document.querySelector('#subjectFilter');
  const topic = document.querySelector('#topicFilter');
  const options = [...topic.querySelectorAll('option[data-subject]')];
  const updateTopics = () => {
    options.forEach(option => option.hidden = Boolean(subject.value) && option.dataset.subject !== subject.value);
    const selected = topic.options[topic.selectedIndex];
    if (selected?.hidden) topic.value = '';
  };
  subject.addEventListener('change', updateTopics);
  updateTopics();
});
</script>
@endsection
