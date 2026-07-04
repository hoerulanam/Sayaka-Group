@extends('layouts.app')
@section('title', 'Pembayaran ' . $order->displayCode())

@php
    $statusLabels = [
        'pending' => 'Menunggu Pembayaran',
        'paid' => 'Terbayar',
        'failed' => 'Gagal',
        'expired' => 'Kedaluwarsa',
        'cancelled' => 'Dibatalkan',
        'denied' => 'Ditolak',
    ];
    $paymentStatus = $statusLabels[$order->payment_status] ?? ucfirst($order->payment_status ?? 'pending');
    $canPay = ($order->payment_status ?? 'pending') === 'pending' && $order->midtrans_snap_token && $midtransClientKey && ! $midtransError;
@endphp

@section('content')
<section class="section customer-payment-page" style="background:#f8fafc; padding:40px 0; min-height:80vh;">
    <div class="customer-payment-wrap" style="max-width:960px; margin:0 auto; padding:0 20px;">
        <div class="customer-payment-layout" style="display:grid; grid-template-columns:minmax(0,1.1fr) minmax(300px,0.9fr); gap:24px; align-items:start;">
            <div class="customer-payment-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:28px; box-shadow:0 10px 30px rgba(15,23,42,0.06);">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:22px;">
                    <div style="width:52px; height:52px; border-radius:14px; background:#e0f2fe; color:#0369a1; display:grid; place-items:center; font-size:26px;">💳</div>
                    <div>
                        <h1 style="margin:0; font-size:1.65rem; color:#0f172a;">Pembayaran Midtrans</h1>
                        <p style="margin:4px 0 0; color:#64748b;">Order {{ $order->displayCode() }}</p>
                    </div>
                </div>

                @if($midtransError)
                    <div class="alert alert-error" style="display:block; margin-bottom:18px; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:14px 16px; border-radius:12px;">
                        {{ $midtransError }}
                    </div>
                @elseif(! $midtransClientKey)
                    <div class="alert alert-error" style="display:block; margin-bottom:18px; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:14px 16px; border-radius:12px;">
                        MIDTRANS_CLIENT_KEY belum dikonfigurasi. Isi sandbox key di file .env untuk membuka popup pembayaran.
                    </div>
                @endif

                <div class="customer-payment-details" style="display:grid; gap:14px; margin-bottom:22px;">
                    <div class="customer-payment-row" style="display:flex; justify-content:space-between; gap:16px; padding:14px 0; border-bottom:1px solid #e2e8f0;">
                        <span style="color:#64748b;">Status Pembayaran</span>
                        <strong style="color:{{ $order->payment_status === 'paid' ? '#15803d' : (in_array($order->payment_status, ['failed', 'expired', 'cancelled', 'denied'], true) ? '#b91c1c' : '#b45309') }};">{{ $paymentStatus }}</strong>
                    </div>
                    <div class="customer-payment-row" style="display:flex; justify-content:space-between; gap:16px; padding:14px 0; border-bottom:1px solid #e2e8f0;">
                        <span style="color:#64748b;">Order ID</span>
                        <strong style="color:#0f172a; text-align:right;">{{ $order->midtrans_order_id ?? '-' }}</strong>
                    </div>
                    <div class="customer-payment-row" style="display:flex; justify-content:space-between; gap:16px; padding:14px 0; border-bottom:1px solid #e2e8f0;">
                        <span style="color:#64748b;">Metode Terakhir</span>
                        <strong style="color:#0f172a; text-align:right;">{{ $order->midtrans_payment_type ? ucfirst(str_replace('_', ' ', $order->midtrans_payment_type)) : 'Dipilih di Midtrans Snap' }}</strong>
                    </div>
                    <div class="customer-payment-row" style="display:flex; justify-content:space-between; gap:16px; padding:14px 0;">
                        <span style="color:#64748b;">Total Tagihan</span>
                        <strong style="color:#2563eb; font-size:1.35rem;">Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="customer-payment-actions" style="display:flex; flex-wrap:wrap; gap:12px;">
                    @if($canPay)
                        <button type="button" id="payWithMidtrans" class="btn btn-primary" style="border:none; padding:13px 22px; border-radius:999px; font-weight:800; cursor:pointer;">
                            Bayar Sekarang
                        </button>
                    @endif

                    <form action="{{ route('payment.sync', $order) }}" method="POST" id="paymentSyncForm" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn" style="background:#0f172a; color:#fff; border:none; padding:13px 22px; border-radius:999px; font-weight:800; cursor:pointer;">
                            Sinkron Status
                        </button>
                    </form>

                    <a href="{{ route('orders.show', $order) }}" class="btn" style="background:#e2e8f0; color:#0f172a; padding:13px 22px; border-radius:999px; font-weight:800; text-decoration:none;">
                        Lihat Pesanan
                    </a>
                </div>

                <p style="margin:18px 0 0; color:#64748b; font-size:0.9rem; line-height:1.6;">
                    Ini memakai Midtrans sandbox. Pembayaran tidak menggunakan uang asli selama konfigurasi masih sandbox.
                    Untuk testing lokal tanpa webhook publik, klik Sinkron Status setelah menyelesaikan simulasi.
                </p>
            </div>

            <aside class="customer-payment-sidebar" style="display:grid; gap:16px;">
                <div class="customer-payment-summary" style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:22px; box-shadow:0 10px 30px rgba(15,23,42,0.06);">
                    <h2 style="margin:0 0 16px; font-size:1.1rem; color:#0f172a;">Ringkasan Pesanan</h2>
                    <div style="display:grid; gap:12px;">
                        @foreach($order->items as $item)
                            <div style="display:flex; gap:12px; align-items:center; padding-bottom:12px; border-bottom:1px solid #f1f5f9;">
                                <img src="{{ asset($item->product->thumbnail) }}" alt="{{ $item->product->name }}" style="width:56px; height:56px; object-fit:cover; border-radius:10px; border:1px solid #e2e8f0;">
                                <div style="flex:1; min-width:0;">
                                    <strong style="display:block; color:#0f172a; font-size:0.92rem;">{{ $item->product->name }}</strong>
                                    @if($item->variant_label)
                                        <span style="display:block; color:#2563eb; font-size:0.78rem; font-weight:700;">Varian: {{ $item->variant_label }}</span>
                                    @endif
                                    @if($item->selected_color)
                                        <span style="display:block; color:#2563eb; font-size:0.78rem; font-weight:700;">Warna: {{ $item->selected_color }}</span>
                                    @endif
                                    <span style="display:block; color:#64748b; font-size:0.82rem;">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:16px; padding:22px;">
                    <h2 style="margin:0 0 10px; font-size:1rem; color:#1e40af;">Pengiriman</h2>
                    <p style="margin:0 0 8px; color:#1e3a8a; font-weight:700;">{{ $order->shipping_carrier }} - {{ $order->shipping_service }}</p>
                    <p style="margin:0; color:#334155; line-height:1.55;">{{ $order->shipping_address }}</p>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection

@if($canPay)
    @push('scripts')
        <script src="{{ $snapScriptUrl }}" data-client-key="{{ $midtransClientKey }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const payButton = document.getElementById('payWithMidtrans');
                const syncForm = document.getElementById('paymentSyncForm');
                const snapToken = @json($order->midtrans_snap_token);

                const syncStatus = () => {
                    if (syncForm.requestSubmit) {
                        syncForm.requestSubmit();
                    } else {
                        syncForm.submit();
                    }
                };

                payButton.addEventListener('click', function () {
                    if (!window.snap) {
                        window.alert('Midtrans Snap belum siap. Muat ulang halaman lalu coba lagi.');
                        return;
                    }

                    window.snap.pay(snapToken, {
                        onSuccess: syncStatus,
                        onPending: syncStatus,
                        onError: syncStatus
                    });
                });
            });
        </script>
    @endpush
@endif
