@extends('layouts.app')

@section('title', 'Beranda - SAYAKA GROUP')

@section('content')
@php
    $slides = [
        [
            'image' => 'images/hero/noid_banner.jpg',
            'brand' => 'NOID',
            'title' => 'Home care praktis untuk rutinitas harian.',
            'text' => 'Produk pembersih dan perawatan rumah yang mudah dipakai, aman, dan efisien.',
        ],
        [
            'image' => 'images/hero/ssc_banner.jpg',
            'brand' => 'SummerShoesCare',
            'title' => 'Rawat sepatu favorit dengan standar profesional.',
            'text' => 'Shoes care, repair, dan perlengkapan perawatan untuk aktivitas setiap hari.',
        ],
        [
            'image' => 'images/hero/summerlabs_banner.jpg',
            'brand' => 'SummerLabs',
            'title' => 'Fragrance modern untuk karakter yang lebih percaya diri.',
            'text' => 'Pilihan aroma lokal dengan nuansa premium dan harga yang tetap masuk akal.',
        ],
    ];

    $categories = [
        ['label' => 'Shoescare', 'code' => 'SC', 'route' => route('brands.index', ['category' => 'shoescare'])],
        ['label' => 'Parfum', 'code' => 'PF', 'route' => route('brands.index', ['category' => 'parfum'])],
        ['label' => 'Homecare', 'code' => 'HC', 'route' => route('brands.index', ['category' => 'homecare'])],
        ['label' => 'Autocare', 'code' => 'AC', 'route' => route('brands.index', ['category' => 'autocare'])],
    ];
@endphp

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<section class="hero">
    <div class="hero-container">
        <div class="hero-main">
            <div class="hero-slider" id="heroSlider" data-index="0">
                <div class="slides">
                    @foreach($slides as $slide)
                        <div class="slide">
                            <img
                                src="{{ asset($slide['image']) }}"
                                alt="{{ $slide['brand'] }}"
                                decoding="async"
                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                fetchpriority="{{ $loop->first ? 'high' : 'auto' }}"
                            >
                            <div class="hero-slide-content">
                                <span>{{ $slide['brand'] }}</span>
                                <h1>{{ $slide['title'] }}</h1>
                                <p>{{ $slide['text'] }}</p>
                                <div class="hero-actions">
                                    <a href="{{ route('brands.index') }}" class="hero-primary-link">Belanja Produk</a>
                                    <a href="{{ route('about') }}" class="hero-secondary-link">Tentang Kami</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hero-slider-controls" aria-label="Kontrol banner">
                    <button type="button" class="hero-slider-button" data-hero-prev aria-label="Slide sebelumnya">&lt;</button>
                    <div class="hero-slider-dots" aria-hidden="true">
                        @foreach($slides as $slide)
                            <span class="{{ $loop->first ? 'active' : '' }}"></span>
                        @endforeach
                    </div>
                    <button type="button" class="hero-slider-button" data-hero-next aria-label="Slide berikutnya">&gt;</button>
                </div>
            </div>
        </div>

        <div class="hero-bottom">
            <a href="{{ route('brands.index') }}" class="hero-box hero-box-left">
                <span>Multi Brand Store</span>
                <strong>Jelajahi katalog dari 4 brand utama SAYAKA GROUP.</strong>
                <small>Shoescare, fragrance, home care, dan auto care dalam satu website.</small>
            </a>

            <a href="{{ route('contact') }}" class="hero-box hero-box-right">
                <span>Partnership</span>
                <strong>Butuh distribusi atau kerja sama produk?</strong>
                <small>Hubungi tim kami untuk kebutuhan retail, reseller, dan kolaborasi brand.</small>
            </a>
        </div>
    </div>
</section>

<section class="about-section">
    <div class="about-content">
        <span class="section-eyebrow">Official multi brand store</span>
        <h1>Hai, kami SAYAKA GROUP</h1>
        <p>
            SAYAKA GROUP menaungi berbagai brand berkualitas, mulai dari perawatan sepatu hingga kebutuhan gaya hidup modern.
            Kami menghadirkan produk inovatif yang membantu aktivitas harian dengan standar kualitas tinggi.
        </p>
    </div>

    <div class="category-nav">
        @foreach($categories as $category)
            <a href="{{ $category['route'] }}" class="cat-item">
                <span class="cat-icon">{{ $category['code'] }}</span>
                <span>{{ $category['label'] }}</span>
            </a>
        @endforeach
    </div>
</section>

