@php
    $reviewCount = (int) ($product->reviews_count ?? 0);
    $reviewAverage = $product->reviews_avg_rating ?? null;
@endphp

<div class="product-rating-summary">
    @if($reviewCount > 0 && $reviewAverage !== null)
        <span>
            <x-star-rating :rating="$reviewAverage" />
            <strong>{{ number_format((float) $reviewAverage, 1, ',', '.') }}/5</strong>
        </span>
        <small>{{ $reviewCount }} review</small>
    @else
        <span>
            <x-star-rating :rating="0" />
            <strong>Belum ada rating</strong>
        </span>
    @endif
</div>
