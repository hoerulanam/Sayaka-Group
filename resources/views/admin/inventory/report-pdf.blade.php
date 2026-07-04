<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Inventaris</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #222;
            margin: 24px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 4px;
        }
        .header p {
            margin: 0;
            font-size: 0.95rem;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f3f3;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .footer {
            margin-top: 24px;
            font-size: 0.9rem;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Transaksi Inventaris</h2>
        <p>Periode: {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }} sampai {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}</p>
    </div>

    @if($records->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Barang</th>
                    <th>Kode Barang</th>
                    <th>Varian</th>
                    <th>Tipe</th>
                    <th>Jumlah</th>
                    <th>Keterangan</th>
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
                        <td>{{ $record->type == 'Masuk' ? 'Barang Masuk' : 'Barang Keluar' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">Tidak ada data untuk periode ini.</div>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
