<x-guest-layout wide>
    <main class="min-h-screen bg-slate-50">
        <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
            <section class="relative hidden overflow-hidden bg-slate-950 text-white lg:block">
                <img
                    src="{{ asset('images/hero/ssc_banner.jpg') }}"
                    alt="Produk SAYAKA GROUP"
                    class="absolute inset-0 h-full w-full object-cover opacity-55"
                >
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-950/80 to-blue-950/40"></div>

                <div class="relative z-10 flex min-h-screen flex-col justify-between p-12">
                    <a href="{{ route('home') }}" class="inline-flex w-fit items-center gap-3 text-white no-underline">
                        <img src="{{ asset('images/logo_sayaka.jpg') }}" alt="SAYAKA GROUP" class="h-12 w-12 rounded-xl object-cover">
                        <span>
                            <strong class="block text-base font-extrabold leading-tight">SAYAKA GROUP</strong>
                            <small class="text-sm font-semibold text-slate-300">Shop care products by brand</small>
                        </span>
                    </a>

                    <div class="max-w-xl">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-normal text-yellow-300">
                            Belanja produk pilihan
                        </span>
                        <h1 class="mt-6 text-5xl font-extrabold leading-tight text-white">
                            Masuk dan lanjutkan pesananmu dengan lebih cepat.
                        </h1>
                        <p class="mt-5 text-base font-medium leading-8 text-slate-200">
                            Akses keranjang, checkout, riwayat pesanan, dan chat admin dalam satu akun SAYAKA GROUP.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div class="rounded-xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <strong class="block text-2xl font-extrabold">4</strong>
                            <span class="mt-1 block text-slate-300">Brand utama</span>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <strong class="block text-2xl font-extrabold">24/7</strong>
                            <span class="mt-1 block text-slate-300">Akses order</span>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <strong class="block text-2xl font-extrabold">COD</strong>
                            <span class="mt-1 block text-slate-300">Checkout mudah</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div class="w-full max-w-[480px]">
                    <div class="mb-8 flex items-center justify-between gap-4 lg:hidden">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 no-underline">
                            <img src="{{ asset('images/logo_sayaka.jpg') }}" alt="SAYAKA GROUP" class="h-11 w-11 rounded-xl object-cover shadow-sm">
                            <span>
                                <strong class="block text-sm font-extrabold text-slate-950">SAYAKA GROUP</strong>
                                <small class="text-xs font-bold text-slate-500">E-commerce</small>
                            </span>
                        </a>
                        <a href="{{ route('home') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-extrabold text-slate-700 no-underline transition hover:border-slate-950 hover:text-slate-950">
                            Beranda
                        </a>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/10 sm:p-8">
                        <div class="mb-8">
                            <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-extrabold uppercase tracking-normal text-blue-700">
                                Login pelanggan
                            </span>
                            <h2 class="mt-4 text-3xl font-extrabold leading-tight text-slate-950">Selamat datang kembali</h2>
                            <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                                Masuk untuk melihat keranjang, status pembayaran, dan pesanan terbaru.
                            </p>
                        </div>

                        <x-auth-session-status class="mb-5" :status="session('status')" />

                        <form id="login-form" method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            <div>
                                <label for="email" class="mb-2 block text-sm font-extrabold text-slate-700">Email atau username</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                        <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"></path>
                                        </svg>
                                    </span>
                                    <input
                                        id="email"
                                        name="email"
                                        type="text"
                                        value="{{ old('email') }}"
                                        autocomplete="username"
                                        required
                                        class="min-h-[52px] w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-12 pr-4 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                                        placeholder="admin@sayaka.com atau admin"
                                    >
                                </div>
                                @error('email') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-sm font-extrabold text-slate-700">Password</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                        <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-.75 0h10.5a1.5 1.5 0 011.5 1.5v6.75a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V12a1.5 1.5 0 011.5-1.5z"></path>
                                        </svg>
                                    </span>
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                        class="min-h-[52px] w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-12 pr-4 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                                        placeholder="Masukkan password"
                                    >
                                </div>
                                @error('password') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label for="remember_me" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600">
                                    <input id="remember_me" name="remember" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-700">
                                    Ingat saya
                                </label>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-sm font-extrabold text-blue-700 no-underline hover:text-blue-900">
                                        Lupa password?
                                    </a>
                                @endif
                            </div>

                            <button type="submit" class="inline-flex min-h-[52px] w-full items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-extrabold text-white shadow-xl shadow-slate-900/20 transition hover:-translate-y-px hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-700/20">
                                Masuk Sekarang
                            </button>
                        </form>

                        <div class="mt-7 rounded-xl bg-slate-50 px-4 py-4 text-center text-sm font-semibold text-slate-600">
                            Belum punya akun?
                            <a href="{{ route('register') }}" class="font-extrabold text-blue-700 no-underline hover:text-blue-900">Daftar untuk belanja</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</x-guest-layout>
