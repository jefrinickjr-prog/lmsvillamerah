@extends('layouts.app')

@section('title', 'Daftarkan Siswa')

@section('content')
  <div class="mx-auto max-w-3xl">
    <div class="mb-6">
      <p class="text-sm font-bold uppercase tracking-wider text-indigo-500">Akun Internal</p>
      <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Daftarkan Siswa</h2>
      <p class="mt-2 text-slate-500">Hanya admin dan pengajar yang dapat membuat akun siswa. Akun yang dibuat otomatis berstatus verified.</p>
    </div>

    <form method="POST" action="{{ route('register.post') }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
      @endif

      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Nama Siswa</label>
          <input name="name" value="{{ old('name') }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required autofocus>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Email Siswa</label>
          <input name="email" value="{{ old('email') }}" type="email" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Kelas Program</label>
          <select name="student_class" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
            <option value="">Pilih kelas program</option>
            @foreach($studentClasses as $studentClass)
              <option value="{{ $studentClass }}" @selected(old('student_class') === $studentClass)>{{ $studentClass }}</option>
            @endforeach
          </select>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Cabang</label>
            <select name="branch" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
              <option value="">Pilih cabang</option>
              @foreach($branches as $branch)
                <option value="{{ $branch }}" @selected(old('branch') === $branch)>{{ $branch }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Tahun Ajaran</label>
            <input name="academic_year" value="{{ old('academic_year', $defaultAcademicYear) }}" placeholder="2026-2027" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
          </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Password Awal</label>
            <input name="password" type="password" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
          </div>
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Konfirmasi Password</label>
            <input name="password_confirmation" type="password" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
          </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
          Role otomatis menjadi siswa, email langsung verified, dan kode siswa dibuat dari tahun ajaran, cabang, kelas, serta nomor urut.
        </div>

        <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700" type="submit">
          <i class="fa-solid fa-user-plus"></i>
          Buat Akun Siswa
        </button>
      </div>
    </form>
  </div>
@endsection
