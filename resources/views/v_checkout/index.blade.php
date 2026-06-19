<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} - BreadLab</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        body { background: #fffaf3; color: #24160f; font-family: Poppins, sans-serif; }
        .panel { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); }
        .btn-bread { background: #8B4513; border-color: #8B4513; color: #fff; }
        .btn-bread:hover { background: #D2691E; border-color: #D2691E; color: #fff; }
        .choice-card { border: 1px solid #ead8bf; border-radius: 8px; cursor: pointer; }
        .choice-card:has(.form-check-input:checked) { border-color: #8B4513; box-shadow: 0 0 0 3px rgba(139, 69, 19, .14); }
        .summary-line { border-bottom: 1px solid #f0e4d3; }
        .cream-note { background: #fff4e4; border: 1px solid #efd8b6; color: #6d3b12; }
        .step-label { color: #8B4513; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        #delivery-map { border: 1px solid #ead8bf; border-radius: 8px; height: 280px; width: 100%; }
        .shipping-service-row.selected { background: #fff4e4; box-shadow: inset 4px 0 0 #8B4513; }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        <div class="mb-4">
            <a href="{{ route('cart.index') }}" class="text-decoration-none">&larr; Kembali ke keranjang</a>
            <h1 class="h3 mt-3 mb-1">Checkout</h1>
            <p class="text-muted mb-0">Pilih metode transaksi dan pembayaran untuk membuat pesanan.</p>
        </div>

        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('checkout.process') }}" method="post" id="checkout-form">
            @csrf
            <input type="hidden" name="delivery_latitude" id="delivery_latitude" value="{{ old('delivery_latitude') }}">
            <input type="hidden" name="delivery_longitude" id="delivery_longitude" value="{{ old('delivery_longitude') }}">
            <input type="hidden" name="destination" id="destination" value="{{ old('destination') }}">
            <input type="hidden" name="province_name" id="province_name" value="{{ old('province_name') }}">
            <input type="hidden" name="city_name" id="city_name" value="{{ old('city_name') }}">

            <div class="row g-4">
                <section class="col-lg-8">
                    <div class="panel p-4 mb-4">
                        <div class="step-label mb-2">Step 1</div>
                        <h2 class="h5 mb-3">Metode Transaksi</h2>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" value="pickup" @checked(old('transaction_method', 'pickup') === 'pickup') required>
                                        <span class="form-check-label fw-semibold">Pickup</span>
                                    </div>
                                    <div class="small text-muted mt-2">Ambil pesanan di toko. Ongkir Rp 0.</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" value="local_delivery" @checked(old('transaction_method') === 'local_delivery') required>
                                        <span class="form-check-label fw-semibold">Local Delivery</span>
                                    </div>
                                    <div class="small text-muted mt-2">Gunakan lokasi dari browser dan peta OpenStreetMap.</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input transaction-radio" type="radio" name="transaction_method" value="shipping" @checked(old('transaction_method') === 'shipping') required>
                                        <span class="form-check-label fw-semibold">Shipping</span>
                                    </div>
                                    <div class="small text-muted mt-2">Pilih kota, kurir, dan service dari RajaOngkir.</div>
                                </label>
                            </div>
                        </div>
                        @error('transaction_method')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="panel p-4 mb-4">
                        <div class="step-label mb-2">Step 2</div>
                        <h2 class="h5 mb-3">Metode Pembayaran</h2>
                        <div class="row g-3">
                            <div class="col-md-4 payment-option" data-methods="pickup,local_delivery">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input payment-radio" type="radio" name="payment_method" value="cash" @checked(old('payment_method', 'cash') === 'cash') required>
                                        <span class="form-check-label fw-semibold">Cash</span>
                                    </div>
                                    <div class="small text-muted mt-2">Hanya untuk pickup dan local delivery.</div>
                                </label>
                            </div>
                            <div class="col-md-4 payment-option" data-methods="pickup,local_delivery,shipping">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input payment-radio" type="radio" name="payment_method" value="bank_transfer" @checked(old('payment_method') === 'bank_transfer') required>
                                        <span class="form-check-label fw-semibold">Bank Transfer</span>
                                    </div>
                                    <div class="small text-muted mt-2">Status pembayaran pending sampai diverifikasi.</div>
                                </label>
                            </div>
                            <div class="col-md-4 payment-option" data-methods="pickup,local_delivery,shipping">
                                <label class="choice-card d-block p-3 h-100">
                                    <div class="form-check">
                                        <input class="form-check-input payment-radio" type="radio" name="payment_method" value="midtrans" @checked(old('payment_method') === 'midtrans') required>
                                        <span class="form-check-label fw-semibold">Midtrans</span>
                                    </div>
                                    <div class="small text-muted mt-2">Placeholder. Snap akan dibuat pada tahap Midtrans.</div>
                                </label>
                            </div>
                        </div>
                        @error('payment_method')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="panel p-4 mb-4">
                        <div class="step-label mb-2">Step 3</div>
                        <h2 class="h5 mb-3">Detail Pesanan</h2>
                        <div class="row g-3">
                            <div class="col-12 local-field">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <label class="form-label mb-0">Lokasi Local Delivery</label>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="use-location">Gunakan Lokasi Saya</button>
                                </div>
                                <div id="delivery-map"></div>
                                <div class="small text-muted mt-2" id="location-status">Koordinat akan terisi setelah lokasi diizinkan dari browser.</div>
                            </div>

                            <div class="col-12 local-field">
                                <label for="delivery_address" class="form-label">Alamat Local Delivery</label>
                                <textarea name="delivery_address" id="delivery_address" rows="3" class="form-control @error('delivery_address') is-invalid @enderror">{{ old('delivery_address', auth()->user()->customer->address ?? '') }}</textarea>
                                @error('delivery_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 local-field">
                                <label for="delivery_note" class="form-label">Catatan Delivery</label>
                                <textarea name="delivery_note" id="delivery_note" rows="2" class="form-control @error('delivery_note') is-invalid @enderror">{{ old('delivery_note') }}</textarea>
                                @error('delivery_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 shipping-field">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <label for="customer_address_id" class="form-label mb-0">Alamat Pengiriman</label>
                                    <a href="{{ route('customer.address.create') }}" class="btn btn-outline-dark btn-sm">Tambah Alamat Baru</a>
                                </div>
                                @if($customerAddresses->isNotEmpty())
                                <select name="customer_address_id" id="customer_address_id" class="form-control @error('customer_address_id') is-invalid @enderror">
                                    <option value="">Pilih alamat tersimpan</option>
                                    @foreach($customerAddresses as $savedAddress)
                                    <option value="{{ $savedAddress->id }}" @selected((int) old('customer_address_id', optional($customerAddresses->firstWhere('is_default', true))->id) === (int) $savedAddress->id)>
                                        {{ $savedAddress->label ?: 'Alamat' }} - {{ $savedAddress->receiver_name }} - {{ $savedAddress->city_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('customer_address_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="small text-muted mt-2" id="selected-address-preview">Pilih alamat untuk mengisi data shipping.</div>
                                <a href="{{ route('customer.address.index') }}" class="btn btn-link p-0 mt-1">Ubah Alamat</a>
                                @else
                                <div class="alert alert-warning mb-0">
                                    Tambahkan alamat pengiriman terlebih dahulu sebelum memakai shipping.
                                    <a href="{{ route('customer.address.create') }}" class="alert-link">Tambah alamat</a>
                                </div>
                                @endif
                            </div>

                            <div class="col-12 shipping-field">
                                <label for="address" class="form-label">Alamat Shipping</label>
                                <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', auth()->user()->customer->address ?? '') }}</textarea>
                                @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 shipping-field">
                                <label for="postal_code" class="form-label">Kode Pos</label>
                                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', auth()->user()->customer->postal_code ?? '') }}" class="form-control @error('postal_code') is-invalid @enderror">
                                @error('postal_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 shipping-field">
                                <label for="province" class="form-label">Provinsi</label>
                                <select name="province_id" id="province" class="form-control">
                                    <option value="">Pilih provinsi</option>
                                </select>
                            </div>
                            <div class="col-md-6 shipping-field">
                                <label for="city" class="form-label">Kota/Kabupaten</label>
                                <select id="city" class="form-control">
                                    <option value="">Pilih kota</option>
                                </select>
                                @error('destination')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 shipping-field">
                                <label for="courier_select" class="form-label">Kurir</label>
                                <select id="courier_select" class="form-control @error('courier') is-invalid @enderror">
                                    <option value="">Pilih kurir</option>
                                    @foreach($couriers as $code => $label)
                                    <option value="{{ $code }}" @selected(old('courier') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('courier')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 shipping-field">
                                <button type="button" class="btn btn-bread" id="check-shipping-cost">Cek Ongkir</button>
                                <div class="text-danger small mt-2 d-none" id="shipping-error"></div>
                            </div>
                            <div class="col-12 shipping-field">
                                <label class="form-label">Hasil Ongkir</label>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Layanan</th>
                                                <th>Biaya</th>
                                                <th>Estimasi Pengiriman</th>
                                                <th>Total Berat</th>
                                                <th>Total Harga</th>
                                                <th>Bayar/Pilih Pengiriman</th>
                                            </tr>
                                        </thead>
                                        <tbody id="shipping-results">
                                            <tr>
                                                <td colspan="6" class="text-muted">Pilih provinsi, kota/kabupaten, kurir, alamat, dan kode pos. Lalu klik Cek Ongkir.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <input type="hidden" name="courier" id="courier" value="{{ old('courier') }}">
                                <input type="hidden" name="service" id="service" value="{{ old('service') }}">
                                <input type="hidden" name="shipping_cost" id="shipping_cost" value="{{ old('shipping_cost', 0) }}">
                                <input type="hidden" name="estimated_delivery" id="estimated_delivery" value="{{ old('estimated_delivery') }}">
                                @error('service')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                @error('shipping_cost')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Catatan Pesanan</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="cream-note rounded-2 p-3 small">
                        Local delivery memakai lokasi browser dan OpenStreetMap. Shipping memakai RajaOngkir jika API key tersedia. Midtrans masih placeholder pada tahap ini.
                    </div>
                </section>

                <aside class="col-lg-4">
                    <div class="panel p-4 position-sticky" style="top: 24px;">
                        <h2 class="h5 mb-3">Ringkasan Pesanan</h2>
                        @foreach($cart->items as $item)
                        <div class="d-flex justify-content-between gap-3 summary-line py-2">
                            <div>
                                <div class="fw-semibold">{{ $item->product->name }}</div>
                                <small class="text-muted">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</small>
                            </div>
                            <strong>Rp {{ number_format($item->subtotal(), 0, ',', '.') }}</strong>
                        </div>
                        @endforeach

                        <div class="d-flex justify-content-between mt-3">
                            <span>Subtotal</span>
                            <strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Total Berat</span>
                            <strong>{{ number_format($totalWeight, 0, ',', '.') }} gram</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Biaya Pengiriman</span>
                            <strong id="shipping-cost-summary">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-3 mb-4 pt-3 border-top">
                            <span>Total Bayar</span>
                            <strong id="grand-total">Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                        </div>
                        <button type="submit" class="btn btn-bread w-100">Buat Pesanan</button>
                    </div>
                </aside>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @php
        $customerAddressOptions = $customerAddresses->map(function ($address) {
            return [
                'id' => (string) $address->id,
                'receiver_name' => $address->receiver_name,
                'receiver_phone' => $address->receiver_phone,
                'province_id' => $address->province_id,
                'province_name' => $address->province_name,
                'city_id' => $address->city_id,
                'city_name' => $address->city_name,
                'district_id' => $address->district_id,
                'district_name' => $address->district_name,
                'postal_code' => $address->postal_code,
                'full_address' => $address->full_address,
                'detail_address' => $address->detail_address,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'label' => $address->label,
                'is_default' => $address->is_default,
                'snapshot' => $address->snapshotAddress(),
            ];
        })->values();
    @endphp
    <script>
        const subtotal = Number(@json($subtotal));
        const totalWeight = Number(@json($totalWeight));
        const localDeliveryFee = Number(@json($localDeliveryFee));
        const routes = {
            provinces: @json(route('rajaongkir.provinces')),
            cities: @json(route('rajaongkir.cities')),
            cost: @json(route('rajaongkir.cost')),
        };
        const csrfToken = @json(csrf_token());
        const oldDestination = @json(old('destination'));
        const oldService = @json(old('service'));
        const oldShippingCost = Number(@json(old('shipping_cost', 0)));
        const oldEstimatedDelivery = @json(old('estimated_delivery'));
        const rajaOngkirMode = @json(config('services.rajaongkir.mode', 'starter'));
        const customerAddresses = @json($customerAddressOptions);

        const transactionRadios = document.querySelectorAll('.transaction-radio');
        const paymentOptions = document.querySelectorAll('.payment-option');
        const localFields = document.querySelectorAll('.local-field');
        const shippingFields = document.querySelectorAll('.shipping-field');
        const deliveryAddress = document.getElementById('delivery_address');
        const deliveryNote = document.getElementById('delivery_note');
        const deliveryLatitude = document.getElementById('delivery_latitude');
        const deliveryLongitude = document.getElementById('delivery_longitude');
        const address = document.getElementById('address');
        const postalCode = document.getElementById('postal_code');
        const customerAddress = document.getElementById('customer_address_id');
        const selectedAddressPreview = document.getElementById('selected-address-preview');
        const province = document.getElementById('province');
        const city = document.getElementById('city');
        const destination = document.getElementById('destination');
        const provinceName = document.getElementById('province_name');
        const cityName = document.getElementById('city_name');
        const courierSelect = document.getElementById('courier_select');
        const courier = document.getElementById('courier');
        const service = document.getElementById('service');
        const shippingCost = document.getElementById('shipping_cost');
        const estimatedDelivery = document.getElementById('estimated_delivery');
        const shippingResults = document.getElementById('shipping-results');
        const shippingError = document.getElementById('shipping-error');
        const checkShippingCost = document.getElementById('check-shipping-cost');
        const shippingCostSummary = document.getElementById('shipping-cost-summary');
        const grandTotal = document.getElementById('grand-total');
        const locationStatus = document.getElementById('location-status');
        const useLocation = document.getElementById('use-location');
        let map;
        let marker;

        function rupiah(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(value);
        }

        function selectedTransaction() {
            return document.querySelector('input[name="transaction_method"]:checked')?.value || 'pickup';
        }

        function initMap() {
            if (!window.L || map) {
                return;
            }

            const lat = Number(deliveryLatitude.value || -6.2);
            const lng = Number(deliveryLongitude.value || 106.816666);
            map = L.map('delivery-map').setView([lat, lng], deliveryLatitude.value ? 15 : 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (deliveryLatitude.value && deliveryLongitude.value) {
                setMarker(lat, lng);
            }
        }

        function setMarker(lat, lng) {
            if (!map) {
                initMap();
            }

            if (!marker) {
                marker = L.marker([lat, lng]).addTo(map);
            } else {
                marker.setLatLng([lat, lng]);
            }

            map.setView([lat, lng], 16);
            deliveryLatitude.value = lat.toFixed(7);
            deliveryLongitude.value = lng.toFixed(7);
            locationStatus.textContent = `Koordinat: ${deliveryLatitude.value}, ${deliveryLongitude.value}`;
        }

        function applyCheckoutState() {
            const method = selectedTransaction();
            const isLocal = method === 'local_delivery';
            const isShipping = method === 'shipping';

            localFields.forEach((field) => field.classList.toggle('d-none', !isLocal));
            shippingFields.forEach((field) => field.classList.toggle('d-none', !isShipping));

            deliveryAddress.required = false;
            deliveryNote.required = false;
            address.required = isShipping;
            postalCode.required = isShipping;
            province.required = isShipping;
            city.required = isShipping;
            courierSelect.required = isShipping;
            service.required = false;
            shippingCost.required = false;
            destination.required = false;

            paymentOptions.forEach((option) => {
                const allowed = option.dataset.methods.split(',').includes(method);
                option.classList.toggle('d-none', !allowed);
                const input = option.querySelector('input');
                input.disabled = !allowed;
                if (!allowed && input.checked) {
                    input.checked = false;
                }
            });

            if (!document.querySelector('input[name="payment_method"]:checked:not(:disabled)')) {
                document.querySelector('input[name="payment_method"]:not(:disabled)')?.click();
            }

            if (isLocal) {
                initMap();
                setTimeout(() => map?.invalidateSize(), 120);
            }

            const cost = isLocal ? localDeliveryFee : (isShipping ? Number(shippingCost.value || 0) : 0);
            shippingCostSummary.textContent = rupiah(cost);
            grandTotal.textContent = rupiah(subtotal + cost);
        }

        function optionText(item, fallback) {
            return item.province || item.city_name || item.name || item.label || fallback;
        }

        function selectedAddress() {
            if (!customerAddress) {
                return null;
            }

            return customerAddresses.find((item) => item.id === customerAddress.value) || null;
        }

        function ensureSelectedOption(select, value, text) {
            if (!select || !value) {
                return;
            }

            let option = Array.from(select.options).find((item) => item.value === String(value));

            if (!option) {
                option = document.createElement('option');
                option.value = value;
                option.textContent = text || value;
                select.appendChild(option);
            }

            select.value = value;
        }

        function applySavedAddress() {
            const savedAddress = selectedAddress();

            if (!savedAddress) {
                if (selectedAddressPreview) {
                    selectedAddressPreview.textContent = customerAddresses.length
                        ? 'Pilih alamat untuk mengisi data shipping.'
                        : 'Tambahkan alamat pengiriman terlebih dahulu.';
                }
                return;
            }

            address.value = savedAddress.snapshot || savedAddress.full_address || '';
            postalCode.value = savedAddress.postal_code || '';
            destination.value = rajaOngkirMode === 'komerce'
                ? (savedAddress.district_id || '')
                : (savedAddress.city_id || '');
            provinceName.value = savedAddress.province_name || '';
            cityName.value = savedAddress.city_name || '';
            ensureSelectedOption(province, savedAddress.province_id, savedAddress.province_name);
            ensureSelectedOption(city, savedAddress.city_id, savedAddress.city_name);

            if (selectedAddressPreview) {
                selectedAddressPreview.textContent = `${savedAddress.receiver_name} - ${savedAddress.receiver_phone} - ${savedAddress.snapshot}`;
            }

            clearServices(destination.value ? 'Klik Cek Ongkir untuk menampilkan layanan.' : 'Alamat belum memiliki kecamatan. Edit alamat terlebih dahulu.');
        }

        function rajaResults(payload) {
            return payload?.rajaongkir?.results || payload?.data || [];
        }

        function rajaError(payload, fallback) {
            return payload?.message || payload?.body?.rajaongkir?.status?.description || fallback;
        }

        function normalizeCostServices(results) {
            if (Array.isArray(results) && results[0]?.costs) {
                const courierCode = courierSelect.value;
                const courierName = results[0].name || courierCode.toUpperCase();

                return results[0].costs.map((item) => {
                    const cost = item.cost?.[0] || {};

                    return {
                        courier: courierCode,
                        courier_name: courierName,
                        service: item.service || '',
                        description: item.description || '',
                        cost: parseRawCost(cost.value || 0),
                        estimation: cost.etd || '',
                    };
                }).filter((item) => item.service && item.cost > 0);
            }

            return results.map((item) => ({
                ...item,
                cost: parseRawCost(item.cost ?? item.price ?? item.value ?? 0),
                estimation: item.estimation || item.etd || item.duration || '',
            })).filter((item) => item.service && item.cost > 0);
        }

        function parseRawCost(value) {
            if (typeof value === 'number') {
                return Math.trunc(value);
            }

            if (typeof value === 'string') {
                const normalized = value.replace(/[^\d]/g, '');

                return normalized ? Number(normalized) : 0;
            }

            return 0;
        }

        function isCargoLike(item) {
            const text = `${item.service || ''} ${item.description || ''} ${item.courier_name || ''}`.toLowerCase();
            const blocked = ['jtr', 'jtr<130', 'jtr>130', 'jtr>200', 'cargo', 'trucking'];

            return blocked.some((keyword) => text.includes(keyword));
        }

        function filterReasonableServices(services) {
            return services.filter((item) => {
                const cost = parseRawCost(item.cost);

                if (cost <= 0) {
                    return false;
                }

                if (totalWeight < 10000 && isCargoLike(item)) {
                    return false;
                }

                if (totalWeight < 10000 && subtotal > 0 && cost > subtotal * 5) {
                    return false;
                }

                item.cost = cost;

                return true;
            });
        }

        async function loadProvinces() {
            try {
                const response = await fetch(routes.provinces);
                const payload = await response.json();

                if (!response.ok || payload.success === false) {
                    province.innerHTML = '<option value="">RajaOngkir tidak tersedia</option>';
                    showShippingError(rajaError(payload, 'RajaOngkir tidak tersedia. Periksa API key dan base URL.'));
                    return;
                }

                province.innerHTML = '<option value="">Pilih provinsi</option>';
                rajaResults(payload).forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.province_id || item.id;
                    option.textContent = optionText(item, option.value);
                    province.appendChild(option);
                });
                applySavedAddress();
            } catch (error) {
                province.innerHTML = '<option value="">RajaOngkir tidak tersedia</option>';
            }
        }

        async function loadCities() {
            destination.value = '';
            city.innerHTML = '<option value="">Memuat kota...</option>';
            clearServices('Pilih kota/kabupaten dan kurir, lalu klik Cek Ongkir.');

            if (!province.value) {
                city.innerHTML = '<option value="">Pilih kota</option>';
                return;
            }

            try {
                const response = await fetch(`${routes.cities}?province_id=${encodeURIComponent(province.value)}`);
                const payload = await response.json();

                if (!response.ok || payload.success === false) {
                    city.innerHTML = '<option value="">Kota tidak tersedia</option>';
                    showShippingError(rajaError(payload, 'Kota/kabupaten RajaOngkir tidak tersedia.'));
                    return;
                }

                city.innerHTML = '<option value="">Pilih kota</option>';
                rajaResults(payload).forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.city_id || item.id;
                    option.textContent = `${item.type ? item.type + ' ' : ''}${item.city_name || item.name || option.value}`;
                    city.appendChild(option);
                });
                if (selectedAddress()) {
                    ensureSelectedOption(city, selectedAddress().city_id, selectedAddress().city_name);
                }
            } catch (error) {
                city.innerHTML = '<option value="">Kota tidak tersedia</option>';
            }
        }

        async function loadCosts() {
            hideShippingError();
            clearServices('Memuat service RajaOngkir...');

            if (!customerAddress?.value) {
                showShippingError('Pilih alamat pengiriman tersimpan sebelum cek ongkir.');
                clearServices('Pilih alamat pengiriman tersimpan.');
                return;
            }

            const savedAddress = selectedAddress();

            if (rajaOngkirMode === 'komerce' && !savedAddress?.district_id) {
                showShippingError('Alamat belum memiliki kecamatan. Edit alamat terlebih dahulu.');
                clearServices('Alamat belum memiliki kecamatan. Edit alamat terlebih dahulu.');
                return;
            }

            if (!destination.value || !courierSelect.value || !address.value.trim() || !postalCode.value.trim()) {
                showShippingError('Lengkapi alamat, destination RajaOngkir, kurir, dan kode pos sebelum cek ongkir.');
                clearServices('Lengkapi data shipping, lalu klik Cek Ongkir.');
                return;
            }

            courier.value = courierSelect.value;

            try {
                const response = await fetch(routes.cost, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        destination: destination.value,
                        weight: totalWeight,
                        courier: courierSelect.value,
                    }),
                });
                const payload = await response.json();

                if (!response.ok || payload.success === false) {
                    const message = rajaError(payload, 'RajaOngkir tidak tersedia.');
                    showShippingError(message);
                    clearServices(message);
                    return;
                }

                renderServices(filterReasonableServices(normalizeCostServices(rajaResults(payload))));
            } catch (error) {
                showShippingError('RajaOngkir tidak tersedia. Periksa koneksi dan konfigurasi API.');
                clearServices('RajaOngkir tidak tersedia.');
            }
        }

        function clearServices(message) {
            service.value = '';
            shippingCost.value = 0;
            estimatedDelivery.value = '';
            shippingResults.innerHTML = `<tr><td colspan="6" class="text-muted">${escapeHtml(message)}</td></tr>`;
            applyCheckoutState();
        }

        function renderServices(services) {
            if (!services.length) {
                clearServices('Tidak ada layanan reguler yang tersedia. Coba kurir lain.');
                return;
            }

            shippingResults.innerHTML = '';
            services.forEach((item) => {
                const row = document.createElement('tr');
                row.className = 'shipping-service-row';
                const selected = oldService === item.service && oldShippingCost === Number(item.cost);
                row.innerHTML = `
                    <td>
                        <div class="fw-semibold">${escapeHtml(item.courier_name || item.courier)} ${escapeHtml(item.service)}</div>
                        <div class="small text-muted">${escapeHtml(item.description || '-')}</div>
                    </td>
                    <td>${rupiah(Number(item.cost || 0))}</td>
                    <td>${item.estimation ? escapeHtml(item.estimation) + ' hari' : '-'}</td>
                    <td>${new Intl.NumberFormat('id-ID').format(totalWeight)} gram</td>
                    <td>${rupiah(subtotal + Number(item.cost || 0))}</td>
                    <td>
                        <button type="button" class="btn btn-outline-dark btn-sm select-shipping-service ${selected ? 'active' : ''}" data-service="${escapeHtml(item.service)}" data-cost="${Number(item.cost || 0)}" data-estimation="${escapeHtml(item.estimation || '')}">
                            Pilih Pengiriman
                        </button>
                    </td>
                `;
                shippingResults.appendChild(row);
            });

            document.querySelectorAll('.select-shipping-service').forEach((button) => {
                button.addEventListener('click', () => chooseService(button));
                if (button.classList.contains('active')) {
                    chooseService(button);
                }
            });
        }

        function chooseService(button) {
            document.querySelectorAll('.shipping-service-row').forEach((row) => row.classList.remove('selected'));
            document.querySelectorAll('.select-shipping-service').forEach((option) => option.classList.remove('active'));
            button.classList.add('active');
            button.closest('tr')?.classList.add('selected');
            courier.value = courierSelect.value;
            service.value = button.dataset.service;
            shippingCost.value = button.dataset.cost;
            estimatedDelivery.value = button.dataset.estimation;
            hideShippingError();
            applyCheckoutState();
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value || '';
            return div.innerHTML;
        }

        function showShippingError(message) {
            shippingError.textContent = message;
            shippingError.classList.remove('d-none');
        }

        function hideShippingError() {
            shippingError.textContent = '';
            shippingError.classList.add('d-none');
        }

        useLocation.addEventListener('click', () => {
            if (!navigator.geolocation) {
                locationStatus.textContent = 'Browser tidak mendukung geolocation.';
                return;
            }

            locationStatus.textContent = 'Mengambil lokasi...';
            navigator.geolocation.getCurrentPosition((position) => {
                setMarker(position.coords.latitude, position.coords.longitude);
            }, () => {
                locationStatus.textContent = 'Lokasi tidak bisa diambil. Pastikan permission browser diizinkan.';
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
            });
        });

        province.addEventListener('change', () => {
            provinceName.value = province.options[province.selectedIndex]?.textContent || '';
            loadCities();
        });
        city.addEventListener('change', () => {
            destination.value = city.value;
            cityName.value = city.options[city.selectedIndex]?.textContent || '';
            clearServices('Klik Cek Ongkir untuk menampilkan layanan.');
        });
        customerAddress?.addEventListener('change', applySavedAddress);
        courierSelect.addEventListener('change', () => {
            courier.value = courierSelect.value;
            clearServices('Klik Cek Ongkir untuk menampilkan layanan.');
        });
        address.addEventListener('input', () => {
            if (selectedTransaction() === 'shipping') {
                clearServices('Klik Cek Ongkir untuk memperbarui layanan.');
            }
        });
        postalCode.addEventListener('input', () => {
            if (selectedTransaction() === 'shipping') {
                clearServices('Klik Cek Ongkir untuk memperbarui layanan.');
            }
        });
        checkShippingCost.addEventListener('click', loadCosts);
        transactionRadios.forEach((radio) => radio.addEventListener('change', applyCheckoutState));

        document.getElementById('checkout-form').addEventListener('submit', (event) => {
            if (selectedTransaction() === 'shipping' && (!customerAddress?.value || !destination.value || !service.value || Number(shippingCost.value || 0) <= 0)) {
                event.preventDefault();
                showShippingError('Pilih alamat dan service ongkir dari tabel sebelum membuat pesanan.');
                clearServices('Pilih alamat dan service RajaOngkir sebelum membuat pesanan.');
            }
        });

        loadProvinces();
        if (oldDestination) {
            destination.value = oldDestination;
        }
        applySavedAddress();
        applyCheckoutState();
    </script>
</body>

</html>
