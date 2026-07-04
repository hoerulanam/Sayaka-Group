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
                    <h1>Reset Password</h1>
                    <p>Masukkan email akun Anda. Kami akan mengirimkan link reset password agar Anda bisa membuat password baru.</p>
                </div>

                <x-auth-session-status class="auth-reset-status" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="auth-reset-form">
                    @csrf

                    <div class="auth-reset-field">
                        <label for="email">Email</label>
                        <div class="auth-reset-input-wrap">
                            <span>@</span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@email.com">
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="auth-reset-error" />
                    </div>

                    <button type="submit" class="auth-reset-submit">
                        Kirim Link Reset Password
                    </button>
                </form>
            </div>
        </div>
    </section>
</x-guest-layout>
