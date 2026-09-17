@extends('layouts.app')

@section('title', 'Lupa Kata Sandi')

@section('content')
<div class="mx-auto flex min-h-screen max-w-md items-center justify-center px-4 py-12">
    <div class="w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                <i class="fa-solid fa-key text-2xl"></i>
            </div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-500">Akun Anda</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900">Lupa kata sandi?</h1>
            <p class="mt-2 text-sm text-slate-500">
                Masukkan email yang terdaftar. Kami akan mengirimkan link reset ke inbox Anda.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-bold text-slate-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="block w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    placeholder="nama@domain.com"
                >
            </div>

            <button type="submit" class="btn-action btn-primary-solid w-full rounded-2xl px-5 py-3">
                <i class="fa-solid fa-paper-plane"></i>
                Kirim link reset
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500">
            Ingin kembali?
            <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:text-indigo-500">Login sekarang</a>
        </div>
    </div>
</div>
@endsection
