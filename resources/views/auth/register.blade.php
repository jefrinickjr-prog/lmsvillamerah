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
          <label class="mb-2 block text-sm font-bold text-slate-700">Grup Program</label>
          <select name="program_type" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
            @foreach($programTypes as $value => $label)
              <option value="{{ $value }}" @selected(old('program_type', 'gambar') === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Kelas Program</label>
          <select name="student_class" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100" required>
            <option value="">Pilih kelas program</option>
            @foreach($studentClassesByProgram as $programType => $classes)
              <optgroup label="{{ $programTypes[$programType] ?? ucfirst($programType) }}">
                @foreach($classes as $studentClass)
                  <option value="{{ $studentClass }}" @selected(old('student_class') === $studentClass)>{{ $studentClass }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>

        @php
          $selectedVideoAccesses = old('video_accesses', ['gambar']);
          if (! is_array($selectedVideoAccesses)) {
            $selectedVideoAccesses = [$selectedVideoAccesses];
          }
        @endphp
        <div>
          <label class="mb-2 block text-sm font-bold text-slate-700">Akses Video Pembelajaran</label>
          <div class="grid gap-3 sm:grid-cols-2">
            @foreach($videoAccessOptions as $value => $label)
              <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold text-slate-700">
                <input type="checkbox" name="video_accesses[]" value="{{ $value }}" class="video-access-checkbox h-5 w-5 rounded border-slate-300 text-indigo-600" @checked(in_array($value, $selectedVideoAccesses, true))>
                <span>{{ $label }}</span>
              </label>
            @endforeach
          </div>
          <p class="mt-2 text-xs font-semibold text-slate-400">Centang lebih dari satu jika siswa boleh menonton video gambar dan skolastik.</p>
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

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const programSelect = document.querySelector('select[name="program_type"]');
      const classSelect = document.querySelector('select[name="student_class"]');
      const accessCheckboxes = document.querySelectorAll('.video-access-checkbox');

      const syncVideoAccesses = () => {
        const className = (classSelect?.value || '').trim().toLowerCase();
        const programType = programSelect?.value || 'gambar';
        const allowed = className === 'sr gold' ? ['gambar', 'skolastik'] : [programType];

        accessCheckboxes.forEach((checkbox) => {
          checkbox.checked = allowed.includes(checkbox.value);
        });
      };

      programSelect?.addEventListener('change', syncVideoAccesses);
      classSelect?.addEventListener('change', syncVideoAccesses);
    });
  </script>
@endsection
