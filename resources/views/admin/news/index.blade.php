@extends('layouts.admin')
@section('title', 'Kelola Berita')
@section('page-title', 'Kelola Berita')

@section('content')
<div class="admin-actions">
    <a href="{{ route('admin.news.create') }}" class="btn btn-primary">+ Tambah Berita</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Judul</th>
            <th>Thumbnail</th>
            <th>Tanggal Publikasi</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($news as $item)
        <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->title }}</td>
            <td>
                @if($item->thumbnail)
                    <img src="{{ asset($item->thumbnail) }}" alt="{{ $item->title }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                @else
                    <span style="color: #999;">-</span>
                @endif
            </td>
            <td>{{ $item->published_at?->format('d/m/Y H:i') ?? '-' }}</td>
            <td class="actions">
                <a href="{{ route('admin.news.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                <form action="{{ route('admin.news.destroy', $item->id) }}" method="POST" style="display:inline;" data-confirm-title="Hapus berita?" data-confirm-text="Berita {{ $item->title }} akan dihapus.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align:center; padding:20px;">Belum ada berita.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
