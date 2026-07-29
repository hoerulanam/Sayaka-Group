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

            @if(in_array($order->status, ['pending', 'confirmed'], true))
                <form action="{{ route('admin.orders.cancel', $order) }}" method="post" style="display:inline;" data-confirm-title="Batalkan pesanan ini?" data-confirm-text="Pesanan #{{ $order->id }} akan dibatalkan. Jika pesanan sudah dibayar, stok produk akan dikembalikan secara otomatis.">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn" style="background:#ef4444; color:#fff; padding:10px 18px; border:none; border-radius:8px; font-weight:700; cursor:pointer; transition:background 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">Batalkan Pesanan</button>
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

            @if($order->orderReturn)
                <!-- Detail Pengembalian Barang (Return) -->
                <div style="background:#fff7ed; border:1px solid #ffedd5; padding:18px; border-radius:12px;">
                    <h3 style="margin-top:0; margin-bottom:12px; font-size:1rem; font-weight:700; color:#c2410c; border-bottom:1px solid #ffedd5; padding-bottom:6px;">📦 Pengajuan Retur Barang</h3>

                    <p style="margin:0 0 6px 0; font-size:0.88rem; color:#475569;"><strong>Status Retur:</strong> <span style="font-weight:700; color:#c2410c;">{{ $order->orderReturn->statusLabel() }}</span></p>
                    <p style="margin:0 0 6px 0; font-size:0.88rem; color:#475569;"><strong>Alasan:</strong> <span style="color:#0f172a; font-weight:600;">{{ $order->orderReturn->reason }}</span></p>
                    <p style="margin:0 0 10px 0; font-size:0.88rem; color:#475569;"><strong>Deskripsi:</strong><br><span style="color:#1e293b; display:block; margin-top:4px; line-height:1.4; background:#fff; padding:8px; border-radius:6px; border:1px solid #fed7aa; font-size:0.85rem;">{{ $order->orderReturn->description }}</span></p>

                    @if($order->orderReturn->hasBankDetails())
                        <div style="margin-bottom:12px; background:#fff; padding:10px; border-radius:6px; border:1px solid #fed7aa;">
                            <strong style="font-size:0.82rem; color:#9a3412; display:block; margin-bottom:4px;">💳 Rekening Refund Pelanggan:</strong>
                            <p style="margin:0; font-size:0.85rem; color:#0f172a;">
                                <strong>{{ $order->orderReturn->bank_name }}</strong> - {{ $order->orderReturn->bank_account_number }}
                                <br><small style="color:#64748b;">a/n {{ $order->orderReturn->bank_account_holder }}</small>
                            </p>
                        </div>
                    @endif

                    @if($order->orderReturn->proof_image)
                        <div style="margin-bottom:12px;">
                            <strong style="font-size:0.85rem; color:#475569; display:block; margin-bottom:4px;">Foto Bukti Barang Cacat/Rusak:</strong>
                            <a href="{{ asset($order->orderReturn->proof_image) }}" target="_blank">
                                <img src="{{ asset($order->orderReturn->proof_image) }}" alt="Foto Bukti" style="max-width:100%; max-height:160px; object-fit:cover; border-radius:8px; border:1px solid #fdba74;">
                            </a>
                        </div>
                    @endif

                    @if($order->orderReturn->status === 'completed' && $order->orderReturn->refund_proof_image)
                        <div style="margin-bottom:12px; background:#f0fdf4; padding:10px; border-radius:6px; border:1px solid #86efac;">
                            <strong style="font-size:0.82rem; color:#166534; display:block; margin-bottom:4px;">💐 Bukti Transfer Refund Admin:</strong>
                            @if($order->orderReturn->refund_amount)
                                <p style="margin:0 0 6px 0; font-size:0.88rem; color:#14532d;">
                                    Nominal Refund: <strong>Rp {{ number_format($order->orderReturn->refund_amount, 0, ',', '.') }}</strong>
                                </p>
                            @endif
                            <a href="{{ asset($order->orderReturn->refund_proof_image) }}" target="_blank">
                                <img src="{{ asset($order->orderReturn->refund_proof_image) }}" alt="Bukti Transfer Refund" style="max-width:100%; max-height:160px; object-fit:cover; border-radius:8px; border:1px solid #4ade80;">
                            </a>
                        </div>
                    @endif

                    @if($order->orderReturn->admin_note)
                        <div style="margin-bottom:12px; background:#fff; padding:10px; border-radius:6px; border:1px solid #fed7aa;">
                            <strong style="font-size:0.82rem; color:#9a3412; display:block;">Catatan / Alasan Admin:</strong>
                            <p style="margin:2px 0 0 0; font-size:0.85rem; color:#431407;">{{ $order->orderReturn->admin_note }}</p>
                        </div>
                    @endif

                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                        @if($order->orderReturn->status === 'pending')
                            <button type="button" class="btn" onclick="handleApproveReturn('{{ route('admin.returns.approve', $order->orderReturn) }}')" style="background:#22c55e; color:#fff; padding:9px 16px; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                                &#10004; Setujui Retur
                            </button>

                            <button type="button" class="btn" onclick="handleRejectReturn('{{ route('admin.returns.reject', $order->orderReturn) }}')" style="background:#ef4444; color:#fff; padding:9px 16px; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                                &#10006; Tolak Retur
                            </button>
                        @elseif($order->orderReturn->status === 'approved')
                            <button type="button" class="btn" onclick="openCompleteReturnModal({{ $order->orderReturn->id }}, {{ $order->total }})" style="background:#0284c7; color:#fff; padding:9px 16px; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:0.85rem; display:inline-flex; align-items:center; gap:6px;">
                                &#10004; Tandai Diterima & Refund Selesai
                            </button>
                        @endif
                    </div>
                </div>
            @endif

        </div>

    </div>
