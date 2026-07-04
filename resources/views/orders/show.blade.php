@extends('layouts.app')
@section('title', 'Detail Pesanan')

@section('content')
@php
    $statusLabels = [
        'pending' => 'Menunggu Konfirmasi',
        'confirmed' => 'Sedang Dikemas',
        'shipped' => 'Dalam Pengiriman',
        'received' => 'Pesanan Selesai',
    ];

    $statusDescriptions = [
        'pending' => 'Pesanan sedang menunggu konfirmasi pembayaran dari tim SAYAKA GROUP.',
        'confirmed' => 'Pesanan sudah dikonfirmasi dan sedang dipersiapkan untuk pengiriman.',
        'shipped' => 'Pesanan sedang dalam perjalanan menuju alamat penerima.',
        'received' => 'Pesanan sudah selesai. Terima kasih sudah berbelanja di SAYAKA GROUP.',
    ];

    $paymentLabels = [
        'midtrans_snap' => 'Midtrans Snap',
        'qris' => 'QRIS',
        'virtual_account' => 'Virtual Account',
        'bank_transfer' => 'Transfer Bank Manual',
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
        'pending' => 'Pesanan Masuk',
        'confirmed' => 'Sedang Dikemas',
        'shipped' => 'Dalam Pengiriman',
        'received' => 'Selesai',
    ];

    $mobileStatusSteps = [
        'pending' => 'Masuk',
        'confirmed' => 'Dikemas',
        'shipped' => 'Dikirim',
        'received' => 'Selesai',
    ];

    $statusIndex = array_flip(array_keys($statusSteps));
    $status = $order->status ?? 'pending';
    $currentStep = $statusIndex[$status] ?? 0;
    $shippingCost = (float) ($order->shipping_cost ?? 0);
    $subtotalProducts = max(0, (float) $order->total - $shippingCost);
    $paymentMethod = $paymentLabels[$order->payment_method] ?? ucfirst(str_replace('_', ' ', $order->payment_method ?? 'Pembayaran'));
    $paymentStatus = $paymentStatusLabels[$order->payment_status] ?? 'Belum Dibayar';
    $orderCode = $order->displayCode();
    $canReviewProducts = $status === 'received';
@endphp

