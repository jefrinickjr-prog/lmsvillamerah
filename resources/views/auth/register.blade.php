@extends('layouts.app')
@section('title', 'Daftarkan Siswa')
@section('content')
<div class="mx-auto max-w-3xl">
  <div class="mb-6"><p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Pendaftaran & Enrollment</p><h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Daftarkan Siswa</h2><p class="mt-2 text-slate-500">Buat akun dan tempatkan siswa ke kelompok kelas dalam satu langkah.</p></div>
  <form method="POST" action="{{ route('register.post') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">@csrf
    @if($errors->any())<div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
    <div class="space-y-5">
      <div class="grid gap-5 sm:grid-cols-2">
        <label class="block text-sm font-bold text-slate-700">Nama Siswa<input name="name" value="{{ old('name') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3" required autofocus></label>
        <label class="block text-sm font-bold text-slate-700">Email Siswa<input name="email" value="{{ old('email') }}" type="email" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3" required></label>
      </div>
      <div class="rounded-3xl border-2 border-indigo-200 bg-indigo-50 p-5">
        <label class="block text-sm font-black text-slate-900">Kelompok Kelas Tujuan</label>
        <p class="mt-1 text-xs font-semibold text-slate-500">Program, cabang, periode, jenis pembelajaran, dan kode siswa akan diatur otomatis.</p>
        <select name="classroom_id" class="mt-3 block w-full rounded-2xl border border-indigo-300 bg-white px-4 py-3 font-bold text-slate-800" required>
          <option value="">Pilih kelompok kelas...</option>
          @foreach($classrooms->groupBy(fn($classroom) => $classroom->program?->category?->name ?: 'Program Lainnya') as $category => $items)
            <optgroup label="{{ $category }}">
              @foreach($items as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id) @disabled($classroom->active_enrollments_count >= $classroom->capacity)>{{ $classroom->display_name }} · {{ $classroom->branchMaster?->name ?: $classroom->branch }} · {{ $classroom->academicPeriod?->name }} · {{ $classroom->active_enrollments_count }}/{{ $classroom->capacity }} siswa{{ $classroom->active_enrollments_count >= $classroom->capacity ? ' (Penuh)' : '' }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
        @if($classrooms->isEmpty())<p class="mt-3 rounded-xl bg-amber-100 p-3 text-sm font-bold text-amber-800">Belum ada kelas aktif yang dapat Anda kelola. Buat kelas terlebih dahulu.</p>@endif
      </div>
      @php $selectedVideoAccesses=old('video_accesses',['gambar']); if(!is_array($selectedVideoAccesses))$selectedVideoAccesses=[$selectedVideoAccesses]; @endphp
      <div><label class="mb-2 block text-sm font-bold text-slate-700">Akses Video Pembelajaran</label><div class="grid gap-3 sm:grid-cols-2">@foreach($videoAccessOptions as $value=>$label)<label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold text-slate-700"><input type="checkbox" name="video_accesses[]" value="{{ $value }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600" @checked(in_array($value,$selectedVideoAccesses,true))><span>{{ $label }}</span></label>@endforeach</div></div>
      <div class="grid gap-5 sm:grid-cols-2"><label class="block text-sm font-bold text-slate-700">Password Awal<input name="password" type="password" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3" required></label><label class="block text-sm font-bold text-slate-700">Konfirmasi Password<input name="password_confirmation" type="password" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3" required></label></div>
      <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"><i class="fa-solid fa-circle-check mr-2"></i>Akun langsung aktif, kode siswa dibuat otomatis, dan siswa langsung masuk ke daftar anggota kelas.</div>
      <button class="btn-action btn-primary-solid w-full rounded-2xl px-5 py-3" type="submit" @disabled($classrooms->isEmpty())><i class="fa-solid fa-user-plus"></i>Buat Akun & Masukkan ke Kelas</button>
    </div>
  </form>
</div>
@endsection
