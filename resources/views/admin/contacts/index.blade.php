@extends('layouts.admin')
@section('title', 'Kelola Pesan')
@section('page-title', 'Kelola Pesan')

@section('content')
<div class="admin-actions">
    <form id="bulk-delete-form" action="{{ route('admin.contacts.destroyAll') }}" method="POST" style="display:inline;" data-confirm-title="Hapus pesan terpilih?" data-confirm-text="Pesan yang dipilih akan dihapus.">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger" style="display:none;" id="bulk-delete-btn">
            Hapus yang Dipilih
        </button>
    </form>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th style="width:40px;"><input type="checkbox" id="select-all"></th>
            <th>ID</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Pesan</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($messages as $item)
        <tr>
            <td><input type="checkbox" class="message-checkbox" value="{{ $item->id }}" form="bulk-delete-form" name="ids[]"></td>
            <td>{{ $item->id }}</td>
            <td><strong>{{ $item->name }}</strong></td>
            <td><a href="mailto:{{ $item->email }}" style="color:#0066cc; text-decoration:none;">{{ $item->email }}</a></td>
            <td>
                <p style="color:#666; margin:0; max-width:300px; white-space:normal;">
                    {{ Str::limit($item->message, 80) }}
                </p>
            </td>
            <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
            <td class="actions">
                <a href="{{ route('admin.contacts.show', $item->id) }}" class="btn btn-sm btn-warning">Lihat</a>
                <form action="{{ route('admin.contacts.destroy', $item->id) }}" method="POST" style="display:inline;" data-confirm-title="Hapus pesan?" data-confirm-text="Pesan dari {{ $item->name }} akan dihapus.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center; padding:20px;">Belum ada pesan.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{ $messages->links() }}

<script>
document.getElementById('select-all').addEventListener('change', function() {
    const isChecked = this.checked;
    document.querySelectorAll('.message-checkbox').forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    updateDeleteButton();
});

document.querySelectorAll('.message-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateDeleteButton);
});

function updateDeleteButton() {
    const checkedBoxes = document.querySelectorAll('.message-checkbox:checked').length;
    const deleteBtn = document.getElementById('bulk-delete-btn');
    deleteBtn.style.display = checkedBoxes > 0 ? 'inline-block' : 'none';
}
</script>

@endsection
