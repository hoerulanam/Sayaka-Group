@php
    $actionsClass = $actionsClass ?? 'catalog-product-actions';
    $emptyClass = $emptyClass ?? 'catalog-product-empty';
    $variants = $product->variants ?? collect();
    $hasVariants = $variants->isNotEmpty();
    $hasColorVariants = $variants->contains(fn ($variant) => $variant->color);
    $hasSizeVariants = $variants->contains(fn ($variant) => $variant->size || (! $variant->color && $variant->name));
    $hasMixedVariantTypes = $hasColorVariants && $hasSizeVariants;
    $usesShoePaintPalette = $product->requiresColorSelection() && $hasColorVariants;
    $usesBottleSizeVariants = ($product->brand?->usesSizeVariants() ?? false) && $hasSizeVariants && ! $hasColorVariants;
    $variantOptions = $usesShoePaintPalette
        ? $variants->whereNotNull('color')->values()->map(fn ($variant) => [
            'label' => $variant->color,
            'variant' => $variant,
        ])
        : $variants->map(fn ($variant) => [
            'label' => ($hasMixedVariantTypes ? $variant->attributeName() . ': ' : '') . $variant->label(),
            'variant' => $variant,
        ]);
    $selectionPrompt = $hasMixedVariantTypes ? 'varian' : ($hasColorVariants ? 'warna' : ($hasSizeVariants ? 'ukuran' : 'varian'));
    $selectionTitle = $usesShoePaintPalette ? 'Pilih warna cat sepatu' : ($usesBottleSizeVariants ? 'Pilih ukuran botol' : ($selectionPrompt === 'warna' ? 'Pilih warna produk' : ($selectionPrompt === 'ukuran' ? 'Pilih ukuran produk' : 'Pilih varian produk')));
    $selectionDescription = $selectionPrompt === 'warna' ? 'Satu warna' : ($selectionPrompt === 'ukuran' ? 'Satu ukuran' : 'Satu varian');
    $availableStock = $product->availableStock();
    $purchaseFormId = 'catalog-purchase-form-' . $product->id;
    $variantModalId = 'catalog-variant-modal-' . $product->id;
@endphp

@if($availableStock < 1)
    <div class="{{ $emptyClass }}">Produk ini belum tersedia untuk dibeli.</div>