<section class="featured-products-section">
    <div class="featured-products-container">
        <div class="section-heading">
            <span class="section-eyebrow">Pilihan pelanggan</span>
            <h2 class="section-title">Produk Terlaris di SAYAKA GROUP</h2>
            <p>Produk unggulan dari setiap brand untuk perawatan, gaya hidup, dan kebutuhan harian.</p>
        </div>

        <div class="product-grid">
            @forelse($featuredProducts as $product)
                <article class="product-card">
                    <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="product-image product-card-image-link" aria-label="Lihat detail {{ $product->name }}">
                        @if($product->thumbnail)
                            <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                        @else
                            <div class="image-placeholder">No Image</div>
                        @endif
                        <div class="product-badge">Terlaris</div>
                    </a>
                    <div class="product-info">
                        <span class="product-brand product-brand-label">{{ $product->brand->name ?? 'Sayaka' }}</span>
                        <h3 class="product-name"><a href="{{ route('products.show', ['product' => $product->slug]) }}" class="product-title-link">{{ $product->name }}</a></h3>
                        @include('components.product-rating-summary', ['product' => $product])
                        @if($product->size)
                            <div class="product-stock product-size-meta">Ukuran: {{ $product->size }}</div>
                        @endif
                        <div class="product-price">{{ $product->hasVariants() ? 'Mulai ' : '' }}Rp {{ number_format($product->startingPrice(), 0, ',', '.') }}</div>
                        <div class="product-stock product-stock-meta">Stok Tersedia: {{ $product->availableStock() }}</div>
                        @include('components.product-purchase-actions', ['product' => $product])
                    </div>
                </article>
            @empty
                <div class="home-empty-state">Belum ada produk unggulan.</div>
            @endforelse
        </div>

        <div class="view-all-container">
            <a href="{{ route('brands.index') }}" class="btn-more">Selengkapnya</a>
        </div>
    </div>
</section>

<section class="latest-news-home-section">
    <div class="news-home-container">
        <div class="section-heading">
            <span class="section-eyebrow">Update terbaru</span>
            <h2 class="section-title">Berita & Kegiatan Terbaru</h2>
            <p>Ikuti cerita produk, aktivitas brand, dan informasi terbaru dari SAYAKA GROUP.</p>
        </div>

        <div class="news-grid">
            @forelse($latestNews as $item)
                <a href="{{ route('news.show', $item->slug) }}" class="news-card">
                    <div class="news-img-wrap">
                        @if($item->thumbnail)
                            <img src="{{ asset($item->thumbnail) }}" alt="{{ $item->title }}" loading="lazy" decoding="async">
                        @else
                            <img src="https://placehold.co/600x400/e9edf5/374151?text=Berita" alt="{{ $item->title }}" loading="lazy" decoding="async">
                        @endif
                    </div>
                    <div class="news-body">
                        <span class="news-date">{{ \Carbon\Carbon::parse($item->published_at)->format('d M Y') }}</span>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->excerpt ?? Str::limit(strip_tags($item->content), 100) }}</p>
                        <span class="link">Baca Selengkapnya</span>
                    </div>
                </a>
            @empty
                <div class="home-empty-state">Belum ada berita terbaru.</div>
            @endforelse
        </div>

        <div class="view-all-container">
            <a href="{{ route('news.index') }}" class="btn-more">Lihat Semua Berita</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const slider = document.getElementById('heroSlider');
    const slidesWrap = slider.querySelector('.slides');
    const slides = slider.querySelectorAll('.slide');
    const dots = slider.querySelectorAll('.hero-slider-dots span');
    const prevButton = slider.querySelector('[data-hero-prev]');
    const nextButton = slider.querySelector('[data-hero-next]');
    let index = 0;
    const total = slides.length;

    const goto = (i) => {
        index = (i + total) % total;
        slidesWrap.style.transform = `translateX(-${index * 100}%)`;
        slider.dataset.index = index;
        dots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === index));
    };

    let autoplay = setInterval(() => goto(index + 1), 5000);
    const restartAutoplay = () => {
        clearInterval(autoplay);
        autoplay = setInterval(() => goto(index + 1), 5000);
    };

    prevButton.addEventListener('click', () => {
        goto(index - 1);
        restartAutoplay();
    });

    nextButton.addEventListener('click', () => {
        goto(index + 1);
        restartAutoplay();
    });

    dots.forEach((dot, dotIndex) => {
        dot.addEventListener('click', () => {
            goto(dotIndex);
            restartAutoplay();
        });
    });

    goto(0);

    let startX = 0;
    slidesWrap.addEventListener('touchstart', e => startX = e.touches[0].clientX);
    slidesWrap.addEventListener('touchend', e => {
        const dx = (e.changedTouches[0].clientX - startX);
        if (dx > 40) goto(index - 1);
        if (dx < -40) goto(index + 1);
        restartAutoplay();
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.featured-products-section, .latest-news-home-section').forEach(section => {
        observer.observe(section);
    });
});
</script>

@endsection
