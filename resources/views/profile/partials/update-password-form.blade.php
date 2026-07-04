<section class="customer-password-form-card rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-900/5 sm:p-8">
    <div>
        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-extrabold uppercase tracking-normal text-slate-700">
            Keamanan
        </span>
        <h2 class="mt-4 text-2xl font-extrabold leading-tight text-slate-950">Update Password</h2>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-500">
            Gunakan password yang kuat agar akun, riwayat pesanan, dan data checkout tetap aman.
        </p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="mt-7 grid gap-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="mb-2 block text-sm font-extrabold text-slate-700">Password Saat Ini</label>
            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                autocomplete="current-password"
                class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                placeholder="Masukkan password lama"
            >
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2 font-semibold" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="update_password_password" class="mb-2 block text-sm font-extrabold text-slate-700">Password Baru</label>
                <input
                    id="update_password_password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                    placeholder="Minimal 8 karakter"
                >
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2 font-semibold" />
            </div>

            <div>
                <label for="update_password_password_confirmation" class="mb-2 block text-sm font-extrabold text-slate-700">Konfirmasi Password</label>
                <input
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-blue-700 focus:bg-white focus:ring-4 focus:ring-blue-700/10"
                    placeholder="Ulangi password baru"
                >
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2 font-semibold" />
            </div>
        </div>

        <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center">
            <button type="submit" class="inline-flex min-h-[48px] items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-px hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-700/20">
                Update Password
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2200)"
                    class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-extrabold text-emerald-700"
                >Password berhasil diperbarui.</p>
            @endif
        </div>
    </form>
</section>
