@extends('layouts.admin')
@section('title', 'Kelola Pesanan')
@section('page-title', 'Kelola Pesanan')

@section('content')
<div class="admin-actions">
    <!-- if needed -->
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Total</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>{{ $order->user->name }}</td>
                <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                <td>{{ ucfirst($order->status) }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td class="actions">
                    <a href="{{ route('admin.orders.show', $order) }}" class="btn" style="background:#007bff;color:#fff;padding:5px 10px;text-decoration:none;border-radius:4px;">Lihat</a>
                    @if($order->status == 'pending')
                        <form action="{{ route('admin.orders.confirm', $order) }}" method="post" style="display:inline;" data-confirm-title="Konfirmasi pesanan?" data-confirm-text="Pesanan #{{ $order->id }} akan masuk proses pengemasan.">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn" style="background:#28a745;color:#fff;padding:5px 10px;border:none;border-radius:4px;">Konfirmasi</button>
                        </form>
                    @elseif($order->status == 'confirmed')
                        <form action="{{ route('admin.orders.ship', $order) }}" method="post" style="display:inline;" data-confirm-title="Kirim pesanan?" data-confirm-text="Pesanan #{{ $order->id }} akan ditandai sedang dikirim.">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn" style="background:#ffc107;color:#000;padding:5px 10px;border:none;border-radius:4px;">Kirim</button>
                        </form>
                    @elseif($order->status == 'shipped')
                        <form action="{{ route('admin.orders.receive', $order) }}" method="post" style="display:inline;" data-confirm-title="Selesaikan pesanan?" data-confirm-text="Pesanan #{{ $order->id }} akan ditandai diterima.">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn" style="background:#6c757d;color:#fff;padding:5px 10px;border:none;border-radius:4px;">Terima</button>
                        </form>
                    @endif

                    @if(in_array($order->status, ['pending', 'confirmed'], true))
                        <form action="{{ route('admin.orders.cancel', $order) }}" method="post" style="display:inline;" data-confirm-title="Batalkan pesanan?" data-confirm-text="Pesanan #{{ $order->id }} akan dibatalkan. Jika pesanan sudah dibayar, stok produk akan dikembalikan.">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn" style="background:#dc3545;color:#fff;padding:5px 10px;border:none;border-radius:4px;">Batalkan</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;">Belum ada pesanan.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $orders->links() }}
@endsection