</div>

<!-- Modal Complete Return (Upload Bukti Refund) -->
<div id="completeReturnModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 12px; padding: 1.5rem; max-width: 520px; width: 90%; margin: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 1.15rem; color: #0f172a;">Selesaikan Retur & Refund Dana</h3>
        <p style="font-size: 0.88rem; color: #64748b; margin-bottom: 1.25rem; line-height: 1.4;">
            Unggah foto/struk bukti transfer pengembalian dana dan tentukan nominal refund untuk dikirim ke pelanggan.
        </p>

        <form id="completeReturnForm" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 0.4rem;">Nominal Refund (Rp)</label>
                <input type="number" name="refund_amount" id="modalRefundAmount" step="1000" min="0" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.6rem; font-size: 0.95rem; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 0.4rem;">Upload Struk / Bukti Transfer Refund (Opsional)</label>
                <input type="file" name="refund_proof_image" accept="image/jpeg,image/png,image/jpg,image/webp" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.5rem; background: #f8fafc; box-sizing: border-box;">
                <small style="color: #64748b; font-size: 0.78rem; display: block; margin-top: 0.25rem;">Format JPG, PNG, WEBP. Maksimal 3 MB.</small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn" style="background: #e2e8f0; color: #334155; padding: 8px 16px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;" onclick="closeCompleteReturnModal()">Batal</button>
                <button type="submit" class="btn" style="background: #0284c7; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">Proses & Selesaikan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCompleteReturnModal(id, defaultAmount) {
    const form = document.getElementById('completeReturnForm');
    form.action = `/admin/returns/${id}/complete`;
    document.getElementById('modalRefundAmount').value = defaultAmount || '';
    document.getElementById('completeReturnModal').style.display = 'flex';
}
function closeCompleteReturnModal() {
    document.getElementById('completeReturnModal').style.display = 'none';
}
</script>

<script>
function handleApproveReturn(url) {
    if (window.Swal) {
        Swal.fire({
            title: 'Setujui Pengembalian Barang',
            html: `
                <div style="text-align: left; margin-top: 10px;">
                    <label style="display: block; font-weight: 600; font-size: 0.88rem; color: #334155; margin-bottom: 6px;">
                        Catatan Admin / Alamat Instruksi Retur (Opsional):
                    </label>
                    <textarea id="swal-admin-approve-note" class="swal2-textarea" style="width: 100%; height: 110px; margin: 0; padding: 10px; font-family: inherit; font-size: 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; resize: vertical;" placeholder="Pengajuan pengembalian barang disetujui. Silakan kemaskan dan kirimkan barang ke alamat gudang SAYAKA GROUP: Jalan Industri No 45, Jakarta..."></textarea>
                </div>
            `,
            icon: 'info',
            iconColor: '#22c55e',
            showCancelButton: true,
            confirmButtonText: 'Konfirmasi Setuju',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#64748b',
            focusConfirm: false,
            preConfirm: () => {
                return document.getElementById('swal-admin-approve-note').value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PATCH';
                form.appendChild(methodInput);

                const noteInput = document.createElement('input');
                noteInput.type = 'hidden';
                noteInput.name = 'admin_note';
                noteInput.value = result.value || '';
                form.appendChild(noteInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}

function handleRejectReturn(url) {
    if (window.Swal) {
        Swal.fire({
            title: 'Tolak Pengembalian Barang',
            html: `
                <div style="text-align: left; margin-top: 10px;">
                    <label style="display: block; font-weight: 600; font-size: 0.88rem; color: #334155; margin-bottom: 6px;">
                        Alasan Penolakan <span style="color: #ef4444;">*</span>:
                    </label>
                    <textarea id="swal-admin-reject-note" class="swal2-textarea" style="width: 100%; height: 110px; margin: 0; padding: 10px; font-family: inherit; font-size: 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; resize: vertical;" placeholder="Jelaskan alasan penolakan retur (misal: Foto bukti tidak jelas, produk sudah dipakai, dll)..."></textarea>
                </div>
            `,
            icon: 'warning',
            iconColor: '#ef4444',
            showCancelButton: true,
            confirmButtonText: 'Konfirmasi Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            focusConfirm: false,
            preConfirm: () => {
                const note = document.getElementById('swal-admin-reject-note').value.trim();
                if (!note) {
                    Swal.showValidationMessage('Alasan penolakan wajib diisi!');
                    return false;
                }
                return note;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PATCH';
                form.appendChild(methodInput);

                const noteInput = document.createElement('input');
                noteInput.type = 'hidden';
                noteInput.name = 'admin_note';
                noteInput.value = result.value;
                form.appendChild(noteInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}
</script>
@endsection
