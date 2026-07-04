@extends('layouts.app')

@section('title', 'Brands - SAYAKA GROUP')

@section('content')
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

<section class="brands-page">
    <div class="brands-page-wrap">
        <div class="brands-hero">
            <div class="brands-hero-copy">
                <span class="brands-eyebrow">Brands SAYAKA GROUP</span>
                <h1>Pilih brand sesuai kebutuhan harianmu.</h1>
                <p>
                    Temukan produk dari berbagai brand SAYAKA GROUP untuk parfum, perawatan sepatu, homecare, dan autocare dalam satu halaman yang mudah dijelajahi.
                </p>
            </div>

            <div class="brands-hero-stats" aria-label="Ringkasan brand dan produk">
                <div>
                    <strong>{{ $brands->count() }}</strong>
                    <span>Brand tersedia</span>
                </div>
                <div>
                    <strong>{{ $products->count() }}</strong>
                    <span>Produk katalog</span>
                </div>
            </div>
        </div>

        <div class="brands-category-strip" aria-label="Kategori produk SAYAKA GROUP">
            <span>Shoescare</span>
            <span>Parfum</span>
            <span>Homecare</span>
            <span>Autocare</span>
        </div>

        <div class="brands-section-heading">
            <div>
                <span class="brands-eyebrow">Daftar Brand</span>
                <h2>Brands unggulan kami</h2>
            </div>
            <p>Setiap brand memiliki karakter produk berbeda, sehingga customer bisa memilih lebih cepat sesuai kebutuhan.</p>
        </div>

        @if($brands->count())
            <div class="brands-list-grid">
                @foreach($brands as $brand)
                    @php
                        $brandProductsCount = $products->where('brand_id', $brand->id)->count();
                    @endphp

                    <article class="brands-list-card">
                        <a href="{{ route('brands.show', $brand->id) }}" class="brands-logo-box" title="{{ $brand->name }}">
                            @if($brand->logo)
                                <img src="{{ asset($brand->logo) }}" alt="{{ $brand->name }} logo" loading="lazy" decoding="async">
                            @else
                                <span>{{ \Illuminate\Support\Str::limit($brand->name, 2, '') }}</span>
                            @endif
                        </a>

                        <div class="brands-card-body">
                            <span class="brands-product-count">{{ $brandProductsCount }} produk</span>
                            <h3>{{ $brand->name }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit($brand->description ?: 'Temukan pilihan produk terbaik dari brand ini.', 120) }}</p>
                            <a href="{{ route('brands.show', $brand->id) }}" class="brands-card-link">Lihat Produk</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="brands-empty">
                <span>Belum ada brand</span>
                <h2>Brand akan segera ditampilkan.</h2>
                <p>Silakan cek kembali halaman ini untuk melihat brand terbaru dari SAYAKA GROUP.</p>
            </div>
        @endif

        <div class="brands-section-heading brands-catalog-heading">
            <div>
                <span class="brands-eyebrow">Katalog Produk</span>
                <h2>Produk dari semua brand</h2>
            </div>
            <p>Lihat pilihan produk terbaru dari seluruh brand SAYAKA GROUP.</p>
        </div>

        @if($products->count())
            <div class="brands-product-grid">
                @foreach($products as $product)
                    <article class="brands-product-card">
                        <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="brands-product-image product-card-image-link" aria-label="Lihat detail {{ $product->name }}">
                            @if($product->thumbnail)
                                <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                            @else
                                <div class="brands-product-placeholder">No Image</div>
                            @endif
                        </a>

                        <div class="brands-product-body">
                            <span class="brands-product-brand product-brand-label">{{ $product->brand->name ?? 'Sayaka' }}</span>
                            <h3><a href="{{ route('products.show', ['product' => $product->slug]) }}" class="product-title-link">{{ $product->name }}</a></h3>
                            @include('components.product-rating-summary', ['product' => $product])
                            @if($product->size)
                                <div class="brands-product-meta product-size-meta">
                                    <span>Ukuran</span>
                                    <strong>{{ $product->size }}</strong>
                                </div>
                            @endif
                            <div class="brands-product-price">{{ $product->hasVariants() ? 'Mulai ' : '' }}Rp {{ number_format($product->startingPrice(), 0, ',', '.') }}</div>
                            <div class="brands-product-meta product-stock-meta">
                                <span>Stok tersedia</span>
                                <strong>{{ $product->availableStock() }}</strong>
                            </div>
                            @include('components.product-purchase-actions', ['product' => $product])
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="brands-empty">
                <span>Belum ada produk</span>
                <h2>Katalog produk akan segera hadir.</h2>
                <p>Produk dari brand SAYAKA GROUP akan tampil di bagian ini setelah tersedia.</p>
            </div>
        @endif
    </div>
</section>
@endsection
