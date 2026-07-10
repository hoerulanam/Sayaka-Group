@extends('layouts.admin')
@section('title', 'Edit User')
@section('page-title', 'Edit User: ' . $user->name)

@section('content')
<form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="form-admin">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0; padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-group">
        <label for="name">Nama Lengkap *</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" 
            placeholder="Masukkan nama lengkap" value="{{ old('name', $user->name) }}" required>
        @error('name') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="email">Email *</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" 
            placeholder="Masukkan email" value="{{ old('email', $user->email) }}" required>
        @error('email') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="password">Password (Kosongkan jika tidak ingin mengganti)</label>
        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" 
            placeholder="Masukkan password baru (minimal 8 karakter)">
        @error('password') <div class="error-msg">{{ $message }}</div> @enderror
        <small style="color:#666;">Biarkan kosong jika tidak perlu mengubah password</small>
    </div>

    <div class="form-group">
        <label for="password_confirmation">Konfirmasi Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" 
            placeholder="Konfirmasi password baru">
    </div>

    @if(auth()->user()->isSuperAdmin())
    <div class="form-group">
        <label for="is_admin">
            <input id="is_admin" name="is_admin" type="checkbox" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
            Admin (dapat mengakses panel admin)
        </label>
    </div>
    @endif

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="{{ route('admin.users.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;">Batal</a>
    </div>
</form>
@endsection
