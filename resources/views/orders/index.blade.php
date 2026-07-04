@extends('layouts.app')
@section('title', 'Pesanan Saya')

@section('content')
@php
    $statusLabels = [
        'pending' => 'Menyiapkan Pesanan',
        'confirmed' => 'Sedang Dikemas',
        'shipped' => 'Dalam Pengiriman',
        'received' => 'Pesanan Selesai',
    ];

    $paymentLabels = [
        'midtrans_snap' => 'Midtrans Snap',
        'qris' => 'QRIS',
        'virtual_account' => 'Virtual Account',
        'bank_transfer' => 'Transfer Bank',
        'gopay' => 'GoPay',
        'shopeepay' => 'ShopeePay',
        'alfamart' => 'Alfamart',
        'credit_card' => 'Kartu Kredit/Debit',
    ];

    $paymentStatusLabels = [
        'pending' => 'Menunggu Pembayaran',
        'paid' => 'Terbayar',
        'failed' => 'Gagal',
        'expired' => 'Kedaluwarsa',
        'cancelled' => 'Dibatalkan',
        'denied' => 'Ditolak',
    ];

    $statusSteps = [
        'pending' => 'Masuk',
        'confirmed' => 'Dikemas',
        'shipped' => 'Dikirim',
        'received' => 'Selesai',
    ];

    $statusIndex = array_flip(array_keys($statusSteps));
    $activeOrders = $orders->whereIn('status', ['pending', 'confirmed', 'shipped'])->count();
    $completedOrders = $orders->where('status', 'received')->count();
    $totalSpent = $orders->sum('total');
@endphp

<section class="orders-page">
    <div class="orders-wrap">
        <div class="orders-hero">
            <div>
                <span class="orders-eyebrow">Pesanan Saya</span>
                <h1 class="orders-title">Riwayat Pesanan</h1>
                <p>Pantau status pembayaran, pengemasan, pengiriman, dan rincian produk yang sudah Anda beli.</p>
            </div>

            <div class="orders-stats">
                <div>
                    <strong>{{ $orders->count() }}</strong>
                    <span>Total pesanan</span>
                </div>
                <div>
                    <strong>{{ $activeOrders }}</strong>
                    <span>Sedang berjalan</span>
                </div>
                <div>
                    <strong>{{ $completedOrders }}</strong>
                    <span>Selesai</span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if($orders->count())
            <div class="orders-summary-strip">
                <div>
                    <span>Total belanja</span>
                    <strong>Rp {{ number_format($totalSpent, 0, ',', '.') }}</strong>
                </div>
                <div>
                    <span>Pesanan terbaru</span>
                    <strong>{{ $orders->first()?->created_at?->format('d M Y H:i') ?? '-' }}</strong>
                </div>
            </div>

            <div class="orders-list">
                @foreach($orders as $order)
                    @php
                        $status = $order->status ?? 'pending';
                        $currentStep = $statusIndex[$status] ?? 0;
                        $paymentMethod = $paymentLabels[$order->payment_method] ?? ucfirst(str_replace('_', ' ', $order->payment_method ?? 'Pembayaran'));
                        $paymentStatus = $paymentStatusLabels[$order->payment_status] ?? 'Belum Dibayar';
                        $previewItems = $order->items->take(2);
                        $remainingItems = max(0, $order->items->count() - $previewItems->count());
                        $totalQuantity = $order->items->sum('quantity');
                        $orderCode = $order->displayCode();
                    @endphp

                    <article class="order-card-modern">
                        <div class="order-card-top">
                            <div>
                                <span class="order-number">Kode Pesanan {{ $orderCode }}</span>
                                <h2>{{ $order->created_at->format('d M Y H:i') }}</h2>
                                <p>{{ $totalQuantity }} item dalam pesanan ini</p>
                            </div>

                            <span class="order-status-badge is-{{ $status }}">
                                {{ $statusLabels[$status] ?? ucfirst($status) }}
                            </span>
                        </div>

                        <div class="order-progress" aria-label="Status pesanan">
                            @foreach($statusSteps as $stepKey => $stepLabel)
                                @php
                                    $stepPosition = $statusIndex[$stepKey] ?? 0;
                                @endphp
                                <span class="{{ $stepPosition <= $currentStep ? 'is-active' : '' }}">{{ $stepLabel }}</span>
                            @endforeach
                        </div>

                        <div class="order-item-preview-list">
                            @foreach($previewItems as $item)
                                @php
                                    $product = $item->product;
                                    $unitPrice = $item->price ?? ($product->price ?? 0);
                                    $variantAttribute = $item->selected_color ? 'Ukuran' : ($item->productVariant?->attributeName() ?? 'Varian');
                                @endphp

                                <div class="order-item-preview">
                                    <div class="order-item-thumb">
                                        @if($product && $product->thumbnail)
                                            <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                                        @else
                                            <span>Produk</span>
                                        @endif
                                    </div>

                                    <div>
                                        <h3>{{ $product->name ?? 'Produk tidak tersedia' }}</h3>
                                        @if($item->variant_label ?? $product?->size)
                                            <p>{{ $variantAttribute }} {{ $item->variant_label ?? $product?->size }}</p>
                                        @endif
                                        @if($item->selected_color)
                                            <p>Warna {{ $item->selected_color }}</p>
                                        @endif
                                        <p>{{ $item->quantity }} x Rp {{ number_format($unitPrice, 0, ',', '.') }}</p>
                                    </div>

                                    <strong>Rp {{ number_format($item->quantity * $unitPrice, 0, ',', '.') }}</strong>
                                </div>
                            @endforeach

                            @if($remainingItems > 0)
                                <div class="order-more-items">+{{ $remainingItems }} produk lain dalam pesanan ini</div>
                            @endif
                        </div>

                        <div class="order-card-bottom">
                            <div class="order-payment-meta">
                                <span class="order-payment-badge {{ $order->payment_status === 'paid' ? 'is-paid' : 'is-pending' }}">
                                    {{ $paymentStatus }}
                                </span>
                                <span>{{ $paymentMethod }}</span>
                            </div>

                            <div class="order-total-action">
                                <span>Total Pembayaran</span>
                                <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-primary">Lihat Detail</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="orders-empty">
                <span>Belum ada pesanan</span>
                <h2>Riwayat pesanan masih kosong.</h2>
                <p>Mulai pilih produk dari brand SAYAKA GROUP dan pesanan Anda akan muncul di halaman ini.</p>
                <a href="{{ route('brands.index') }}" class="btn btn-primary">Belanja Sekarang</a>
            </div>
        @endif
    </div>
</section>
@endsection
