@extends('layouts.admin')
@section('title', 'Inventaris - Data Barang')
@section('page-title', 'Data Barang')

@section('content')
<section class="card section">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
            <h2 style="margin:0 0 6px 0;">Data Barang</h2>
            <p class="text-muted mb-0">Pantau stok barang inventaris yang tersedia.</p>
        </div>
        <a href="{{ route('admin.inventory.incoming') }}" class="btn btn-primary">Tambah Barang Masuk</a>
    </div>

    <div class="card-body">
        <div style="overflow-x:auto;">
            <table class="admin-table" style="width:100%; min-width:720px;">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th style="width:140px;">Kode</th>
                        <th>Nama Barang</th>
                        <th style="width:150px;">Varian</th>
                        <th style="width:90px;">Tipe</th>
                        <th style="width:120px; text-align:right;">Stok</th>
                        <th style="width:130px;">Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventoryItems as $index => $inventoryItem)
                    <tr id="row-{{ $inventoryItem->id }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $inventoryItem->code ?? '-' }}</td>
                        <td>
                            <strong style="color:#172033;">{{ $inventoryItem->name }}</strong>
                        </td>
                        <td>{{ $inventoryItem->variantLabel() ?? '-' }}</td>
                        <td>{{ $inventoryItem->variantLabel() ? $inventoryItem->variantAttributeName() : '-' }}</td>
                        <td style="text-align:right;">
                            <span class="badge {{ ($inventoryItem->stock ?? 0) > 0 ? 'bg-success' : 'bg-danger' }}">
                                {{ $inventoryItem->stock ?? 0 }}
                            </span>
                        </td>
                        <td>
                            <form id="delete-form-{{ $inventoryItem->id }}" action="{{ route('admin.inventory.destroy', $inventoryItem) }}" method="POST" data-confirm-title="Hapus barang?" data-confirm-text="Barang {{ $inventoryItem->name }} akan dihapus dari inventaris.">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; color:#64748b; padding:28px;">
                            Belum ada data barang. Tambahkan melalui menu Barang Masuk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
