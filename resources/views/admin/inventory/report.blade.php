@extends('layouts.admin')
@section('title', 'Inventaris - Laporan')
@section('page-title', 'Laporan Inventaris')
@section('body-class', 'admin-inventory-report-layout')

@section('content')
@php
    $incomingTotal = $records->where('type', 'Masuk')->sum('quantity');
    $outgoingTotal = $records->where('type', 'Keluar')->sum('quantity');
@endphp

<section class="admin-report-page">
    <aside class="admin-report-panel admin-report-filter-panel">
        <div class="admin-report-panel-head">
            <span>Filter Laporan</span>
            <h2>Pilih Tanggal</h2>
            <p>Pilih rentang tanggal untuk mencetak laporan inventaris.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.inventory.report.pdf') }}" method="get" class="admin-report-form" data-no-loading="true">
            <div class="form-group">
                <label for="date_start" class="form-label">Tanggal Awal</label>
                <input id="date_start" name="date_start" type="date" class="form-control" value="{{ old('date_start', now()->startOfMonth()->toDateString()) }}" required>
            </div>
            <div class="form-group">
                <label for="date_end" class="form-label">Tanggal Akhir</label>
                <input id="date_end" name="date_end" type="date" class="form-control" value="{{ old('date_end', now()->toDateString()) }}" required>
            </div>
            <div class="admin-report-actions">
                <button type="submit" class="btn btn-primary" formaction="{{ route('admin.inventory.report.pdf') }}">Cetak PDF</button>
                <button type="submit" class="btn btn-secondary" formaction="{{ route('admin.inventory.report.excel') }}">Export Excel</button>
            </div>
        </form>

        <div class="admin-report-summary">
            <div>
                <strong>{{ number_format($records->count(), 0, ',', '.') }}</strong>
                <span>Transaksi</span>
            </div>
            <div>
                <strong>{{ number_format($incomingTotal, 0, ',', '.') }}</strong>
                <span>Barang masuk</span>
            </div>
            <div>
                <strong>{{ number_format($outgoingTotal, 0, ',', '.') }}</strong>
                <span>Barang keluar</span>
            </div>
        </div>
    </aside>

    <div class="admin-report-panel admin-report-table-panel">
        <div class="admin-report-panel-head">
            <span>Riwayat</span>
            <h2>Riwayat Inventaris</h2>
            <p>Daftar transaksi barang masuk dan keluar terbaru.</p>
        </div>

        @if($records->count() > 0)
            <div class="admin-report-table-wrap">
                <table class="report-table admin-report-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 30%;">Nama Barang</th>
                            <th style="width: 20%;">Kode Barang</th>
                            <th style="width: 15%;">Varian</th>
                            <th style="width: 10%;">Tipe</th>
                            <th style="width: 15%;">Jumlah</th>
                            <th style="width: 20%;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}</td>
                                <td>{{ $record->inventoryItem->name }}</td>
                                <td>{{ $record->inventoryItem->code }}</td>
                                <td>{{ $record->inventoryItem->variantLabel() ?? '-' }}</td>
                                <td>{{ $record->inventoryItem->variantLabel() ? $record->inventoryItem->variantAttributeName() : '-' }}</td>
                                <td>{{ $record->quantity }}</td>
                                <td>
                                    @if($record->type == 'Masuk')
                                        <span class="badge bg-success">Barang Masuk</span>
                                    @else
                                        <span class="badge bg-danger">Barang Keluar</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="admin-report-empty">
                <strong>Belum ada riwayat transaksi inventaris.</strong>
                <span>Data akan muncul setelah barang masuk atau barang keluar dicatat.</span>
            </div>
        @endif
    </div>
</section>
@endsection
