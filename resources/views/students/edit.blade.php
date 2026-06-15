@extends('layouts.app')

@section('title', 'Edit Siswa')

@section('content')
  <div class="mx-auto max-w-3xl">
    <div class="mb-6">
      <a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-indigo-600">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Kembali ke daftar siswa
      </a>
      <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Edit Siswa</h2>
      <p class="mt-2 text-slate-500">Perbarui data akun, kelas, cabang, dan tahun ajaran siswa.</p>
    </div>

    <form method="POST" action="{{ route('students.update', $student) }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
      @csrf
      @method('PUT')
      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
      @endif

      <div class="mb-5 rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700">
        Kode siswa saat ini: <span class="font-black">{{ $student->student_code ?? '-' }}</span>. Kode akan dibuat ulang jika kelas, cabang, atau tahun ajaran diubah.
      </div>

      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Nama Siswa</label>
          <input name="name" value="{{ old('name', $student->name) }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required autofocus>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Email Siswa</label>
          <input name="email" value="{{ old('email', $student->email) }}" type="email" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Kelas Program</label>
          <select name="student_class" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
            <option value="">Pilih kelas program</option>
            @foreach($studentClasses as $studentClass)
              <option value="{{ $studentClass }}" @selected(old('student_class', $student->student_class) === $studentClass)>{{ $studentClass }}</option>
            @endforeach
          </select>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Cabang</label>
            <select name="branch" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
              <option value="">Pilih cabang</option>
              @foreach($branches as $branch)
                <option value="{{ $branch }}" @selected(old('branch', $student->branch) === $branch)>{{ $branch }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Tahun Ajaran</label>
            <input name="academic_year" value="{{ old('academic_year', $student->academic_year ?? \App\Models\User::currentAcademicYear()) }}" placeholder="2026-2027" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
          </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Password Baru</label>
            <input name="password" type="password" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
            <p class="mt-2 text-xs font-semibold text-slate-400">Kosongkan jika tidak ingin mengganti password.</p>
          </div>
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">Konfirmasi Password Baru</label>
            <input name="password_confirmation" type="password" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
          </div>
        </div>

        <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700" type="submit">
          <i class="fa-solid fa-save"></i>
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
@endsection
