<section class="customer-profile-form-card rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-900/5 sm:p-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-extrabold uppercase tracking-normal text-blue-700">
                Detail Akun
            </span>
            <h2 class="mt-4 text-2xl font-extrabold leading-tight text-slate-950">Informasi Profil</h2>
            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-500">
                Perbarui nama, username, email, dan nomor handphone untuk checkout dan komunikasi pesanan.
            </p>
        </div>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-7 grid gap-5">
        @csrf
        @method('patch')

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-2 block text-sm font-extrabold text-slate-700">Nama Lengkap</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user->name) }}"
                    required
                    autofocus
                    autocomplete="name"
                    class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                    placeholder="Nama lengkap"
                >
                <x-input-error class="mt-2 font-semibold" :messages="$errors->get('name')" />
            </div>

            <div>
                <label for="username" class="mb-2 block text-sm font-extrabold text-slate-700">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username', $user->username) }}"
                    autocomplete="username"
                    class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                    placeholder="Username akun"
                >
                <x-input-error class="mt-2 font-semibold" :messages="$errors->get('username')" />
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="email" class="mb-2 block text-sm font-extrabold text-slate-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    autocomplete="username"
                    class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                    placeholder="nama@email.com"
                >
                <x-input-error class="mt-2 font-semibold" :messages="$errors->get('email')" />
            </div>

            <div>
                <label for="phone" class="mb-2 block text-sm font-extrabold text-slate-700">No. Handphone</label>
                <input
                    id="phone"
                    name="phone"
                    type="text"
                    value="{{ old('phone', $user->phone) }}"
                    autocomplete="tel"
                    class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                    placeholder="08xxxxxxxxxx"
                >
                <x-input-error class="mt-2 font-semibold" :messages="$errors->get('phone')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm font-semibold leading-6 text-slate-700">
                Email kamu belum terverifikasi.
                <button form="send-verification" class="font-extrabold text-blue-700 underline underline-offset-4 hover:text-blue-900">
                    Kirim ulang link verifikasi
                </button>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-extrabold text-emerald-700">
                        Link verifikasi baru sudah dikirim ke email kamu.
                    </p>
                @endif
            </div>
        @endif

        <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center">
            <button type="submit" class="inline-flex min-h-[48px] items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-px hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-700/20">
                Simpan Perubahan
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2200)"
                    class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-extrabold text-emerald-700"
                >Profil berhasil disimpan.</p>
            @endif
        </div>
    </form>
</section>
