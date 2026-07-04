@extends('layouts.admin')
@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk: ' . $product->name)

@section('content')
@php
    $isSummerLabsProduct = $product->brand?->usesSizeVariants() ?? false;
    $isShoePaintProduct = $product->requiresColorSelection();
    $variantRowFor = fn ($variant, string $type, string $value) => [
        'id' => $variant?->id,
        'type' => $type,
        'value' => $value,
        'code' => $variant?->code,
        'price' => $variant?->price,
    ];

    if ($isSummerLabsProduct) {
        $defaultKeys = collect(\App\Services\ProductVariantService::SIZES)->map(fn ($size) => strtolower($size))->all();
        $storedGenericVariants = collect(\App\Services\ProductVariantService::SIZES)
            ->map(fn ($size) => $variantRowFor($product->variants->firstWhere('size', $size), 'size', $size))
            ->concat($product->variants
                ->filter(fn ($variant) => $variant->size && ! in_array(strtolower($variant->size), $defaultKeys, true))
                ->map(fn ($variant) => $variantRowFor($variant, 'size', $variant->size)))
            ->values()
            ->all();
    } elseif ($isShoePaintProduct) {
        $defaultKeys = collect(\App\Models\Product::SHOE_PAINT_COLORS)->map(fn ($color) => strtolower($color))->all();
        $storedGenericVariants = collect(\App\Models\Product::SHOE_PAINT_COLORS)
            ->map(fn ($color) => $variantRowFor($product->variants->firstWhere('color', $color), 'color', $color))
            ->concat($product->variants
                ->filter(fn ($variant) => $variant->color && ! in_array(strtolower($variant->color), $defaultKeys, true))
                ->map(fn ($variant) => $variantRowFor($variant, 'color', $variant->color)))
            ->values()
            ->all();
    } else {
        $storedGenericVariants = $product->variants
            ->values()
            ->map(fn ($variant) => [
                'id' => $variant->id,
                'type' => $variant->color ? 'color' : 'size',
                'value' => $variant->label(),
                'code' => $variant->code,
                'price' => $variant->price,
            ])
            ->all();
    }

    $genericVariantRows = old('variants', $storedGenericVariants ?: [
        ['type' => 'size', 'value' => '', 'code' => '', 'price' => ''],
    ]);
    $hasGenericVariants = old('has_variants', ($storedGenericVariants && ! $isSummerLabsProduct && ! $isShoePaintProduct) ? '1' : null) == '1';
    $defaultSizeVariantPresets = collect(\App\Services\ProductVariantService::SIZES)
        ->map(function ($size) use ($product) {
            $variant = $product->variants->firstWhere('size', $size);

            return [
                'id' => $variant?->id,
                'type' => 'size',
                'value' => $size,
                'code' => $variant?->code ?? '',
                'price' => old("variant_prices.{$size}", $variant?->price ?? ''),
            ];
        })
        ->values();
    $defaultColorVariantPresets = collect(\App\Models\Product::SHOE_PAINT_COLORS)
        ->map(function ($color) use ($product) {
            $variant = $product->variants->firstWhere('color', $color);

            return [
                'id' => $variant?->id,
                'type' => 'color',
                'value' => $color,
                'code' => $variant?->code ?? '',
                'price' => $variant?->price ?? '',
            ];
        })
        ->values();