<section class="order-detail-page">
    <div class="order-detail-wrap">
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

        <div class="order-detail-topbar">
            <a href="{{ route('orders.index') }}" class="order-back-link">Kembali ke Pesanan</a>

            <div class="order-detail-actions">
                @if($order->payment_status !== 'paid')
                    <a href="{{ route('payment.show', $order) }}" class="btn btn-primary">Bayar Sekarang</a>
                @endif
            </div>
        </div>

        <div class="order-detail-hero">
            <div>
                <span class="orders-eyebrow">Detail Pesanan</span>
                <h1>{{ $orderCode }}</h1>
                <p>{{ $statusDescriptions[$status] ?? 'Pantau perkembangan pesanan Anda di halaman ini.' }}</p>
            </div>

            <div class="order-detail-hero-meta">
                <span class="order-status-badge is-{{ $status }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
                <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                <span>Total pembayaran</span>
            </div>
        </div>

        <div class="order-detail-layout">
            <div class="order-detail-main">
                <section class="order-detail-card">
                    <div class="order-section-heading">
                        <span>Timeline</span>
                        <h2>Status Pesanan</h2>
                    </div>

                    <div class="order-detail-timeline">
                        @foreach($statusSteps as $stepKey => $stepLabel)
                            @php
                                $stepPosition = $statusIndex[$stepKey] ?? 0;
                            @endphp

                            <div class="order-timeline-step {{ $stepPosition <= $currentStep ? 'is-active' : '' }} {{ $stepPosition === $currentStep ? 'is-current' : '' }}">
                                <span></span>
                                <div>
                                    <strong>
                                        <span class="order-step-label-full">{{ $stepLabel }}</span>
                                        <span class="order-step-label-short">{{ $mobileStatusSteps[$stepKey] ?? $stepLabel }}</span>
                                    </strong>
                                    <p>
                                        @switch($stepKey)
                                            @case('pending')
                                                Pesanan dibuat dan menunggu pembayaran/konfirmasi.
                                                @break
                                            @case('confirmed')
                                                Admin memproses dan menyiapkan produk.
                                                @break
                                            @case('shipped')
                                                Paket diserahkan ke kurir pilihan.
                                                @break
                                            @case('received')
                                                Pesanan diterima dan transaksi selesai.
                                                @break
                                        @endswitch
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="order-detail-card">
                    <div class="order-section-heading">
                        <span>Produk</span>
                        <h2>Rincian Produk</h2>
                    </div>

                    <div class="order-detail-items">
                        @foreach($order->items as $item)
                            @php
                                $product = $item->product;
                                $unitPrice = $item->price ?? ($product->price ?? 0);
                                $variantAttribute = $item->selected_color ? 'Ukuran' : ($item->productVariant?->attributeName() ?? 'Varian');
                                $review = $item->review;
                                $selectedRating = (int) old('rating', $review?->rating);
                            @endphp

                            <div class="order-detail-item">
                                <div class="order-item-thumb is-large">
                                    @if($product && $product->thumbnail)
                                        <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                                    @else
                                        <span>Produk</span>
                                    @endif
                                </div>

                                <div class="order-detail-item-info">
                                    <h3>{{ $product->name ?? 'Produk tidak tersedia' }}</h3>
                                    @if($item->variant_label ?? $product?->size)
                                        <p>{{ $variantAttribute }} {{ $item->variant_label ?? $product?->size }}</p>
                                    @endif
                                    @if($item->selected_color)
                                        <p>Warna {{ $item->selected_color }}</p>
                                    @endif
                                    <p>{{ $item->quantity }} item x Rp {{ number_format($unitPrice, 0, ',', '.') }}</p>
                                </div>

                                <strong>Rp {{ number_format($item->quantity * $unitPrice, 0, ',', '.') }}</strong>

                                @if($canReviewProducts && $product)
                                    <form action="{{ route('orders.items.review.store', [$order, $item]) }}" method="POST" class="product-review-form">
                                        @csrf

                                        <div class="product-review-heading">
                                            <span>{{ $review ? 'Review Anda' : 'Beri Review' }}</span>
                                            <strong>{{ $review ? 'Perbarui pengalaman produk ini' : 'Bagikan pengalaman setelah memakai produk ini' }}</strong>
                                        </div>

                                        <div class="product-review-star-picker" role="radiogroup" aria-label="Rating produk">
                                            @for($rating = 5; $rating >= 1; $rating--)
                                                @php
                                                    $ratingId = 'review-rating-' . $item->id . '-' . $rating;
                                                @endphp

                                                <input id="{{ $ratingId }}" type="radio" name="rating" value="{{ $rating }}" @checked($selectedRating === $rating) required>
                                                <label for="{{ $ratingId }}" title="{{ $rating }} bintang" aria-label="{{ $rating }} bintang">&#9733;</label>
                                            @endfor
                                        </div>

                                        @error('rating')
                                            <p class="product-review-error">{{ $message }}</p>
                                        @enderror

                                        <textarea name="review" rows="3" maxlength="1000" placeholder="Ceritakan kualitas, aroma, hasil pemakaian, atau pengalaman Anda.">{{ old('review', $review?->review) }}</textarea>

                                        @error('review')
                                            <p class="product-review-error">{{ $message }}</p>
                                        @enderror

                                        <button type="submit" class="btn btn-primary">
                                            {{ $review ? 'Perbarui Review' : 'Kirim Review' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                @if($order->shipping_carrier || $order->shipping_address || $order->phone)
                    <section class="order-detail-card">
                        <div class="order-section-heading">
                            <span>Pengiriman</span>
                            <h2>Alamat dan Kurir</h2>
                        </div>

                        <div class="order-info-grid">
                            <div>
                                <span>Kurir</span>
                                <strong>{{ $order->shipping_carrier ? $order->shipping_carrier . ' - ' . $order->shipping_service : '-' }}</strong>
                            </div>
                            <div>
                                <span>No. HP Penerima</span>
                                <strong>{{ $order->phone ?? '-' }}</strong>
                            </div>
                            <div class="is-full">
                                <span>Alamat Pengiriman</span>
                                <strong>{{ $order->shipping_address ?? '-' }}</strong>
                            </div>
                        </div>
                    </section>
                @endif
            </div>

            <aside class="order-detail-sidebar">
                <section class="order-detail-card">
                    <div class="order-section-heading">
                        <span>Ringkasan</span>
                        <h2>Pembayaran</h2>
                    </div>

                    <div class="order-billing-list">
                        <div>
                            <span>Subtotal Produk</span>
                            <strong>Rp {{ number_format($subtotalProducts, 0, ',', '.') }}</strong>
                        </div>

                        @if($shippingCost > 0)
                            <div>
                                <span>Ongkos Kirim</span>
                                <strong>Rp {{ number_format($shippingCost, 0, ',', '.') }}</strong>
                            </div>
                        @endif

                        <div class="is-total">
                            <span>Total Pembayaran</span>
                            <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </section>

                <section class="order-detail-card">
                    <div class="order-section-heading">
                        <span>Transaksi</span>
                        <h2>Informasi</h2>
                    </div>

                    <div class="order-info-grid is-single">
                        <div>
                            <span>Kode Pesanan</span>
                            <strong>{{ $orderCode }}</strong>
                        </div>
                        <div>
                            <span>Tanggal Pesanan</span>
                            <strong>{{ $order->created_at->format('d M Y H:i') }}</strong>
                        </div>
                        <div>
                            <span>Metode Pembayaran</span>
                            <strong>{{ $paymentMethod }}</strong>
                        </div>
                        <div class="order-payment-status-row">
                            <span>Status Pembayaran</span>
                            <strong>
                                <span class="order-payment-badge {{ $order->payment_status === 'paid' ? 'is-paid' : 'is-pending' }}">
                                    {{ $paymentStatus }}
                                </span>
                            </strong>
                        </div>
                    </div>
                </section>

                <section class="order-help-card">
                    <span>Butuh bantuan?</span>
                    <p>Hubungi admin jika ingin menanyakan pembayaran, stok, atau status pengiriman pesanan ini.</p>
                    <a href="{{ route('chat.index') }}">Chat Admin</a>
                </section>
            </aside>
        </div>
    </div>
</section>
@endsection
