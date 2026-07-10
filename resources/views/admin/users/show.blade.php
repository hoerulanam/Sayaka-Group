@extends('layouts.admin')
@section('title', 'Detail User')
@section('page-title', 'Detail User: ' . $user->name)

@section('content')
<div class="card" style="max-width:600px; margin:0 auto; padding:24px;">
    <a href="{{ route('admin.users.index') }}" class="btn" style="background:#999;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;display:inline-block;margin-bottom:20px;">← Kembali ke Daftar User</a>

    <div style="background:#f9fafb; padding:20px; border-radius:8px; margin-bottom:20px;">
        <div style="margin-bottom:16px;">
            <h3 style="margin:0 0 8px; color:#444; font-size:14px; font-weight:600; text-transform:uppercase;">Nama</h3>
            <p style="margin:0; color:#111; font-size:16px;">{{ $user->name }}</p>
        </div>

        <div style="margin-bottom:16px;">
            <h3 style="margin:0 0 8px; color:#444; font-size:14px; font-weight:600; text-transform:uppercase;">Email</h3>
            <p style="margin:0; color:#111; font-size:16px;">{{ $user->email }}</p>
        </div>

        <div style="margin-bottom:16px;">
            <h3 style="margin:0 0 8px; color:#444; font-size:14px; font-weight:600; text-transform:uppercase;">Role</h3>
            <p style="margin:0; color:#111; font-size:16px;">
                @if($user->isSuperAdmin())
                    <span style="color:#2563eb; font-weight:bold;">Superadmin</span>
                @elseif($user->is_admin)
                    <span style="color:#059669; font-weight:bold;">Admin</span>
                @else
                    <span style="color:#6b7280;">User</span>
                @endif
            </p>
        </div>

        <div style="margin-bottom:16px;">
            <h3 style="margin:0 0 8px; color:#444; font-size:14px; font-weight:600; text-transform:uppercase;">Terdaftar</h3>
            <p style="margin:0; color:#111; font-size:16px;">{{ $user->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div style="margin-bottom:0;">
            <h3 style="margin:0 0 8px; color:#444; font-size:14px; font-weight:600; text-transform:uppercase;">Email Terverifikasi</h3>
            <p style="margin:0; color:#111; font-size:16px;">
                @if($user->email_verified_at)
                    <span style="color:green; font-weight:bold;">✓ {{ $user->email_verified_at->format('d/m/Y H:i') }}</span>
                @else
                    <span style="color:#999;">Belum terverifikasi</span>
                @endif
            </p>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary">Edit User</a>
        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;" data-confirm-title="Hapus user?" data-confirm-text="User {{ $user->name }} akan dihapus.">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus User</button>
        </form>
    </div>
</div>
@endsection
