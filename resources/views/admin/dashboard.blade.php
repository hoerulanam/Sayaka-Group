@extends('layouts.admin')
@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')
@section('body-class', 'admin-dashboard-layout')

@section('content')
@php
    $formatNumber = fn ($value) => number_format((float) $value, 0, ',', '.');
    $formatMoney = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $totalOrders = max((int) $stats['orders'], 1);
@endphp

<section class="admin-dashboard-page">
    <div class="admin-dashboard-hero">
        <div>
            <span class="admin-dashboard-eyebrow">Ringkasan Operasional</span>
            <h2></h2>
            <p>Pantau konten, produk, stok, pesanan, user, kontak, dan percakapan customer.</p>
        </div>

        <div class="admin-dashboard-hero-stats">
            <div>
                <strong>{{ $formatNumber($stats['orders']) }}</strong>
                <span>Total Pesanan</span>
            </div>
            <div>
                <strong>{{ $formatMoney($stats['revenue']) }}</strong>
                <span>Pendapatan Dibayar</span>
            </div>
        </div>
    </div>

    <div class="admin-stat-grid">
        <a href="{{ route('admin.news.index') }}" class="admin-stat-card">
            <span class="admin-stat-icon"><x-admin-icon name="news" /></span>
            <div>
                <span>Jumlah Berita</span>
                <strong>{{ $formatNumber($stats['news']) }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.products.index') }}" class="admin-stat-card">
            <span class="admin-stat-icon"><x-admin-icon name="products" /></span>
            <div>
                <span>Jumlah Produk</span>
                <strong>{{ $formatNumber($stats['products']) }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.products.index') }}" class="admin-stat-card">
            <span class="admin-stat-icon"><x-admin-icon name="stock" /></span>
            <div>
                <span>Stok Produk</span>
                <strong>{{ $formatNumber($stats['product_stock']) }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.inventory.index') }}" class="admin-stat-card">
            <span class="admin-stat-icon"><x-admin-icon name="inventory" /></span>
            <div>
                <span>Stok Inventaris</span>
                <strong>{{ $formatNumber($stats['inventory_stock']) }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.orders.index') }}" class="admin-stat-card">
            <span class="admin-stat-icon"><x-admin-icon name="orders" /></span>
            <div>
                <span>Total Pesanan</span>
                <strong>{{ $formatNumber($stats['orders']) }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.orders.index') }}" class="admin-stat-card">
            <span class="admin-stat-icon"><x-admin-icon name="paid" /></span>
            <div>
                <span>Pesanan Dibayar</span>
                <strong>{{ $formatNumber($stats['paid_orders']) }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.users.index') }}" class="admin-stat-card">
            <span class="admin-stat-icon"><x-admin-icon name="users" /></span>
            <div>
                <span>User Terdaftar</span>
                <strong>{{ $formatNumber($stats['users']) }}</strong>
            </div>
        </a>

        <a href="{{ route('admin.contacts.index') }}" class="admin-stat-card">
            <span class="admin-stat-icon"><x-admin-icon name="contacts" /></span>
            <div>
                <span>Pesan Kontak</span>
                <strong>{{ $formatNumber($stats['contacts']) }}</strong>
            </div>
        </a>
    </div>

    <div class="admin-dashboard-grid">
        <article class="admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <span class="admin-dashboard-eyebrow">Pesanan</span>
                    <h3>Status Pesanan</h3>
                </div>
                <a href="{{ route('admin.orders.index') }}">Kelola</a>
            </div>

            <div class="admin-status-list">
                @foreach($orderStatuses as $item)
                    @php
                        $percentage = min(100, round(($item['count'] / $totalOrders) * 100));
                    @endphp
                    <div class="admin-status-item">
                        <div>
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $formatNumber($item['count']) }}</strong>
                        </div>
                        <div class="admin-status-track">
                            <span style="width: {{ $percentage }}%;"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <span class="admin-dashboard-eyebrow">Brand</span>
                    <h3>Produk per Brand</h3>
                </div>
                <a href="{{ route('admin.products.index') }}">Lihat</a>
            </div>

            <div class="admin-brand-summary-list">
                @forelse($productsByBrand as $brand)
                    <div class="admin-brand-summary-item">
                        <div>
                            <strong>{{ $brand->name }}</strong>
                            <span>{{ $formatNumber($brand->products_count) }} produk</span>
                        </div>
                        <span>{{ $formatNumber($brand->stock_total ?? 0) }} stok</span>
                    </div>
                @empty
                    <div class="admin-dashboard-empty">Belum ada brand.</div>
                @endforelse
            </div>
        </article>

        <article class="admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <span class="admin-dashboard-eyebrow">Stok</span>
                    <h3>Produk dan Stok Terendah</h3>
                </div>
                <a href="{{ route('admin.inventory.index') }}">Inventaris</a>
            </div>

            <div class="admin-low-stock-list">
                @forelse($lowStockProducts as $product)
                    <div class="admin-low-stock-item">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $product->brand->name ?? 'Tanpa brand' }}</span>
                        </div>
                        <span class="{{ ($product->stock ?? 0) > 0 ? 'is-ok' : 'is-empty' }}">
                            {{ $formatNumber($product->stock ?? 0) }}
                        </span>
                    </div>
                @empty
                    <div class="admin-dashboard-empty">Belum ada produk.</div>
                @endforelse
            </div>
        </article>

        <article class="admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <span class="admin-dashboard-eyebrow">Inventaris</span>
                    <h3>Pergerakan Barang</h3>
                </div>
                <a href="{{ route('admin.inventory.report') }}">Laporan</a>
            </div>

            <div class="admin-inventory-split">
                <div>
                    <span>Barang Masuk</span>
                    <strong>{{ $formatNumber($stats['incoming_items']) }}</strong>
                </div>
                <div>
                    <span>Barang Keluar</span>
                    <strong>{{ $formatNumber($stats['outgoing_items']) }}</strong>
                </div>
                <div>
                    <span>Admin</span>
                    <strong>{{ $formatNumber($stats['admins']) }}</strong>
                </div>
                <div>
                    <span>Chat Belum Dibaca</span>
                    <strong>{{ $formatNumber($stats['unread_messages']) }}</strong>
                </div>
            </div>
        </article>
    </div>

    <div class="admin-dashboard-wide-grid">
        <article class="admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <span class="admin-dashboard-eyebrow">Terbaru</span>
                    <h3>Pesanan Terbaru</h3>
                </div>
                <a href="{{ route('admin.orders.index') }}">Semua</a>
            </div>

            <div class="admin-dashboard-feed">
                @forelse($latestOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="admin-dashboard-feed-item">
                        <div>
                            <strong>#{{ $order->id }} - {{ $order->user->name ?? 'User' }}</strong>
                            <span>{{ ucfirst($order->status) }} · {{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <span>{{ $formatMoney($order->total) }}</span>
                    </a>
                @empty
                    <div class="admin-dashboard-empty">Belum ada pesanan.</div>
                @endforelse
            </div>
        </article>

        <article class="admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <span class="admin-dashboard-eyebrow">Konten</span>
                    <h3>Berita Terbaru</h3>
                </div>
                <a href="{{ route('admin.news.index') }}">Semua</a>
            </div>

            <div class="admin-dashboard-feed">
                @forelse($latestNews as $item)
                    <a href="{{ route('admin.news.edit', $item) }}" class="admin-dashboard-feed-item">
                        <div>
                            <strong>{{ $item->title }}</strong>
                            <span>{{ $item->published_at?->format('d/m/Y H:i') ?? 'Belum dijadwalkan' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="admin-dashboard-empty">Belum ada berita.</div>
                @endforelse
            </div>
        </article>

        <article class="admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <span class="admin-dashboard-eyebrow">Kontak</span>
                    <h3>Pesan Kontak Terbaru</h3>
                </div>
                <a href="{{ route('admin.contacts.index') }}">Semua</a>
            </div>

            <div class="admin-dashboard-feed">
                @forelse($latestContacts as $contact)
                    <a href="{{ route('admin.contacts.show', $contact) }}" class="admin-dashboard-feed-item">
                        <div>
                            <strong>{{ $contact->name }}</strong>
                            <span>{{ $contact->email }} · {{ $contact->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </a>
                @empty
                    <div class="admin-dashboard-empty">Belum ada pesan kontak.</div>
                @endforelse
            </div>
        </article>
    </div>
</section>
@endsection
