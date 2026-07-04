@extends('layouts.app')
@section('title', 'Keranjang Belanja')

@section('content')
@php
    $subtotal = $carts->sum(function ($cart) {
        return $cart->quantity * $cart->unitPrice();
    });
    $totalItems = $carts->sum('quantity');
@endphp

<section class="cart-page">
    <div class="cart-wrap">
        <div class="cart-heading">
            <span class="cart-eyebrow">Keranjang</span>
            <h1>Keranjang Belanja</h1>
            <p>Periksa kembali produk pilihan Anda sebelum melanjutkan ke checkout.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div id="cartStockWarning" class="cart-stock-warning" hidden></div>

        @if($carts->count())
            <div class="cart-layout">
                <div class="cart-items-panel">
                    <div class="cart-panel-title">
                        <h2>Produk Dipilih</h2>
                        <div class="cart-panel-tools">
                            <label class="cart-select-all">
                                <input type="checkbox" id="selectAllCartItems" checked>
                                <span>Pilih semua</span>
                            </label>
                            <span id="cartTotalItemsLabel">{{ $totalItems }} item</span>
                        </div>
                    </div>

                    <div class="cart-items-list">
                        @foreach($carts as $cart)
                            @php
                                $product = $cart->product;
                                $price = $cart->unitPrice();
                                $lineTotal = $cart->quantity * $price;
                                $stock = $cart->availableStock();
                                $displayName = $cart->displayName();
                                $displaySize = $cart->productVariant?->color ? $product?->size : null;
                                $displayVariantLabel = $cart->productVariant?->label();
                                $displayVariantAttribute = $cart->productVariant?->attributeName() ?? 'Varian';
                                $displayFixedSize = ! $displayVariantLabel ? $product?->size : null;
                                $displayColor = $cart->productVariant?->color;
                                $isOverStock = $stock < 1 || $cart->quantity > $stock;
                            @endphp

                            <article class="cart-item-card">
                                <label class="cart-item-check" aria-label="Pilih {{ $displayName }} untuk checkout">
                                    <input
                                        type="checkbox"
                                        name="cart_items[]"
                                        value="{{ $cart->id }}"
                                        form="cartCheckoutForm"
                                        class="cart-item-checkbox"
                                        data-line-total="{{ $lineTotal }}"
                                        data-quantity="{{ $cart->quantity }}"
                                        data-stock="{{ $stock }}"
                                        data-product-name="{{ $displayName }}"
                                        checked
                                    >
                                    <span></span>
                                </label>

                                <div class="cart-item-image">
                                    @if($product && $product->thumbnail)
                                        <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                                    @else
                                        <div class="image-placeholder">Produk</div>
                                    @endif
                                </div>

                                <div class="cart-item-info">
                                    <span class="cart-item-brand">{{ $product && $product->brand ? $product->brand->name : 'Sayaka' }}</span>
                                    <h3>{{ $product->name ?? 'Produk tidak tersedia' }}</h3>
                                    @if($displaySize)
                                        <div class="cart-item-variant">Ukuran: <strong>{{ $displaySize }}</strong></div>
                                    @endif
                                    @if($displayVariantLabel && ! $displayColor)
                                        <div class="cart-item-variant">{{ $displayVariantAttribute }}: <strong>{{ $displayVariantLabel }}</strong></div>
                                    @endif
                                    @if($displayColor)
                                        <div class="cart-item-variant">Warna: <strong>{{ $displayColor }}</strong></div>
                                    @endif
                                    @if($displayFixedSize)
                                        <div class="cart-item-variant">Ukuran: <strong>{{ $displayFixedSize }}</strong></div>
                                    @endif
                                    <div class="cart-item-meta">
                                        <span>Harga: <strong>Rp {{ number_format($price, 0, ',', '.') }}</strong></span>
                                        <span>Stok: <strong>{{ $stock }}</strong></span>
                                    </div>

                                    <div class="cart-item-stock-warning" @if(!$isOverStock) hidden @endif>
                                        @if($stock < 1)
                                            Stok {{ $displayName }} sedang kosong. Hapus produk atau pilih produk lain sebelum checkout.
                                        @else
                                            Stok {{ $displayName }} hanya tersedia {{ $stock }} item. Kurangi jumlah sebelum checkout.
                                        @endif
                                    </div>

                                    @if($product)
                                        <div
                                            class="cart-quantity-control"
                                            data-update-url="{{ route('cart.update', $cart) }}"
                                            data-csrf="{{ csrf_token() }}"
                                            data-price="{{ $price }}"
                                            data-stock="{{ $stock }}"
                                            data-last-synced-quantity="{{ $cart->quantity }}"
                                        >
                                            <button
                                                type="button"
                                                data-quantity-action="decrease"
                                                class="cart-quantity-btn"
                                                aria-label="Kurangi jumlah {{ $displayName }}"
                                                @disabled($cart->quantity <= 1 || $stock < 1)
                                            >-</button>

                                            <label for="cart-quantity-{{ $cart->id }}" class="sr-only">Jumlah {{ $displayName }}</label>
                                            <input
                                                id="cart-quantity-{{ $cart->id }}"
                                                name="quantity"
                                                type="number"
                                                min="1"
                                                max="{{ max(1, $stock) }}"
                                                value="{{ $cart->quantity }}"
                                                class="cart-quantity-input"
                                                @disabled($stock < 1)
                                            >

                                            <button
                                                type="button"
                                                data-quantity-action="increase"
                                                class="cart-quantity-btn"
                                                aria-label="Tambah jumlah {{ $displayName }}"
                                                @disabled($cart->quantity >= $stock || $stock < 1)
                                            >+</button>

                                            <span class="cart-quantity-status" aria-live="polite"></span>
                                        </div>
                                    @endif
                                </div>

                                <div class="cart-item-side">
                                    <strong class="cart-line-total">Rp {{ number_format($lineTotal, 0, ',', '.') }}</strong>
                                    @if($product)
                                        <form action="{{ route('cart.remove', $cart) }}" method="post" class="cart-remove-form" data-confirm-title="Hapus produk?" data-confirm-text="{{ $displayName }} akan dihapus dari keranjang.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cart-remove-btn">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <aside class="cart-summary">
                    <h2>Ringkasan Belanja</h2>
                    <div class="cart-summary-row">
                        <span>Item dipilih</span>
                        <strong id="selectedItemCount">{{ $totalItems }}</strong>
                    </div>
                    <div class="cart-summary-row">
                        <span>Subtotal dipilih</span>
                        <strong id="selectedSubtotal">Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                    </div>
                    <div class="cart-summary-note">
                        Hanya produk yang dicentang yang akan masuk ke halaman checkout.
                    </div>
                    <form action="{{ route('checkout.index') }}" method="get" id="cartCheckoutForm" class="cart-checkout-form">
                        <button type="submit" class="btn btn-primary cart-checkout-btn">Lanjut Checkout</button>
                    </form>
                    <a href="{{ route('brands.index') }}" class="cart-continue-link">Tambah produk lain</a>
                </aside>
            </div>
        @else
            <div class="cart-empty">
                <span>Keranjang kosong</span>
                <h2>Belum ada produk yang ditambahkan.</h2>
                <p>Lihat katalog brand SAYAKA GROUP dan pilih produk yang Anda butuhkan.</p>
                <a href="{{ route('brands.index') }}" class="btn btn-primary">Belanja Sekarang</a>
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAllCartItems');
        const checkboxes = Array.from(document.querySelectorAll('.cart-item-checkbox'));
        const selectedItemCount = document.getElementById('selectedItemCount');
        const selectedSubtotal = document.getElementById('selectedSubtotal');
        const cartTotalItemsLabel = document.getElementById('cartTotalItemsLabel');
        const checkoutForm = document.getElementById('cartCheckoutForm');
        const checkoutButton = checkoutForm ? checkoutForm.querySelector('.cart-checkout-btn') : null;
        const currencyFormatter = new Intl.NumberFormat('id-ID');

        function formatCurrency(value) {
            return 'Rp ' + currencyFormatter.format(value);
        }

        function updateSelectedSummary() {
            const checkedItems = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            });

            const totalQuantity = checkedItems.reduce(function (sum, checkbox) {
                return sum + Number(checkbox.dataset.quantity || 0);
            }, 0);

            const subtotal = checkedItems.reduce(function (sum, checkbox) {
                return sum + Number(checkbox.dataset.lineTotal || 0);
            }, 0);

            if (selectedItemCount) {
                selectedItemCount.textContent = totalQuantity;
            }

            if (selectedSubtotal) {
                selectedSubtotal.textContent = formatCurrency(subtotal);
            }

            if (cartTotalItemsLabel) {
                const allQuantity = checkboxes.reduce(function (sum, checkbox) {
                    return sum + Number(checkbox.dataset.quantity || 0);
                }, 0);

                cartTotalItemsLabel.textContent = allQuantity + ' item';
            }

            if (checkoutButton) {
                checkoutButton.disabled = checkedItems.length === 0;
                checkoutButton.classList.toggle('is-disabled', checkedItems.length === 0);
            }

            if (selectAll) {
                selectAll.checked = checkedItems.length === checkboxes.length;
                selectAll.indeterminate = checkedItems.length > 0 && checkedItems.length < checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                updateSelectedSummary();
            });
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateSelectedSummary);
        });

        function clampQuantity(quantity, stock) {
            const parsedQuantity = Number.parseInt(quantity, 10);
            const parsedStock = Number.parseInt(stock, 10);
            const safeStock = Number.isFinite(parsedStock) && parsedStock > 0 ? parsedStock : 1;
            const safeQuantity = Number.isFinite(parsedQuantity) ? parsedQuantity : 1;

            return Math.max(1, Math.min(safeQuantity, safeStock));
        }

        function updateQuantityButtons(control, quantity) {
            const stock = Number(control.dataset.stock || 0);
            const decreaseButton = control.querySelector('[data-quantity-action="decrease"]');
            const increaseButton = control.querySelector('[data-quantity-action="increase"]');

            if (decreaseButton) {
                decreaseButton.disabled = quantity <= 1 || stock < 1;
            }

            if (increaseButton) {
                increaseButton.disabled = quantity >= stock || stock < 1;
            }
        }

        function updateCartItemDisplay(control, quantity) {
            const itemCard = control.closest('.cart-item-card');
            const checkbox = itemCard ? itemCard.querySelector('.cart-item-checkbox') : null;
            const lineTotalTarget = itemCard ? itemCard.querySelector('.cart-line-total') : null;
            const price = Number(control.dataset.price || 0);
            const stock = Number(control.dataset.stock || 0);
            const lineTotal = quantity * price;

            if (checkbox) {
                checkbox.dataset.quantity = quantity;
                checkbox.dataset.lineTotal = lineTotal;
            }

            if (lineTotalTarget) {
                lineTotalTarget.textContent = formatCurrency(lineTotal);
            }

            updateQuantityButtons(control, quantity);
            updateCartStockWarning(control, quantity, stock);
            updateSelectedSummary();
        }

        function stockWarningMessage(productName, stock, quantity) {
            if (stock < 1) {
                return 'Stok ' + productName + ' sedang kosong. Hapus produk atau pilih produk lain sebelum checkout.';
            }

            return 'Stok ' + productName + ' hanya tersedia ' + stock + ' item, sedangkan jumlah yang dipilih ' + quantity + ' item.';
        }

        function updateCartStockWarning(control, quantity, stock) {
            const itemCard = control.closest('.cart-item-card');
            const warning = itemCard ? itemCard.querySelector('.cart-item-stock-warning') : null;
            const checkbox = itemCard ? itemCard.querySelector('.cart-item-checkbox') : null;
            const productName = checkbox ? (checkbox.dataset.productName || 'produk ini') : 'produk ini';
            const isInvalid = stock < 1 || quantity > stock;

            if (warning) {
                warning.hidden = !isInvalid;
                if (isInvalid) {
                    warning.textContent = stockWarningMessage(productName, stock, quantity);
                }
            }

            if (checkbox) {
                checkbox.dataset.quantity = quantity;
                checkbox.dataset.stock = stock;
                checkbox.dataset.stockInvalid = isInvalid ? 'true' : 'false';
            }
        }

        function setQuantityStatus(control, message, type) {
            const status = control.querySelector('.cart-quantity-status');

            if (!status) {
                return;
            }

            status.textContent = message || '';
            status.classList.toggle('is-error', type === 'error');
            status.classList.toggle('is-saving', type === 'saving');
        }

        function applyQuantity(control, quantity, options = {}) {
            const input = control.querySelector('.cart-quantity-input');
            const stock = Number(control.dataset.stock || 0);
            const requestedQuantity = Number.parseInt(quantity, 10);
            const isOverStock = stock > 0 && Number.isFinite(requestedQuantity) && requestedQuantity > stock;
            const nextQuantity = clampQuantity(quantity, stock);

            if (input) {
                input.value = nextQuantity;
            }

            updateCartItemDisplay(control, nextQuantity);

            if (options.sync !== false) {
                if (isOverStock) {
                    const itemCard = control.closest('.cart-item-card');
                    const checkbox = itemCard ? itemCard.querySelector('.cart-item-checkbox') : null;
                    const productName = checkbox ? (checkbox.dataset.productName || 'produk ini') : 'produk ini';

                    scheduleQuantitySync(control, {
                        delay: 900,
                        message: 'Stok ' + productName + ' hanya tersedia ' + stock + ' item. Jumlah disesuaikan.',
                        type: 'error',
                    });
                    return;
                }

                scheduleQuantitySync(control);
            }
        }

        function scheduleQuantitySync(control, options = {}) {
            clearTimeout(control.quantitySyncTimer);
            control.dataset.dirty = 'true';
            setQuantityStatus(control, options.message || 'Menyimpan...', options.type || 'saving');
            control.quantitySyncTimer = setTimeout(function () {
                syncQuantity(control);
            }, options.delay || 350);
        }

        async function syncQuantity(control) {
            clearTimeout(control.quantitySyncTimer);

            const input = control.querySelector('.cart-quantity-input');
            const quantity = clampQuantity(input ? input.value : control.dataset.lastSyncedQuantity, control.dataset.stock);

            control.dataset.dirty = 'true';
            setQuantityStatus(control, 'Menyimpan...', 'saving');

            try {
                const response = await fetch(control.dataset.updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': control.dataset.csrf,
                    },
                    body: JSON.stringify({
                        quantity,
                        action: 'set',
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Jumlah produk gagal diperbarui.');
                }

                control.dataset.lastSyncedQuantity = data.quantity;
                control.dataset.dirty = 'false';
                applyQuantity(control, data.quantity, { sync: false });
                setQuantityStatus(control, 'Tersimpan', 'saved');

                setTimeout(function () {
                    if (control.dataset.dirty === 'false') {
                        setQuantityStatus(control, '', '');
                    }
                }, 1200);
            } catch (error) {
                const previousQuantity = clampQuantity(control.dataset.lastSyncedQuantity, control.dataset.stock);
                control.dataset.dirty = 'false';
                applyQuantity(control, previousQuantity, { sync: false });
                setQuantityStatus(control, error.message, 'error');
            }
        }

        document.querySelectorAll('.cart-quantity-control').forEach(function (control) {
            const input = control.querySelector('.cart-quantity-input');

            control.querySelectorAll('[data-quantity-action]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const currentQuantity = clampQuantity(input ? input.value : control.dataset.lastSyncedQuantity, control.dataset.stock);
                    const action = button.dataset.quantityAction;
                    const nextQuantity = action === 'increase' ? currentQuantity + 1 : currentQuantity - 1;

                    applyQuantity(control, nextQuantity);
                });
            });

            if (input) {
                input.addEventListener('input', function () {
                    if (input.value === '') {
                        return;
                    }

                    applyQuantity(control, input.value);
                });

                input.addEventListener('blur', function () {
                    applyQuantity(control, input.value || 1);
                });
            }
        });

        function syncPendingQuantities() {
            const dirtyControls = Array.from(document.querySelectorAll('.cart-quantity-control')).filter(function (control) {
                return control.dataset.dirty === 'true';
            });

            return Promise.all(dirtyControls.map(syncQuantity));
        }

        function showCartStockWarning(message) {
            const warning = document.getElementById('cartStockWarning');

            if (!warning) {
                window.alert(message);
                return;
            }

            warning.textContent = message;
            warning.hidden = false;
            warning.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function firstSelectedStockIssue() {
            const checkedItems = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            });

            for (const checkbox of checkedItems) {
                const quantity = Number(checkbox.dataset.quantity || 0);
                const stock = Number(checkbox.dataset.stock || 0);

                if (stock < 1 || quantity > stock) {
                    return stockWarningMessage(checkbox.dataset.productName || 'produk ini', stock, quantity);
                }
            }

            return null;
        }

        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function (event) {
                const hasSelectedItem = checkboxes.some(function (checkbox) {
                    return checkbox.checked;
                });

                if (!hasSelectedItem) {
                    event.preventDefault();

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Pilih produk dulu',
                            text: 'Centang minimal satu produk yang ingin checkout.',
                            confirmButtonColor: '#2563eb'
                        });
                        return;
                    }

                    window.alert('Centang minimal satu produk yang ingin checkout.');
                    return;
                }

                const stockIssue = firstSelectedStockIssue();

                if (stockIssue) {
                    event.preventDefault();
                    showCartStockWarning(stockIssue + ' Kurangi jumlah sebelum lanjut checkout.');
                    return;
                }

                if (checkoutForm.dataset.quantitySynced === 'true') {
                    return;
                }

                const hasPendingQuantity = Array.from(document.querySelectorAll('.cart-quantity-control')).some(function (control) {
                    return control.dataset.dirty === 'true';
                });

                if (!hasPendingQuantity) {
                    return;
                }

                event.preventDefault();
                if (checkoutButton) {
                    checkoutButton.disabled = true;
                    checkoutButton.classList.add('is-disabled');
                }

                syncPendingQuantities().then(function () {
                    checkoutForm.dataset.quantitySynced = 'true';
                    checkoutForm.requestSubmit();
                }).finally(function () {
                    if (checkoutButton) {
                        checkoutButton.disabled = false;
                        checkoutButton.classList.remove('is-disabled');
                    }
                });
            });
        }

        updateSelectedSummary();
    });
</script>
@endpush
