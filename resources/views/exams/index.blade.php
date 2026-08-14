@extends('layouts.app')
@section('title', 'Ujian')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
  <div><h2 class="text-3xl font-black">Ujian</h2><p class="mt-1 text-slate-500">Susun ujian, pantau peserta, dan kelola hasil pengerjaan.</p></div>
  <a href="{{ route('exams.create') }}" class="btn-action btn-primary-solid rounded-2xl px-5">+ Ujian Baru</a>
</div>
<div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
  @forelse($exams as $exam)
    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-3"><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ ['draft'=>'Draft','published'=>'Terbit','closed'=>'Ditutup'][$exam->status] ?? $exam->status }}</span><span class="text-sm font-semibold text-slate-400">{{ $exam->duration }} menit</span></div>
      <h3 class="mt-4 text-xl font-black text-slate-900">{{ $exam->title }}</h3>
      <p class="mt-2 min-h-10 text-sm leading-6 text-slate-500">{{ $exam->description ?: 'Tidak ada deskripsi.' }}</p>
      <div class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-xl bg-slate-50 p-3"><b class="block text-slate-900">{{ $exam->questions_count }}</b><span class="text-xs text-slate-500">Soal</span></div><div class="rounded-xl bg-slate-50 p-3"><b class="block text-slate-900">{{ $exam->attempts_count }}</b><span class="text-xs text-slate-500">Peserta</span></div><div class="rounded-xl bg-slate-50 p-3"><b class="block text-slate-900">{{ number_format($exam->passing_grade, 0) }}</b><span class="text-xs text-slate-500">KKM</span></div></div>
      <div class="mt-5 grid grid-cols-2 gap-2">
        <a href="{{ route('exams.edit', $exam) }}" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-center text-sm font-black text-indigo-700 hover:bg-indigo-100">Edit Ujian</a>
        <a href="{{ route('exams.results', $exam) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-black text-emerald-700 hover:bg-emerald-100">Hasil Siswa</a>
      </div>
      <form method="post" action="{{ route('exams.destroy', $exam) }}" class="mt-2" onsubmit="return confirm('Hapus ujian ini? Seluruh pengerjaan dan hasil siswa pada ujian ini juga akan terhapus permanen.')">@csrf @method('DELETE')<button class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm font-black text-rose-600 hover:bg-rose-50">Hapus Ujian</button></form>
    </article>
  @empty
    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 md:col-span-2 xl:col-span-3">Belum ada ujian.</div>
  @endforelse
</div>
@endsection