@endphp
<form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="form-admin">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-group">
        <label for="brand_id">Brand *</label>
        <select id="brand_id" name="brand_id" class="form-control">
            <option value="">-- Pilih Brand --</option>
            @foreach($brands as $b)
            <option value="{{ $b->id }}" data-uses-size-variants="{{ $b->usesSizeVariants() ? 'true' : 'false' }}" {{ (old('brand_id', $product->brand_id) == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        @error('brand_id') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="name">Nama Produk *</label>
        <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $product->name) }}" required>
        @error('name') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="code">Kode/SKU Produk</label>
        <input id="code" name="code" type="text" class="form-control" value="{{ old('code', $product->code) }}" placeholder="Contoh: BRD-PROD-001">
        @error('code') <div class="error-msg">{{ $message }}</div> @enderror
        <small>Opsional. Jika produk punya varian, kode ini dipakai sebagai dasar kode varian.</small>
    </div>

    <div class="form-group">
        <label for="thumbnail">Thumbnail</label>
        @if($product->thumbnail)
            <div style="margin-bottom:8px;">
                <img src="{{ asset($product->thumbnail) }}" alt="thumb" style="width:120px;height:80px;object-fit:cover;border-radius:6px;">
            </div>
        @endif
        <input id="thumbnail" name="thumbnail" type="file" class="form-control" accept="image/*">
        @error('thumbnail') <div class="error-msg">{{ $message }}</div> @enderror
        <small>Kosongkan jika tidak ingin mengganti. Max 2MB</small>
    </div>

    <div class="form-group" id="base-price-field">
        <label for="price">Harga (opsional)</label>
        <input id="price" name="price" type="text" class="form-control" value="{{ old('price', $product->price) }}">
        @error('price') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-group" id="fixed-size-field">
        <label for="size">Kapasitas (opsional)</label>
        <input id="size" name="size" type="text" maxlength="20" class="form-control" value="{{ old('size', $product->size) }}" placeholder="Contoh: 100ml">
        @error('size') <div class="error-msg">{{ $message }}</div> @enderror
        <small>Isi untuk produk dengan satu ukuran kemasan tetap.</small>
    </div>

    <div class="form-group" id="generic-variant-toggle-field">
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
            <input type="checkbox" id="has_variants" name="has_variants" value="1" {{ $hasGenericVariants ? 'checked' : '' }} style="margin-top:4px;">
            <span>
                Produk ini memiliki beberapa varian
                <small style="display:block; color:#64748b; font-weight:400;">Gunakan untuk varian independen bertipe size atau warna.</small>
            </span>
        </label>
        @error('variants') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div id="variant-price-fields" class="card" style="display:none; padding:16px; margin-bottom:18px; background:#f8fafc;">
        <h3 style="margin:0 0 6px;">Harga Varian Parfum SummerLabs</h3>
        <p class="text-muted" style="margin:0 0 14px;">Stok tiap ukuran dikelola melalui menu inventaris.</p>

        @foreach(\App\Services\ProductVariantService::SIZES as $size)
            @php
                $variant = $product->variants->firstWhere('size', $size);
            @endphp
            <div class="form-group">
                <label for="variant-price-{{ $size }}">Harga {{ $size }} *</label>
                <input id="variant-price-{{ $size }}" name="variant_prices[{{ $size }}]" type="number" min="0" class="form-control variant-price-input" value="{{ old("variant_prices.{$size}", $variant?->price ?? $product->price) }}">
                @error("variant_prices.{$size}") <div class="error-msg">{{ $message }}</div> @enderror
            </div>
        @endforeach
    </div>

    <div id="generic-variant-fields" class="card" style="display:none; padding:16px; margin-bottom:18px; background:#f8fafc;">
        <h3 id="generic-variant-title" style="margin:0 0 6px;">Varian Produk</h3>
        <p id="generic-variant-help" class="text-muted" style="margin:0 0 14px;">Tambahkan tipe varian, nilai, dan harga. Stok tiap varian dikelola melalui menu inventaris.</p>

        <div id="generic-variant-rows" style="display:grid; gap:12px;">
            @foreach($genericVariantRows as $index => $variantRow)
                <div class="generic-variant-row" style="display:grid; grid-template-columns: 0.8fr 1.2fr 1fr 1fr auto; gap:10px; align-items:start;">
                    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variantRow['id'] ?? '' }}" class="generic-variant-input">
                    <div>
                        <label for="variant-type-{{ $index }}">Tipe *</label>
                        <select id="variant-type-{{ $index }}" name="variants[{{ $index }}][type]" class="form-control generic-variant-input" data-generic-required>
                            <option value="size" {{ ($variantRow['type'] ?? 'size') === 'size' ? 'selected' : '' }}>Size</option>
                            <option value="color" {{ ($variantRow['type'] ?? '') === 'color' ? 'selected' : '' }}>Warna</option>
                        </select>
                        @error("variants.{$index}.type") <div class="error-msg">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="variant-value-{{ $index }}">Nilai Varian *</label>
                        <input id="variant-value-{{ $index }}" name="variants[{{ $index }}][value]" type="text" maxlength="20" class="form-control generic-variant-input" value="{{ $variantRow['value'] ?? $variantRow['name'] ?? '' }}" placeholder="Contoh: 100ml / Hitam" data-generic-required>
                        @error("variants.{$index}.value") <div class="error-msg">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="variant-code-{{ $index }}">Kode/SKU Varian</label>
                        <input id="variant-code-{{ $index }}" name="variants[{{ $index }}][code]" type="text" maxlength="100" class="form-control generic-variant-input" value="{{ $variantRow['code'] ?? '' }}" placeholder="Opsional">
                        @error("variants.{$index}.code") <div class="error-msg">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="variant-price-{{ $index }}">Harga *</label>
                        <input id="variant-price-{{ $index }}" name="variants[{{ $index }}][price]" type="number" min="0" class="form-control generic-variant-input" value="{{ $variantRow['price'] ?? '' }}" data-generic-required>
                        @error("variants.{$index}.price") <div class="error-msg">{{ $message }}</div> @enderror
                    </div>
                    <button type="button" class="btn btn-secondary generic-variant-remove" style="margin-top:24px;">Hapus</button>
                </div>
            @endforeach
        </div>

        <button type="button" id="add-generic-variant" class="btn btn-secondary" style="margin-top:14px;">Tambah Varian</button>
    </div>

    <div class="form-group">
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" class="form-control" rows="6">{{ old('description', $product->description) }}</textarea>
        @error('description') <div class="error-msg">{{ $message }}</div> @enderror
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Perbarui</button>
    </div>
