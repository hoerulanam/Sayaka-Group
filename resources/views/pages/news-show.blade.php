@extends('layouts.app')

@section('title', $news->title . ' - Berita - SAYAKA GROUP')

@section('content')
<section class="news-detail-page">
    <div class="news-detail-wrap">
        <a href="{{ route('news.index') }}" class="news-detail-back">Kembali ke Berita</a>

        <article class="news-detail-article">
            <header class="news-detail-header">
                <span class="news-page-eyebrow">Berita SAYAKA GROUP</span>
                <h1>{{ $news->title }}</h1>
                <time datetime="{{ $news->published_at?->toDateString() ?? $news->created_at?->toDateString() }}">
                    {{ $news->published_at?->format('d M Y') ?? $news->created_at?->format('d M Y') }}
                </time>
            </header>

            <div class="news-detail-image">
                @if($news->thumbnail)
                    <img src="{{ asset($news->thumbnail) }}" alt="{{ $news->title }}" loading="eager" decoding="async">
                @else
                    <div class="news-page-placeholder">SAYAKA GROUP</div>
                @endif
            </div>

            <div class="news-detail-content">
                {!! $news->content !!}
            </div>
        </article>

        @if($relatedNews->count())
            <aside class="news-related-section">
                <div class="news-page-heading">
                    <div>
                        <span class="news-page-eyebrow">Berita Lainnya</span>
                        <h2>Artikel terbaru</h2>
                    </div>
                    <p>Lanjutkan membaca kabar produk, kegiatan, dan update dari SAYAKA GROUP.</p>
                </div>

                <div class="news-page-grid">
                    @foreach($relatedNews as $item)
                        <a href="{{ route('news.show', $item->slug) }}" class="news-page-card">
                            <div class="news-page-image">
                                @if($item->thumbnail)
                                    <img src="{{ asset($item->thumbnail) }}" alt="{{ $item->title }}" loading="lazy" decoding="async">
                                @else
                                    <div class="news-page-placeholder">Berita</div>
                                @endif
                            </div>

                            <div class="news-page-body">
                                <span class="news-page-date">{{ $item->published_at?->format('d M Y') ?? 'Tanggal belum tersedia' }}</span>
                                <h3>{{ $item->title }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($item->content), 130) }}</p>
                                <span class="news-read-link">Baca Detail</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </aside>
        @endif
    </div>
</section>
@endsection
