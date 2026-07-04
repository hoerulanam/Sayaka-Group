@extends('layouts.admin')
@section('title', 'Kelola User')
@section('page-title', 'Kelola User')

@section('content')
<div class="admin-actions">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Tambah User</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Terdaftar</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>
                @if($user->is_admin)
                    <span style="color:#059669; font-weight:bold;">Admin</span>
                @else
                    <span style="color:#6b7280;">User</span>
                @endif
            </td>
            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
            <td class="actions">
                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm" style="background:#6b7280;color:#fff;padding:6px 12px;text-decoration:none;border-radius:4px;font-size:12px;">Lihat</a>
                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-warning">Edit</a>
                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;" data-confirm-title="Hapus user?" data-confirm-text="User {{ $user->name }} akan dihapus.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center; padding:20px;">Belum ada user.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{ $users->links() }}

@endsection
