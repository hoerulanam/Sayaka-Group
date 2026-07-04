<section class="customer-delete-card rounded-2xl border border-red-200 bg-white p-6 shadow-lg shadow-red-900/5">
    <div>
        <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-extrabold uppercase tracking-normal text-red-700">
            Zona Berbahaya
        </span>
        <h2 class="mt-4 text-xl font-extrabold text-slate-950">Hapus Akun</h2>
        <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
            Setelah akun dihapus, data akun tidak bisa dipulihkan. Pastikan tidak ada pesanan aktif sebelum melanjutkan.
        </p>
    </div>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="mt-5 inline-flex min-h-[46px] w-full items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-red-600/15 transition hover:-translate-y-px hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-600/20"
    >
        Hapus Akun
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8">
            @csrf
            @method('delete')

            <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-extrabold uppercase tracking-normal text-red-700">
                Konfirmasi
            </span>
            <h2 class="mt-4 text-2xl font-extrabold leading-tight text-slate-950">
                Yakin ingin menghapus akun?
            </h2>

            <p class="mt-3 text-sm font-medium leading-6 text-slate-500">
                Masukkan password untuk mengonfirmasi penghapusan akun secara permanen.
            </p>

            <div class="mt-6">
                <label for="password" class="mb-2 block text-sm font-extrabold text-slate-700">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-red-600 focus:bg-white focus:ring-4 focus:ring-red-600/10"
                    placeholder="Masukkan password"
                >

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2 font-semibold" />
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="inline-flex min-h-[46px] items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-300/40"
                >
                    Batal
                </button>

                <button type="submit" class="inline-flex min-h-[46px] items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-red-600/15 transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-600/20">
                    Hapus Permanen
                </button>
            </div>
        </form>
    </x-modal>
</section>
