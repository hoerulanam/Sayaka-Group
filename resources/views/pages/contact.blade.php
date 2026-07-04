@extends('layouts.app')
@section('title','Kontak Kami - SAYAKA GROUP')

@section('content')
<section class="contact-modern">
    <div class="contact-modern-wrap">
        <div class="contact-intro">
            <span class="contact-eyebrow">Customer Care</span>
            <h1>Kontak Kami</h1>
            <p>
                Ada pertanyaan tentang produk, stok, kerja sama, atau pesanan? Kirim pesan melalui formulir ini dan tim SAYAKA GROUP akan membantu Anda.
            </p>

            <div class="contact-info-grid" aria-label="Informasi kontak SAYAKA GROUP">
                <div class="contact-info-card">
                    <span class="contact-info-mark">CS</span>
                    <div>
                        <strong>Bantuan Customer</strong>
                        <p>Pertanyaan produk, pesanan, dan kendala belanja.</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <span class="contact-info-mark">HQ</span>
                    <div>
                        <strong>Alamat Kantor</strong>
                        <p>Jl. Betung Raya No.270, Pd. Bambu, Duren Sawit, Jakarta Timur.</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <span class="contact-info-mark">24</span>
                    <div>
                        <strong>Estimasi Respons</strong>
                        <p>Pesan akan ditinjau secepatnya pada hari kerja.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-form-panel">
            <div class="contact-form-header">
                <h2>Kirim Pesan</h2>
                <p>Isi data berikut agar kami bisa menghubungi Anda kembali.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form class="contact-form" method="post" action="{{ route('contact.send') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <input id="name" name="name" type="text" placeholder="Nama Anda" required value="{{ old('name') }}">
                        @error('name') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" placeholder="email@domain.com" required value="{{ old('email') }}">
                        @error('email') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group full-width">
                        <label for="message">Pesan</label>
                        <textarea id="message" name="message" rows="6" placeholder="Tuliskan pertanyaan atau kebutuhan Anda..." required>{{ old('message') }}</textarea>
                        @error('message') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary contact-submit">Kirim Pesan</button>
            </form>
        </div>
    </div>
</section>
@endsection
