<x-guest-layout wide>
    <section class="auth-reset-page">
        <div class="auth-reset-shell">
            <a href="{{ route('home') }}" class="auth-reset-brand" aria-label="SAYAKA GROUP">
                <img src="{{ asset('images/logo_sayaka.jpg') }}" alt="SAYAKA GROUP" decoding="async">
                <span>
                    <strong>SAYAKA GROUP</strong>
                    <small>Official multi brand store</small>
                </span>
            </a>

            <div class="auth-reset-card">
                <div class="auth-reset-heading">
                    <span>Keamanan Akun</span>
                    <h1>Buat Password Baru</h1>
                    <p>Masukkan email dan password baru untuk mengamankan kembali akun Anda.</p>
                </div>

                <form method="POST" action="{{ route('password.store') }}" class="auth-reset-form">
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="auth-reset-field">
                        <label for="email">Email</label>
                        <div class="auth-reset-input-wrap">
                            <span>@</span>
                            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" placeholder="nama@email.com">
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="auth-reset-error" />
                    </div>

                    <div class="auth-reset-field">
                        <label for="password">Password Baru</label>
                        <div class="auth-reset-input-wrap">
                            <span aria-hidden="true">#</span>
                            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter">
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="auth-reset-error" />
                    </div>

                    <div class="auth-reset-field">
                        <label for="password_confirmation">Konfirmasi Password Baru</label>
                        <div class="auth-reset-input-wrap">
                            <span aria-hidden="true">#</span>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi password baru">
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="auth-reset-error" />
                    </div>

                    <button type="submit" class="auth-reset-submit">
                        Simpan Password Baru
                    </button>
                </form>
            </div>
        </div>
    </section>
</x-guest-layout>
