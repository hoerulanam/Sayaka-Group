@extends('layouts.admin')
@section('title', 'Detail Pesanan')
@section('page-title', 'Detail Pesanan #' . $order->id)

@section('content')
<div class="card" style="max-width:900px; margin:0 auto; padding:28px; background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:1px solid #f1f5f9; padding-bottom:16px; flex-wrap:wrap; gap:12px;">
        <a href="{{ route('admin.orders.index') }}" class="btn" style="background:#64748b; color:#fff; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:600; display:inline-block;">← Kembali ke Daftar</a>
        
        <div style="display:flex; gap:8px;">
            @if($order->status == 'pending')
                <form action="{{ route('admin.orders.confirm', $order) }}" method="post" style="display:inline;" data-confirm-title="Konfirmasi pesanan?" data-confirm-text="Pesanan #{{ $order->id }} akan masuk proses pengemasan.">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn" style="background:#22c55e; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:700; cursor:pointer; transition:background 0.2s;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">Konfirmasi Pesanan</button>
                </form>
            @elseif($order->status == 'confirmed')
                <form action="{{ route('admin.orders.ship', $order) }}" method="post" style="display:inline;" data-confirm-title="Kirim pesanan?" data-confirm-text="Pesanan #{{ $order->id }} akan ditandai sedang dikirim.">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn" style="background:#eab308; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:700; cursor:pointer; transition:background 0.2s;" onmouseover="this.style.background='#ca8a04'" onmouseout="this.style.background='#eab308'">Kirim Pesanan</button>
                </form>
            @elseif($order->status == 'shipped')
                <form action="{{ route('admin.orders.receive', $order) }}" method="post" style="display:inline;" data-confirm-title="Selesaikan pesanan?" data-confirm-text="Pesanan #{{ $order->id }} akan ditandai diterima.">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn" style="background:#64748b; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:700; cursor:pointer; transition:background 0.2s;" onmouseover="this.style.background='#475569'" onmouseout="this.style.background='#64748b'">Selesaikan Pesanan</button>
                </form>
            @endif
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:28px; align-items:start;">
        
        <!-- Kiri: Detail Item dan Pembayaran -->
        <div>
            <h3 style="margin-top:0; margin-bottom:16px; font-size:1.15rem; font-weight:700; color:#0f172a;">🛒 Item Pesanan</h3>
            <div style="border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:20px; background:#fff;">
                @foreach($order->items as $item)
                    @php
                        $variantAttribute = $item->selected_color ? 'Ukuran' : ($item->productVariant?->attributeName() ?? 'Varian');
                    @endphp
                    <div style="display:flex; gap:14px; padding:14px; border-bottom:1px solid #f1f5f9; align-items:center;
                        @if($loop->last) border-bottom:none; @endif
                    ">
                        <img src="{{ asset($item->product->thumbnail) }}" alt="{{ $item->product->name }}" style="width:60px; height:60px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; flex-shrink:0;">
                        <div style="flex:1; min-width:0;">
                            <h4 style="margin:0 0 4px 0; font-size:0.9rem; font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item->product->name }}</h4>
                            @if($item->variant_label ?? $item->product?->size)
                                <p style="margin:0 0 4px; font-size:0.8rem; color:#2563eb; font-weight:700;">{{ $variantAttribute }}: {{ $item->variant_label ?? $item->product?->size }}</p>
                            @endif
                            @if($item->selected_color)
                                <p style="margin:0 0 4px; font-size:0.8rem; color:#2563eb; font-weight:700;">Warna: {{ $item->selected_color }}</p>
                            @endif
                            <p style="margin:0; font-size:0.8rem; color:#64748b;">Jumlah: <strong>{{ $item->quantity }}</strong> | @ Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                            @if($item->review)
                                <div style="margin-top:10px; padding:10px; border-radius:8px; background:#f8fafc; border:1px solid #e2e8f0;">
                                    <p style="margin:0 0 4px; display:flex; align-items:center; gap:8px; font-size:0.78rem; color:#0f172a; font-weight:800;">
                                        <x-star-rating :rating="$item->review->rating" />
                                        <span>{{ $item->review->rating }}/5</span>
                                    </p>
                                    @if($item->review->review)
                                        <p style="margin:0; font-size:0.78rem; color:#475569; line-height:1.45;">{{ $item->review->review }}</p>
                                    @else
                                        <p style="margin:0; font-size:0.78rem; color:#64748b;">Customer hanya memberi rating tanpa komentar.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div style="font-weight:700; font-size:0.9rem; color:#0f172a; white-space:nowrap;">
                            Rp {{ number_format($item->quantity * $item->price, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Rincian Biaya -->
            @php
                $shippingCost = $order->shipping_cost ?? 0;
                $subtotalProducts = $order->total - $shippingCost;
            @endphp
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:18px; border-radius:12px;">
                <h4 style="margin:0 0 12px 0; font-size:0.95rem; font-weight:700; color:#0f172a;">Ringkasan Pembayaran</h4>
                <div style="display:grid; gap:10px; font-size:0.88rem; color:#475569;">
                    <div style="display:flex; justify-content:space-between;">
                        <span>Subtotal Produk:</span>
                        <span style="font-weight:600; color:#0f172a;">Rp {{ number_format($subtotalProducts, 0, ',', '.') }}</span>
                    </div>
                    @if($shippingCost > 0)
                    <div style="display:flex; justify-content:space-between;">
                        <span>Ongkos Kirim:</span>
                        <span style="font-weight:600; color:#0f172a;">Rp {{ number_format($shippingCost, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div style="display:flex; justify-content:space-between; border-top:1px dashed #cbd5e1; padding-top:10px; margin-top:4px;">
                        <span style="font-weight:700; color:#0f172a; font-size:0.95rem;">Total Tagihan:</span>
                        <span style="font-weight:800; color:#2563eb; font-size:1.1rem;">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanan: Detail User & Pengiriman -->
        <div style="display:grid; gap:20px;">
            
            <!-- Detail Akun Pelanggan -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:18px; border-radius:12px;">
                <h3 style="margin-top:0; margin-bottom:12px; font-size:1rem; font-weight:700; color:#0f172a; border-bottom:1px solid #e2e8f0; padding-bottom:6px;">👤 Akun Pelanggan</h3>
                <p style="margin:0 0 6px 0; font-size:0.88rem; color:#475569;"><strong>Nama:</strong> <span style="color:#0f172a;">{{ $order->user->name }}</span></p>
                <p style="margin:0 0 6px 0; font-size:0.88rem; color:#475569;"><strong>Email:</strong> <span style="color:#0f172a;">{{ $order->user->email }}</span></p>
                <p style="margin:0 0 6px 0; font-size:0.88rem; color:#475569;"><strong>Tanggal Order:</strong> <span style="color:#0f172a;">{{ $order->created_at->format('d M Y H:i') }}</span></p>
                <p style="margin:0; font-size:0.88rem; color:#475569;"><strong>Metode Bayar:</strong> <span style="color:#0f172a;">
                    @switch($order->payment_method)
                        @case('midtrans_snap')
                            💳 Midtrans Snap
                            @break
                        @case('qris')
                            📱 QRIS (Otomatis)
                            @break
                        @case('virtual_account')
                            🏛️ Virtual Account (Otomatis)
                            @break
                        @case('bank_transfer')
                            💵 Transfer Bank Manual
                            @break
                        @default
                            {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                    @endswitch
                </span></p>
            </div>

            <!-- Detail Pengiriman Alamat Kurir -->
            <div style="background:#eff6ff; border:1px solid #bfdbfe; padding:18px; border-radius:12px;">
                <h3 style="margin-top:0; margin-bottom:12px; font-size:1rem; font-weight:700; color:#1e40af; border-bottom:1px solid #bfdbfe; padding-bottom:6px;">🚚 Informasi Pengiriman</h3>
                
                @if($order->shipping_carrier)
                    <p style="margin:0 0 10px 0; font-size:0.88rem; color:#1e40af;"><strong>Ekspedisi Kurir:</strong><br><span style="color:#0f172a; font-weight:700; font-size:0.95rem;">{{ $order->shipping_carrier }} - {{ $order->shipping_service }}</span></p>
                    <p style="margin:0 0 10px 0; font-size:0.88rem; color:#1e40af;"><strong>No. HP Penerima:</strong><br><span style="color:#0f172a; font-weight:700; font-size:0.95rem;">{{ $order->phone ?? '-' }}</span></p>
                    <p style="margin:0; font-size:0.88rem; color:#1e40af;"><strong>Alamat Lengkap:</strong><br><span style="color:#1e293b; font-weight:500; display:block; margin-top:4px; line-height:1.4; background:#fff; padding:10px; border-radius:8px; border:1px solid #dbeafe;">{{ $order->shipping_address }}</span></p>
                @else
                    <p style="margin:0; font-size:0.88rem; color:#ef4444; font-weight:600;">Metode pengiriman konvensional / tidak diinput.</p>
                @endif
            </div>

            <!-- Detail Status Transaksi -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:18px; border-radius:12px;">
                <h3 style="margin-top:0; margin-bottom:12px; font-size:1rem; font-weight:700; color:#0f172a; border-bottom:1px solid #e2e8f0; padding-bottom:6px;">📈 Status Transaksi</h3>
                <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
                    <span style="font-size:0.85rem; color:#475569; font-weight:600; width:100px;">Status Order:</span>
                    <span style="padding:4px 10px; border-radius:20px; font-weight:700; font-size:11px;
                        @if($order->status === 'pending') background:#fef3c7; color:#d97706;
                        @elseif($order->status === 'confirmed') background:#dbeafe; color:#2563eb;
                        @elseif($order->status === 'shipped') background:#f0fdf4; color:#16a34a;
                        @elseif($order->status === 'received') background:#eff6ff; color:#1e40af; @endif
                    ">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <span style="font-size:0.85rem; color:#475569; font-weight:600; width:100px;">Pembayaran:</span>
                    <span style="padding:4px 10px; border-radius:20px; font-weight:700; font-size:11px;
                        @if($order->payment_status === 'paid') background:#dcfce7; color:#15803d;
                        @elseif(in_array($order->payment_status, ['failed', 'expired', 'cancelled', 'denied'], true)) background:#fee2e2; color:#b91c1c;
                        @else background:#fef3c7; color:#d97706; @endif
                    ">
                        @switch($order->payment_status)
                            @case('paid')
                                Terbayar
                                @break
                            @case('failed')
                                Gagal
                                @break
                            @case('expired')
                                Kedaluwarsa
                                @break
                            @case('cancelled')
                                Dibatalkan
                                @break
                            @case('denied')
                                Ditolak
                                @break
                            @default
                                Menunggu Pembayaran
                        @endswitch
                    </span>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