@elseif(auth()->check())
    <form id="{{ $purchaseFormId }}" action="{{ route('cart.add', $product) }}" method="post" class="{{ $actionsClass }} catalog-purchase-form" data-cart-action="{{ route('cart.add', $product) }}">
        @if($hasVariants)
            <input type="hidden" name="product_variant_id" value="">
            <input type="hidden" name="purchase_mode" value="cart">
        @endif

        <div class="catalog-purchase-buttons">
            @csrf
            @if($hasVariants)
                <button type="button" class="btn btn-secondary" data-variant-modal-open="{{ $variantModalId }}" data-purchase-mode="cart" data-preserve-scroll="cart-add">Keranjang</button>
                <button type="button" class="btn btn-primary" data-variant-modal-open="{{ $variantModalId }}" data-purchase-mode="checkout"><span class="catalog-checkout-label-desktop">Checkout</span><span class="catalog-checkout-label-mobile">Beli</span></button>
            @else
                <button type="submit" class="btn btn-secondary" data-preserve-scroll="cart-add">Keranjang</button>
                <button type="submit" formaction="{{ route('checkout.product', $product) }}" formmethod="get" class="btn btn-primary"><span class="catalog-checkout-label-desktop">Checkout</span><span class="catalog-checkout-label-mobile">Beli</span></button>
            @endif
        </div>
    </form>
    @if($hasVariants)
        @push('modals')
            <div id="{{ $variantModalId }}" class="catalog-variant-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $variantModalId }}-title" data-selection-prompt="{{ $selectionPrompt }}" hidden>
                <div class="catalog-variant-modal-backdrop" data-variant-modal-close></div>
                <div class="catalog-variant-modal-panel">
                    <button type="button" class="catalog-variant-modal-close" data-variant-modal-close aria-label="Tutup">&times;</button>
                    <span class="catalog-variant-modal-eyebrow">{{ $usesShoePaintPalette ? 'Cat Sepatu ' . $product->size : $product->name }}</span>
                    <h3 id="{{ $variantModalId }}-title">{{ $selectionTitle }}</h3>
                    <p>{{ $selectionDescription }} berlaku untuk seluruh jumlah pada baris keranjang ini.</p>
                    <div class="catalog-variant-options">
                        @foreach($variantOptions as $option)
                            @php
                                $variant = $option['variant'];
                                $isUnavailable = ! $variant || $variant->stock < 1;
                            @endphp
                            <button
                                type="button"
                                class="catalog-variant-option"
                                data-variant-id="{{ $variant?->id }}"
                                @disabled($isUnavailable)
                            >
                                <strong>{{ $option['label'] }}</strong>
                                <span>
                                    @if($variant)
                                        Rp {{ number_format($variant->price ?? 0, 0, ',', '.') }} &middot;
                                    @endif
                                    {{ $isUnavailable ? 'Stok habis' : 'Stok ' . $variant->stock }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-primary catalog-variant-confirm" data-variant-modal-confirm data-form-id="{{ $purchaseFormId }}" disabled>Pilih {{ $selectionPrompt }} dahulu</button>
                </div>
            </div>
        @endpush
    @endif
@else
    <div class="{{ $actionsClass }} catalog-purchase-form">
        @if($hasVariants)
            <label class="catalog-variant-field">
                <span>{{ $selectionPrompt === 'warna' ? 'Warna tersedia' : ($selectionPrompt === 'ukuran' ? 'Ukuran tersedia' : 'Varian tersedia') }}</span>
                <select disabled>
                    @foreach($variants as $variant)
                        <option>{{ $variant->attributeName() }}: {{ $variant->label() }} - Rp {{ number_format($variant->price ?? 0, 0, ',', '.') }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <div class="catalog-purchase-buttons">
        <a href="{{ route('register') }}" class="btn btn-secondary">Keranjang</a>
        <a href="{{ route('register') }}" class="btn btn-primary"><span class="catalog-checkout-label-desktop">Checkout</span><span class="catalog-checkout-label-mobile">Beli</span></a>
        </div>
    </div>
@endif

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const closeModal = modal => {
                    modal.hidden = true;
                    document.body.classList.remove('catalog-variant-modal-open');
                };

                document.addEventListener('click', function (event) {
                    const openButton = event.target.closest('[data-variant-modal-open]');
                    const closeButton = event.target.closest('[data-variant-modal-close]');
                    const optionButton = event.target.closest('.catalog-variant-option[data-variant-id]');
                    const confirmButton = event.target.closest('[data-variant-modal-confirm]');

                    if (openButton) {
                        const modal = document.getElementById(openButton.dataset.variantModalOpen);

                        if (!modal) {
                            return;
                        }

                        modal.dataset.purchaseMode = openButton.dataset.purchaseMode;
                        delete modal.dataset.selectedVariantId;
                        modal.querySelectorAll('.catalog-variant-option[data-variant-id]').forEach(button => {
                            button.classList.remove('is-selected');
                        });
                        const modalConfirm = modal.querySelector('[data-variant-modal-confirm]');
                        modalConfirm.disabled = true;
                        modalConfirm.textContent = `Pilih ${modal.dataset.selectionPrompt} dahulu`;
                        modal.hidden = false;
                        document.body.classList.add('catalog-variant-modal-open');
                        return;
                    }

                    if (closeButton) {
                        const modal = closeButton.closest('.catalog-variant-modal');

                        if (modal) {
                            closeModal(modal);
                        }

                        return;
                    }

                    if (optionButton && !optionButton.disabled) {
                        const modal = optionButton.closest('.catalog-variant-modal');

                        modal.querySelectorAll('.catalog-variant-option[data-variant-id]').forEach(button => {
                            button.classList.toggle('is-selected', button === optionButton);
                        });

                        modal.dataset.selectedVariantId = optionButton.dataset.variantId;
                        const modalConfirm = modal.querySelector('[data-variant-modal-confirm]');
                        modalConfirm.disabled = false;
                        modalConfirm.textContent = modal.dataset.purchaseMode === 'checkout' ? 'Lanjut Checkout' : 'Tambah ke Keranjang';
                        return;
                    }

                    if (confirmButton) {
                        const modal = confirmButton.closest('.catalog-variant-modal');
                        const form = document.getElementById(confirmButton.dataset.formId);
                        const variantInput = form?.querySelector('input[name="product_variant_id"]');
                        const purchaseModeInput = form?.querySelector('input[name="purchase_mode"]');

                        if (!form || !variantInput || !purchaseModeInput || !modal.dataset.selectedVariantId) {
                            return;
                        }

                        variantInput.value = modal.dataset.selectedVariantId;
                        purchaseModeInput.value = modal.dataset.purchaseMode === 'checkout' ? 'checkout' : 'cart';
                        form.action = form.dataset.cartAction;
                        form.method = 'post';

                        if (purchaseModeInput.value === 'cart' && typeof window.rememberCartClickPosition === 'function') {
                            window.rememberCartClickPosition(form, confirmButton);
                        }

                        if (typeof window.scheduleSiteLoading === 'function') {
                            window.scheduleSiteLoading();
                        }

                        HTMLFormElement.prototype.submit.call(form);
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key !== 'Escape') {
                        return;
                    }

                    document.querySelectorAll('.catalog-variant-modal:not([hidden])').forEach(closeModal);
                });
            });
        </script>
    @endpush
@endonce
