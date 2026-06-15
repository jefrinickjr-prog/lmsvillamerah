@extends('layouts.app')

@section('title', 'Profil')

@section('content')
  @php
    $roleLabel = [
      'admin' => 'Administrator',
      'super_admin' => 'Super Admin',
      'teacher' => 'Pengajar',
      'student' => 'Siswa',
    ][$user->role ?? 'student'] ?? 'Siswa';
    $photoUrl = $user->photo_path ? asset('storage/'.$user->photo_path) : null;
  @endphp

  <div class="mb-6">
    <div class="text-sm font-bold uppercase tracking-wider text-indigo-500">Akun Saya</div>
    <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Profil</h2>
    <p class="mt-2 max-w-2xl text-sm font-medium text-slate-500">Kelola nama, foto profil, dan password akun Anda.</p>
  </div>

  <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
    <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-4">
        @if($photoUrl)
          <img src="{{ $photoUrl }}" alt="Foto {{ $user->name }}" class="h-24 w-24 rounded-3xl object-cover ring-4 ring-indigo-50">
        @else
          <div class="grid h-24 w-24 place-items-center rounded-3xl bg-gradient-to-br from-indigo-500 to-sky-400 text-3xl font-black text-white ring-4 ring-indigo-50">
            {{ strtoupper(substr($user->name, 0, 1)) }}
          </div>
        @endif
        <div class="min-w-0">
          <h3 class="truncate text-xl font-black text-slate-950">{{ $user->name }}</h3>
          <div class="mt-1 text-sm font-semibold text-slate-500">{{ $user->email }}</div>
          <div class="mt-3 inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-indigo-600">{{ $roleLabel }}</div>
        </div>
      </div>

      @if(($user->role ?? null) === 'student' && $user->student_class)
        <div class="mt-6 grid gap-3 rounded-2xl bg-slate-50 p-4">
          <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kelas dan Cabang</div>
            <div class="mt-1 font-extrabold text-slate-900">{{ $user->student_class }} - {{ $user->branch ?? 'Cabang belum diisi' }}</div>
          </div>
          <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Tahun Ajaran</div>
            <div class="mt-1 font-extrabold text-slate-900">{{ $user->academic_year ?? '-' }}</div>
          </div>
          <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kode Siswa</div>
            <div class="mt-1 font-extrabold text-indigo-700">{{ $user->student_code ?? '-' }}</div>
          </div>
        </div>
      @endif
    </section>

    <div class="space-y-6">
      <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-black text-slate-950">Ubah Profil</h3>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-5 space-y-5">
          @csrf
          @method('PUT')

          <div>
            <label for="name" class="text-sm font-bold text-slate-700">Nama</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
            @error('name')
              <div class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</div>
            @enderror
          </div>

          <div>
            <label for="photo" class="text-sm font-bold text-slate-700">Foto Profil</label>
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
            <p class="mt-2 text-xs font-semibold text-slate-400">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
            @error('photo')
              <div class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</div>
            @enderror
          </div>

          <button class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700">
            <i class="fa-solid fa-floppy-disk"></i>
            Simpan Profil
          </button>
        </form>
      </section>

      <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-black text-slate-950">Ubah Password</h3>
        <form method="POST" action="{{ route('profile.password.update') }}" class="mt-5 space-y-5">
          @csrf
          @method('PUT')

          <div>
            <label for="current_password" class="text-sm font-bold text-slate-700">Password Saat Ini</label>
            <input id="current_password" name="current_password" type="password" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
            @error('current_password')
              <div class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</div>
            @enderror
          </div>

          <div class="grid gap-5 md:grid-cols-2">
            <div>
              <label for="password" class="text-sm font-bold text-slate-700">Password Baru</label>
              <input id="password" name="password" type="password" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
              @error('password')
                <div class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</div>
              @enderror
            </div>
            <div>
              <label for="password_confirmation" class="text-sm font-bold text-slate-700">Konfirmasi Password Baru</label>
              <input id="password_confirmation" name="password_confirmation" type="password" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
            </div>
          </div>

          <button class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-200 hover:bg-slate-800">
            <i class="fa-solid fa-key"></i>
            Simpan Password
          </button>
        </form>
      </section>
    </div>
  </div>
@endsection
