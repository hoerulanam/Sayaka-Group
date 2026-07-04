@extends('layouts.admin')
@section('title', 'Inventaris - Barang Keluar')
@section('page-title', 'Barang Keluar')
@section('body-class', 'admin-inventory-incoming-layout admin-inventory-outgoing-layout')

@section('content')
<section class="admin-incoming-page">
    <div class="admin-incoming-hero">
        <div>
            <span>Inventaris</span>
            <h2>Barang Keluar</h2>
            <p>Catat pengurangan stok dari barang aktif dan pantau riwayat keluar terbaru.</p>
        </div>
        <div class="admin-incoming-summary">
            <div>
                <strong>{{ number_format($inventoryItems->count(), 0, ',', '.') }}</strong>
                <span>Item inventaris</span>
            </div>
            <div>
                <strong>{{ number_format($outgoingRecords->sum('quantity'), 0, ',', '.') }}</strong>
                <span>Total keluar</span>
            </div>
        </div>
    </div>

    <div class="admin-incoming-layout">
        <div class="admin-incoming-panel admin-incoming-form-panel">
            <div class="admin-incoming-panel-head">
                <div>
                    <span>Form Input</span>
                    <h3>Tambah Barang Keluar</h3>
                </div>
                <a href="{{ route('admin.inventory.index') }}">Data Barang</a>
            </div>

            <form action="{{ route('admin.inventory.outgoing.store') }}" method="POST" class="admin-incoming-form">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="form-group">
                    <label for="inventory_item_id" class="form-label">Barang</label>
                    <select name="inventory_item_id" id="inventory_item_id" class="form-control" required>
                        <option value="">Pilih Barang</option>
                        @foreach($inventoryItems as $item)
                            <option value="{{ $item->id }}" {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}
                                @if($item->variantLabel())
                                    - {{ $item->variantAttributeName() }}: {{ $item->variantLabel() }}
                                @endif
                                (Kode: {{ $item->code ?? '-' }}) - Stok: {{ $item->stock }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="admin-incoming-inline-fields">
                    <div class="form-group">
                        <label for="quantity" class="form-label">Jumlah Keluar</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="{{ old('quantity') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="date" class="form-label">Tanggal</label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="admin-incoming-actions">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('admin.inventory.report') }}" class="btn btn-secondary">Laporan</a>
                </div>
            </form>
        </div>

        <aside class="admin-incoming-panel admin-incoming-history-panel">
            <div class="admin-incoming-panel-head">
                <div>
                    <span>Riwayat</span>
                    <h3>Barang Keluar Terbaru</h3>
                </div>
                <a href="{{ route('admin.inventory.report') }}">Lihat Semua</a>
            </div>

            <div class="admin-incoming-history-list">
                @forelse($outgoingRecords as $record)
                    @php
                        $item = $record->inventoryItem;
                        $variantLabel = $item?->variantLabel();
                        $variantType = $variantLabel ? $item?->variantAttributeName() : null;
                    @endphp

                    <div class="admin-incoming-history-item">
                        <div>
                            <strong>{{ $item->name ?? 'Barang tidak tersedia' }}</strong>
                            <span>
                                {{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}
                                @if($item?->code)
                                    - {{ $item->code }}
                                @endif
                                @if($variantLabel)
                                    - {{ $variantType }}: {{ $variantLabel }}
                                @endif
                            </span>
                        </div>
                        <span>-{{ number_format($record->quantity, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="admin-incoming-empty">Belum ada riwayat barang keluar.</div>
                @endforelse
            </div>
        </aside>
    </div>
</section>
@endsection
