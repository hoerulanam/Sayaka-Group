@extends('layouts.app')

@section('title', $product->name . ' - Produk - SAYAKA GROUP')

@section('content')
<section class="product-detail-page">
    <div class="product-detail-wrap">
        <a href="{{ route('brands.show', $product->brand) }}" class="product-detail-back">&larr; Kembali ke {{ $product->brand->name }}</a>

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

        <div class="product-detail-hero">
            <div class="product-detail-media">
                @if($product->thumbnail)
                    <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" decoding="async" fetchpriority="high">
                @else
                    <div class="product-detail-placeholder">Produk</div>
                @endif
            </div>

            <div class="product-detail-summary">
                <a href="{{ route('brands.show', $product->brand) }}" class="product-detail-brand">{{ $product->brand->name }}</a>
                <h1>{{ $product->name }}</h1>
                @include('components.product-rating-summary', ['product' => $product])

                <div class="product-detail-price">
                    {{ $product->hasVariants() ? 'Mulai ' : '' }}Rp {{ number_format($product->startingPrice(), 0, ',', '.') }}
                </div>

                <div class="product-detail-meta-grid">
                    <div>
                        <span>Kode Produk</span>
                        <strong>{{ $product->code ?: 'Tanpa kode' }}</strong>
                    </div>
                    <div>
                        <span>Stok Tersedia</span>
                        <strong>{{ $product->availableStock() }}</strong>
                    </div>
                    @if($product->size)
                        <div>
                            <span>Ukuran</span>
                            <strong>{{ $product->size }}</strong>
                        </div>
                    @endif
                    <div>
                        <span>Brand</span>
                        <strong>{{ $product->brand->name }}</strong>
                    </div>
                </div>

                @include('components.product-purchase-actions', [
                    'product' => $product,
                    'actionsClass' => 'product-detail-actions',
                    'emptyClass' => 'product-detail-empty',
                ])
            </div>
        </div>

        <div class="product-detail-content">
            <section class="product-detail-panel product-detail-description">
                <span class="product-detail-eyebrow">Deskripsi Produk</span>
                <h2>Tentang {{ $product->name }}</h2>
                <div class="product-detail-description-text">
                    {!! nl2br(e($product->description ?: 'Deskripsi produk belum tersedia.')) !!}
                </div>
            </section>

            <aside class="product-detail-panel product-detail-variants">
                <span class="product-detail-eyebrow">Informasi Produk</span>
                <h2>{{ $product->hasVariants() ? 'Varian Tersedia' : 'Detail Pembelian' }}</h2>

                @if($product->hasVariants())
                    <div class="product-detail-variant-list">
                        @foreach($product->variants as $variant)
                            <div class="product-detail-variant-row">
                                <div>
                                    <span>{{ $variant->attributeName() }}</span>
                                    <strong>{{ $variant->label() }}</strong>
                                    @if($variant->code)
                                        <small>{{ $variant->code }}</small>
                                    @endif
                                </div>
                                <div>
                                    <strong>Rp {{ number_format($variant->price ?? 0, 0, ',', '.') }}</strong>
                                    <span>{{ $variant->stock > 0 ? 'Stok ' . $variant->stock : 'Stok habis' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="product-detail-simple-stock">
                        <span>Produk ini tersedia tanpa pilihan varian tambahan.</span>
                        <strong>{{ $product->availableStock() > 0 ? 'Stok siap dibeli' : 'Stok sedang habis' }}</strong>
                    </div>
                @endif
            </aside>
        </div>

        <section class="product-detail-reviews">
            <div class="product-detail-reviews-heading">
                <div>
                    <span class="product-detail-eyebrow">Review Produk</span>
                    <h2>Ulasan dari pengguna lain</h2>
                </div>
                <p>{{ $productReviews->count() }} review terbaru dari customer yang sudah membeli produk ini.</p>
            </div>

            @if($productReviews->count())
                <div class="product-detail-review-list">
                    @foreach($productReviews as $review)
                        <article class="product-detail-review-card">
                            <div class="product-detail-review-top">
                                <div>
                                    <strong>{{ $review->user->name ?? 'Pengguna SAYAKA' }}</strong>
                                    @if($review->productVariant)
                                        <span>{{ $review->productVariant->attributeName() }}: {{ $review->productVariant->label() }}</span>
                                    @endif
                                </div>
                                <div class="product-detail-review-rating">
                                    <x-star-rating :rating="$review->rating" />
                                    <span>{{ $review->rating }}/5</span>
                                </div>
                            </div>

                            <p>{{ $review->review ?: 'Customer memberikan rating tanpa komentar tambahan.' }}</p>

                            @if($review->created_at)
                                <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('d M Y') }}</time>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="product-detail-review-empty">
                    Belum ada review untuk produk ini.
                </div>
            @endif
        </section>

        @if($relatedProducts->count())
            <section class="product-detail-related">
                <div class="product-detail-related-heading">
                    <span class="product-detail-eyebrow">Produk Lainnya</span>
                    <h2>Masih dari {{ $product->brand->name }}</h2>
                </div>

                <div class="product-detail-related-grid">
                    @foreach($relatedProducts as $relatedProduct)
                        <article class="product-detail-related-card">
                            <a href="{{ route('products.show', ['product' => $relatedProduct->slug]) }}" class="product-detail-related-image" aria-label="Lihat detail {{ $relatedProduct->name }}">
                                @if($relatedProduct->thumbnail)
                                    <img src="{{ asset($relatedProduct->thumbnail) }}" alt="{{ $relatedProduct->name }}" loading="lazy" decoding="async">
                                @else
                                    <div class="product-detail-placeholder">Produk</div>
                                @endif
                            </a>
                            <div>
                                <span>{{ $relatedProduct->brand->name ?? 'Sayaka' }}</span>
                                <h3><a href="{{ route('products.show', ['product' => $relatedProduct->slug]) }}">{{ $relatedProduct->name }}</a></h3>
                                <strong>{{ $relatedProduct->hasVariants() ? 'Mulai ' : '' }}Rp {{ number_format($relatedProduct->startingPrice(), 0, ',', '.') }}</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</section>
@endsection
