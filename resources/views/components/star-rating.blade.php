@props(['rating' => 0])

@php
    $ratingValue = max(0, min(5, (float) $rating));
    $ratingPercent = ($ratingValue / 5) * 100;
@endphp

<span class="product-star-rating" aria-label="{{ number_format($ratingValue, 1, ',', '.') }} dari 5 bintang">
    <span class="product-star-rating-base" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
    <span class="product-star-rating-fill" aria-hidden="true" style="width: {{ $ratingPercent }}%">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
</span>
