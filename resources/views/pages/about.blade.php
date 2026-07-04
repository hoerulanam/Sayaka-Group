@extends('layouts.app')

@section('title', 'Tentang Kami - SAYAKA GROUP')

@section('content')
<section class="about-page">
    <div class="about-page-wrap">
        <div class="about-page-hero">
            <div class="about-hero-copy">
                <span class="about-eyebrow">Tentang SAYAKA GROUP</span>
                <h1>Menghadirkan brand kebutuhan harian yang mudah dijangkau customer.</h1>
                <p>
                    SAYAKA GROUP berada di bawah PT Kreasi Produk Nusantara, perusahaan multi brand yang fokus menghadirkan produk berkualitas untuk kebutuhan parfum, shoescare, homecare, dan autocare.
                </p>

                <div class="about-hero-actions">
                    <a href="{{ route('brands.index') }}" class="about-primary-link">Lihat Brand</a>
                    <a href="{{ route('contact') }}" class="about-secondary-link">Hubungi Kami</a>
                </div>
            </div>

            <div class="about-hero-media">
                <img src="{{ asset('images/tim_kami.png') }}" alt="Tim SAYAKA GROUP" loading="lazy" decoding="async">
                <div class="about-media-note">
                    <strong>PT Kreasi Produk Nusantara</strong>
                    <span>Berdiri sejak 2021</span>
                </div>
            </div>
        </div>

        <div class="about-stats-grid" aria-label="Ringkasan perusahaan">
            <div>
                <strong>2021</strong>
                <span>Tahun berdiri</span>
            </div>
            <div>
                <strong>4</strong>
                <span>Kategori brand</span>
            </div>
            <div>
                <strong>Online</strong>
                <span>Marketplace dan digital store</span>
            </div>
        </div>

        <div class="about-story-grid">
            <div class="about-story-copy">
                <span class="about-eyebrow">Profil Perusahaan</span>
                <h2>PT Kreasi Produk Nusantara</h2>
                <p>
                    PT Kreasi Produk Nusantara merupakan perusahaan yang bergerak di bidang penjualan produk melalui platform marketplace. Didirikan oleh Handri Handa Yani, perusahaan ini hadir sebagai solusi atas meningkatnya kebutuhan masyarakat terhadap produk yang mudah diakses secara daring.
                </p>
                <p>
                    Kami membangun SAYAKA GROUP sebagai rumah untuk berbagai brand yang relevan dengan kebutuhan customer modern, dengan perhatian pada kualitas produk, inovasi, dan pengalaman belanja yang praktis.
                </p>
            </div>

            <aside class="about-focus-panel">
                <span>Fokus Kami</span>
                <ul>
                    <li>Parfum dengan karakter aroma yang mudah dipakai.</li>
                    <li>Shoescare untuk perawatan sepatu harian.</li>
                    <li>Homecare untuk menjaga kenyamanan rumah.</li>
                    <li>Autocare untuk kebutuhan perawatan kendaraan.</li>
                </ul>
            </aside>
        </div>

        <div class="about-vm-grid">
            <article class="about-vm-card">
                <span>Visi</span>
                <h2>Menjadi perusahaan multibrand terdepan.</h2>
                <p>
                    Menghadirkan dan menciptakan produk inovatif, berkualitas, dan terpercaya, serta menyediakan pengalaman belanja online dan offline yang terpersonalisasi bagi setiap pelanggan di Indonesia dan mancanegara.
                </p>
            </article>

            <article class="about-vm-card">
                <span>Misi</span>
                <h2>Membuat belanja kebutuhan harian terasa lebih mudah.</h2>
                <p>
                    Menghadirkan produk dari brand terpercaya, memberikan pengalaman belanja yang nyaman, cepat, dan aman, terus berinovasi mengikuti tren, serta membangun hubungan jangka panjang melalui pelayanan dan after-sales support yang prima.
                </p>
            </article>
        </div>

        <div class="about-values-section">
            <div class="about-section-heading">
                <span class="about-eyebrow">Nilai Utama</span>
                <h2>Yang kami jaga dalam setiap produk.</h2>
            </div>

            <div class="about-values-grid">
                <div class="about-value-card">
                    <strong>01</strong>
                    <h3>Kualitas Produk</h3>
                    <p>Setiap brand dikembangkan untuk menjawab kebutuhan customer dengan standar kualitas yang konsisten.</p>
                </div>
                <div class="about-value-card">
                    <strong>02</strong>
                    <h3>Inovasi Brand</h3>
                    <p>Kami mengikuti perubahan tren agar produk tetap relevan, fungsional, dan mudah digunakan.</p>
                </div>
                <div class="about-value-card">
                    <strong>03</strong>
                    <h3>Belanja Praktis</h3>
                    <p>Customer dapat menemukan produk melalui kanal digital dengan proses yang jelas dan mudah dipahami.</p>
                </div>
                <div class="about-value-card">
                    <strong>04</strong>
                    <h3>Layanan Terpercaya</h3>
                    <p>Kami membangun hubungan jangka panjang melalui pelayanan responsif dan dukungan setelah pembelian.</p>
                </div>
            </div>
        </div>

        <div class="about-cta">
            <div>
                <span class="about-eyebrow">Mulai Kenali Kami</span>
                <h2>Temukan brand SAYAKA GROUP yang sesuai kebutuhanmu.</h2>
            </div>
            <a href="{{ route('brands.index') }}" class="about-primary-link">Jelajahi Brand</a>
        </div>
    </div>
</section>
@endsection