</form>

<template id="generic-variant-row-template">
    <div class="generic-variant-row" style="display:grid; grid-template-columns: 0.8fr 1.2fr 1fr 1fr auto; gap:10px; align-items:start;">
        <input type="hidden" name="variants[__INDEX__][id]" class="generic-variant-input">
        <div>
            <label for="variant-type-__INDEX__">Tipe *</label>
            <select id="variant-type-__INDEX__" name="variants[__INDEX__][type]" class="form-control generic-variant-input" data-generic-required>
                <option value="size">Size</option>
                <option value="color">Warna</option>
            </select>
        </div>
        <div>
            <label for="variant-value-__INDEX__">Nilai Varian *</label>
            <input id="variant-value-__INDEX__" name="variants[__INDEX__][value]" type="text" maxlength="20" class="form-control generic-variant-input" placeholder="Contoh: 100ml / Hitam" data-generic-required>
        </div>
        <div>
            <label for="variant-code-__INDEX__">Kode/SKU Varian</label>
            <input id="variant-code-__INDEX__" name="variants[__INDEX__][code]" type="text" maxlength="100" class="form-control generic-variant-input" placeholder="Opsional">
        </div>
        <div>
            <label for="variant-price-__INDEX__">Harga *</label>
            <input id="variant-price-__INDEX__" name="variants[__INDEX__][price]" type="number" min="0" class="form-control generic-variant-input" data-generic-required>
        </div>
        <button type="button" class="btn btn-secondary generic-variant-remove" style="margin-top:24px;">Hapus</button>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const brandSelect = document.getElementById('brand_id');
    const productCodeInput = document.getElementById('code');
    const basePriceField = document.getElementById('base-price-field');
    const basePriceInput = document.getElementById('price');
    const fixedSizeField = document.getElementById('fixed-size-field');
    const fixedSizeInput = document.getElementById('size');
    const summerVariantFields = document.getElementById('variant-price-fields');
    const summerVariantInputs = summerVariantFields.querySelectorAll('.variant-price-input');
    const genericToggleField = document.getElementById('generic-variant-toggle-field');
    const genericToggle = document.getElementById('has_variants');
    const genericVariantFields = document.getElementById('generic-variant-fields');
    const genericVariantTitle = document.getElementById('generic-variant-title');
    const genericVariantHelp = document.getElementById('generic-variant-help');
    const genericRows = document.getElementById('generic-variant-rows');
    const addGenericVariantButton = document.getElementById('add-generic-variant');
    const genericVariantTemplate = document.getElementById('generic-variant-row-template');
    const defaultSizeVariants = @json($defaultSizeVariantPresets);
    const defaultColorVariants = @json($defaultColorVariantPresets);

    const rowValue = row => row.querySelector('input[name$="[value]"]')?.value.trim() || '';

    const fillRow = (row, data) => {
        const id = row.querySelector('input[name$="[id]"]');
        const type = row.querySelector('select[name$="[type]"]');
        const value = row.querySelector('input[name$="[value]"]');
        const code = row.querySelector('input[name$="[code]"]');
        const price = row.querySelector('input[name$="[price]"]');

        if (id) id.value = data.id ?? '';
        if (type) type.value = data.type || 'size';
        if (value) value.value = data.value || '';
        if (code) code.value = data.code || '';
        if (price) price.value = data.price ?? '';
    };

    const appendVariantRow = data => {
        const index = variantIndex++;
        genericRows.insertAdjacentHTML('beforeend', genericVariantTemplate.innerHTML.replace(/__INDEX__/g, index));
        fillRow(genericRows.lastElementChild, data);
    };

    const ensurePresetRows = (type, defaults) => {
        const rows = [...genericRows.querySelectorAll('.generic-variant-row')];
        const filledRows = rows.filter(row => rowValue(row) !== '');

        if (filledRows.length === 0) {
            genericRows.innerHTML = '';
            defaults.forEach(appendVariantRow);
        } else {
            const existingValues = new Set(filledRows.map(row => rowValue(row).toLowerCase()));

            defaults.forEach(defaultRow => {
                if (!existingValues.has(defaultRow.value.toLowerCase())) {
                    appendVariantRow(defaultRow);
                }
            });
        }

        genericRows.querySelectorAll('select[name$="[type]"]').forEach(select => {
            select.value = type;
        });
    };

    const toggleVariantPrices = () => {
        const usesSummerLabsVariants = brandSelect.selectedOptions[0]?.dataset.usesSizeVariants === 'true';
        const isShoePaint = productCodeInput.value.trim().toUpperCase() === '{{ \App\Models\Product::SHOE_PAINT_CODE }}';
        const usesSpecialVariants = usesSummerLabsVariants || isShoePaint;
        const usesGenericVariants = genericToggle.checked && !usesSpecialVariants;
        const showsVariantRows = usesSpecialVariants || usesGenericVariants;
        const hidesBaseFields = usesSummerLabsVariants || usesGenericVariants;

        if (usesSummerLabsVariants) {
            ensurePresetRows('size', defaultSizeVariants);
            genericVariantTitle.textContent = 'Varian Size SummerLabs';
            genericVariantHelp.textContent = 'Default 30ml dan 50ml tetap tersedia. Tambahkan size lain jika diperlukan.';
            addGenericVariantButton.textContent = 'Tambah Size';
        } else if (isShoePaint) {
            ensurePresetRows('color', defaultColorVariants);
            genericVariantTitle.textContent = 'Varian Warna Cat Sepatu';
            genericVariantHelp.textContent = 'Default warna tetap tersedia. Tambahkan warna lain jika diperlukan.';
            addGenericVariantButton.textContent = 'Tambah Warna';
        } else {
            genericVariantTitle.textContent = 'Varian Produk';
            genericVariantHelp.textContent = 'Tambahkan tipe varian, nilai, dan harga. Stok tiap varian dikelola melalui menu inventaris.';
            addGenericVariantButton.textContent = 'Tambah Varian';
        }

        basePriceField.style.display = hidesBaseFields ? 'none' : 'block';
        basePriceInput.disabled = hidesBaseFields;
        fixedSizeField.style.display = hidesBaseFields ? 'none' : 'block';
        fixedSizeInput.disabled = hidesBaseFields;
        genericToggleField.style.display = usesSummerLabsVariants || isShoePaint ? 'none' : 'block';
        genericToggle.disabled = usesSummerLabsVariants || isShoePaint;
        summerVariantFields.style.display = 'none';
        genericVariantFields.style.display = showsVariantRows ? 'block' : 'none';

        summerVariantInputs.forEach(input => {
            input.disabled = true;
            input.required = false;
        });

        genericVariantFields.querySelectorAll('.generic-variant-input').forEach(input => {
            const isPriceInput = input.name.endsWith('[price]');
            input.disabled = !showsVariantRows;
            input.required = showsVariantRows
                && input.hasAttribute('data-generic-required')
                && !(isShoePaint && isPriceInput);
        });
    };

    let variantIndex = genericRows.querySelectorAll('.generic-variant-row').length;

    addGenericVariantButton.addEventListener('click', function () {
        const usesSummerLabsVariants = brandSelect.selectedOptions[0]?.dataset.usesSizeVariants === 'true';
        const isShoePaint = productCodeInput.value.trim().toUpperCase() === '{{ \App\Models\Product::SHOE_PAINT_CODE }}';

        appendVariantRow({
            type: isShoePaint ? 'color' : 'size',
            value: '',
            code: '',
            price: '',
        });

        if (! usesSummerLabsVariants && ! isShoePaint) {
            genericRows.lastElementChild.querySelector('select[name$="[type]"]').value = 'size';
        }

        toggleVariantPrices();
    });

    genericRows.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.generic-variant-remove');

        if (!removeButton) {
            return;
        }

        const rows = genericRows.querySelectorAll('.generic-variant-row');

        if (rows.length <= 1) {
            rows[0].querySelectorAll('input').forEach(input => input.value = '');
            return;
        }

        removeButton.closest('.generic-variant-row').remove();
        toggleVariantPrices();
    });

    brandSelect.addEventListener('change', toggleVariantPrices);
    productCodeInput.addEventListener('input', toggleVariantPrices);
    genericToggle.addEventListener('change', toggleVariantPrices);
    toggleVariantPrices();
});
</script>
@endpush
