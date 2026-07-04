@extends('layouts.admin')
@section('title', 'Kelola Produk')
@section('page-title', 'Kelola Produk')

@section('content')
<section class="card section admin-products-page">
    <div class="admin-products-heading">
        <div>
            <h2 class="section-title">Produk per Brand</h2>
            <p class="text-muted mb-0">Kelola katalog produk tiap brand dalam tampilan yang tetap rapi di desktop dan mobile.</p>
        </div>
    </div>

    <div class="admin-brand-list">
        @forelse($brands as $brand)
            <article class="admin-brand-card">
                <div class="admin-brand-card-header">
                    <div class="admin-brand-identity">
                        @if($brand->logo)
                            <img src="{{ asset($brand->logo) }}" alt="Logo {{ $brand->name }}" class="admin-brand-logo">
                        @else
                            <span class="admin-brand-logo admin-brand-logo-placeholder">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                        @endif

                        <div>
                            <h3>{{ $brand->name }}</h3>
                            <p>{{ $brand->products->count() }} produk terdaftar</p>
                        </div>
                    </div>

                    <a href="{{ route('admin.products.create', ['brand' => $brand->id]) }}" class="btn btn-primary">+ Tambah Produk</a>
                </div>

                @if($brand->products->count())
                    <div class="admin-product-list">
                        @foreach($brand->products as $p)
                            <article class="admin-product-item">
                                <div class="admin-product-media">
                                    @if($p->thumbnail)
                                        <img src="{{ asset($p->thumbnail) }}" alt="{{ $p->name }}">
                                    @else
                                        <span>Produk</span>
                                    @endif
                                </div>

                                <div class="admin-product-info">
                                    <span>ID #{{ $p->id }}</span>
                                    <h4>{{ $p->name }}</h4>
                                    <div class="admin-product-meta">
                                        <span>Harga: <strong>{{ $p->hasVariants() ? 'Mulai ' : '' }}{{ $p->startingPrice() ? 'Rp ' . number_format($p->startingPrice(), 0, ',', '.') : '-' }}</strong></span>
                                        <span>Stok: <strong>{{ $p->availableStock() }}</strong></span>
                                        @if($p->size)
                                            <span>Kapasitas: <strong>{{ $p->size }}</strong></span>
                                        @endif
                                    </div>
                                    @if($p->hasVariants())
                                        <div class="admin-product-meta">
                                            @foreach($p->variants as $variant)
                                                <span>{{ $variant->attributeName() }} {{ $variant->label() }}: <strong>Rp {{ number_format($variant->price ?? 0, 0, ',', '.') }}</strong> | stok <strong>{{ $variant->stock }}</strong></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="admin-product-actions">
                                    <a href="{{ route('admin.products.edit', $p->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('admin.products.destroy', $p->id) }}" method="POST" data-confirm-title="Hapus produk?" data-confirm-text="Produk {{ $p->name }} akan dihapus.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="admin-product-empty">
                        Belum ada produk untuk brand ini.
                        <a href="{{ route('admin.products.create', ['brand' => $brand->id]) }}">Tambah produk</a>
                    </div>
                @endif
            </article>
        @empty
            <div class="admin-product-empty">Belum ada brand.</div>
        @endforelse
    </div>
</section>
@endsection
