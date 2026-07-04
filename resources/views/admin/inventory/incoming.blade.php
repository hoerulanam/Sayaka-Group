@extends('layouts.admin')
@section('title', 'Inventaris - Barang Masuk')
@section('page-title', 'Barang Masuk')
@section('body-class', 'admin-inventory-incoming-layout')

@section('content')
@php
    $productOptions = $products->mapWithKeys(fn ($product) => [
        $product->id => [
            'name' => $product->name,
            'brand' => $product->brand?->name,
            'has_variants' => $product->variants->isNotEmpty(),
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'label' => $variant->label(),
                'attribute' => $variant->attributeName(),
                'code' => $variant->code,
                'stock' => $variant->stock,
            ])->values(),
        ],
    ]);
@endphp
<section class="admin-incoming-page">
    <div class="admin-incoming-hero">
        <div>
            <span>Inventaris</span>
            <h2>Barang Masuk</h2>
            <p>Tambah stok dari barang lama, produk terdaftar, atau barang manual baru.</p>
        </div>
        <div class="admin-incoming-summary">
            <div>
                <strong>{{ number_format($inventoryItems->count(), 0, ',', '.') }}</strong>
                <span>Item inventaris</span>
            </div>
            <div>
                <strong>{{ number_format($incomingRecords->sum('quantity'), 0, ',', '.') }}</strong>
                <span>Total masuk</span>
            </div>
        </div>
    </div>

    <div class="admin-incoming-layout">
        <div class="admin-incoming-panel admin-incoming-form-panel">
            <div class="admin-incoming-panel-head">
                <div>
                    <span>Form Input</span>
                    <h3>Tambah Barang Masuk</h3>
                </div>
                <a href="{{ route('admin.inventory.index') }}">Data Barang</a>
            </div>

            <form action="{{ route('admin.inventory.incoming.store') }}" method="POST" class="admin-incoming-form">
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
                    <label for="inventory_item_id" class="form-label">Barang</label>
                    <select name="inventory_item_id" id="inventory_item_id" class="form-control">
                        <option value="">Tambah Barang Baru</option>
                        @foreach($inventoryItems as $item)
                            <option value="{{ $item->id }}" {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}
                                @if($item->code)
                                    (Kode: {{ $item->code }})
                                @endif
                                @if($item->size)
                                    - Ukuran: {{ $item->size }}
                                @endif
                                @if($item->color)
                                    - Warna: {{ $item->color }}
                                @endif
                                @if(! $item->size && ! $item->color && $item->variantLabel())
                                    - {{ $item->variantAttributeName() }}: {{ $item->variantLabel() }}
                                @endif
                                - {{ $item->brand->name ?? 'Tanpa Brand' }} - Stok: {{ $item->stock ?? 0 }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="new_item_fields">
                    <div class="form-group">
                        <label for="product_id" class="form-label">Produk Terdaftar</label>
                        <select name="product_id" id="product_id" class="form-control">
                            <option value="">Input barang manual</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} - {{ $product->brand->name ?? 'Tanpa Brand' }}
                                    @if($product->variants->isNotEmpty())
                                        ({{ $product->variants->count() }} varian)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small>Pilih produk ini jika produk sudah dibuat di menu Produk. Varian akan muncul otomatis.</small>
                    </div>

                    <div class="form-group" id="registered_variant_field" style="display:none;">
                        <label for="product_variant_id" class="form-label">Varian Produk</label>
                        <select name="product_variant_id" id="product_variant_id" class="form-control">
                            <option value="">Pilih Varian</option>
                        </select>
                    </div>

                    <div id="manual_item_fields" class="admin-incoming-manual-fields">
                        <div class="form-group">
                            <label for="product_name" class="form-label">Nama Barang Baru</label>
                            <input type="text" name="product_name" id="product_name" class="form-control" value="{{ old('product_name') }}" data-required-when-new>
                        </div>
                        <div class="form-group">
                            <label for="product_code" class="form-label">Kode Barang Baru</label>
                            <input type="text" name="product_code" id="product_code" class="form-control" value="{{ old('product_code') }}" data-required-when-new>
                        </div>
                        <div class="form-group">
                            <label for="brand_id" class="form-label">Brand</label>
                            <select name="brand_id" id="brand_id" class="form-control" data-required-when-new>
                                <option value="">Pilih Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" data-uses-size-variants="{{ $brand->usesSizeVariants() ? 'true' : 'false' }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="admin-incoming-inline-fields">
                    <div class="form-group">
                        <label for="quantity" class="form-label">Stok Barang</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="{{ old('quantity') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="date" class="form-label">Tanggal</label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="admin-incoming-actions">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('admin.inventory.report') }}" class="btn btn-secondary">Laporan</a>
                </div>
            </form>
        </div>

        <aside class="admin-incoming-panel admin-incoming-history-panel">
            <div class="admin-incoming-panel-head">
                <div>
                    <span>Riwayat</span>
                    <h3>Barang Masuk Terbaru</h3>
                </div>
                <a href="{{ route('admin.inventory.report') }}">Lihat Semua</a>
            </div>

            <div class="admin-incoming-history-list">
                @forelse($incomingRecords as $record)
                    @php
                        $item = $record->inventoryItem;
                        $variantLabel = $item?->variantLabel();
                        $variantType = $variantLabel ? $item?->variantAttributeName() : null;
                    @endphp

                    <div class="admin-incoming-history-item">
                        <div>
                            <strong>{{ $item->name ?? 'Barang tidak tersedia' }}</strong>
                            <span>
                                {{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}
                                @if($item?->code)
                                    - {{ $item->code }}
                                @endif
                                @if($variantLabel)
                                    - {{ $variantType }}: {{ $variantLabel }}
                                @endif
                            </span>
                        </div>
                        <span>+{{ number_format($record->quantity, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="admin-incoming-empty">Belum ada riwayat barang masuk.</div>
                @endforelse
            </div>
        </aside>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const itemSelect = document.getElementById('inventory_item_id');
        const newItemFields = document.getElementById('new_item_fields');
        const manualItemFields = document.getElementById('manual_item_fields');
        const newItemInputs = manualItemFields.querySelectorAll('[data-required-when-new]');
        const productSelect = document.getElementById('product_id');
        const productVariantField = document.getElementById('registered_variant_field');
        const productVariantSelect = document.getElementById('product_variant_id');
        const productOptions = @json($productOptions);
        const oldProductVariantId = '{{ old('product_variant_id') }}';

        const renderProductVariants = () => {
            const product = productOptions[productSelect.value];
            const variants = product?.variants || [];
            const currentValue = productVariantSelect.value || oldProductVariantId;

            productVariantSelect.innerHTML = '<option value="">Pilih Varian</option>';

            variants.forEach(variant => {
                const option = document.createElement('option');
                option.value = variant.id;
                option.textContent = `${variant.attribute}: ${variant.label}${variant.code ? ' (Kode: ' + variant.code + ')' : ''} - Stok gudang mengikuti inventaris`;
                option.selected = String(variant.id) === String(currentValue);
                productVariantSelect.appendChild(option);
            });
        };

        const toggleNewItemFields = () => {
            const usingExistingItem = itemSelect.value !== '';
            const usingRegisteredProduct = productSelect.value !== '';
            const registeredProduct = productOptions[productSelect.value];
            const registeredVariants = registeredProduct?.variants || [];
            const registeredProductHasVariants = registeredVariants.length > 0;

            newItemFields.style.display = usingExistingItem ? 'none' : 'block';
            productSelect.disabled = usingExistingItem;
            manualItemFields.style.display = !usingExistingItem && !usingRegisteredProduct ? 'grid' : 'none';
            productVariantField.style.display = !usingExistingItem && usingRegisteredProduct && registeredProductHasVariants ? 'block' : 'none';
            productVariantSelect.disabled = usingExistingItem || !usingRegisteredProduct || !registeredProductHasVariants;
            productVariantSelect.required = !usingExistingItem && usingRegisteredProduct && registeredProductHasVariants;

            newItemInputs.forEach(input => {
                input.disabled = usingExistingItem || usingRegisteredProduct;
                input.required = !usingExistingItem && !usingRegisteredProduct;
            });

        };

        itemSelect.addEventListener('change', toggleNewItemFields);
        productSelect.addEventListener('change', function () {
            renderProductVariants();
            toggleNewItemFields();
        });
        renderProductVariants();
        toggleNewItemFields();
    });
</script>
@endpush
