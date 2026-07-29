@extends('layouts.app')
@section('title', 'Detail Pesanan')

@section('content')
@php
    $statusLabels = [
        'pending' => 'Menunggu Konfirmasi',
        'confirmed' => 'Sedang Dikemas',
        'shipped' => 'Dalam Pengiriman',
        'received' => 'Pesanan Selesai',
        'returned' => 'Pengembalian Selesai',
    ];

    $statusDescriptions = [
        'pending' => 'Pesanan sedang menunggu konfirmasi pembayaran dari tim SAYAKA GROUP.',
        'confirmed' => 'Pesanan sudah dikonfirmasi dan sedang dipersiapkan untuk pengiriman.',
        'shipped' => 'Pesanan sedang dalam perjalanan menuju alamat penerima.',
        'received' => 'Pesanan sudah selesai. Terima kasih sudah berbelanja di SAYAKA GROUP.',
        'returned' => 'Barang telah berhasil dikembalikan ke SAYAKA GROUP.',
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
                @if($order->payment_status !== 'paid' && $order->status !== 'cancelled')
                    <a href="{{ route('payment.show', $order) }}" class="btn btn-primary">Bayar Sekarang</a>
                    <form action="{{ route('payment.cancel', $order) }}" method="POST" style="display:inline;"
                          data-confirm-title="Batalkan Pesanan Ini?"
                          data-confirm-text="Pesanan {{ $orderCode }} akan dibatalkan secara permanen."
                          data-confirm-button="Ya, Batalkan Pesanan"
                          data-confirm-color="#dc2626">
                        @csrf
                        <button type="submit" class="btn btn-danger" style="background:#ef4444; color:#fff; padding:10px 18px; border:none; border-radius:999px; font-weight:700; cursor:pointer; text-decoration:none;">Batalkan Pesanan</button>
                    </form>
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

                @if($order->orderReturn)
                    <section class="order-detail-card" style="border-left: 4px solid var(--primary, #0f172a);">
                        <div class="order-section-heading">
                            <span>Pengembalian Barang</span>
                            <h2>Status Retur Barang</h2>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; background: #f8fafc; padding: 1rem; border-radius: 8px;">
                                <div>
                                    <span style="font-size: 0.85rem; color: #64748b; display: block;">Status Pengajuan</span>
                                    <strong style="font-size: 1.1rem; color: #0f172a;">{{ $order->orderReturn->statusLabel() }}</strong>
                                </div>
                                <span class="order-status-badge is-{{ $order->orderReturn->status }}">
                                    {{ ucfirst($order->orderReturn->status) }}
                                </span>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div>
                                    <span style="font-size: 0.85rem; color: #64748b;">Alasan Retur</span>
                                    <p style="font-weight: 600; margin-top: 0.25rem;">{{ $order->orderReturn->reason }}</p>
                                </div>
                                <div>
                                    <span style="font-size: 0.85rem; color: #64748b;">Tanggal Pengajuan</span>
                                    <p style="font-weight: 600; margin-top: 0.25rem;">{{ $order->orderReturn->created_at->format('d M Y H:i') }}</p>
                                </div>
                            </div>

                            <div>
                                <span style="font-size: 0.85rem; color: #64748b;">Deskripsi Masalah</span>
                                <p style="background: #f1f5f9; padding: 0.75rem; border-radius: 6px; margin-top: 0.25rem; font-size: 0.95rem;">
                                    {{ $order->orderReturn->description }}
                                </p>
                            </div>

                            @if($order->orderReturn->hasBankDetails())
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1rem; border-radius: 8px;">
                                    <span style="font-size: 0.85rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 0.5rem;">Rekening Tujuan Pengembalian Dana (Refund):</span>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; font-size: 0.9rem;">
                                        <div>
                                            <span style="color: #64748b;">Bank / E-Wallet:</span>
                                            <strong style="display: block; color: #0f172a;">{{ $order->orderReturn->bank_name }}</strong>
                                        </div>
                                        <div>
                                            <span style="color: #64748b;">No. Rekening / No. HP:</span>
                                            <strong style="display: block; color: #0f172a;">{{ $order->orderReturn->bank_account_number }}</strong>
                                        </div>
                                        <div>
                                            <span style="color: #64748b;">Atas Nama:</span>
                                            <strong style="display: block; color: #0f172a;">{{ $order->orderReturn->bank_account_holder }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($order->orderReturn->status === 'completed' && $order->orderReturn->refund_proof_image)
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 1rem; border-radius: 8px;">
                                    <span style="font-size: 0.85rem; color: #166534; font-weight: 700; display: block; margin-bottom: 0.5rem;">Bukti Transfer Refund Dana dari Admin:</span>
                                    
                                    @if($order->orderReturn->refund_amount)
                                        <p style="font-size: 0.95rem; color: #14532d; font-weight: 600; margin-bottom: 0.5rem;">
                                            Nominal Refund: <span style="font-size: 1.1rem; color: #16a34a;">Rp {{ number_format($order->orderReturn->refund_amount, 0, ',', '.') }}</span>
                                        </p>
                                    @endif

                                    <a href="{{ asset($order->orderReturn->refund_proof_image) }}" target="_blank">
                                        <img src="{{ asset($order->orderReturn->refund_proof_image) }}" alt="Bukti Transfer Refund" style="max-width: 260px; max-height: 220px; object-fit: cover; border-radius: 8px; border: 1px solid #86efac;">
                                    </a>
                                </div>
                            @endif

                            @if($order->orderReturn->proof_image)
                                <div>
                                    <span style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 0.5rem;">Foto Bukti Kondisi Barang</span>
                                    <a href="{{ asset($order->orderReturn->proof_image) }}" target="_blank">
                                        <img src="{{ asset($order->orderReturn->proof_image) }}" alt="Foto Bukti Retur" style="max-width: 220px; max-height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                                    </a>
                                </div>
                            @endif

                            @if($order->orderReturn->admin_note)
                                <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 1rem; border-radius: 8px;">
                                    <span style="font-size: 0.85rem; color: #1e40af; font-weight: 600; display: block;">Catatan / Respon Admin:</span>
                                    <p style="color: #1e3a8a; margin-top: 0.25rem; font-size: 0.95rem;">
                                        {{ $order->orderReturn->admin_note }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </section>
                @elseif($order->canBeReturned())
                    <section class="order-detail-card">
                        <div class="order-section-heading">
                            <span>Pengembalian</span>
                            <h2>Ajukan Pengembalian Barang (Return)</h2>
                        </div>

                        <p style="color: #64748b; margin-bottom: 1rem; font-size: 0.95rem;">
                            Jika produk yang Anda terima tidak sesuai, rusak, atau salah varian, Anda dapat mengajukan pengembalian barang dan memasukkan rekening pengembalian dana.
                        </p>

                        <details class="return-form-details" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
                            <summary style="font-weight: 600; cursor: pointer; color: var(--primary, #0f172a);">
                                Klik di sini untuk mengisi Formulir Retur & Rekening Refund
                            </summary>

                            <form action="{{ route('orders.return.store', $order) }}" method="POST" enctype="multipart/form-data" style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                                @csrf

                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Alasan Pengembalian <span style="color: red;">*</span></label>
                                    <select name="reason" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.6rem; font-size: 0.95rem;">
                                        <option value="">-- Pilih Alasan --</option>
                                        <option value="Barang Rusak/Cacat" @selected(old('reason') === 'Barang Rusak/Cacat')>Barang Rusak / Cacat</option>
                                        <option value="Tidak Sesuai Pesanan" @selected(old('reason') === 'Tidak Sesuai Pesanan')>Tidak Sesuai Pesanan</option>
                                        <option value="Ukuran/Varian Salah" @selected(old('reason') === 'Ukuran/Varian Salah')>Ukuran / Varian Salah</option>
                                        <option value="Lainnya" @selected(old('reason') === 'Lainnya')>Lainnya</option>
                                    </select>
                                    @error('reason')
                                        <p style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Deskripsi Detail Masalah <span style="color: red;">*</span></label>
                                    <textarea name="description" rows="3" required maxlength="1000" placeholder="Jelaskan secara rinci kondisi produk saat diterima..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.6rem; font-size: 0.95rem;">{{ old('description') }}</textarea>
                                    @error('description')
                                        <p style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Upload Foto Bukti <span style="color: red;">*</span></label>
                                    <input type="file" name="proof_image" accept="image/jpeg,image/png,image/jpg,image/webp" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.5rem; background: #fff;">
                                    <small style="color: #64748b; display: block; margin-top: 0.25rem;">Format JPG, PNG, WEBP. Maksimal 3 MB.</small>
                                    @error('proof_image')
                                        <p style="color: #ef4444; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1rem;">
                                    <h4 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 0.95rem; font-weight: 700; color: #0f172a;">Informasi Rekening Pengembalian Dana (Refund)</h4>

                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                        <div>
                                            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.88rem;">Nama Bank / E-Wallet <span style="color: red;">*</span></label>
                                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" required placeholder="Contoh: BCA, Mandiri, GoPay" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.5rem; font-size: 0.9rem;">
                                            @error('bank_name')
                                                <p style="color: #ef4444; font-size: 0.8rem; margin-top: 0.2rem;">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.88rem;">No. Rekening / No. HP <span style="color: red;">*</span></label>
                                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" required placeholder="Contoh: 1234567890" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.5rem; font-size: 0.9rem;">
                                            @error('bank_account_number')
                                                <p style="color: #ef4444; font-size: 0.8rem; margin-top: 0.2rem;">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.88rem;">Atas Nama Pemilik <span style="color: red;">*</span></label>
                                            <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder') }}" required placeholder="Nama sesuai di rekening/e-wallet" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.5rem; font-size: 0.9rem;">
                                            @error('bank_account_holder')
                                                <p style="color: #ef4444; font-size: 0.8rem; margin-top: 0.2rem;">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" style="align-self: flex-start;">
                                    Kirim Pengajuan Retur ke Admin
                                </button>
                            </form>
                        </details>
                    </section>
                @endif

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
