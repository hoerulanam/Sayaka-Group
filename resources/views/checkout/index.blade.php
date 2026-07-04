@extends('layouts.app')
@section('title', 'Checkout Keranjang')

@section('content')
<section class="section customer-checkout-page" style="background:#f8fafc; padding: 40px 0;">
    <div class="customer-checkout-wrap" style="max-width:1200px; margin:0 auto; padding:0 20px;">
        <h2 class="customer-checkout-title" style="font-size:2rem; font-weight:800; color:#1e293b; margin-bottom:24px; display:flex; align-items:center; gap:12px;">
            <span style="font-size:2.5rem;">🛍️</span> Checkout Pesanan
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

        <form action="{{ route('checkout.store') }}" method="post" id="checkoutForm" class="customer-checkout-form">
            @csrf
            <input type="hidden" name="payment_method" value="midtrans_snap">
            @foreach($selectedCartIds as $cartId)
                <input type="hidden" name="cart_items[]" value="{{ $cartId }}">
            @endforeach
            
            <!-- Hidden Fields for Shipping Aggregator Selection -->
            <input type="hidden" name="shipping_carrier" id="shippingCarrierInput" required>
            <input type="hidden" name="shipping_service" id="shippingServiceInput" required>
            <input type="hidden" name="shipping_cost" id="shippingCostInput" value="0" required>

            <div class="customer-checkout-layout" style="display:grid; grid-template-columns: 1.6fr 1fr; gap:32px; align-items:start;">
                
                <!-- Kiri: Form & Opsi Pengiriman -->
                <div class="customer-checkout-main" style="display:grid; gap:24px;">
                    
                    <!-- 1. Data Penerima & Alamat -->
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

                    <!-- 2. Shipping Aggregator Widget -->
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
                    
                    <!-- Rincian Produk -->
                    <div class="card" style="padding:28px; background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0; margin-bottom:20px; font-size:1.25rem; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                            📋 Ringkasan Pesanan
                        </h3>
                        
                        <div class="customer-checkout-order-items" style="display:grid; gap:16px; max-height:220px; overflow-y:auto; padding-right:6px; margin-bottom:20px;">
                            @foreach($carts as $cart)
                                @php
                                    $unitPrice = $cart->unitPrice();
                                    $variantLabel = $cart->productVariant?->label();
                                    $variantAttribute = $cart->productVariant?->attributeName() ?? 'Varian';
                                    $displayPackageSize = $cart->productVariant?->color ? $cart->product?->size : null;
                                    $displayFixedSize = ! $variantLabel ? $cart->product?->size : null;
                                @endphp
                                <div style="display:flex; gap:12px; align-items:center; padding-bottom:12px; border-bottom:1px solid #f1f5f9;">
                                    <img src="{{ asset($cart->product->thumbnail) }}" alt="{{ $cart->product->name }}" loading="lazy" decoding="async" style="width:50px; height:50px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0;">
                                    <div style="flex:1; min-width:0;">
                                        <h4 style="margin:0 0 4px 0; font-size:0.85rem; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $cart->product->name }}</h4>
                                        @if($displayPackageSize)
                                            <p style="margin:0 0 3px; font-size:0.78rem; color:#2563eb; font-weight:700;">Ukuran {{ $displayPackageSize }}</p>
                                        @endif
                                        @if($cart->productVariant?->color)
                                            <p style="margin:0 0 3px; font-size:0.78rem; color:#2563eb; font-weight:700;">Warna {{ $cart->productVariant->color }}</p>
                                        @elseif($variantLabel)
                                            <p style="margin:0 0 3px; font-size:0.78rem; color:#2563eb; font-weight:700;">{{ $variantAttribute }} {{ $variantLabel }}</p>
                                        @elseif($displayFixedSize)
                                            <p style="margin:0 0 3px; font-size:0.78rem; color:#2563eb; font-weight:700;">Ukuran {{ $displayFixedSize }}</p>
                                        @endif
                                        <p style="margin:0; font-size:0.8rem; color:#64748b;">{{ $cart->quantity }}x @ Rp {{ number_format($unitPrice, 0, ',', '.') }}</p>
                                    </div>
                                    <div style="font-weight:700; font-size:0.9rem; color:#0f172a;">
                                        Rp {{ number_format($cart->quantity * $unitPrice, 0, ',', '.') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Rincian Biaya -->
                        <div style="display:grid; gap:12px; background:#f8fafc; border-radius:12px; padding:16px; margin-bottom:20px;">
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#475569;">
                                <span>Subtotal Produk:</span>
                                <span style="font-weight:600; color:#0f172a;">Rp {{ number_format($total, 0, ',', '.') }}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#475569;">
                                <span>Ongkos Kirim:</span>
                                <span style="font-weight:600; color:#0f172a;" id="displayShippingCost">Rp 0</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#475569; border-top:1px dashed #cbd5e1; padding-top:12px; margin-top:4px;">
                                <span style="font-weight:700; color:#0f172a; font-size:1rem;">Total Pembayaran:</span>
                                <span style="font-weight:800; color:#2563eb; font-size:1.15rem;" id="displayGrandTotal">Rp {{ number_format($total, 0, ',', '.') }}</span>
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
    // Simulator data untuk kota dan kurir beserta biayanya
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
        // default rates untuk luar kota lainnya
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

    const subtotalProducts = {{ $total }};
    let selectedCarrier = "";
    let selectedService = "";
    let selectedCost = 0;

    function loadShippingRates() {
        // Reset kurir dan layanan jika kota berubah
        document.querySelectorAll('.courier-card').forEach(card => card.classList.remove('selected'));
        document.getElementById('services_section').style.display = "none";
        document.getElementById('services_list').innerHTML = "";
        
        selectedCarrier = "";
        selectedService = "";
        selectedCost = 0;
        updateSummary();

        // Tampilkan section kurir
        document.getElementById('courier_section').style.display = "block";
    }

    function selectCourier(carrier) {
        selectedCarrier = carrier;
        
        // Update highlight courier cards
        document.querySelectorAll('.courier-card').forEach(card => {
            if(card.getAttribute('data-carrier') === carrier) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });

        // Tampilkan loading spinner
        document.getElementById('services_section').style.display = "none";
        document.getElementById('shipping_loading').style.display = "block";

        // Simulasi loading aggregator selama 800ms
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
            
            // Hover effect
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

        // Highlight selected service option
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

        // Set hidden form fields
        document.getElementById('shippingCarrierInput').value = selectedCarrier;
        document.getElementById('shippingServiceInput').value = selectedService;
        document.getElementById('shippingCostInput').value = selectedCost;

        updateSummary();
    }

    function updateSummary() {
        const formattedShipping = new Intl.NumberFormat('id-ID').format(selectedCost);
        const formattedGrandTotal = new Intl.NumberFormat('id-ID').format(subtotalProducts + selectedCost);

        document.getElementById('displayShippingCost').textContent = "Rp " + formattedShipping;
        document.getElementById('displayGrandTotal').textContent = "Rp " + formattedGrandTotal;

        if (selectedCarrier && selectedService) {
            document.getElementById('infoCarrier').textContent = selectedCarrier;
            document.getElementById('infoService').textContent = selectedService;
            document.getElementById('selected_shipping_info').style.display = "block";

            // Enable submit button
            const submitBtn = document.getElementById('checkoutSubmitBtn');
            submitBtn.disabled = false;
            submitBtn.style.background = "#2563eb";
            submitBtn.style.color = "#fff";
            submitBtn.style.cursor = "pointer";
            submitBtn.textContent = "Bayar Sekarang";
        } else {
            document.getElementById('selected_shipping_info').style.display = "none";

            // Disable submit button
            const submitBtn = document.getElementById('checkoutSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.style.background = "#94a3b8";
            submitBtn.style.color = "#fff";
            submitBtn.style.cursor = "not-allowed";
            submitBtn.textContent = "Pilih Kota & Pengiriman Terlebih Dahulu";
        }
    }
</script>
@endsection
