@extends('layouts.admin')
@section('title', 'Tambah Berita')
@section('page-title', 'Tambah Berita Baru')

@section('content')
<form action="{{ route('admin.news.store') }}" method="POST" enctype="multipart/form-data" id="news-form" class="form-admin">
    @csrf

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-group">
        <label for="title">Judul Berita *</label>
        <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" 
            placeholder="Masukkan judul berita" value="{{ old('title') }}" required>
        @error('title') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="thumbnail">Thumbnail</label>
        <input id="thumbnail" name="thumbnail" type="file" class="form-control @error('thumbnail') is-invalid @enderror" 
            accept="image/*">
        @error('thumbnail') <div class="error-msg">{{ $message }}</div> @enderror
        <small>Max: 2MB. Format: JPG, PNG, WebP</small>
    </div>

    <div class="form-group">
        <label for="published_at">Tanggal Publikasi</label>
        <input id="published_at" name="published_at" type="datetime-local" class="form-control" 
            value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}">
        <small>Kosongkan untuk gunakan waktu sekarang</small>
    </div>

    <div class="form-group">
        <label for="content">Konten *</label>
        <textarea id="content" name="content" rows="10" class="form-control @error('content') is-invalid @enderror" 
            data-required-message="Konten berita wajib diisi.">{{ old('content') }}</textarea>
        @error('content') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Simpan Berita</button>
        <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection


@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.0/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('news-form');
    const contentTextarea = document.getElementById('content');
    let newsEditor = null;

    const syncEditorContent = () => {
        if (newsEditor) {
            contentTextarea.value = newsEditor.getData();
        }
    };

    if (window.ClassicEditor) {
        ClassicEditor
            .create(contentTextarea, {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote'],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    ]
                }
            })
            .then(editor => {
                newsEditor = editor;
                editor.model.document.on('change:data', syncEditorContent);
                syncEditorContent();
            })
            .catch(error => console.error(error));
    }

    form.addEventListener('submit', function (event) {
        syncEditorContent();

        if (!contentTextarea.value.trim()) {
            event.preventDefault();
            contentTextarea.classList.add('is-invalid');
            const message = contentTextarea.dataset.requiredMessage || 'Konten wajib diisi.';

            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Lengkapi konten',
                    text: message,
                    confirmButtonColor: '#2f6fed'
                });
                return;
            }

            alert(message);
        }
    });
});
</script>
@endpush
