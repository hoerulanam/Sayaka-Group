@extends('layouts.app')
@section('title', 'Checkout Produk')

@section('content')
@php
    $hasVariants = $product->hasVariants();
    $hasColorVariants = $product->variants->contains(fn ($variant) => $variant->color);
    $hasSizeVariants = $product->variants->contains(fn ($variant) => $variant->size || (! $variant->color && $variant->name));
    $hasMixedVariantTypes = $hasColorVariants && $hasSizeVariants;
    $variantFieldLabel = $product->requiresColorSelection()
        ? 'Warna Cat Sepatu'
        : ($hasMixedVariantTypes ? 'Varian Produk' : ($hasColorVariants ? 'Warna Produk' : ($hasSizeVariants ? 'Ukuran Produk' : 'Varian Produk')));
    $unitPrice = (float) ($selectedVariant?->price ?? $product->price ?? 0);
    $availableStock = max(0, (int) ($selectedVariant?->stock ?? $product->stock ?? 0));
    $variantOptions = $product->variants->mapWithKeys(fn ($variant) => [
        $variant->id => [
            'price' => (float) ($variant->price ?? 0),
            'stock' => (int) $variant->stock,
        ],
    ]);
@endphp
<section class="section customer-checkout-page" style="background:#f8fafc; padding: 40px 0;">
    <div class="customer-checkout-wrap" style="max-width:1200px; margin:0 auto; padding:0 20px;">
        <div class="customer-checkout-back-row" style="margin-bottom:24px;">
            <a href="{{ route('brands.show', $product->brand_id) }}" class="btn" style="background:#64748b; color:#fff; padding:10px 20px; text-decoration:none; border-radius:10px; display:inline-block; font-weight:600; transition:background 0.3s;" onmouseover="this.style.background='#475569'" onmouseout="this.style.background='#64748b'">← Kembali ke Produk</a>
        </div>

        <h2 class="customer-checkout-title" style="font-size:2rem; font-weight:800; color:#1e293b; margin-bottom:24px; display:flex; align-items:center; gap:12px;">
            <span style="font-size:2.5rem;">🛍️</span> Checkout Beli Langsung
        </h2>

        @if(session('error'))
            <div class="alert alert-error" style="background:#fef2f2; border-left:4px solid #ef4444; color:#991b1b; padding:16px; border-radius:8px; margin-bottom:24px; font-weight:500;">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-error" style="background:#fef2f2; border-left:4px solid #ef4444; color:#991b1b; padding:16px; border-radius:8px; margin-bottom:24px;">
                <ul style="margin:0; padding-left:20px; font-weight:500;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('checkout.direct', $product) }}" method="post" id="checkoutForm" class="customer-checkout-form">
            @csrf
            <input type="hidden" name="payment_method" value="midtrans_snap">
            
            <!-- Hidden Fields for Shipping Aggregator Selection -->
            <input type="hidden" name="shipping_carrier" id="shippingCarrierInput" required>
            <input type="hidden" name="shipping_service" id="shippingServiceInput" required>
            <input type="hidden" name="shipping_cost" id="shippingCostInput" value="0" required>

            <div class="customer-checkout-layout" style="display:grid; grid-template-columns: 1.6fr 1fr; gap:32px; align-items:start;">
                
                <!-- Kiri: Form & Opsi Pengiriman -->
                <div class="customer-checkout-main" style="display:grid; gap:24px;">
                    
                    <!-- 1. Detail Produk & Jumlah Kuantitas -->
                    <div class="card" style="padding:28px; background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0; margin-bottom:20px; font-size:1.25rem; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <span style="color:#2563eb;">📦</span> Detail Produk
                        </h3>
                        
                        <div class="customer-checkout-product" style="display:flex; gap:20px; align-items:center; flex-wrap:wrap; margin-bottom:20px;">
                            <img src="{{ asset($product->thumbnail ?? 'https://placehold.co/300x300?text=Produk') }}" alt="{{ $product->name }}" loading="lazy" decoding="async" style="width:120px; height:120px; object-fit:cover; border-radius:12px; border:1px solid #e2e8f0; background:#f5f5f5;">
                            <div style="flex:1; min-width:200px;">
                                <h4 style="margin:0 0 8px 0; font-size:1.2rem; font-weight:700; color:#0f172a;">{{ $product->name }}</h4>
                                @if($product->size)
                                    <p style="color:#2563eb; font-size:0.9rem; font-weight:700; margin:0 0 8px 0;">Ukuran: {{ $product->size }}</p>
                                @endif
                                <p style="color:#64748b; font-size:0.9rem; margin:0 0 12px 0; line-height:1.4;">{{ $product->description }}</p>
                                <p style="font-size:1.15rem; color:#2563eb; font-weight:800; margin:0;"><span id="detailUnitPrice">Rp {{ number_format($unitPrice, 0, ',', '.') }}</span> <span style="font-size:0.85rem; color:#64748b; font-weight:400;">/ pcs</span></p>
                            </div>
                        </div>
                        
                        <div style="background:#f8fafc; border-radius:12px; padding:16px;">
                            @if($hasVariants)
                                <label for="product_variant_id" style="display:block; font-weight:600; font-size:0.9rem; color:#475569; margin-bottom:8px;">{{ $variantFieldLabel }} <span style="color:#ef4444;">*</span></label>
                                <select id="product_variant_id" name="product_variant_id" required style="width:100%; max-width:320px; padding:10px; margin-bottom:16px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:0.95rem; color:#1e293b;">
                                    @foreach($product->variants as $variant)
                                        <option value="{{ $variant->id }}" @selected($selectedVariant?->id === $variant->id) @disabled($variant->stock < 1)>
                                            {{ $variant->attributeName() }}: {{ $variant->label() }} - Rp {{ number_format($variant->price ?? 0, 0, ',', '.') }} (stok {{ $variant->stock }})
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            <label for="quantity" style="display:block; font-weight:600; font-size:0.9rem; color:#475569; margin-bottom:8px;">Jumlah Produk yang Ingin Dibeli <span style="color:#ef4444;">*</span></label>
                            <input id="quantity" name="quantity" type="number" min="1" max="{{ $availableStock }}" value="{{ old('quantity', 1) }}" required style="width:100px; padding:10px; border-radius:8px; border:1px solid #cbd5e1; font-size:1rem; font-weight:700; color:#1e293b; text-align:center;">
                            <div id="quantityStockWarning" style="display:none; margin-top:10px; border:1px solid #fecaca; background:#fef2f2; color:#991b1b; border-radius:10px; padding:10px 12px; font-size:0.85rem; font-weight:700; line-height:1.5;"></div>
                            <p id="detailAvailableStock" style="margin:10px 0 0; color:#64748b; font-size:0.85rem; font-weight:600;">Stok tersedia: {{ $availableStock }} item</p>
                        </div>
                    </div>

                    <!-- 2. Data Penerima & Alamat -->
                    <div class="card" style="padding:28px; background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0; margin-bottom:20px; font-size:1.25rem; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <span style="color:#2563eb;">👤</span> Informasi Pengiriman
                        </h3>
                        
                        <div style="display:grid; gap:20px;">
                            <div>
                                <label for="phone" style="display:block; font-weight:600; font-size:0.9rem; color:#475569; margin-bottom:8px;">Nomor HP Penerima <span style="color:#ef4444;">*</span></label>
                                <div style="position:relative;">
                                    <span style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-weight:500;">📞</span>
                                    <input type="text" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" required placeholder="Contoh: 08123456789" style="width:100%; padding:12px 12px 12px 40px; border-radius:10px; border:1px solid #cbd5e1; font-size:0.95rem; color:#1e293b; transition:border-color 0.2s;" onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#cbd5e1'">
                                </div>
                            </div>
                            
                            <div>
                                <label for="shipping_address" style="display:block; font-weight:600; font-size:0.9rem; color:#475569; margin-bottom:8px;">Alamat Pengiriman Lengkap <span style="color:#ef4444;">*</span></label>
                                <div style="position:relative;">
                                    <span style="position:absolute; left:14px; top:15px; color:#94a3b8;">📍</span>
                                    <textarea id="shipping_address" name="shipping_address" required rows="4" placeholder="Tuliskan nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota, dan kode pos..." style="width:100%; padding:12px 12px 12px 40px; border-radius:10px; border:1px solid #cbd5e1; font-size:0.95rem; color:#1e293b; resize:vertical; transition:border-color 0.2s;" onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#cbd5e1'">{{ old('shipping_address') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Shipping Aggregator Widget -->
                    <div class="card" style="padding:28px; background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0; margin-bottom:20px; font-size:1.25rem; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <span style="color:#2563eb;">🚚</span> Agregator Pengiriman (Sayaka Ship)
                        </h3>
                        
                        <div style="display:grid; gap:20px;">
                            <!-- Dropdown Kota Tujuan -->
                            <div>
                                <label for="destination_city" style="display:block; font-weight:600; font-size:0.9rem; color:#475569; margin-bottom:8px;">Pilih Kota Tujuan <span style="color:#ef4444;">*</span></label>
                                <select id="destination_city" name="destination_city" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #cbd5e1; font-size:0.95rem; color:#1e293b; background-color:#fff; cursor:pointer;" onchange="loadShippingRates()">
                                    <option value="" disabled selected>-- Pilih Kota Tujuan --</option>
                                    <option value="jakarta">DKI Jakarta (Jabodetabek)</option>
                                    <option value="surabaya">Surabaya, Jawa Timur</option>
                                    <option value="bandung">Bandung, Jawa Barat</option>
                                    <option value="medan">Medan, Sumatera Utara</option>
                                    <option value="makassar">Makassar, Sulawesi Selatan</option>
                                    <option value="denpasar">Denpasar, Bali</option>
                                    <option value="yogyakarta">Yogyakarta, DIY</option>
                                    <option value="semarang">Semarang, Jawa Tengah</option>
                                    <option value="palembang">Palembang, Sumatera Selatan</option>
                                    <option value="balikpapan">Balikpapan, Kalimantan Timur</option>
                                </select>
                            </div>

                            <!-- Opsi Kurir (Grid Card) -->
                            <div id="courier_section" style="display:none;">
                                <label style="display:block; font-weight:600; font-size:0.9rem; color:#475569; margin-bottom:10px;">Pilih Kurir Ekspedisi</label>
                                <div class="customer-courier-grid" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px;">
                                    
                                    <!-- JNE Card -->
                                    <div class="courier-card" data-carrier="JNE" onclick="selectCourier('JNE')" style="border:2px solid #e2e8f0; border-radius:12px; padding:12px; text-align:center; cursor:pointer; transition:all 0.2s; background:#fff;">
                                        <div style="font-size:24px; margin-bottom:4px;">🔵</div>
                                        <div style="font-weight:700; font-size:0.85rem; color:#1e293b;">JNE Express</div>
                                        <span style="font-size:11px; background:#eff6ff; color:#2563eb; padding:2px 6px; border-radius:8px; font-weight:600; display:inline-block; margin-top:4px;">Cepat</span>
                                    </div>

                                    <!-- J&T Card -->
                                    <div class="courier-card" data-carrier="J&T" onclick="selectCourier('J&T')" style="border:2px solid #e2e8f0; border-radius:12px; padding:12px; text-align:center; cursor:pointer; transition:all 0.2s; background:#fff;">
                                        <div style="font-size:24px; margin-bottom:4px;">🔴</div>
                                        <div style="font-weight:700; font-size:0.85rem; color:#1e293b;">J&T Express</div>
                                        <span style="font-size:11px; background:#fef2f2; color:#dc2626; padding:2px 6px; border-radius:8px; font-weight:600; display:inline-block; margin-top:4px;">Aman</span>
                                    </div>

                                    <!-- SiCepat Card -->
                                    <div class="courier-card" data-carrier="SiCepat" onclick="selectCourier('SiCepat')" style="border:2px solid #e2e8f0; border-radius:12px; padding:12px; text-align:center; cursor:pointer; transition:all 0.2s; background:#fff;">
                                        <div style="font-size:24px; margin-bottom:4px;">🟣</div>
                                        <div style="font-weight:700; font-size:0.85rem; color:#1e293b;">SiCepat</div>
                                        <span style="font-size:11px; background:#faf5ff; color:#7c3aed; padding:2px 6px; border-radius:8px; font-weight:600; display:inline-block; margin-top:4px;">Hemat</span>
                                    </div>

                                    <!-- POS Indonesia Card -->
                                    <div class="courier-card" data-carrier="POS" onclick="selectCourier('POS')" style="border:2px solid #e2e8f0; border-radius:12px; padding:12px; text-align:center; cursor:pointer; transition:all 0.2s; background:#fff;">
                                        <div style="font-size:24px; margin-bottom:4px;">🟠</div>
                                        <div style="font-weight:700; font-size:0.85rem; color:#1e293b;">POS Indo</div>
                                        <span style="font-size:11px; background:#fff7ed; color:#ea580c; padding:2px 6px; border-radius:8px; font-weight:600; display:inline-block; margin-top:4px;">Nusantara</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Loading Spinner Simulator -->
                            <div id="shipping_loading" style="display:none; text-align:center; padding:20px; background:#f8fafc; border-radius:12px; border:1px dashed #cbd5e1; margin-top:8px;">
                                <div style="display:inline-block; width:28px; height:28px; border:3px solid #cbd5e1; border-top-color:#2563eb; border-radius:50%; animation:spin 0.8s linear infinite; margin-bottom:8px;"></div>
                                <div style="font-size:0.9rem; font-weight:600; color:#475569;">Mencari tarif kurir terbaik...</div>
                            </div>

                            <!-- Opsi Layanan Kurir -->
                            <div id="services_section" style="display:none;">
                                <label style="display:block; font-weight:600; font-size:0.9rem; color:#475569; margin-bottom:10px;">Pilih Layanan Pengiriman</label>
                                <div id="services_list" style="display:grid; gap:10px;">
                                    <!-- Dynamic elements from JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Kanan: Ringkasan Belanja & Total (Sticky) -->
                <div class="customer-checkout-sidebar" style="position:-webkit-sticky; position:sticky; top:100px; display:grid; gap:24px;">
                    
                    <!-- Rincian Biaya -->
                    <div class="card" style="padding:28px; background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0; margin-bottom:20px; font-size:1.25rem; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                            📋 Ringkasan Pembayaran
                        </h3>

                        <div style="display:grid; gap:12px; background:#f8fafc; border-radius:12px; padding:16px; margin-bottom:20px;">
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#475569;">
                                <span>Harga Satuan:</span>
                                <span style="font-weight:600; color:#0f172a;" id="displayUnitPrice">Rp {{ number_format($unitPrice, 0, ',', '.') }}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#475569;">
                                <span>Jumlah Beli:</span>
                                <span style="font-weight:700; color:#0f172a;" id="displayQuantity">1</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#475569;">
                                <span>Subtotal Produk:</span>
                                <span style="font-weight:600; color:#0f172a;" id="displaySubtotal">Rp {{ number_format($unitPrice, 0, ',', '.') }}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#475569;">
                                <span>Ongkos Kirim:</span>
                                <span style="font-weight:600; color:#0f172a;" id="displayShippingCost">Rp 0</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#475569; border-top:1px dashed #cbd5e1; padding-top:12px; margin-top:4px;">
                                <span style="font-weight:700; color:#0f172a; font-size:1rem;">Total Pembayaran:</span>
                                <span style="font-weight:800; color:#2563eb; font-size:1.15rem;" id="displayGrandTotal">Rp {{ number_format($unitPrice, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Info kurir terpilih -->
                        <div id="selected_shipping_info" style="display:none; font-size:0.85rem; background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; border-radius:10px; padding:12px; margin-bottom:20px; font-weight:500;">
                            🚚 Kurir: <strong id="infoCarrier">-</strong> (<span id="infoService">-</span>)
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="checkoutSubmitBtn" disabled class="btn btn-primary w-full" style="padding:14px; border-radius:30px; font-weight:700; font-size:1.05rem; cursor:pointer; text-align:center; border:none; transition:all 0.2s; box-shadow: 0 10px 15px -3px rgba(37,99,235,0.3); background:#94a3b8; cursor:not-allowed;">
                            Pilih Kota & Pengiriman Terlebih Dahulu
                        </button>
                    </div>

                </div>

            </div>
        </form>
    </div>
</section>

<!-- CSS animations & interactivity styles -->
<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .courier-card.selected {
        border-color: #2563eb !important;
        background-color: #eff6ff !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
    }
    
    .payment-card:hover, .courier-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .payment-card input:checked + div {
        color: #2563eb;
    }
    
    .payment-card:has(input:checked) {
        border-color: #2563eb !important;
        background-color: #f8fafc !important;
    }
</style>

<!-- Shipping Aggregator Simulator Script -->
<script>
    const shippingRatesDatabase = {
        jakarta: {
            "JNE": [
                { service: "Reguler (REG)", cost: 12000, etd: "1-2 Hari" },
                { service: "Express (YES)", cost: 22000, etd: "Besok Sampai" },
                { service: "Oke (Hemat)", cost: 9000, etd: "3-4 Hari" }
            ],
            "J&T": [
                { service: "J&T EZ (Reguler)", cost: 11000, etd: "1-2 Hari" },
                { service: "J&T Super (Express)", cost: 24000, etd: "Besok Sampai" }
            ],
            "SiCepat": [
                { service: "REG (Reguler)", cost: 11500, etd: "1-2 Hari" },
                { service: "GOKIL (Cargo)", cost: 8000, etd: "3-4 Hari" }
            ],
            "POS": [
                { service: "Kilat Khusus", cost: 10000, etd: "2-3 Hari" },
                { service: "Express Sameday", cost: 25000, etd: "1 Hari" }
            ]
        },
        surabaya: {
            "JNE": [
                { service: "Reguler (REG)", cost: 22000, etd: "2-3 Hari" },
                { service: "Express (YES)", cost: 35000, etd: "1-2 Hari" },
                { service: "Oke (Hemat)", cost: 18000, etd: "4-5 Hari" }
            ],
            "J&T": [
                { service: "J&T EZ (Reguler)", cost: 21000, etd: "2-3 Hari" },
                { service: "J&T Super (Express)", cost: 38000, etd: "1-2 Hari" }
            ],
            "SiCepat": [
                { service: "REG (Reguler)", cost: 20000, etd: "2-3 Hari" },
                { service: "GOKIL (Cargo)", cost: 15000, etd: "4-6 Hari" }
            ],
            "POS": [
                { service: "Kilat Khusus", cost: 19000, etd: "3-4 Hari" }
            ]
        },
        bandung: {
            "JNE": [
                { service: "Reguler (REG)", cost: 15000, etd: "1-2 Hari" },
                { service: "Express (YES)", cost: 26000, etd: "Besok Sampai" }
            ],
            "J&T": [
                { service: "J&T EZ (Reguler)", cost: 14000, etd: "1-2 Hari" }
            ],
            "SiCepat": [
                { service: "REG (Reguler)", cost: 14500, etd: "1-2 Hari" }
            ],
            "POS": [
                { service: "Kilat Khusus", cost: 13000, etd: "2-3 Hari" }
            ]
        },
        default: {
            "JNE": [
                { service: "Reguler (REG)", cost: 32000, etd: "3-4 Hari" },
                { service: "Express (YES)", cost: 48000, etd: "2 Hari" },
                { service: "Oke (Hemat)", cost: 25000, etd: "5-7 Hari" }
            ],
            "J&T": [
                { service: "J&T EZ (Reguler)", cost: 30000, etd: "3-4 Hari" }
            ],
            "SiCepat": [
                { service: "REG (Reguler)", cost: 29000, etd: "3-4 Hari" },
                { service: "GOKIL (Cargo)", cost: 20000, etd: "5-7 Hari" }
            ],
            "POS": [
                { service: "Kilat Khusus", cost: 26000, etd: "4-5 Hari" }
            ]
        }
    };

    const variantOptions = @json($variantOptions);
    let unitPrice = {{ $unitPrice }};
    let availableStock = {{ $availableStock }};
    const quantityInput = document.getElementById('quantity');
    const variantSelect = document.getElementById('product_variant_id');
    const quantityStockWarning = document.getElementById('quantityStockWarning');
    const checkoutForm = document.getElementById('checkoutForm');
    
    let selectedCarrier = "";
    let selectedService = "";
    let selectedCost = 0;

    function updateVariantSelection() {
        if (!variantSelect) {
            return;
        }

        const variant = variantOptions[variantSelect.value];

        if (!variant) {
            return;
        }

        unitPrice = Number(variant.price || 0);
        availableStock = Number(variant.stock || 0);
        quantityInput.max = Math.max(1, availableStock);

        document.getElementById('detailUnitPrice').textContent = "Rp " + new Intl.NumberFormat('id-ID').format(unitPrice);
        document.getElementById('displayUnitPrice').textContent = "Rp " + new Intl.NumberFormat('id-ID').format(unitPrice);
        document.getElementById('detailAvailableStock').textContent = 'Stok tersedia: ' + availableStock + ' item';
        document.getElementById('displaySubtotal').textContent = "Rp " + new Intl.NumberFormat('id-ID').format(getSubtotal());

        updateSummary();
    }

    function getQuantityValue() {
        const qty = parseInt(quantityInput.value, 10);
        return Number.isFinite(qty) ? qty : 1;
    }

    function quantityStockMessage(qty) {
        if (availableStock < 1) {
            return 'Stok produk sedang kosong. Silakan pilih produk lain.';
        }

        return 'Stok produk hanya tersedia ' + availableStock + ' item, sedangkan jumlah yang dipilih ' + qty + ' item.';
    }

    function updateQuantityWarning() {
        const qty = getQuantityValue();
        const hasIssue = availableStock < 1 || qty > availableStock;

        if (quantityStockWarning) {
            quantityStockWarning.style.display = hasIssue ? 'block' : 'none';
            quantityStockWarning.textContent = hasIssue ? quantityStockMessage(qty) : '';
        }

        quantityInput.style.borderColor = hasIssue ? '#ef4444' : '#cbd5e1';

        return hasIssue;
    }

    function getSubtotal() {
        return unitPrice * getQuantityValue();
    }

    quantityInput.addEventListener('input', () => {
        const qty = getQuantityValue();
        if(qty < 1) quantityInput.value = 1;
        
        document.getElementById('displayQuantity').textContent = quantityInput.value;
        document.getElementById('displaySubtotal').textContent = "Rp " + new Intl.NumberFormat('id-ID').format(getSubtotal());
        
        updateSummary();
    });

    variantSelect?.addEventListener('change', updateVariantSelection);

    checkoutForm.addEventListener('submit', function (event) {
        if (updateQuantityWarning()) {
            event.preventDefault();
            quantityStockWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
            quantityInput.focus();
        }
    });

    function loadShippingRates() {
        document.querySelectorAll('.courier-card').forEach(card => card.classList.remove('selected'));
        document.getElementById('services_section').style.display = "none";
        document.getElementById('services_list').innerHTML = "";
        
        selectedCarrier = "";
        selectedService = "";
        selectedCost = 0;
        updateSummary();

        document.getElementById('courier_section').style.display = "block";
    }

    function selectCourier(carrier) {
        selectedCarrier = carrier;
        
        document.querySelectorAll('.courier-card').forEach(card => {
            if(card.getAttribute('data-carrier') === carrier) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });

        document.getElementById('services_section').style.display = "none";
        document.getElementById('shipping_loading').style.display = "block";

        setTimeout(() => {
            document.getElementById('shipping_loading').style.display = "none";
            renderServices();
        }, 800);
    }

    function renderServices() {
        const city = document.getElementById('destination_city').value;
        const rates = shippingRatesDatabase[city] || shippingRatesDatabase['default'];
        const carrierServices = rates[selectedCarrier] || [];
        
        const listContainer = document.getElementById('services_list');
        listContainer.innerHTML = "";

        if (carrierServices.length === 0) {
            listContainer.innerHTML = `<div style="padding:12px; color:#ef4444; font-weight:600;">Layanan tidak tersedia.</div>`;
            document.getElementById('services_section').style.display = "block";
            return;
        }

        carrierServices.forEach((serv, index) => {
            const formattedCost = new Intl.NumberFormat('id-ID').format(serv.cost);
            const isChecked = selectedService === serv.service;

            const labelEl = document.createElement('label');
            labelEl.style.cssText = "display:flex; justify-content:space-between; align-items:center; padding:14px; border:1px solid #cbd5e1; border-radius:10px; cursor:pointer; background:#fff; transition:all 0.15s;";
            labelEl.className = "service-option";
            
            labelEl.innerHTML = `
                <div style="display:flex; align-items:center; gap:12px;">
                    <input type="radio" name="temp_service" value="${serv.service}" data-cost="${serv.cost}" ${isChecked ? 'checked' : ''} onchange="applyService('${serv.service}', ${serv.cost})" style="accent-color:#2563eb; width:16px; height:16px;">
                    <div>
                        <strong style="color:#0f172a; font-size:0.92rem;">${serv.service}</strong>
                        <div style="font-size:0.8rem; color:#64748b; margin-top:2px;">Estimasi: ${serv.etd}</div>
                    </div>
                </div>
                <div style="font-weight:700; color:#2563eb; font-size:0.95rem;">
                    Rp ${formattedCost}
                </div>
            `;
            
            labelEl.onmouseover = () => labelEl.style.borderColor = "#2563eb";
            labelEl.onmouseout = () => {
                if(!labelEl.querySelector('input').checked) {
                    labelEl.style.borderColor = "#cbd5e1";
                }
            };
            
            listContainer.appendChild(labelEl);
        });

        document.getElementById('services_section').style.display = "block";
    }

    function applyService(serviceName, cost) {
        selectedService = serviceName;
        selectedCost = cost;

        document.querySelectorAll('.service-option').forEach(el => {
            const radio = el.querySelector('input');
            if(radio && radio.checked) {
                el.style.borderColor = "#2563eb";
                el.style.backgroundColor = "#eff6ff";
            } else {
                el.style.borderColor = "#cbd5e1";
                el.style.backgroundColor = "#fff";
            }
        });

        document.getElementById('shippingCarrierInput').value = selectedCarrier;
        document.getElementById('shippingServiceInput').value = selectedService;
        document.getElementById('shippingCostInput').value = selectedCost;

        updateSummary();
    }

    function updateSummary() {
        const hasQuantityIssue = updateQuantityWarning();
        const formattedShipping = new Intl.NumberFormat('id-ID').format(selectedCost);
        const formattedGrandTotal = new Intl.NumberFormat('id-ID').format(getSubtotal() + selectedCost);

        document.getElementById('displayShippingCost').textContent = "Rp " + formattedShipping;
        document.getElementById('displayGrandTotal').textContent = "Rp " + formattedGrandTotal;

        if (selectedCarrier && selectedService && !hasQuantityIssue) {
            document.getElementById('infoCarrier').textContent = selectedCarrier;
            document.getElementById('infoService').textContent = selectedService;
            document.getElementById('selected_shipping_info').style.display = "block";

            const submitBtn = document.getElementById('checkoutSubmitBtn');
            submitBtn.disabled = false;
            submitBtn.style.background = "#2563eb";
            submitBtn.style.color = "#fff";
            submitBtn.style.cursor = "pointer";
            submitBtn.textContent = "Bayar Sekarang";
        } else {
            document.getElementById('selected_shipping_info').style.display = "none";

            const submitBtn = document.getElementById('checkoutSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.style.background = "#94a3b8";
            submitBtn.style.color = "#fff";
            submitBtn.style.cursor = "not-allowed";
            submitBtn.textContent = hasQuantityIssue ? "Jumlah Melebihi Stok" : "Pilih Kota & Pengiriman Terlebih Dahulu";
        }
    }

    updateSummary();
</script>
@endsection
