<x-guest-layout wide>
    <main class="min-h-screen bg-slate-50">
        <div class="grid min-h-screen lg:grid-cols-[0.95fr_1.05fr]">
            <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div class="w-full max-w-[520px]">
                    <div class="mb-8 flex items-center justify-between gap-4">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 no-underline">
                            <img src="{{ asset('images/logo_sayaka.jpg') }}" alt="SAYAKA GROUP" class="h-11 w-11 rounded-xl object-cover shadow-sm">
                            <span>
                                <strong class="block text-sm font-extrabold text-slate-950">SAYAKA GROUP</strong>
                                <small class="text-xs font-bold text-slate-500">Member Store</small>
                            </span>
                        </a>
                        <a href="{{ route('home') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-extrabold text-slate-700 no-underline transition hover:border-slate-950 hover:text-slate-950">
                            Beranda
                        </a>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/10 sm:p-8">
                        <div class="mb-8">
                            <span class="inline-flex rounded-full bg-yellow-100 px-3 py-1 text-xs font-extrabold uppercase tracking-normal text-slate-950">
                                Buat akun belanja
                            </span>
                            <h2 class="mt-4 text-3xl font-extrabold leading-tight text-slate-950">Gabung dan mulai checkout lebih cepat</h2>
                            <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                                Simpan detail akunmu untuk keranjang, pesanan, pembayaran, dan chat admin.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('register') }}" class="space-y-5">
                            @csrf

                            <div>
                                <label for="name" class="mb-2 block text-sm font-extrabold text-slate-700">Nama lengkap</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                        <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"></path>
                                        </svg>
                                    </span>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        value="{{ old('name') }}"
                                        autocomplete="name"
                                        required
                                        autofocus
                                        class="min-h-[52px] w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-12 pr-4 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                                        placeholder="Nama sesuai profil"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('name')" class="mt-2 font-semibold" />
                            </div>

                            <div>
                                <label for="email" class="mb-2 block text-sm font-extrabold text-slate-700">Email</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                        <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 7.5v9a2.25 2.25 0 01-2.25 2.25h-15A2.25 2.25 0 012.25 16.5v-9m19.5 0A2.25 2.25 0 0019.5 5.25h-15A2.25 2.25 0 002.25 7.5m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0l-7.5-4.615A2.25 2.25 0 012.25 7.743V7.5"></path>
                                        </svg>
                                    </span>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email') }}"
                                        autocomplete="username"
                                        required
                                        class="min-h-[52px] w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-12 pr-4 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                                        placeholder="nama@email.com"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2 font-semibold" />
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
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
                                            autocomplete="new-password"
                                            required
                                            class="min-h-[52px] w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-12 pr-4 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                                            placeholder="Minimal 8 karakter"
                                        >
                                    </div>
                                    <x-input-error :messages="$errors->get('password')" class="mt-2 font-semibold" />
                                </div>

                                <div>
                                    <label for="password_confirmation" class="mb-2 block text-sm font-extrabold text-slate-700">Konfirmasi</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                            <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </span>
                                        <input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            autocomplete="new-password"
                                            required
                                            class="min-h-[52px] w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-12 pr-4 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                                            placeholder="Ulangi password"
                                        >
                                    </div>
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 font-semibold" />
                                </div>
                            </div>

                            <button type="submit" class="inline-flex min-h-[52px] w-full items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-extrabold text-white shadow-xl shadow-slate-900/20 transition hover:-translate-y-px hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-700/20">
                                Daftar Sekarang
                            </button>
                        </form>

                        <div class="mt-7 rounded-xl bg-slate-50 px-4 py-4 text-center text-sm font-semibold text-slate-600">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="font-extrabold text-blue-700 no-underline hover:text-blue-900">Masuk di sini</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="relative hidden overflow-hidden bg-slate-950 text-white lg:block">
                <img
                    src="{{ asset('images/Logo-merk.png') }}"
                    alt="Brand SAYAKA GROUP"
                    class="absolute inset-0 h-full w-full object-cover opacity-45"
                >
                <div class="absolute inset-0 bg-gradient-to-br from-blue-950/85 via-slate-950/90 to-slate-950"></div>

                <div class="relative z-10 flex min-h-screen flex-col justify-between p-12">
                    <div class="max-w-xl">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-normal text-yellow-300">
                            Member benefit
                        </span>
                        <h1 class="mt-6 text-5xl font-extrabold leading-tight text-white">
                            Satu akun untuk semua kebutuhan belanja SAYAKA.
                        </h1>
                        <p class="mt-5 text-base font-medium leading-8 text-slate-200">
                            Jelajahi produk shoes care, fragrance, home care, dan auto care dengan pengalaman checkout yang lebih praktis.
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="rounded-xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                            <strong class="block text-lg font-extrabold">Keranjang tersimpan</strong>
                            <span class="mt-2 block text-sm leading-6 text-slate-300">Tambahkan produk favorit dan lanjutkan belanja kapan saja.</span>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                            <strong class="block text-lg font-extrabold">Riwayat pesanan</strong>
                            <span class="mt-2 block text-sm leading-6 text-slate-300">Pantau status pembayaran, pengiriman, dan pesanan selesai.</span>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                            <strong class="block text-lg font-extrabold">Chat admin</strong>
                            <span class="mt-2 block text-sm leading-6 text-slate-300">Tanyakan stok, rekomendasi, atau bantuan checkout langsung dari akunmu.</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</x-guest-layout>
