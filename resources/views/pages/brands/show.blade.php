@extends('layouts.app')
@section('title', $brand->name . ' - Brands - SAYAKA GROUP')

@section('content')
<section class="brand-show-page">
    <div class="brand-show-wrap">
        <a href="{{ route('brands.index') }}" class="brand-back-link">Kembali ke Brands</a>

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

        <div class="brand-show-hero">
            <div class="brand-show-logo">
                @if($brand->logo)
                    <img src="{{ asset($brand->logo) }}" alt="{{ $brand->name }}" loading="lazy" decoding="async">
                @else
                    <span>{{ \Illuminate\Support\Str::limit($brand->name, 2, '') }}</span>
                @endif
            </div>

            <div class="brand-show-copy">
                <span class="brand-show-eyebrow">Brand SAYAKA GROUP</span>
                <h1>{{ $brand->name }}</h1>
                <p>{{ $brand->description ?: 'Temukan pilihan produk dari brand ini dan pilih sesuai kebutuhan Anda.' }}</p>

                <div class="brand-show-stats">
                    <span><strong>{{ $products->count() }}</strong> produk</span>
                    <span><strong>{{ $products->filter(fn ($product) => $product->availableStock() > 0)->count() }}</strong> tersedia</span>
                </div>

                @if(!empty($brand->marketplace_url))
                    <a href="{{ $brand->marketplace_url }}" target="_blank" rel="noopener" class="btn btn-primary">Kunjungi Marketplace</a>
                @endif
            </div>
        </div>

        <div class="brand-products-heading">
            <div>
                <span class="brand-show-eyebrow">Katalog Produk</span>
                <h2>Produk {{ $brand->name }}</h2>
            </div>
            <p>Pilih produk, cek harga dan stok, lalu lanjutkan ke keranjang atau checkout.</p>
        </div>

        <div class="brand-products-grid">
            @forelse($products as $product)
                <article class="brand-product-card">
                    <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="brand-product-image product-card-image-link" aria-label="Lihat detail {{ $product->name }}">
                        @if($product->thumbnail)
                            <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                        @else
                            <div class="image-placeholder">Produk</div>
                        @endif
                    </a>

                    <div class="brand-product-body">
                        <span class="brand-product-code {{ $product->code ? 'product-code-meta' : 'product-brand-label' }}">{{ $product->code ?? $brand->name }}</span>
                        <h3><a href="{{ route('products.show', ['product' => $product->slug]) }}" class="product-title-link">{{ $product->name }}</a></h3>
                        @include('components.product-rating-summary', ['product' => $product])
                        @if($product->size)
                            <span class="brand-product-code product-size-meta">Ukuran: {{ $product->size }}</span>
                        @endif
                        <p class="product-description-meta">{{ \Illuminate\Support\Str::limit($product->description ?? 'Deskripsi produk belum tersedia.', 110) }}</p>

                        <div class="brand-product-meta">
                            <strong>{{ $product->hasVariants() ? 'Mulai ' : '' }}Rp {{ number_format($product->startingPrice(), 0, ',', '.') }}</strong>
                            <span class="stock-badge product-stock-meta {{ $product->availableStock() > 0 ? 'in-stock' : 'out-stock' }}">
                                {{ $product->availableStock() > 0 ? 'Stok ' . $product->availableStock() : 'Stok habis' }}
                            </span>
                        </div>

                        @include('components.product-purchase-actions', [
                            'product' => $product,
                            'actionsClass' => 'brand-product-actions',
                            'emptyClass' => 'brand-product-empty',
                        ])
                    </div>
                </article>
            @empty
                <div class="brand-products-empty">
                    Belum ada produk untuk brand ini.
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
