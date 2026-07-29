@extends('layouts.admin')
@section('title', 'Pengembalian Barang (Return)')
@section('page-title', 'Pengembalian Barang (Return)')

@section('content')
<section class="admin-page-section">
    <div class="admin-page-header">
        <div>
            <h2>Daftar Pengajuan Return Barang</h2>
            <p>Kelola dan berikan konfirmasi persetujuan untuk barang yang diajukan retur oleh pelanggan.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            {{ session('error') }}
        </div>
    @endif

    <div class="admin-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="{{ route('admin.returns.index', ['status' => 'all']) }}" class="btn {{ $status === 'all' ? 'btn-primary' : 'btn-outline' }}">
            Semua ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.returns.index', ['status' => 'pending']) }}" class="btn {{ $status === 'pending' ? 'btn-primary' : 'btn-outline' }}">
            Menunggu Konfirmasi ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('admin.returns.index', ['status' => 'approved']) }}" class="btn {{ $status === 'approved' ? 'btn-primary' : 'btn-outline' }}">
            Disetujui ({{ $counts['approved'] }})
        </a>
        <a href="{{ route('admin.returns.index', ['status' => 'rejected']) }}" class="btn {{ $status === 'rejected' ? 'btn-primary' : 'btn-outline' }}">
            Ditolak ({{ $counts['rejected'] }})
        </a>
        <a href="{{ route('admin.returns.index', ['status' => 'completed']) }}" class="btn {{ $status === 'completed' ? 'btn-primary' : 'btn-outline' }}">
            Selesai ({{ $counts['completed'] }})
        </a>
    </div>

    <div class="admin-table-card">
        @if($returns->isEmpty())
            <div style="padding: 2.5rem; text-align: center; color: var(--text-muted, #64748b);">
                <p>Tidak ada pengajuan pengembalian barang yang ditemukan.</p>
            </div>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Alasan Return</th>
                        <th>Rekening Refund</th>
                        <th>Bukti Foto</th>
                        <th>Status</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returns as $ret)
                        <tr>
                            <td>
                                <strong>{{ $ret->order?->displayCode() ?? '-' }}</strong>
                            </td>
                            <td>
                                <strong>{{ $ret->user?->name ?? 'User' }}</strong>
                                <br><small style="color: #64748b;">{{ $ret->user?->email }}</small>
                            </td>
                            <td>
                                <strong>{{ $ret->reason }}</strong>
                                <p style="font-size: 0.85rem; color: #475569; margin-top: 0.25rem; max-width: 250px;">
                                    {{ Str::limit($ret->description, 70) }}
                                </p>
                            </td>
                            <td>
                                @if($ret->hasBankDetails())
                                    <strong style="color: #0f172a; font-size: 0.88rem;">{{ $ret->bank_name }}</strong>
                                    <br><span style="font-size: 0.85rem; color: #334155;">{{ $ret->bank_account_number }}</span>
                                    <br><small style="color: #64748b;">a/n {{ $ret->bank_account_holder }}</small>
                                @else
                                    <span style="font-size: 0.85rem; color: #94a3b8;">-</span>
                                @endif
                            </td>
                            <td>
                                @if($ret->proof_image)
                                    <a href="{{ asset($ret->proof_image) }}" target="_blank" title="Lihat Foto Bukti Barang">
                                        <img src="{{ asset($ret->proof_image) }}" alt="Bukti Return" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    </a>
                                @else
                                    <span style="font-size: 0.85rem; color: #94a3b8;">Tidak ada foto</span>
                                @endif

                                @if($ret->refund_proof_image)
                                    <br>
                                    <a href="{{ asset($ret->refund_proof_image) }}" target="_blank" title="Lihat Bukti Transfer Refund" style="font-size: 0.75rem; color: #16a34a; font-weight: 600; text-decoration: underline;">
                                        Bukti Refund
                                    </a>
                                @endif
                            </td>
                            <td>
                                <span class="order-status-badge is-{{ $ret->status }}">
                                    {{ $ret->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                {{ $ret->created_at->format('d M Y H:i') }}
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="{{ route('admin.orders.show', $ret->order_id) }}" class="btn btn-sm btn-outline">
                                        Detail Order
                                    </a>

                                    @if($ret->status === 'pending')
                                        <button type="button" class="btn btn-sm btn-primary" onclick="handleApproveReturn('/admin/returns/{{ $ret->id }}/approve')">
                                            Setujui
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="handleRejectReturn('/admin/returns/{{ $ret->id }}/reject')">
                                            Tolak
                                        </button>
                                    @elseif($ret->status === 'approved')
                                        <button type="button" class="btn btn-sm btn-success" onclick="openCompleteReturnModal({{ $ret->id }}, {{ $ret->order?->total ?? 0 }})">
                                            Tandai Diterima / Selesai
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="padding: 1rem;">
                {{ $returns->links() }}
            </div>
        @endif
    </div>
</section>

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
                <button type="button" class="btn btn-outline" onclick="closeCompleteReturnModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Proses & Selesaikan</button>
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
