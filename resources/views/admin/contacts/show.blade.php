@extends('layouts.admin')
@section('title', 'Detail Pesan')
@section('page-title', 'Detail Pesan')

@section('content')
<div class="card" style="max-width:800px; margin:0 auto; padding:24px;">
    <a href="{{ route('admin.contacts.index') }}" class="btn" style="background:#999;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;display:inline-block;margin-bottom:16px;">← Kembali ke Daftar Pesan</a>

    <div style="background:#f9f9f9; padding:20px; border-radius:8px; margin-bottom:20px;">
        <h3 style="margin-top:0; color:#333;">Dari: {{ $contact->name }}</h3>
        
        <div style="margin:16px 0;">
            <p style="margin:8px 0; color:#666;"><strong>Email:</strong></p>
            <p style="margin:8px 0;"><a href="mailto:{{ $contact->email }}" style="color:#0066cc; word-break:break-all;">{{ $contact->email }}</a></p>
        </div>

        <div style="margin:16px 0;">
            <p style="margin:8px 0; color:#666;"><strong>Tanggal Dikirim:</strong></p>
            <p style="margin:8px 0;">{{ $contact->created_at->format('d M Y - H:i') }}</p>
        </div>

        <hr style="margin:20px 0; border:none; border-top:1px solid #ddd;">

        <div style="margin:16px 0;">
            <p style="margin:8px 0; color:#666; font-weight:bold;">Pesan:</p>
            <p style="margin:12px 0; line-height:1.6; white-space:pre-wrap; word-wrap:break-word;">{{ $contact->message }}</p>
        </div>
    </div>

    <div style="display:flex; gap:10px;">
        <form action="{{ route('admin.contacts.destroy', $contact->id) }}" method="POST" style="display:inline;" data-confirm-title="Hapus pesan?" data-confirm-text="Pesan dari {{ $contact->name }} akan dihapus.">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Hapus Pesan</button>
        </form>
        <button type="button" class="btn btn-primary" onclick="window.location.href='mailto:{{ $contact->email }}'">Balas Email</button>
    </div>
</div>

@endsection
