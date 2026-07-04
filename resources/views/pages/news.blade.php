@extends('layouts.app')

@section('title', 'Berita & Kegiatan - SAYAKA GROUP')

@section('content')
@php
    $featuredNews = $news->first();
    $otherNews = $news->skip(1);
@endphp

<section class="news-page">
    <div class="news-page-wrap">
        <div class="news-page-hero">
            <div class="news-page-hero-copy">
                <span class="news-page-eyebrow">Berita & Kegiatan</span>
                <h1>Update terbaru dari SAYAKA GROUP</h1>
                <p>Ikuti informasi produk, aktivitas brand, dan kabar terbaru yang membantu customer mengenal SAYAKA GROUP dengan lebih mudah.</p>
            </div>

            <div class="news-page-stats" aria-label="Ringkasan berita">
                <div>
                    <strong>{{ $news->count() }}</strong>
                    <span>Artikel tersedia</span>
                </div>
                <div>
                    <strong>{{ $featuredNews?->published_at?->format('d M Y') ?? '-' }}</strong>
                    <span>Update terbaru</span>
                </div>
            </div>
        </div>

        @if($featuredNews)
            <a href="{{ route('news.show', $featuredNews->slug) }}" class="news-featured-card">
                <div class="news-featured-media">
                    @if($featuredNews->thumbnail)
                        <img src="{{ asset($featuredNews->thumbnail) }}" alt="{{ $featuredNews->title }}" loading="lazy" decoding="async">
                    @else
                        <div class="news-page-placeholder">SAYAKA GROUP</div>
                    @endif
                </div>

                <div class="news-featured-content">
                    <span class="news-page-eyebrow">Berita Utama</span>
                    <span class="news-page-date">{{ $featuredNews->published_at?->format('d M Y') ?? 'Tanggal belum tersedia' }}</span>
                    <h2>{{ $featuredNews->title }}</h2>
                    <p>{{ $featuredNews->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($featuredNews->content), 190) }}</p>
                    <span class="news-read-link">Baca Detail</span>
                </div>
            </a>
        @endif

        <div class="news-page-heading">
            <div>
                <span class="news-page-eyebrow">Daftar Berita</span>
                <h2>Berita & Kegiatan Terbaru</h2>
            </div>
            <p>Temukan kabar produk, kegiatan, dan informasi penting dari brand SAYAKA GROUP.</p>
        </div>

        @if($otherNews->count())
            <div class="news-page-grid">
                @foreach($otherNews as $item)
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
                            <p>{{ $item->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($item->content), 130) }}</p>
                            <span class="news-read-link">Baca Detail</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @elseif(!$featuredNews)
            <div class="news-empty">
                <span>Belum ada berita</span>
                <h2>Konten berita akan segera hadir.</h2>
                <p>Silakan cek kembali halaman ini untuk melihat update terbaru dari SAYAKA GROUP.</p>
            </div>
        @endif
    </div>
</section>
@endsection
