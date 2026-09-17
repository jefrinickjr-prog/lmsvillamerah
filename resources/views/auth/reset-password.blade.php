@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi')

@section('content')
<div class="mx-auto flex min-h-screen max-w-md items-center justify-center px-4 py-12">
    <div class="w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                <i class="fa-solid fa-shield-heart text-2xl"></i>
            </div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-500">Keamanan akun</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900">Atur ulang kata sandi</h1>
            <p class="mt-2 text-sm text-slate-500">Buat kata sandi baru yang kuat untuk akun Anda.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ old('email', $email) }}">

            <div>
                <label for="password" class="mb-2 block text-sm font-bold text-slate-700">Kata sandi baru</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="block w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    placeholder="Minimal 8 karakter"
                >
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-bold text-slate-700">Konfirmasi kata sandi</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    class="block w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    placeholder="Ulangi kata sandi baru"
                >
            </div>

            <button type="submit" class="btn-action btn-primary-solid w-full rounded-2xl px-5 py-3">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan kata sandi baru
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500">
            Sudah ingat?
            <a href="{{ route('login') }}" class="font-bold text-emerald-600 hover:text-emerald-500">Kembali ke login</a>
        </div>
    </div>
</div>
@endsection
