@extends('layouts.app')
@section('title', 'Pencarian - SAYAKA GROUP')

@section('content')
<section class="section customer-search-page">
    <div class="customer-search-wrap" style="max-width:1100px; margin:0 auto; padding:24px;">
        <h2 style="margin:0 0 16px 0;">Hasil Pencarian</h2>

        <form action="{{ route('search') }}" method="get" class="customer-search-form" style="display:flex; gap:10px; margin-bottom:24px;">
            <input type="text" name="q" value="{{ $query }}" placeholder="Cari brand, produk, atau berita..." style="flex:1; padding:12px 14px; border:1px solid #cbd5e1; border-radius:8px;">
            <button type="submit" class="btn btn-primary" style="padding:12px 20px; border:none; border-radius:8px; background:#2563eb; color:#fff; font-weight:700; cursor:pointer;">Cari</button>
        </form>

        @if(!filled($query))
            <div class="card" style="padding:20px; color:#64748b;">
                Masukkan kata kunci untuk mencari produk, brand, atau berita.
            </div>
        @else
            @php
                $totalResults = $products->count() + $brands->count() + $news->count();
            @endphp

            <p style="margin:0 0 24px 0; color:#64748b;">
                Ditemukan {{ $totalResults }} hasil untuk "{{ $query }}".
            </p>

            @if($totalResults === 0)
                <div class="card" style="padding:20px; color:#64748b;">
                    Tidak ada hasil yang cocok.
                </div>
            @endif

            @if($products->count())
                <div class="customer-search-products-section" style="margin-bottom:32px;">
                    <h3 class="customer-search-products-heading" style="margin:0 0 14px 0;">Produk</h3>
                    <div class="customer-search-grid customer-search-product-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px;">
                        @foreach($products as $product)
                            <article class="card customer-search-product-card" style="padding:16px; border:1px solid #e2e8f0; border-radius:8px;">
                                <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="customer-search-product-image-link" aria-label="Lihat detail {{ $product->name }}">
                                    <img class="customer-search-product-image" src="{{ $product->thumbnail ? asset($product->thumbnail) : 'https://placehold.co/300x200?text=Produk' }}" alt="{{ $product->name }}" loading="lazy" decoding="async" style="width:100%; height:140px; object-fit:cover; border-radius:8px; background:#f8fafc;">
                                </a>
                                <h4 class="customer-search-product-name" style="margin:12px 0 6px 0;"><a href="{{ route('products.show', ['product' => $product->slug]) }}" class="product-title-link">{{ $product->name }}</a></h4>
                                <p class="customer-search-product-brand product-brand-label" style="margin:0 0 8px 0; color:#64748b;">{{ $product->brand->name ?? 'Sayaka' }}</p>
                                @include('components.product-rating-summary', ['product' => $product])
                                @if($product->size)
                                    <p class="product-size-meta" style="margin:0 0 8px 0; color:#64748b;">Ukuran: <strong>{{ $product->size }}</strong></p>
                                @endif
                                <p class="customer-search-product-price" style="margin:0 0 12px 0; font-weight:700;">{{ $product->hasVariants() ? 'Mulai ' : '' }}Rp {{ number_format($product->startingPrice(), 0, ',', '.') }}</p>
                                <p class="customer-search-product-stock product-stock-meta">Stok Tersedia: {{ $product->availableStock() }}</p>
                                <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="btn btn-primary customer-search-desktop-link" style="display:inline-block; padding:9px 14px; border-radius:20px; background:#2563eb; color:#fff; text-decoration:none; font-weight:600;">Lihat Produk</a>
                                @include('components.product-purchase-actions', [
                                    'product' => $product,
                                    'actionsClass' => 'customer-search-mobile-actions',
                                    'emptyClass' => 'customer-search-mobile-empty',
                                ])
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($brands->count())
                <div style="margin-bottom:32px;">
                    <h3 style="margin:0 0 14px 0;">Brand</h3>
                    <div class="customer-search-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px;">
                        @foreach($brands as $brand)
                            <a href="{{ route('brands.show', $brand) }}" class="card" style="display:block; padding:16px; border:1px solid #e2e8f0; border-radius:8px; color:inherit; text-decoration:none;">
                                <h4 style="margin:0 0 8px 0;">{{ $brand->name }}</h4>
                                <p style="margin:0; color:#64748b;">{{ \Illuminate\Support\Str::limit($brand->description ?? '-', 100) }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($news->count())
                <div>
                    <h3 style="margin:0 0 14px 0;">Berita</h3>
                    <div style="display:grid; gap:12px;">
                        @foreach($news as $item)
                            <a href="{{ route('news.show', $item->slug) }}" class="card" style="display:block; padding:16px; border:1px solid #e2e8f0; border-radius:8px; color:inherit; text-decoration:none;">
                                <h4 style="margin:0 0 6px 0;">{{ $item->title }}</h4>
                                <p style="margin:0; color:#64748b;">{{ \Illuminate\Support\Str::limit(strip_tags($item->content), 140) }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>
@endsection
